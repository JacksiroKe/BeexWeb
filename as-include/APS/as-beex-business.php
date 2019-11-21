<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Class to handle business module


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
	header('Location: ../');
	exit;
}

/**
 * Class to handle business module
 */

class BxBusiness
{
  public $businessid = null;
  public $departments = null;
  public $bstype = null;
  public $title = null;
  public $contact = null;
  public $location = null;
  public $username = null;
  public $content = null;
  public $icon = null;
  public $images = null;
  public $tags = null;
  public $userid = null;
  public $managers = null;
  public $extra = null;
  public $extra1 = null;
  public $extra2 = null;
  public $extra3 = null;
  public $created = null;
  public $updated = null;
  public $updatetype = null;
  public $closedbyid = null;
  public $cookieid = null;
  public $createip = null;
  public $lastuserid = null;
  public $lastip = null;
  public $users = null;
  public $hotness = null;
  public $flagcount = null;

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
   * @return mixed
   */
  public function create_business()
  {
    require_once AS_INCLUDE_DIR . 'db/users.php';
    require_once AS_INCLUDE_DIR . 'db/post-create.php';
    require_once AS_INCLUDE_DIR . 'app/options.php';
    require_once AS_INCLUDE_DIR . 'app/emails.php';

    $manager = as_db_select_with_pending(as_db_user_account_selectspec(as_get_logged_in_handle(), false));
    $businessid = as_db_business_create($this->type, $this->title, $this->contact, $this->location, $this->username, $this->content, $this->icon, $this->tags, $this->userid);
    
    $link = as_opt('site_url').'business/'.$businessid;

    as_send_notification($userid, $manager['firstname'].' '.$manager['lastname'], $manager['email'], $manager['handle'], as_lang('emails/new_business_subject'), as_lang('emails/new_business_body'), array(
      '^business_title' => $this->title,
      '^business_username' => $this->username,
      '^business_location' => $this->location,
      '^business_contact' => $this->contact,
      '^business_description' => $this->content,
      '^business_url' => $link,
      '^url' => as_opt('site_url'),
    ));
    
    as_db_notification_create($this->userid, as_lang_html_sub('notify/new_business', $this->title), 'open-bs-page', $link, '');
    
    //Create default Departments
    $ccdept = new BxCustomercare();
		$ccdept->depttype = 'CC';
    $ccdept->userid = $this->userid;
    $ccdept->businessid = $businessid;
    $ccdept->create_department();

    $findept = new BxFinanceDept();
		$stockdept->depttype = 'FIN';
    $stockdept->userid = $this->userid;
    $stockdept->businessid = $businessid;
    $findept->create_department();

    $hrdept = new BxHumanResource();
		$hrdept->depttype = 'HR';
    $hrdept->userid = $this->userid;
    $hrdept->businessid = $businessid;
    $hrdept->create_department();
    
    $salesdept = new BxSalesDept();
		$salesdept->depttype = 'SALE';
    $salesdept->userid = $this->userid;
    $salesdept->businessid = $businessid;
    $salesdept->create_department();
    
    $stockdept = new BxStockDept();
		$stockdept->depttype = 'STK';
    $stockdept->userid = $this->userid;
    $stockdept->businessid = $businessid;
    $stockdept->create_department();
    
    return $businessid;
  }

  /**
   * Edit a existing business
   */
  public function edit_business()
  {
    require_once AS_INCLUDE_DIR . 'db/users.php';
    require_once AS_INCLUDE_DIR . 'db/post-create.php';
    require_once AS_INCLUDE_DIR . 'app/options.php';
    require_once AS_INCLUDE_DIR . 'app/emails.php';

    as_db_record_set('businesses', 'businessid', $this->businessid, 'title', $this->title);
    as_db_record_set('businesses', 'businessid', $this->businessid, 'content', $this->content);
    as_db_record_set('businesses', 'businessid', $this->businessid, 'contact', $this->contact);
    as_db_record_set('businesses', 'businessid', $this->businessid, 'username', $this->username);
    as_db_record_set('businesses', 'businessid', $this->businessid, 'icon', $this->icon);
  }

  /**
   * Fetches the a single of record in the business class
   */
  public static function get_single( $userid, $businessid ) 
  {
    $selectspec['columns'] = array('businessid', 'bstype', 'title', 'contact', 'location', 'username', 'content', 'icon', 'images', 'tags', '^businesses.userid',
    'departments' => '(SELECT COUNT(*) FROM ^businessdepts WHERE businessid = ^businesses.businessid)',  'created' => 'UNIX_TIMESTAMP(^businesses.created)', 'updated' => 'UNIX_TIMESTAMP(^businesses.updated)', 'managers');
		
    $selectspec['source'] = '^businesses LEFT JOIN ^users ON ^users.userid=^businesses.userid';
    $selectspec['source'] .= " WHERE ^businesses.businessid=#";	
    $selectspec['arguments'][] = $businessid;
    $selectspec['single'] = true;

    $result = as_db_select_with_pending( $selectspec );
    if ( $result ) {
      $business = new BxBusiness();
      $business->businessid = (int) $result['businessid'];
      $business->bstype = $result['bstype'];
      $business->title = $result['title'];
      $business->contact = $result['contact'];
      $business->location = $result['location'];
      $business->username = $result['username'];
      $business->content = $result['content'];
      $business->icon = $result['icon'];
      $business->images = $result['images'];
      $business->tags = $result['tags'];
      $business->userid = (int) $result['userid'];
      $business->managers = $result['managers'];
      $business->created = $result['created'];
      $business->updated = $result['updated'];
      $business->departments = $result['departments'];
      return $business;
    }
    else return null;
  }

  /**
   * Fetches the list of record in the business class
   */
  public static function get_list( $userid )
  {
    $list = array();
    if (!empty($userid)) 
    {
      $results = as_db_select_with_pending(array(
        'columns' => array('businessid', 'bstype', 'title', 'contact', 'location', 'username', 'content', 'icon', 'images', 'tags', 'managers', 
          'departments' => '(SELECT COUNT(*) FROM ^businessdepts WHERE businessid = ^businesses.businessid)', 'userid', 
          'created' => 'UNIX_TIMESTAMP(^businesses.created)', 'updated' => 'UNIX_TIMESTAMP(^businesses.updated)'),
        'source' => '^businesses WHERE userid='.$userid.' OR managers ='.$userid.' OR managers LIKE "%'.$userid.',%"',
        'sortasc' => 'title',
      ));
      
      if (count($results))
      foreach ( $results as $result ) {
        $business = new BxBusiness();
        $business->businessid = (int) $result['businessid'];
        $business->bstype = $result['bstype'];
        $business->title = $result['title'];
        $business->contact = $result['contact'];
        $business->location = $result['location'];
        $business->username = $result['username'];
        $business->content = $result['content'];
        $business->icon = $result['icon'];
        $business->images = $result['images'];
        $business->tags = $result['tags'];
        $business->userid = (int) $result['userid'];
        $business->managers = $result['managers'];
        $business->created = $result['created'];
        $business->updated = $result['updated'];
        $business->departments = $result['departments'];
        $list[] = $business;
      }
    }  
    return $list;
  }
}
