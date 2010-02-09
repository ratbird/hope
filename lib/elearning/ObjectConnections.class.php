<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* class to handle object connections
*
* This class contains methods to handle connections between stud.ip-objects and external content.
*
* @author	Arne Schröder <schroeder@data-quest.de>
* @access	public
* @modulegroup	elearning_interface_modules
* @module		ObjectConnections
* @package	ELearning-Interface
*/
class ObjectConnections
{
	var $id;
	var $object_connections;
	/**
	* constructor
	*
	* init class.
	* @access public
	* @param string $object_id object-id
	*/ 
	function ObjectConnections($object_id = "")
	{
		$this->id = $object_id;
		if ($object_id != "")
			$this->readData();
	}

	/**
	* read object connections
	*
	* gets object connections from database
	* @access public
	*/
	function readData()
	{
		global $ELEARNING_INTERFACE_MODULES;
		
		$this->object_connections = "";
		$db = New DB_Seminar;
		$db->query("SELECT * FROM object_contentmodules WHERE object_id = '" . $this->id . "' ORDER BY chdate DESC");
		$module_count = 0;
		while ($db->next_record())
		{
			// show only connected modules with valid module-type
			if ($ELEARNING_INTERFACE_MODULES[$db->f("system_type")]["types"][$db->f("module_type")] == "")
				continue;
			$module_count++;
			$d_system_type = $db->f("system_type");
			$d_module_type = $db->f("module_type");
			$d_module_id = $db->f("module_id");

			$reference = $d_system_type . "_" . $d_module_type . "_" . $d_module_id;
			$this->object_connections[$reference]["cms"] = $d_system_type;
			$this->object_connections[$reference]["type"] = $d_module_type;
			$this->object_connections[$reference]["id"] = $d_module_id;
			$this->object_connections[$reference]["chdate"] = $db->f("chdate");
		}
		if ($module_count == 0)
		{
			$this->object_connections = false;
		}
	}

	/**
	* get object connections
	*
	* returns object connections
	* @access public
	* @return array object connections
	*/
	function getConnections()
	{
		return $this->object_connections;
	}
	
	/**
	* get connection-status
	*
	* returns true, if object has connections
	* @access public
	* @param string $object_id object-id (optional)
	* @return boolean connection-status
	*/
	function isConnected($object_id = NULL)
	{
		// function call as part of the object
		if ($this instanceof ObjectConnections)
		{
			return (boolean) $this->object_connections;
		}
		// direct functioncall without existing instance
		if (isset($object_id))
		{
			$db = New DB_Seminar;
			$db->query("SELECT * FROM object_contentmodules WHERE object_id = '" . $object_id . "'");
			if ($db->next_record())
				return true;
		}
		return false;
	}
	
	/**
	* get module-id
	*
	* returns module-id of given connection
	* @access public
	* @param string $connection_object_id object-id
	* @param string $connection_module_type module-type
	* @param string $connection_cms system-type
	* @return string module-id
	*/
	function getConnectionModuleId($connection_object_id, $connection_module_type, $connection_cms)
	{
		$db = New DB_Seminar;
		$db->query("SELECT * FROM object_contentmodules WHERE object_id = '" . $connection_object_id . "' AND system_type = '" . $connection_cms . "' AND module_type = '" . $connection_module_type . "'");
		if ($db->next_record())
		{
			return $db->f("module_id");
		}
		else
			return false;
	}

	/**
	* set connection
	*
	* sets connection with object
	* @access public
	* @param string $connection_object_id object-id
	* @param string $connection_module_id module-id
	* @param string $connection_module_type module-type
	* @param string $connection_cms system-type
	* @return boolean successful
	*/
	function setConnection($connection_object_id, $connection_module_id, $connection_module_type, $connection_cms)
	{
		$db = New DB_Seminar;
		$db->query("SELECT * FROM object_contentmodules WHERE object_id = '" . $connection_object_id . "' AND module_id = '" . $connection_module_id . "' AND system_type = '" . $connection_cms . "' AND module_type = '" . $connection_module_type . "'");
		if ($db->next_record())
		{
			$db->query("UPDATE object_contentmodules SET module_type='" . $connection_module_type . "', chdate='" . time() . "' "
			."WHERE object_id = '" . $connection_object_id . "' AND module_id = '" . $connection_module_id . "' AND system_type = '" . $connection_cms . "'");
		}
		else
		{
			$db->query("INSERT INTO object_contentmodules (object_id, module_id, system_type, module_type, mkdate, chdate) "
				."VALUES ('" . $connection_object_id . "', '" . $connection_module_id . "', '" . $connection_cms . "', '" . $connection_module_type . "', '" . time() . "', '" . time() . "')");
		}
		//uargl, warum immer ich
		if ($this instanceof ObjectConnections)	$this->readData();
		return true;
	}

	/**
	* unset connection
	*
	* deletes connection with object
	* @access public
	* @param string $connection_object_id object-id
	* @param string $connection_module_id module-id
	* @param string $connection_module_type module-type
	* @param string $connection_cms system-type
	* @return boolean successful
	*/
	function unsetConnection($connection_object_id, $connection_module_id, $connection_module_type, $connection_cms)
	{
		$db = New DB_Seminar;
		$db->query("SELECT * FROM object_contentmodules WHERE object_id = '" . $connection_object_id . "' AND module_id = '" . $connection_module_id . "' AND system_type = '" . $connection_cms . "' AND module_type = '" . $connection_module_type . "'");
		if ($db->next_record())
		{
			$db->query("DELETE FROM object_contentmodules WHERE object_id = '" . $connection_object_id . "' AND module_id = '" . $connection_module_id . "' AND system_type = '" . $connection_cms . "' AND module_type = '" . $connection_module_type . "'");
			//uargl, warum immer ich
			if ($this instanceof ObjectConnections)	$this->readData();
			return true;
		}
		else
			return false;
	}

	function GetConnectedSystems($object_id){
		$ret = array();
		$db = new DB_Seminar("SELECT DISTINCT system_type FROM object_contentmodules WHERE object_id='$object_id'");
		while($db->next_record()){
			$ret[] = $db->f(0);
		}
		return $ret;
	}
	
	function DeleteAllConnections($object_id, $cms_type){
		$db = new DB_Seminar("DELETE FROM object_contentmodules WHERE object_id='$object_id' AND system_type='$cms_type'");
		return $db->affected_rows();
	}
}
?>
