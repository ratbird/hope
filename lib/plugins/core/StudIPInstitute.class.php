<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
* @author Dennis Reil, <Dennis.Reil@offis.de>
* @package pluginengine
* @subpackage core
*/

class StudIPInstitute {
	var $id;
	var $name;
	var $childinstitutes;

	function StudIPInstitute(){
		$this->childinstitutes = array();
		$this->name = "";
	}

	/**
	* Adds a new child to this institute
	* @param $child the new child to this institute
	*/
	function addChild($child){
		if (is_a($child,"StudIPInstitute")){
			$this->childinstitutes[] = $child;
		}
	}

	/**
	* Removes a child institute
	* @param the child, which should be removed
	*/
	function removeChild($child){
		$this->childinstitutes = array_diff($this->childinstitutes,$child);
	}

	/**
	* Returns all childs of this institute.
	*/
	function getAllChildInstitutes(){
		return $this->childinstitutes;
	}

	/**
	* Sets the id of this institute
	* @param $newid the new id
	*/
	function setId($newid){
		$this->id = $newid;
	}

	/**
	* Returns the id of this institute
	*/
	function getId(){
		return $this->id;
	}

	/**
	* Sets the name of the institute
	* @param the new name
	*/
	function setName($newname){
		$this->name = $newname;
	}

	/**
	* Returns the name of this institute
	*/
	function getName(){
		return $this->name;
	}

}

?>
