<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Class to handle deparment module


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.appsmata.org/license.php
*/

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('icon: ../');
	exit;
}

/**
 * Class to handle deparment module
 */

class BxDepartment
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
        
        if (empty($this->title)) {
            switch ($this->depttype) {
                case 'STK':
                    $stockdept = new BxStockDept();
                    $stockdept->businessid = $this->businessid;
                    $stockdept->parentid = $this->parentid;
                    $stockdept->icon = $this->icon;
                    $stockdept->userid = $this->userid;
                    $departid = $stockdept->create_department();
                    break;

                case 'SALE':
                    $salesdept = new BxSalesDept();
                    $salesdept->businessid = $this->businessid;
                    $salesdept->parentid = $this->parentid;
                    $salesdept->icon = $this->icon;
                    $salesdept->userid = $this->userid;
                    $departid = $salesdept->create_department();
                    break;

                case 'FIN':
                    $findept = new BxFinanceDept();
                    $findept->businessid = $this->businessid;
                    $findept->parentid = $this->parentid;
                    $findept->icon = $this->icon;
                    $findept->userid = $this->userid;
                    $departid = $findept->create_department();
                    break;

                case 'HR':
                    $hrdept = new BxHumanResource();
                    $hrdept->businessid = $this->businessid;
                    $hrdept->parentid = $this->parentid;
                    $hrdept->icon = $this->icon;
                    $hrdept->userid = $this->userid;
                    $departid = $hrdept->create_department();
                    break;

                case 'CC':
                    $ccdept = new BxCustomercare();
                    $ccdept->businessid = $this->businessid;
                    $ccdept->parentid = $this->parentid;
                    $ccdept->icon = $this->icon;
                    $ccdept->userid = $this->userid;
                    $departid = $ccdept->create_department();
                    break;
                
                default:
                    $this->title = $this->business . ' General';
                    $this->content = 'This a General Department for managing general matters at ' . $this->business;
                    $departid = as_db_department_create($this->depttype, $this->title, $this->content, $this->icon, $this->userid);
                    
                    if (empty($this->parentid)) as_db_record_set('businessdepts', 'departid', $departid, 'businessid', $this->businessid);
                    else as_db_record_set('businessdepts', 'departid', $departid, 'parentid', $this->parentid);
            
                    break;
            }
        }
        
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

    public static function department_types()
    {
        return array(
            'GEN' => as_lang('main/general') . ' ' . as_lang('main/department'),
            'STK' => as_lang('main/stock') . ' ' . as_lang('main/department'),
            'SALE' => as_lang('main/sale') . ' ' . as_lang('main/department'),
            'FIN' => as_lang('main/finance') . ' ' . as_lang('main/department'),
            'HR' => as_lang('main/human_resource') . ' ' . as_lang('main/department'),
            'CC' => as_lang('main/customer_care') . ' ' . as_lang('main/department'),
        );
    }

}
