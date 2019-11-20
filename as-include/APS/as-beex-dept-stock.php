<?php 

/* *
* Class to handle the Stock Department 
*/
class BxStockDept extends BxDepartment
{
    public $departid = null;
    public $depttype = null;
    public $businessid = null;
    public $parentid = null;
    public $title = null;
    public $icon = null;
    public $content = null;
    public $userid = null;
    public $managers = null;
    public $users = null;
    public $extra = null;
    public $extra1 = null;
    public $extra2 = null;
    public $extra3 = null;
    public $extra4 = null;
    public $created = null;
    public $updated = null;
    public $supplier = null;
    public $product = null;
    public $payment = null;
    
    /**
     * Sets the object's properties using the values in the supplied array
    *
    * @param assoc The property values
    */
    public function __construct( $data=array() ) { }

    /**
     * Create a new business (application level) with $type, $location, $contact, $title, $username, $content, $icon, $tags and $userid.
     * Set $confirmed to true if the email address has been confirmed elsewhere.
     * Handles user points, notification and optional email confirmation.
     * @param $email
     * @param $password
     * @param $handle
     * @param int $level
     * @param bool $confirmed
     * @return mixed
     */
    public function create_department()
    {
        require_once AS_INCLUDE_DIR . 'db/post-update.php';
        require_once AS_INCLUDE_DIR . 'db/users.php';
        require_once AS_INCLUDE_DIR . 'app/options.php';
        require_once AS_INCLUDE_DIR . 'app/emails.php';
        
        if (empty($this->depttype)) $this->depttype = "STK";
        if (empty($this->icon)) $this->icon = "department.jpg";
        if (empty($this->title)) $this->title = "Stock";
        if (empty($this->content)) $this->content = "This is the Stock Management Department.";

        $departid = as_db_department_create($this->depttype, $this->title, $this->content, $this->icon, $this->userid);
        
        if (empty($this->parentid)) as_db_record_set('businessdepts', 'departid', $departid, 'businessid', $this->businessid);
        else as_db_record_set('businessdepts', 'departid', $departid, 'parentid', $this->parentid);

        return $departid;
    }


    /**
     * Fetches the a single of record in the department class
     */
    public static function get_single( $userid, $departid ) 
    {
        $selectspec['columns'] = array('departid', 'depttype', 'businessid', 'parentid', 'title', 'icon', 'content', '^businessdepts.userid', 'managers', 'users', 'extra',
        'sections' => '(SELECT COUNT(*) FROM ^businessdepts WHERE ^businessdepts.parentid = ^businessdepts.departid)',
        'created' => 'UNIX_TIMESTAMP(^businessdepts.created)', 'updated' => 'UNIX_TIMESTAMP(^businessdepts.updated)');
            
        $selectspec['source'] = '^businessdepts LEFT JOIN ^users ON ^users.userid=^businessdepts.userid';
        $selectspec['source'] .= " WHERE ^businessdepts.departid=#";	
        $selectspec['arguments'][] = $departid;
        $selectspec['single'] = true;

        $result = as_db_select_with_pending( $selectspec );
        if ( $result ) {
            $department = new BxDepartment();
            $department->departid = (int) $result['departid'];
            $department->depttype = $result['depttype'];
            $department->businessid = (int) $result['businessid'];
            $department->parentid = $result['parentid'];
            $department->title = $result['title'];
            $department->icon = $result['icon'];
            $department->content = $result['content'];
            $department->userid = (int) $result['userid'];
            $department->managers = (int) $result['managers'];
            $department->sections = (int) $result['sections'];
            $department->created = $result['created'];
            $department->updated = $result['updated'];
            return $department;
        }
        else return null;
    }

    /**
     * Fetches the list of record in the business class
     */
    public static function get_list( $identifier, $sections = false )
    {
        $selectspec['columns'] = array('departid', 'depttype', 'businessid', 'parentid', 'title', 'icon', 'content', '^businessdepts.userid', 'managers',
            'users', 'extra', 'created' => 'UNIX_TIMESTAMP(^businessdepts.created)', 'updated' => 'UNIX_TIMESTAMP(^businessdepts.updated)',
            'sections' => '(SELECT COUNT(*) FROM ^businessdepts WHERE parentid = ^businessdepts.departid)');
        
        if ($sections) {
            $selectspec['source'] = '^businessdepts WHERE parentid=#';
            $selectspec['arguments'] = array($identifier);
        } else {
            $selectspec['source'] = '^businessdepts WHERE businessid=#';
            $selectspec['arguments'] = array($identifier);
        }

        $selectspec['sortasc'] = 'title';
        
        $results = as_db_select_with_pending($selectspec);
        $list = array();

        foreach ( $results as $result ) {
            $department = new BxDepartment();
            $department->departid = (int) $result['departid'];
            $department->depttype = $result['depttype'];
            $department->businessid = $result['businessid'];
            $department->parentid = $result['parentid'];
            $department->title = $result['title'];
            $department->icon = $result['icon'];
            $department->content = $result['content'];
            $department->userid = (int) $result['userid'];
            $department->managers = (int) $result['managers'];
            $department->sections = (int) $result['sections'];
            $department->created = $result['created'];
            $department->updated = $result['updated'];
            $list[] = $department;
        }
        return $list;
    }

    public static function stocks_view($department, $as_content)
    {
        $as_content['title'] = $department->business . ' ' . $department->title. '<small> DEPARTMENT</small>';
        $in = array();
        $in['searchitem'] = as_get_post_title('searchitem');
        $managers = explode(',', $department->managers);	

        $userid = as_get_logged_in_userid();
        $categoryslugs = as_get('cart');
        $section = as_get('section');

        list($categories, $products) = as_db_select_with_pending(
            as_db_category_nav_selectspec($categoryslugs, true, false, true),
            as_db_products_selectspec('title', $department->businessid)
        );

        $bodycontent = array( 'type' => 'form', 'style' => 'tall', 'theme' => 'primary'); 
        $bodycontent['title'] = strtoupper(strip_tags($as_content['title']));
        
        $searchhtml = '<div class="form-group" id="searchdiv">
        <div class="col-sm-12">
        <label for="searchuser">Search by a User\'s Name, or Email Address</label>
        <input id="searchuser" autocomplete="off" onkeyup="as_searchuser_change(this.value);" type="text" class="form-control">
        <input id="department_id" type="hidden" value="' . $department->businessid . '">
        </div>
        </div>
        <div class="form-group" id="managers_feedback">
        <div class="col-sm-12"><div id="manager_results"></div></div>';

        $managershtml = '<ul class="products-list product-list-in-box" style="border-top: 1px solid #000">';
        $owner = as_db_select_with_pending(as_db_user_profile($userid));
        
        $managershtml .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $owner).'</div>';
        $managershtml .= '<div class="product-info"><a href="'.as_path_html('user/' . $owner['handle']).'" class="product-title" style="font-size: 20px;">';
        $managershtml .= $owner['firstname'].' '.$owner['lastname'].'</a><span class="product-description">DEPARTMENT MANAGER</span>';
        $managershtml .= "</div><br></li>\n";

        if (count($managers)) {
            foreach ($managers as $mid) {
                if (!empty($mid) && $userid != $mid) {
                    $manager = as_db_select_with_pending(as_db_user_profile($mid));
                    $managershtml .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
                    $managershtml .= '<div class="product-info"><a href="'.as_path_html('user/' . $manager['handle']).'" class="product-title" style="font-size: 20px;">';
                    $managershtml .= $manager['firstname'].' '.$manager['lastname'].'</a><span class="product-description">DEPARTMENT MANAGER</span>';
                    $managershtml .= "</div><br></li>\n";
                }
            }
        }

        $managershtml .= '</ul>';

        $modalboxes = array(
            'modal-dmanagers' => array(
                'class' => 'modal fade',
                'header' => array(
                    'title' => 'DEPARTMENT MANAGERS',
                ),
                'view' => array(
                    'type' => 'form', 'style' => 'tall',
                    'fields' => array(
                        'namesearch' => array(
                            'type' => 'custom',
                            'html' => htmLawed($searchhtml, array('tidy'=>'  '))
                        ),
                        'managerlist' => array(
                            'type' => 'custom',
                            'html' => '<span id="manager_list">'.htmLawed($managershtml, array('tidy'=>'  ')).'</span>',
                        ),
                    ),
                ),
            ),
        );

        switch ($section) {
            
            default:

                $bodycontent = array(
                    'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
                    'type' => 'form', 'title' => $as_content['title'], 'style' => 'tall',
            
                    'table' => array( 'id' => 'allproducts', 'inline' => true,
                        'headers' => array('#', 'ProductID', 'Category', 'Item Code', 'Date of Entry', 'Qty', '*') ),
                   
                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html( isset($department->parentid) ? 'department/' . $department->parentid : 'business/' . $department->businessid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),
                    
                    'tools' => array(
                        'stock' => array(
                            'type' => 'button_md',
                            'url' => '#modal-entry',
                            'class' => 'btn btn-primary',
                            'label' => 'ADD STOCK',
                        ),
                        '' => array(
                            'type' => 'link', 'label' => ' ',
                            'url' => '#', 
                            'class' => 'btn btn-tool',
                        ),
                        'managers' => array(
                            'type' => 'button_md',
                            'url' => '#modal-dmanagers',
                            'class' => 'btn btn-primary',
                            'label' => 'MANAGERS',
                        ),
                    ),
                    
                    'hidden' => array(
                        'code' => as_get_form_security_code('admin/products'),
                    ),
                );
            
                $searchhtml = '<div class="form-group" id="searchdiv">
                <div class="col-sm-12">
                <label for="searchitem">Search by an Item\'s Title, Code, Category or Description</label>
                <input id="searchitem" autocomplete="off" onkeyup="as_searchitem_change();" onkeydown="as_searchitem_change();" type="text" value="" class="form-control">
                <input id="business_id" type="hidden" value="' . $department->businessid . '">
                </div>
                </div>
                <div class="form-group" id="results">
                <div class="col-sm-12">
                <div id="itemresults"></div>
                </div>';

                $modalboxes['modal-entry'] = array(
                    'class' => 'modal fade',
                    'header' => array( 'title' => 'STOCK ENTRY' ),
                    'view' => array(
                        'type' => 'form', 'style' => 'tall',
                        'fields' => array(
                            'namesearch' => array(
                                'type' => 'custom',
                                'html' => htmLawed($searchhtml, array('tidy'=>'  ')),
                            ),
                        ),
                    ),
                );
                
                if (count($products)) {
                    $p = 1;
                    foreach ($products as $product) {
                        $delivery = as_when_to_html($product['delivered'], 0);
                        $deliverydate = isset($product['delivered']) ? $delivery['data'] : '';
                        $deliveryago = as_time_to_string(as_opt('db_time') - $product['delivered']);

                        $bodycontent['table']['rows'][$p] = array(
                            'title' => 'Click on this product to edit or view',
                            'tags' => 'data-toggle="modal" data-target="#modal-item'.$p.'" ',
                            'fields' => array(
                                'id' => array( 'data' => $p),
                                'title' => array( 'data' => as_get_media_html($product['icon'], 20, 20) . as_html($product['title']) ),
                                'cat' => array( 'data' => $product['category']),
                                'itemcode' => array( 'data' => $product['itemcode']),
                                'date' => array( 'data' => $deliverydate . ' (' .$deliveryago . ' ago)' ),
                                'qty' => array( 'data' => $product['quantity'] ),
                                '*' => array( 'data' => '' ),
                            ),
                        );
                        $p++;            
                    }
                    
                    $as_content['script_onloads'][] = array(
                        "$(function () { $('#allproducts').DataTable() })"
                    );
                    
                    $m = 1;
                    foreach ($products as $product) {
                        $product['stock'] = (isset($product['quantity']) ? $product['quantity'] : 0);
                        $producthtml = '<div id="itemresults_'.$product['postid'].'"></div>';
                        $producthtml .= '<div class="nav-tabs-custom">';

                        $producthtml .= '<ul class="nav nav-tabs">';
                        $producthtml .= '<li class="active"><a href="#item-about'.$product['postid'].'" data-toggle="tab">ITEM INFORMATION</a></li>';
                        $producthtml .= '<li><a href="#item-history'.$product['postid'].'" data-toggle="tab">STOCK HISTORY</a></li>';
                        $producthtml .= '<li><a href="#item-entry'.$product['postid'].'" data-toggle="tab">ADD STOCK</a></li>';
                        $producthtml .= '</ul>';
                        
                        $producthtml .= '<div class="tab-content no-padding">';

                        $producthtml .= '<div class="tab-pane" id="item-entry'.$product['postid'].'" style="position: relative;">';	
                        $producthtml .= as_stock_add_form($product);
                        $producthtml .= '</div>';

                        $producthtml .= '<div class="chart tab-pane" id="item-history'.$product['postid'].'" style="position: relative;">';
                        
                        $stockids = as_db_find_by_stockitem($product['postid'], $department->businessid);
                        if (count($stockids))
                        {
                            $history = as_db_select_with_pending( as_db_product_stock_activity($stockids[0]));
                            $producthtml .= as_stock_history($history);
                            $producthtml .= '</div>';
                        }
                        else
                        {
                            $producthtml .= '<h3>No Stock History for this product at the moment</h3></div>';
                        }

                        $producthtml .= '<div class="tab-pane active" id="item-about'.$product['postid'].'" style="position: relative;">';	
                        $producthtml .= as_product_dialog($product);
                        $producthtml .= '</div>';

                        $producthtml .= '</div>';

                        $producthtml .= '</div>';
                        $producthtml .= '</div>';
                        $producthtml .= '</div>';
                        $producthtml .= '</div>';

                        $modalboxes['modal-item'.$m] = array(
                            'class' => 'modal fade',
                            'header' => array(
                                'title' =>  'ITEM VIEW: ' .strtoupper($product['title']),
                            ),
                            'view' => array(
                                'type' => 'html', 
                                'html' => htmLawed($producthtml, array('tidy'=>'  ')),
                            ),
                        );                        
                        $m++; 
                    }
                }
                
                if (as_get('alert') != null) 
                    $bodycontent['alert_view'] = array('type' => as_get('alert'), 'message' => as_get('message'));

                if (as_get('callout') != null) 
                    $bodycontent['callout_view'] = array('type' => as_get('callout'), 'message' => as_get('message'));

                $as_content['row_view'][] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-12 col-xs-12', 'c_items' => array($bodycontent) ),
                        1 => array('class' => 'col-lg-12 col-xs-12', 'modals' => $modalboxes ),
                    ),
                );

                break;
        }
        
        return $as_content;
    }

}