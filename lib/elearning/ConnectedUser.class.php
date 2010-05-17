<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

DEFINE ("USER_TYPE_ORIGINAL" , "1");
DEFINE ("USER_TYPE_CREATED", "0");

/**
* class to handle user-accounts
*
* This class contains methods to handle connected user-accounts.
*
* @author   Arne Schröder <schroeder@data-quest.de>
* @access   public
* @modulegroup  elearning_interface_modules
* @module       ConnectedUser
* @package  ELearning-Interface
*/
class ConnectedUser
{
    var $cms_type;
    var $id;
    var $studip_id;
    var $studip_login;
    var $studip_password;
    var $login;
    var $external_password;
    var $category;
    var $gender;
    var $title_front;
    var $title_rear;
    var $title;
    var $firstname;
    var $lastname;
    var $institution;
    var $department;
    var $street;
    var $city;
    var $zipcode;
    var $country;
    var $phone_home;
    var $fax;
    var $matriculation;
    var $email;
    var $type;
    var $is_connected;

    var $db_class;
    /**
    * constructor
    *
    * init class. don't call directly, class is loaded by ConnectedCMS.
    * @access public
    * @param string $cms system-type
    */ 
    function ConnectedUser($cms, $user_id = false)
    {
        global $auth, $RELATIVE_PATH_ELEARNING_INTERFACE, $ELEARNING_INTERFACE_MODULES;

        $this->studip_id = $user_id ? $user_id : $auth->auth["uid"];
        $this->cms_type = $cms;

        if ($ELEARNING_INTERFACE_MODULES[$this->cms_type]["RELATIVE_PATH_DB_CLASSES"] != false)
        {   
            require_once($RELATIVE_PATH_ELEARNING_INTERFACE . "/" . $ELEARNING_INTERFACE_MODULES[$this->cms_type]["RELATIVE_PATH_DB_CLASSES"] . "/" . $ELEARNING_INTERFACE_MODULES[$this->cms_type]["db_classes"]["user"]["file"] );
            $classname = $ELEARNING_INTERFACE_MODULES[$this->cms_type]["db_classes"]["user"]["classname"];
            $this->db_class = new $classname();
        }
        $this->readData();
        $this->getStudipUserData();
    }

    /**
    * get data
    *
    * gets data from database
    * @access public
    * @return boolean returns false, if no data was found
    */
    function readData()
    {
        $db = New DB_Seminar;
        $db->query("SELECT * FROM auth_extern WHERE studip_user_id = '" . $this->studip_id . "' AND external_user_system_type = '" . $this->cms_type . "'");
        if ($db->next_record())
        {
            $this->id = $db->f("external_user_id");
            $this->login = $db->f("external_user_name");
            $this->external_password = $db->f("external_user_password");
            $this->category = $db->f("external_user_category");
            $this->type = $db->f("external_user_type");
            $this->is_connected = true;
        }
        else
        {
            $this->id = "";
            $this->is_connected = false;
            return false;
        }
    }

    /**
    * get stud.ip-user-data
    *
    * gets stud.ip-user-data from database
    * @access public
    * @return boolean returns false, if no data was found
    */
    function getStudipUserData()
    {
        global $connected_cms;
        
        $db = New DB_Seminar;
        $db->query("SELECT * FROM user_info LEFT JOIN auth_user_md5 USING(user_id) WHERE user_info.user_id = '" . $this->studip_id . "'");
        if ($db->next_record())
        {
            $this->studip_login = $db->f("username");
            if ($this->is_connected == false)
            {
                $this->login = $connected_cms[$this->cms_type]->getUserPrefix() . $this->studip_login;
            }
            $this->studip_password = $db->f("password");
            $this->title_front = $db->f("title_front");
            $this->title_rear = $db->f("title_rear");
            if ($this->title_front != "")
                $this->title = $this->title_front;
            if (($this->title_front != "") AND ($this->title_rear != ""))
                $this->title .= " ";
            if ($this->title_rear != "")
                $this->title .= $this->title_rear;
            $this->firstname = $db->f("Vorname");
            $this->lastname = $db->f("Nachname");
            $this->email = $db->f("Email");
            $this->phone_home = $db->f("privatnr");
            $this->street = $db->f("privadr");
            if ($db->f("geschlecht") == '2')
                $this->gender = 'f';
            else
                $this->gender = 'm';
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
    * create new user-account
    *
    * dummy-method. returns false. must be overwritten by subclass.
    * @access public
    * @return boolean returns false
    */
    function newUser()
    {
        return false;
    }

    /**
    * update user-account
    *
    * dummy-method. returns false. must be overwritten by subclass.
    * @access public
    * @return boolean returns false
    */
    function updateUser()
    {
        return false;
    }

    /**
    * delete user-account
    *
    * dummy-method. returns false. must be overwritten by subclass.
    * @access public
    * @return boolean returns false
    */
    function deleteUser()
    {
        return false;
    }

    /**
    * get login-data of user-account
    *
    * dummy-method. returns false. must be overwritten by subclass.
    * @access public
    * @return boolean returns false
    */
    function getLoginData()
    {
        return false;
    }

    /**
    * get id
    *
    * returns id
    * @access public
    * @return string id
    */
    function getId()
    {
        return $this->id;
    }

    /**
    * get stud.ip user-id
    *
    * returns id
    * @access public
    * @return string stud.ip user-id
    */
    function getStudipId()
    {
        return $this->studip_id;
    }

    /**
    * get username
    *
    * returns username
    * @access public
    * @return string username
    */
    function getUsername()
    {
        return $this->login;
    }

    /**
    * set username
    *
    * sets username
    * @access public
    * @param string $user_login username
    */
    function setUsername($user_login)
    {
        $this->login = $user_login;
    }

    /**
    * get password
    *
    * returns password
    * @access public
    * @return string password
    */
    function getPassword()
    {
        return $this->external_password;
    }

    /**
    * set password
    *
    * sets password
    * @access public
    * @param string $user_password password
    */
    function setPassword($user_password)
    {
        $this->external_password = $user_password;
    }

    /**
    * get user category
    *
    * returns id
    * @access public
    * @return string id
    */
    function getCategory()
    {
        return $this->category;
    }

    /**
    * set user category
    *
    * sets user category
    * @access public
    * @param string $user_category category
    */
    function setCategory($user_category)
    {
        $this->category = $user_category;
    }

    /**
    * get crypted password
    *
    * dummy-method. returns false. must be overwritten by subclass.
    * @access public
    * @return boolean returns false
    */
    function getCryptedPassword($password)
    {
        return false;
    }

    /**
    * verify login data
    *
    * returns true, if login-data is valid
    * @access public
    * @param string $username username
    * @param string $password password
    * @return boolean login-validation
    */
    function verifyLogin($username, $password)
    {
        $this->getLoginData($username);
        if (($username == "") OR ($password == ""))
            return false;
        if ( ($this->login == $username) AND  ($this->external_password == $this->getCryptedPassword($password) ) )
            return true;
        return false;
    }

    /**
    * get gender
    *
    * returns gender-setting
    * @access public
    * @return string gender-setting
    */
    function getGender()
    {
        return $this->gender;
    }

    /**
    * set gender
    *
    * sets gender
    * @access public
    * @param string $user_gender gender-setting
    */
    function setGender($user_gender)
    {
        $this->gender = $user_gender;
    }

    /**
    * get full name
    *
    * returns full name
    * @access public
    * @return string name
    */
    function getName()
    {
        if ($this->title != "")
            return $this->title . ' ' . $this->firstname . ' ' . $this->lastname;
        else
            return $this->firstname . ' ' . $this->lastname;
    }

    /**
    * get firstname
    *
    * returns firstname
    * @access public
    * @return string firstname
    */
    function getFirstname()
    {
        return $this->firstname;
    }

    /**
    * set firstname
    *
    * sets firstname
    * @access public
    * @param string $user_firstname firstname
    */
    function setFirstname($user_firstname)
    {
        $this->firstname = $user_firstname;
    }

    /**
    * get lastname
    *
    * returns lastname
    * @access public
    * @return string lastname
    */
    function getLastname()
    {
        return $this->lastname;
    }

    /**
    * set lastname
    *
    * sets lastname
    * @access public
    * @param string $user_lastname lastname
    */
    function setLastname($user_lastname)
    {
        $this->lastname = $user_lastname;
    }

    /**
    * get email-adress
    *
    * returns email-adress
    * @access public
    * @return string email-adress
    */
    function getEmail()
    {
        return $this->email;
    }

    /**
    * set email-adress
    *
    * sets email-adress
    * @access public
    * @param string $user_email email-adress
    */
    function setEmail($user_email)
    {
        $this->email = $user_email;
    }

    /**
    * get user-type
    *
    * returns user-type
    * @access public
    * @return string user-type
    */
    function getUserType()
    {
        return $this->type;
    }

    /**
    * set user-type
    *
    * sets user-type
    * @access public
    * @param string $user_type user-type
    */
    function setUserType($user_type)
    {
        $this->type = $user_type;
    }

    /**
    * save connection for user-account
    *
    * saves user-connection to database and sets type for actual user
    * @access public
    * @param string $user_type user-type
    */
    function setConnection($user_type)
    {
        $this->setUserType($user_type);
        $db = New DB_Seminar;
        $db->query("SELECT * FROM auth_extern WHERE studip_user_id = '" . $this->studip_id . "' AND external_user_system_type = '" . $this->cms_type . "'");
        if ($db->next_record())
        {
            $db->query("UPDATE auth_extern SET external_user_name='" . $this->login. "', external_user_password='" . $this->external_password. "', external_user_category='" . $this->category . "', external_user_id='" . $this->id. "', external_user_type='" . $this->type . "' "
            ."WHERE studip_user_id = '" . $this->studip_id . "' AND external_user_system_type = '" . $this->cms_type . "'");
        }
        else
        {
            $db->query("INSERT INTO auth_extern (studip_user_id, external_user_id, external_user_name, external_user_password, external_user_category, external_user_system_type, external_user_type) "
                ."VALUES ('" . $this->studip_id . "', '" . $this->id . "', '" . $this->login . "', '" . $this->external_password . "', '" . $this->category . "', '" . $this->cms_type . "', '" . $this->type . "')");
        }
        $this->is_connected = true;
        $this->readData();
    }

    /**
    * get connection-status
    *
    * returns true, if there is a connected user
    * @access public
    * @return boolean connection-status
    */
    function isConnected()
    {
        return $this->is_connected;
    }
}
?>
