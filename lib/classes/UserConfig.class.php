<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

require_once("lib/classes/Config.class.php");   //Acces to config-values


/**
 * Access to user preferences
 *
 * Usage:
 * 
 * $uc=UserConfig() // construct without implicit user/key
 * $uc=UserConfig($someuser_id, "somekey"); // construct and set implicit user/key
 * 
 * $v=$uc->getValue();  // get value for "someuser" and "somekey"
 * $v=$uc->getValue($otheruser_id); // get value for "otheruser" and "somekey"
 * $v=$uc->getValue(NULL, "otherkey"); // get value for "someuser" and "otherkey"
 * $v=$uc->getValue($otheruser_id, "otherkey"); // get value for "otheruser" and "otherkey"
 * 
 * $uc->setValue("somevalue"); // set value to "somevalue" for "someuser"/"somekey"
 * ... combinations of explicit user_id and key same as getValue(...) ...
 *    
 * $uc->unsetValue(); // unset (delete from db) value for "someuser"/"somekey"
 * ... combinations of explicit user_id and key same as getValue(...) ...
 * 
 * $uc->unsetAll($user_id); // delete all entries for a user (user_id explicit or implicit)
 * $uc->getAll($user_id); // get all entries for a user (user_id explicit or implicit)
 * 
 * $uc->setUserId($otheruser_id); // switch to "otheruser" for implict get/set
 * $uc->setKey("otherkey"); // switch to "otherkey" for implict get/set
 *
 * Future:
 * $uc->getValues(...); // return (multidim) array stored for key
 * $uc->setValues($array, ...); // store (multidim) array for key
 * $uc->extractGlobal(...); // extract values for user to global name space   
 */
class UserConfig {
    var $db;        // DB connection
    var $user_id;   // user's md5 id
    var $key;       // key
    var $data;      // assoc. array for caching ([$user_id][$key]=>value ) 
    var $defaults;  // assoc. arry for defaults ([$key]=>value)

    /**
     * Construct UserConfig object.
     * 
     * Set user_id and key for later use, if given.
     * @param   string  user_id
     * @param   string  key
     */
    function UserConfig($user_id=NULL, $key=NULL) {
        $this->user_id=$user_id;
        $this->key=$key;
        $this->data=array();
        $this->db=new DB_Seminar();
        if ($this->user_id){
            $this->_retrieveAll();
        }
    }


    /**
    * Return value for user_id/key combination.
    * 
    * Use user_id and key set earlier, or override with given arguments.
    * Returns default value for key if no entry in db is found.
    * 
    * @param    string  Override internal user_id, if set
    * @param    string  Override internal key, if set
    * @return   string  Value (or default value)
    */
    function getValue($user_id=NULL, $key=NULL) {
        if ($user_id==NULL) $user_id=$this->user_id;
        if ($key==NULL) $key=$this->key;
        if (!isset ($this->data[$user_id][$key])) { // check for cached value
            $this->_retrieve($user_id, $key); // otherwise, retrieve
        }
        return (isset($this->data[$user_id][$key]) ? $this->data[$user_id][$key] : $this->defaults[$key]);
    }

    


    /**
     * Return array with all key/value pairs set for user
     * 
     * @param   string  explicit user_id to override implicit
     */
    function getAll($user_id=NULL) {
        if ($user_id==NULL) $user_id=$this->user_id;
        //first, get all default values from config
        $cfg =& Config::GetInstance();
        $arr = $cfg->getAllDefaults('user');

        $sql="SELECT userconfig_id, field, value, comment FROM user_config WHERE user_id='$user_id' ORDER BY field";
        $this->db->query($sql);
        while ($this->db->next_record()) {
            $arr[$this->db->f("field")]["value"] = $this->db->f("value");
            $arr[$this->db->f("field")]["id"] = $this->db->f("userconfig_id");
            $arr[$this->db->f("field")]["comment"] = $this->db->f("comment");
        }
        return $arr;
    }

        
    /**
    * Set value for user_id/key combination.
    * 
    * @param    string  value to set
    * @param    string  user_id, overrides internal user_id if set
    * @param    string  key, overrides internal key, if set
    * @return   string  the value set
    */
    function setValue($value, $user_id=NULL, $key=NULL, $comment='') {
        if ($user_id==NULL) $user_id=$this->user_id;
        if ($key==NULL) $key=$this->key;
        if ($this->_retrieve($user_id, $key)) {
            $sql=sprintf("UPDATE user_config SET value='%s', chdate='%s', comment='%s'  WHERE user_id='%s' AND field ='%s'", $value, time(), $comment, $user_id, $key);
        } else {
            $sql=sprintf("INSERT INTO user_config SET userconfig_id='%s', parent_id=NULL, user_id='%s', field='%s', value='%s', mkdate='%s', chdate='%s', comment='%s' ", 
                md5(uniqid("userconfig!")),$user_id, $key, $value, time(), time(), $comment);
        }
        $this->db->query($sql);
        $this->data[$user_id][$key]=$value;
        return $value;
    }

    /**
     * Unset entry for user_id/key combination.
     * 
     * @param   string  user_id, overrides internal user_id if set
     * @param   string  key, overrides internal key, if set
     * @return  bool    operation succeeded?
     */
    function unsetValue($user_id=NULL, $key=NULL) {
    if ($user_id==NULL) $user_id=$this->user_id;
    if ($key==NULL) $key=$this->key;
    $sql=sprintf("DELETE FROM user_config WHERE user_id='%s' AND field='%s'", $user_id, $key);
    $this->db->query($sql);
    if ($this->db->affected_rows()>0) {
            unset($this->data[$user_id][$key]);
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Unset all entries for user
     * 
     * @param   string  explicit user_id to override implicit
     */
    function unsetAll($user_id=NULL) {
        if ($user_id==NULL) $user_id=$this->$user_id;
    $sql=sprintf("DELETE FROM user_config WHERE user_id='%s'", $user_id);
        $this->db->query($sql);
        if ($this->db->affected_rows()>0) {
            unset($this->data[$user_id]);
            return TRUE;
        } else {
            return FALSE;
        }
    }
        

    /**
     * Get default value for a given key.
     * 
     * The default value is stored in config table and has the
     * same name as the user_config key.
     * 
     * @param   string  key for user_config table 
     */
    function getDefault($key) {
        if (!isset($this->defaults[$key])) {
            $cfg =& Config::GetInstance();
            $this->defaults[$key] = $cfg->getValue($key);
        }
        return $this->defaults[$key];
    }

    /**
     * Switch implicit user_id
     * 
     * @param   string  new implicit user_id
     * @return  void
     */
    function setUserId($user_id) {
        $this->user_id = $user_id;
        $this->_retrieveAll();
    }
    
    /**
     * Switch implicit key
     * 
     * @param   string  new implicit key
     * @param   void
     */
    function setKey($key) {
        $this->key=$key;
    }
    
    /* ---------------------------------------------------------------- 
     * private methods
     * 
     */
     
    /**
     * Fetch value for user/key from db
     * 
     * Internal function. Commit SELECT-query and Fills caching array
     * for [$user_id][$key]. Unsets array at that position if no value.
     * 
     * @param   string  user_id
     * @param   string  key
     * @return  void
     */    

    function _retrieve($user_id, $key) {
        $sql="SELECT value FROM user_config WHERE user_id='$user_id' AND field='$key' ORDER BY field";
        $this->db->query($sql);
        if ($this->db->next_record()) { // get and return value
            $this->data[$user_id][$key]=$this->db->f("value");
            return true;
        } else {
            $this->data[$user_id][$key] = $this->getDefault($key);
            return false;
        }
    }
    
    function _retrieveAll() {
        $this->data[$this->user_id] = array();
        $this->defaults = array();
        $cfg =& Config::GetInstance();
        foreach ($cfg->getAllFieldNames('user') as $key) {
            $this->getDefault($key);
        }
        $sql = "SELECT field, value FROM user_config WHERE user_id='{$this->user_id}' ORDER BY field";
        $this->db->query($sql);
        while($this->db->next_record()){
            $this->data[$this->user_id][$this->db->f("field")] = $this->db->f("value");
        }
    }
    
}
?>
