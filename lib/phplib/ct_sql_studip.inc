<?php

##
## Copyright (c) 1998-2000 NetUSE AG
##                    Boris Erdmann, Kristian Koehntopp
##
## Copyright (c) 1998-2000 Sascha Schumann <sascha@schumann.cx>
##
##
## PHPLIB Data Storage Container using a SQL database
## patched for use with MySQL only!

class CT_Sql {

##
  ## Define these parameters by overwriting or by
  ## deriving your own class from it (recommened)
  ##

  var $database_table = "";
  var $database_class = "DB_Sql";
  var $gzip_level = 0;

  ## end of configuration

  var $db;

  function ac_start() {
    $name = $this->database_class;
    $this->db = new $name();
  }

  function ac_get_lock() {
    return true;
  }

  function ac_release_lock() {
    return true;
  }

  function ac_gc($gc_time, $name = null) {
    $this->db->query(sprintf("DELETE FROM %s WHERE changed < FROM_UNIXTIME(%s) ",
                    $this->database_table,
                    (time() - ($gc_time * 60))
                    ));
    }

  function ac_store($id, $name, $str) {
	  if ($this->gzip_level){
		  $str = gzcompress($str, $this->gzip_level);
	  }
	  $str = mysql_escape_string($str);
	  $query = sprintf("REPLACE INTO %s ( sid, val ) VALUES ('%s', '%s')",
	  $this->database_table,
	  $id,
	  $str);
	  $this->db->query($query);
	  return true;
  }

  function ac_delete($id, $name = null) {
    $this->db->query(sprintf("DELETE FROM %s WHERE sid = '%s' LIMIT 1",
      $this->database_table,
      $id));
	  return $this->db->affected_rows();
  }

  function ac_get_value($id, $name = null) {
    $this->db->query(sprintf("SELECT val FROM %s where sid  = '%s'",
    	$this->database_table,
		$id));
    if ($this->db->next_record()) {
		$str  = $this->db->f(0);
		if ($this->gzip_level){
			$str = @gzuncompress($str);
		}
		return $str;
    }
    return "";
  }
  
  function ac_get_changed($id, $name = null){
	   $this->db->query(sprintf("SELECT UNIX_TIMESTAMP(changed) FROM %s WHERE sid  = '%s'",
	   		$this->database_table,
			$id));
		$this->db->next_record();
		return $this->db->f(0);
  }
  
  function ac_set_changed($id, $name = null, $timestamp){
  	   $this->db->query(sprintf("UPDATE %s SET changed = FROM_UNIXTIME(%s) WHERE sid  = '%s'",
	   		$this->database_table,
			$timestamp,
			$id));
		return $this->db->affected_rows();
  }
  
  function ac_newid($str, $name = null) {
		$query = "SELECT sid FROM " . $this->database_table . " WHERE sid = '$str'";
		$this->db->query($query);
		if (!$this->db->next_record()) {
			return $str;
		} else {
			return FALSE;
		}
  }

  function ac_halt($s) {
    $this->db->halt($s);
  }
}
?>
