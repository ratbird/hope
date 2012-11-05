<?php

require "StudipDebug.class.php";

class StudipDebugPDO extends StudipPDO {

    public function query($statement, $mode = NULL, $arg1 = NULL, $arg2 = NULL)
    {
        $time = microtime(true);
        $ret = parent::query($statement, $mode, $arg1, $arg2);
        StudipDebug::log_query($statement, $time);
        return $ret;
    }

    public function exec($query_string)
    {
        $time = microtime(true);
        $ret = parent::exec($query_string);
        StudipDebug::log_query($query_string, $time);
        return $ret;
    }

    public function prepare($statement, $driver_options = array())
    {
        $this->verify($statement);
        return new StudipDebugPDOStatement($this, $statement, $driver_options);
    }
}

class StudipDebugPDOStatement extends StudipPDOStatement
{

    /**
     * Executes the prepared statement and returns a PDOStatement object.
     */
     public function execute($input_parameters = NULL)
     {
         // bind additional parameters from execute()
         if (isset($input_parameters)) {
             foreach ($input_parameters as $key => $value) {
                 $this->bindValue(is_int($key) ? $key + 1 : $key, $value, NULL);
             }
         }

         // build the actual query string and prepared statement
         if (count($this->params)) {
             $this->count = 1;
             $query = preg_replace_callback('/\?|:\w+/', array($this, 'replaceParam'), $this->query);
         } else {
             $query = $this->query;
         }

         $this->stmt = $this->db->prepareStatement($query, $this->options);

         // set up column bindings on the actual statement
         if (isset($this->columns)) {
             foreach ($this->columns as $args) {
                 call_user_func_array(array($this->stmt, 'bindColumn'), $args);
             }
         }
         $time = microtime(true);
         $ret = $this->stmt->execute();
         StudipDebug::log_query($query, $time);
         return $ret;
     }

}

$_debug_pdo = new StudipDebugPDO( 'mysql:host='.$GLOBALS['DB_STUDIP_HOST'].
                  ';dbname='.$GLOBALS['DB_STUDIP_DATABASE'],
                  $GLOBALS['DB_STUDIP_USER'],
                  $GLOBALS['DB_STUDIP_PASSWORD']);
DBManager::getInstance()->setConnection('studip',$_debug_pdo);

