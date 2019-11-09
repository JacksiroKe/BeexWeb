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

        $userid = as_get_logged_in_userid();
        $selectsort = 'title';
        $categoryslugs = as_get('cart');
        $section = as_get('section');

        list($categories, $products) = as_db_select_with_pending(
            as_db_category_nav_selectspec($categoryslugs, true, false, true),
            as_db_products_selectspec($userid, $selectsort)
        );

        $bodycontent = array( 'type' => 'form', 'style' => 'tall', 'theme' => 'primary'); 
        $bodycontent['title'] = strtoupper(strip_tags($as_content['title']));

        switch ($section) {
            case 'stockentry':
                $bodycontent = array(
                    'title' => 'ADD A STOCK ENTRY', 'type' => 'form', 'style' => 'tall',                    
                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html( 'department/' . $department->departid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),
                    
                    'fields' => array(
                        'searchitem' => array(
                            'label' => 'Search a Product by Item Name, Code or Category',
                            'tags' => 'id="searchitem" autocomplete="off"',
                            'value' => as_html(@$in['searchitem']),
                        ),
                        
                        'searchresult' => array(
                            'type' => 'custom',
                            'html' => '<div id="similar"></div>',
                        ),

                    ),
                );
                
                /*&$qa_content['form']['fields']['title']['tags'] .= ' onchange="qa_title_change(this.value);"';

                if (strlen(@$in['title'])) {
                    $qa_content['script_onloads'][] = 'as_searchitem_change();';
                }*/

                $as_content['script_onloads'][] = 'as_searchitem_change();';

                if (as_get('alert') != null) 
                    $bodycontent['alert_view'] = array('type' => as_get('alert'), 'message' => as_get('message'));

                if (as_get('callout') != null) 
                    $bodycontent['callout_view'] = array('type' => as_get('callout'), 'message' => as_get('message'));

                $as_content['row_view'][] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-6 col-xs-12', 'c_items' => array($bodycontent) ),
                    ),
                );

                break;
            
            default:
                $bodycontent = array(
                    'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
                    'type' => 'form', 'title' => $as_content['title'], 'style' => 'tall',
            
                    'table' => array( 'id' => 'allproducts', 'inline' => true,
                        'headers' => array('#', 'ProductID', 'Category', 'Item Code', 'Length', 'Width', 'Height / <br>Thickness', 
                        'Fill', 'Fill-material', 'Weight', 'Color', 'Type / <br>Pattern', 'Date of Entry', 'Qty', '*') ),
                   
                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html( isset($department->parentid) ? 'department/' . $department->parentid : 'business/' . $department->businessid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),
                    
                    'tools' => array(
                        /*'products' => array(
                            'type' => 'link', 'label' => 'MANAGE PRODUCTS',
                            'url' => as_path_html('business/'. $department->businessid.'/products'), 
                            'class' => 'btn btn-primary btn-tool',
                        ),
                        '' => array(
                            'type' => 'link', 'label' => ' ',
                            'url' => '#', 
                            'class' => 'btn btn-tool',
                        ),*/
                        'stock' => array(
                            'type' => 'link', 'label' => 'MANAGE STOCK',
                            'url' => as_path_html('department/'. $department->departid, array('section' => 'stockentry')), 
                            'class' => 'btn btn-primary btn-tool',
                        ),
                    ),
                    
                    'hidden' => array(
                        'code' => as_get_form_security_code('admin/products'),
                    ),
                );
            
                if (count($products)) {
                    //$as_content['title'] .= ' ('.count($products).')';
                    $p = 1;
                    foreach ($products as $product) {
                        $volume = explode('by', $product['volume']);
                        $mass = explode(';', $product['mass']);
                        $texture = explode(';', $product['texture']);
            
                        $bodycontent['table']['rows'][$p] = array(
                            'onclick' => ' title="Click on this product to edit or view"',
                            'fields' => array(
                                'id' => array( 'data' => $p),
                                'title' => array( 'data' => as_get_media_html($product['icon'], 20, 20) .'<a href="' . as_path_html('admin/products', array('edit' => $product['postid'])) . '">' . as_html($product['title']) .'</a>' ),
                                'cat' => array( 'data' => $product['category']),
                                'itemcode' => array( 'data' => $product['itemcode']),
                                'length' => array( 'data' => trim($volume[0])),
                                'width' => array( 'data' => trim($volume[1])),
                                'height' => array( 'data' => trim($volume[2])),
                                'fill' => array( 'data' => trim($mass[0])),
                                'fill-material' => array( 'data' => trim($mass[1])),
                                'weight' => array( 'data' => trim($mass[2])),
                                'color' => array( 'data' => trim($texture[0])),
                                'pattern' => array( 'data' => trim($texture[1])),
                                'date' => array( 'data' => '' ),
                                'qty' => array( 'data' => '' ),
                                '*' => array( 'data' => '' ),
                            ),
                        );
                        $p++;
            
                    }
                    
                    $as_content['script_onloads'][] = array(
                        "$(function () { $('#allproducts').DataTable() })"
                    );
                }
                
                if (as_get('alert') != null) 
                    $bodycontent['alert_view'] = array('type' => as_get('alert'), 'message' => as_get('message'));

                if (as_get('callout') != null) 
                    $bodycontent['callout_view'] = array('type' => as_get('callout'), 'message' => as_get('message'));

                $as_content['row_view'][] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-12 col-xs-12', 'c_items' => array($bodycontent) ),
                    ),
                );

                break;
        }
        
        return $as_content;
    }

}