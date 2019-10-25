<?php 

/* *
* Class to handle the Stock Department 
*/
class BxFinanceDept extends BxDepartment
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
    public function create_new()
    {
        require_once AS_INCLUDE_DIR . 'db/post-update.php';
        require_once AS_INCLUDE_DIR . 'db/users.php';
        require_once AS_INCLUDE_DIR . 'app/options.php';
        require_once AS_INCLUDE_DIR . 'app/emails.php';
        
        if (empty($this->depttype)) $this->depttype = "FIN";
        if (empty($this->icon)) $this->icon = "department.jpg";
        if (empty($this->title)) $this->title = "Finance";
        if (empty($this->content)) $this->content = "This is the Finance Management Department.";

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


}