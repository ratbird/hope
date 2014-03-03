<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Stud.IP authentication against CAS Server
*
* @access   public
* @author   Dennis Reil <dennis.reil@offis.de>
* @package
*/

require_once 'StudipAuthSSO.class.php';
require_once 'vendor/phpCAS/CAS.php';

class StudipAuthCAS extends StudipAuthSSO {

    var $host;
    var $port;
    var $uri;
    var $cacert;

    var $cas;
    var $userdata;

    /**
    * Constructor
    *
    *
    * @access public
    *
    */
    function StudipAuthCAS() {
        parent::__construct();
        $this->cas = new CASClient(CAS_VERSION_2_0,false,$this->host,$this->port,$this->uri,false);

        if (isset($this->cacert)) {
            $this->cas->setCasServerCACert($this->cacert);
        } else {
            $this->cas->setNoCasServerValidation();
        }
    }

    function getUser(){
        return $this->cas->getUser();
    }

    function isAuthenticated($username, $password){
        // do CASAuthentication
        $this->cas->forceAuthentication();
        return true;
    }

    function getUserData($key){
        $userdataclassname = $GLOBALS["STUDIP_AUTH_CONFIG_CAS"]["user_data_mapping_class"];
        if (empty($userdataclassname)){
            echo ("ERROR: no userdataclassname specified.");
            return;
        }
        require_once($userdataclassname . ".class.php");
        // get the userdata
        if (empty($this->userdata)){
            $this->userdata = new $userdataclassname();
        }
        $result = $this->userdata->getUserData($key, $this->cas->getUser());
        return $result;
    }

    function logout(){
        // do a global cas logout
        $this->cas->logout();
    }
}
