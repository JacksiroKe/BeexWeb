<?php 

/* *
* Class to handle the Stock Department 
*/
class BxCustomerCare extends BxDepartment
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
    public $customer = null;
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
        
        if (empty($this->depttype)) $this->depttype = "CC";
        if (empty($this->icon)) $this->icon = "department.jpg";
        if (empty($this->title)) $this->title = "Customer Care";
        if (empty($this->content)) $this->content = "This is the Customer Care Management Department.";

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

    public static function customers_viewx($department, $as_content)
    {
        $as_content['title'] = $department->business . ' ' . $department->title. '<small> DEPARTMENT</small>';
        
        $bodycontent = array( 'type' => 'form', 'style' => 'tall', 'theme' => 'primary'); 
        $bodycontent['title'] = strtoupper(strip_tags($as_content['title']));

        $bodycontent['icon'] = array(
            'fa' => 'arrow-left',
            'url' => as_path_html( isset($department->parentid) ? 'department/' . $department->parentid : 'business/' . $department->businessid ),
            'class' => 'btn btn-social btn-primary',
            'label' => as_lang_html('main/back_button'),
        );

        $start = as_get_start();
        $users = as_db_select_with_pending(as_db_newest_users_selectspec($start, as_opt_if_loaded('page_size_users')));
        $usercount = as_opt('cache_userpointscount');
        $pagesize = as_opt('page_size_users');
        $users = array_slice($users, 0, $pagesize);
        $usershtml = as_userids_handles_html($users);

        if (count($users)){				
            $bodycontent['tools'] = array(
                'customers' => array(
                    'type' => 'link', 'label' => 'ADD A CUSTOMER',
                    'url' => as_path_html('business/'. $department->businessid.'/cust_new'), 
                    'class' => 'btn btn-primary btn-tool',
                ),
            );
                        
            unset($as_content['form']['fields']['intro']);

            $tablelist = array( 'id' => 'allcategories', 'headers' => array('*', '#', 'Name', 'Mobile', 'Email Address', 'Location', 'Registered', '*') );		

            $navcategoryhtml = '';
            $k = 1;
            
            foreach ($users as $user) {
                $when = as_when_to_html($user['created'], 7);
                $tablelist['rows'][$k] = array(
                    'fields' => array(
                        '*' => array( 'data' => ''),
                        'id' => array( 'data' => $k),
                        'avatar' => array( 'data' => as_get_media_html('user.jpg', 20, 20).' ' . $usershtml[$user['userid']] ),
                        'mobile' => array( 'data' => $user['mobile'] ),
                        'email' => array( 'data' => $user['email'] ),
                        'location' => array( 'data' => '' ),
                        'joined' => array( 'data' => $when['data'] ),
                        'x' => array( 'data' => ''),
                    ),
                );

                $k++;
            }

            $bodycontent['table']	= $tablelist;	
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

        return $as_content;
    }

    public static function customers_view($department, $as_content)
    {
        $as_content['title'] = $department->business . ' ' . $department->title. '<small> DEPARTMENT</small>';
        $in = array();
        $in['searchitem'] = as_get_post_title('searchitem');

        $userid = as_get_logged_in_userid();
        $section = as_get('section');

        list($customers, $locations) = as_db_select_with_pending(
            as_db_recent_customers($department->businessid),
            as_db_latest_locations('COUNTY')
        );
        
        if (as_clicked('doaddcustomer')) {
            require_once AS_INCLUDE_DIR . 'app/post-create.php';
            require_once AS_INCLUDE_DIR . 'db/post-create.php';          

            $intitle = as_post_text('title');
            $incounty = as_post_text('county');
            $insubcounty = as_post_text('subcounty');

            $inphone = as_post_text('person')." xx ".as_post_text('phone1').", ".as_post_text('phone2');
            $inemail = as_post_text('email');
            $innewtown = as_post_text('newtown');

            if (strlen($innewtown)) {
                $townstr = explode(",", $innewtown);
				$intown = as_db_location_create('TOWN', $townstr[0], '', $townstr[1], $townstr[2], $insubcounty);
            }
            else $intown = as_post_text('town');

            as_db_customer_register($userid, $department->businessid, $intitle, $inphone, $inemail, $incounty, $insubcounty, $intown);		
            as_redirect('department/' . $department->departid );
        }
        
        $bodycontent = array( 'type' => 'form', 'style' => 'tall', 'theme' => 'primary'); 
        $bodycontent['title'] = strtoupper(strip_tags($as_content['title']));

        $countieshtml = '<input type="hidden" id="townsfeedback" value="select"/><div class="row">';

        $countieshtml .= '<div class="col-lg-4 col-xs-12">
            <label>County:</label>
            <select name="county" id="county" onchange="as_select_county()" class="form-control" required>
            <option>Select County</option>';
        foreach ($locations as $location)
        {
            $countieshtml .= '<option value="'.$location['locationid'].'">'.$location['title'].'</option>';
        }
        $countieshtml .= '</select>
        </div>';

        $countieshtml .= '<div class="col-lg-4 col-xs-12" id="bs_subcounty"></div>';

        $countieshtml .= '<div class="col-lg-4 col-xs-12" id="bs_town"></div>';

        $countieshtml .= '</div>';

        $phonehtml = '<div class="row">';
        $phonehtml .= '<div class="col-lg-6 col-xs-12">
            <label>Phone Number 1:</label>
            <div class="input-group">
            <div class="input-group-addon"><i class="fa fa-phone"></i></div>
            <input type="phone" class="form-control" autocomplete="off" name="phone1" required>
        </div></div>';

        $phonehtml .= '<div class="col-lg-6 col-xs-12">
        <label>Phone Number 2 (Optional):</label>
        <div class="input-group">
        <div class="input-group-addon"><i class="fa fa-phone"></i></div>
        <input type="phone" class="form-control" autocomplete="off" name="phone2">
    </div></div>';

        $phonehtml .= '</div>';
        
        switch ($section) {

            case 'register':
                
                $formcontent = array(
                    'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
                    'title' => 'REGISTER A CUSTOMER', 'type' => 'form', 'style' => 'tall',

                    'ok' => as_get('saved') ? as_lang_html('admin/product_saved') : (as_get('added') ? as_lang_html('admin/product_added') : null),

                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html('department/' . $department->departid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),

                    'fields' => array(
                        'name' => array(
                            'tags' => 'name="title" id="title" required',
                            'label' => 'Name of the the Business',
                        ),
                        
                        'location' => array(
                            'type' => 'custom',
                            'label' => 'Location of the Business',
                            'html' => $countieshtml,
                        ),
                        
                        'person' => array(
                            'tags' => 'name="person" autocomplete="off"',
                            'label' => 'Contact Person',
                        ),
                        
                        'html' => array(
                            'type' => 'custom',
                            'html' => $phonehtml,
                        ),
                        
                        'email' => array(
                            'tags' => 'name="email" autocomplete="off"',
                            'label' => 'Email Address (Optional)',
                        ),
                        
                    ),

                    'buttons' => array(
                        'save' => array(
                            'tags' => 'id="doaddcustomer"', // just used for as_recalc_click
                            'label' => 'Register Customer',
                        ),

                        'cancel' => array(
                            'tags' => 'name="docancel"',
                            'label' => as_lang_html('main/cancel_button'),
                        ),
                    ),

                    'hidden' => array(
                        'doaddcustomer' => '1',
                        'code' => as_get_form_security_code('customer-care'),
                    ),
                );
                
                if (as_get('alert') != null) 
                    $formcontent['alert_view'] = array('type' => as_get('alert'), 'message' => as_get('message'));

                if (as_get('callout') != null) 
                    $formcontent['callout_view'] = array('type' => as_get('callout'), 'message' => as_get('message'));

                $as_content['row_view'][0] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-6 col-xs-12', 'c_items' => array($formcontent) ),
                    ),
                );
                break;

            default:
                $bodycontent = array(
                    'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
                    'type' => 'form', 'title' => $as_content['title'], 'style' => 'tall',
            
                    'table' => array( 'id' => 'allcustomers', 'inline' => true,
                        'headers' => array('#', 'Name', 'Contact Person', 'Mobile', 'Email', 'Location', 'Registered', '*') ),
                    'icon' => array(
                        'fa' => 'arrow-left',
                        'url' => as_path_html( isset($department->parentid) ? 'department/' . $department->parentid : 'business/' . $department->businessid ),
                        'class' => 'btn btn-social btn-primary',
                        'label' => as_lang_html('main/back_button'),
                    ),
                    
                    'tools' => array(
                        'stock' => array(
                            'type' => 'link',
                            'url' => as_path_html( 'department/' . $department->departid, array('section' => 'register') ),
                            'class' => 'btn btn-primary btn-block',
                            'label' => 'REGISTER A CUSTOMER',
                        ),
                    ),
                    
                    'hidden' => array(
                        'code' => as_get_form_security_code('admin/customers'),
                    ),
                );
            
                $modalboxes['modal-register'] = array(
                    'class' => 'modal fade',
                    'header' => array( 'title' => 'NEW CUSTOMER REGISTRATION' ),
                    'view' => array(
                        'type' => 'html', 'style' => 'tall',
                        'html' => '<div id="userresults"></div>',//as_new_customer_form($department->businessid),
                    ),
                );
                
                if (count($customers)) {
                    $p = 1;
                    foreach ($customers as $customer) {
                        $registered = as_when_to_html($customer['created'], 0);
                        $registereddate = isset($customer['created']) ? $registered['data'] : '';
                        $registeredago = as_time_to_string(as_opt('db_time') - $customer['created']);
                        
                        $phone = explode('xx', $customer['phone']);

                        $bodycontent['table']['rows'][$p] = array(
                            'title' => 'Click on this customer to edit or view',
                            'tags' => 'data-toggle="modal" data-target="#modal-item'.$p.'" ',
                            'fields' => array(
                                'id' => array( 'data' => $p),
                                'title' => array( 'data' => $customer['title'] ),
                                'person' => array( 'data' => trim($phone[0]) ),
                                'mobile' => array( 'data' => trim($phone[1]) ),
                                'email' => array( 'data' => $customer['email'] ),
                                'location' => array( 'data' => $customer['code'] . ' ' . $customer['county'] .', ' . $customer['subcounty'] .', ' . $customer['town']),
                                'date' => array( 'data' => $registereddate . ' (' .$registeredago . ' ago)' ),
                                '*' => array( 'data' => '' ),
                            ),
                        );
                        $p++;            
                    }
                    
                    $as_content['script_onloads'][] = array(
                        "$(function () { $('#allcustomers').DataTable() })"
                    );
                }
                
                if (as_get('alert') != null) 
                    $bodycontent['alert_view'] = array('type' => as_get('alert'), 'message' => as_get('message'));

                if (as_get('callout') != null) 
                    $bodycontent['callout_view'] = array('type' => as_get('callout'), 'message' => as_get('message'));

                $as_content['row_view'][0] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-12 col-xs-12', 'c_items' => array($bodycontent) ),
                    ),
                );

                if (isset($modalboxes)) 
                $as_content['row_view'][1] = array(
                    'colms' => array(
                        0 => array('class' => 'col-lg-12 col-xs-12', 'modals' => $modalboxes ),
                    ),
                );

                break;
        }
        
        return $as_content;
    }

}