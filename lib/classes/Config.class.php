<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
 * Access to global config options
 *
 * Usage:
 * 
 * $cfg=Config() // construct without implicit key
 * $cfg=Config("somekey"); // construct and set implicit key
 * 
 * $v=$cfg->getValue(); // get value for "somekey"
 * $v=$cfg->getValue("otherkey"); // get value for "otherkey"
 * 
 * $cfg->setValue("somevalue"); // set value to "somevalue" for "somekey"
 * $cfg->setValue("somevalue", "otherkey"); // set value to "somevalue" for "otherkey"
 *    
 * $cfg->unsetValue(); // unset (delete from db) value for "somekey"
 * $cfg->unsetValue("otherkey"); // unset (delete from db) value for "otherkey"
 * 
 * $cfg->getAll(); // get all entries
 *  
 * $cfg->setKey("otherkey");    // switch to "otherkey" for implict get/set
 *
 * $cfg->extractGlobal(); // extract "somekey" to global name space   
 * $cfg->extractGlobal("otherkey",$check=FALSE); // extract "otherkey" to global name space
 * $cfg->extractAllGlobal($check=FALSE); // extract all keys to global name space   
 * // If true, $check preserves variables already set    
 *
 *  * Future:
 * $cfg->getValues(...); // return (multidim) array stored for key
 * $cfg->setValues($array, ...); // store (multidim) array for key
 * 
 * TODO: 
 * - store default values as own key with "_DEFAULT_" prefix
 */
 
class Config {
    var $db; // DB connection
    var $key; // key
    var $data = array(); // assoc. array for caching ([$key]=>value ) 
    var $defaults = array(); // assoc. arry for defaults ([$key]=>value)
    
    function GetInstance($refresh_cache = false){
        
        static $config_object;
        
        if ($refresh_cache){
            $config_object = null;
        }
        if (is_object($config_object)){
            return $config_object;
        } else {
            $config_object = new Config();
            return $config_object;
        }
    }

    /**
     * Construct Config object.
     * 
     * Set key for later use, if given.
     * @param   string  key
     */
    function Config($key = NULL) {
        $this->key = $key;
        $this->db = new DB_Seminar();
        $this->_retrieveAll();
    }

    /**
    * Return value for key.
    * 
    * Use key set earlier, or override with given arguments.
    * Returns default value for key if no entry in db is found.
    * 
    * @param    string  Override internal key, if set
    * @return   string  Value (or default value)
    */
    function getValue($key = NULL) {
        if ($key == NULL)
            $key = $this->key;
        if (!isset ($this->data[$key]) && !isset ($this->defaults[$key])) { // check for cached value
            $this->_retrieve($key); // otherwise, retrieve
        }
        return (isset($this->data[$key]) ? $this->data[$key] : $this->defaults[$key]);
    }

    /**
     * Return array with all key/value pairs
     */
    function getAll() {
        //first, get all default values from config
        $arr = $this->getAllDefaults();

        $sql = "SELECT config_id, field, value, comment FROM `config` WHERE is_default = 0 ORDER BY field";
        $this->db->query($sql);
        while ($this->db->next_record()) {
            $arr[$this->db->f("field")]["value"] = $this->db->f("value");
            $arr[$this->db->f("field")]["id"] = $this->db->f("config_id");
            $arr[$this->db->f("field")]["comment"] = $this->db->f("comment");
        }
        uksort($arr, 'strcasecmp');
        return $arr;
    }
    
    /**
     * Return array with all default fields
     * 
     * @param   string
     * @return  array   array with the fields based on config-table
     */
    function getAllDefaults($range = null) {
        $arr=array();
        $query_range = ($range ? " AND `range`='$range' " : "");
        $sql = "SELECT config_id, field, value, description, type, section FROM config WHERE is_default = '1' $query_range ORDER BY field";
        $this->db->query($sql);
        while ($this->db->next_record()) {
            $arr[$this->db->f("field")] = array("value" =>$this->db->f("value"), "id"=>$this->db->f("config_id"), "description"=>$this->db->f("description"), "comment"=>$this->db->f("comment"), "message_template"=>$this->db->f("message_template"), "type"=>$this->db->f("type"), "section"=>$this->db->f("section"));
        }
        return $arr;
    }
    
    function getAllFieldNames($range){
        $ret = array();
        $query_range = ($range ? " WHERE `range`='$range' " : "");
        $this->db->query("SELECT DISTINCT(field) FROM config $query_range ORDER BY field");
        while($this->db->next_record()){
            $ret[] = $this->db->f(0);
        }
        return $ret;
    }

    /**
    * Set value for key
    * 
    * @param    string  value to set
    * @param    string  key, overrides internal key, if set
    * @return   string  the value set
    */
    function setValue($value, $key = NULL, $comment = '') {
        if ($key == NULL)
            $key = $this->key;
        $this->_retrieve($key); // otherwise, retrieve
        if (isset ($this->data[$key])) {
            $sql = sprintf("UPDATE `config` SET value='%s', chdate='%s', comment='%s' WHERE `field`='%s' AND is_default != '1' ", $value, time(), $comment, $key);
        } else {
            $sql = sprintf("SELECT type, description, comment, message_template, `range` FROM `config` WHERE `field` = '%s' AND is_default = '1' ", $key);
            $this->db->query($sql);
            $this->db->next_record();
            $sql = sprintf("INSERT INTO `config` SET config_id='%s', parent_id='', field ='%s', value='%s', mkdate='%s', chdate='%s', is_default = '0', type='%s', description='%s', comment='%s', message_template='%s', `range`='%s'", 
                md5(uniqid("config!")), $key, (!$value) ? '' : $value, time(), time(), $this->db->f('type'),addslashes($this->db->f('description')), $comment, addslashes($this->db->f('message_template')), $this->db->f('range') );
        }
        $this->db->query($sql);
        // Hack for StEP 00158: Update dozent visibilities if dozent may not be invisible.
        if ($key == 'DOZENT_ALWAYS_VISIBLE' && $value) {
            $this->db->query("UPDATE auth_user_md5 SET visible='yes' WHERE perms='dozent'");
        }
        $this->data[$key] = $value;
        return $value;
    }

    /**
     * Unset entry for key.
     * 
     * @param   string  key, overrides internal key, if set
     * @return  bool    operation succeeded?
     */

    function unsetValue($key = NULL) {
        if ($key == NULL)
            $key = $this->key;
        $sql = sprintf("DELETE FROM `config` WHERE `field`='%s'", $key);
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            unset ($this->data[$key]);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Unset all entries - too dangerous and most probably useless
     * 
     */
     /*
    function unsetAll() {
        $sql = sprintf("DELETE FROM config");
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            unset ($this->data);
            return TRUE;
        } else {
            return FALSE;
        }
    }
    */

    /**
     * Get default value for a given key.
     * 
     * @param   string  key for user_config table 
     */
    function getDefault($key) {
        if (!isset ($this->defaults[$key])) {
            $this->_retrieve($key); // retrieve
        }
        return $this->defaults[$key];
    }

    /**
     * Switch implicit key
     * 
     * @param   string  new implicit key
     * @param   void
     */
    function setKey($key) {
        $this->key = $key;
    }

    /**
     * Extract key=>value to global namespace
     * 
     * @param   string  override implicit key
     * @param   bool    If true: check if variable already set and skip (default: FALSE)
     * @return  bool    key set 
     */
    function extractGlobal($key=NULL, $check=FALSE) {
        if ($key==NULL) $key=$this->key;
        if ($check && isset($GLOBALS[$key])) {
            return FALSE;
        } else {
            $GLOBALS[$key]=$this->getValue($key);
            return TRUE;
        }
    }

    /**
     * Import all keys from table into global namespace
     * 
     * @param   bool    If true: check if variable already set and skip (default: FALSE)
     */
    function extractAllGlobal($check=FALSE) {
        foreach ($this->getAllFieldNames('global') as $key) {
            if (!($check && isset($GLOBALS[$key]))) {
                $GLOBALS[$key] = $this->getValue($key);
            }
        }           
    }
    
    /* ---------------------------------------------------------------- 
     * private methods
     * 
     */

    /**
     * Fetch value from db
     * 
     * Internal function. Commit SELECT-query and fills chaching arrays
     * for [$key]. Unsets arrays at that position if no value.
     * 
     * @param   string  key
     * @return  void
     */
    function _retrieve($key) {
        $sql = "SELECT `value`, `is_default` FROM `config` WHERE `field`='$key' ORDER BY field";
        $this->db->query($sql);
        while ($this->db->next_record()) { // get value and default
            if ($this->db->f("is_default")) {
                $this->defaults[$key] = $this->db->f("value");
                $has_default_value = TRUE;
            } else {
                $this->data[$key] = $this->db->f("value");
                $has_value = TRUE;
            }
        } 
        if (!$has_value) {
            unset ($this->data[$key]);
        }
        if (!$has_default_value) {
            unset ($this->defaults[$key]);
        }
    }
    
    function _retrieveAll() {
        $this->defaults = array();
        $this->data = array();
        $sql = "SELECT `value`, `is_default`,`field` FROM `config` ORDER BY field";
        $this->db->query($sql);
        while ($this->db->next_record()) { // get value and default
            if ($this->db->f("is_default")) {
                $this->defaults[$this->db->f("field")] = $this->db->f("value");
            } else {
                $this->data[$this->db->f("field")] = $this->db->f("value");
            }
        } 
    }
}

?>
