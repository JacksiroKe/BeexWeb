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
        $section = as_get('section');

        list ($products, $customers) = as_db_select_with_pending( 
            as_db_products_selectspec('title', $department->businessid),
            as_db_recent_customers($department->businessid)
        );
        
        $bodycontent = array( 'type' => 'form', 'style' => 'tall', 'theme' => 'primary'); 
        $bodycontent['title'] = strtoupper(strip_tags($as_content['title']));
        
        $managershtml = '<ul class="products-list product-list-in-box" style="border-top: 1px solid #000">';
        $owner = as_db_select_with_pending(as_db_user_profile($userid));
        
        $managershtml .= "\n\t".'<li class="item">';
        $managershtml .= "\n\t\t".'<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $owner).'</div>';
        $managershtml .= "\n\t\t\t".'<div class="product-info">';
        $managershtml .= "\n\t\t\t\t".'<a href="'.as_path_html('user/' . $owner['handle']).'" class="product-title" style="font-size: 20px;">';
        $managershtml .= $owner['firstname'].' '.$owner['lastname']."</a>\n\t\t\t";
        $managershtml .= "\n\t\t\t".'<span class="product-description">DEPARTMENT MANAGER</span>';
        $managershtml .= "\n\t\t"."</div><br>";
        $managershtml .= "\n\t</li>";

        if (count($managers)) {
            foreach ($managers as $mid) {
                if (!empty($mid) && $userid != $mid) {
                    $manager = as_db_select_with_pending(as_db_user_profile($mid));
                    $managershtml .= "\n\t".'<li class="item">';
                    $managershtml .= "\n\t\t".'<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
                    $managershtml .= "\n\t\t\t".'<div class="product-info">';
                    $managershtml .= "\n\t\t\t\t".'<a href="'.as_path_html('user/' . $manager['handle']).'" class="product-title" style="font-size: 20px;">';
                    $managershtml .= $manager['firstname'].' '.$manager['lastname']."</a>\n\t\t\t";
                    $managershtml .= "\n\t\t\t".'<span class="product-description">DEPARTMENT MANAGER</span>';
                    $managershtml .= "\n\t\t"."</div><br>";
                    $managershtml .= "\n\t</li>";
                }
            }
        }

        $managershtml .= '</ul>';

        $modalboxes['modal-dmanagers'] = array(
            'class' => 'modal fade',
            'header' => array( 'title' => 'DEPARTMENT MANAGERS' ),
            'view' => array(
                'type' => 'form', 'style' => 'tall',
                'fields' => array(
                    'namesearch' => array(
                        'type' => 'custom',
                        'html' => as_search_manager( $department->businessid )
                    ),
                    'managerlist' => array(
                        'type' => 'custom',
                        'html' => '<span id="manager_list">'.$managershtml.'</span>',
                    ),
                ),
            ),
        );

        switch ($section) {

            case 'orders':
                $toppanel = array(
                    'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
                    'type' => 'form', 'title' => 'MANAGE ORDERS', 'style' => 'tall',
            
                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html( 'department/' . $department->departid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),
                    
                    'tools' => array(
                        'managers' => array(
                            'type' => 'button_md',
                            'url' => '#modal-dmanagers',
                            'class' => 'btn btn-primary',
                            'label' => 'MANAGERS',
                        ),
                    ),
                );
            
                if (as_get('alert') != null) 
                    $toppanel['alert_view'] = array('type' => as_get('alert'), 'message' => as_get('message'));

                if (as_get('callout') != null) 
                    $toppanel['callout_view'] = array('type' => as_get('callout'), 'message' => as_get('message'));

                $customer_search = array(
                    'id' => 'latest_towns', 'theme' => 'primary',
                    'type' => 'custom',
                    'title' => 'CUSTOMER SEARCH', 
                    'body' => '<div class="box-body" id="customer_search">'.as_business_customers_search($department->businessid).'</div>',
                );
                
                $item_search = array(
                    'theme' => 'primary',
                    'id' => 'item_search',
                    'tags' => 'style="display:none;"', 
                    'type' => 'custom',
                    'title' => 'ITEM SEARCH', 
                    'body' => '<div class="box-body">'.
                        as_business_items_search($department->businessid, 'outline').'</div>',
                );
                
                $order_preview = array(
                    'theme' => 'primary',
                    'id' => 'order_preview',
                    'tags' => 'style="display:none;"', 
                    'type' => 'custom',
                    'title' => '<span>ORDER PREVIEW</span>',
                    'body' => '<div class="box-body">'.as_order_form().'</div>',
                );
                
                $as_content['row_view'][] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-12 col-xs-12', 'c_items' => array($toppanel ) ),
                        1 => array('class' => 'col-lg-12 col-xs-12', 'modals' => $modalboxes ),
                    ),
                );
                
                $as_content['row_view'][] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-3 col-xs-12', 'c_items' => array($customer_search ) ),
                        1 => array('class' => 'col-lg-4 col-xs-12', 'c_items' => array($item_search ) ),
                        2 => array('class' => 'col-lg-5 col-xs-12', 'c_items' => array($order_preview ) ),
                    ),
                );
                break;
            
            default:

                $bodycontent = array(
                    'type' => 'custom', 'title' => 'STOCK VIEW', 'theme' => 'primary',
                    'body' => '<div id="main_content" style="margin: 10px;"></div>',
            
                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html( isset($department->parentid) ? 'department/' . $department->parentid : 'business/' . $department->businessid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),
                    
                    'tools' => array(
                        'stock' => array(
                            'type' => 'button_md',
                            'url' => '#modal-search',
                            'class' => 'btn btn-primary',
                            'label' => 'MANAGE STOCK',
                        ),
                        'x' => array(
                            'type' => 'link', 'label' => ' ',
                            'url' => '#', 
                            'class' => 'btn btn-tool',
                        ),
                        'orders' => array(
                            'type' => 'link',
                            'url' => as_path_html( 'department/' . $department->departid, array('section' => 'orders') ),
                            'class' => 'btn btn-primary',
                            'label' => 'MANAGE ORDERS',
                        ),
                        'xx' => array(
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
                );
            
                $modalboxes['modal-search'] = array(
                    'class' => 'modal fade',
                    'header' => array( 'title' => 'STOCK MANAGEMENT' ),
                    'view' => array(
                        'type' => 'form', 'style' => 'tall',
                        'fields' => array(
                            'namesearch' => array(
                                'type' => 'custom',
                                'html' => as_business_items_search($department->businessid, 'inline'),
                            ),
                        ),
                    ),
                );
                     
                $as_content['script_onloads'][] = array(
                    "as_tableview('stockview', ".$department->businessid.")"
                );

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

                $as_content['script_src'][] = as_opt('site_url').'as-content/as-tables.js?'.AS_VERSION;
                break;
        }
        
        return $as_content;
    }

}