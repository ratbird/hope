<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
 * The permission of an object, usually a user.
 * @author Dennis Reil, <dennis.reil@offis.de>
 * @package pluginengine
 * @subpackage core
 */

class Permission {
	var $userid;
	var $poiid;
	var $perm;


	function Permission($userid) {
		$this->userid = $userid;
		$this->perm = $GLOBALS['perm'];
	}

	function setPoiid($id) {
		$this->poiid = $id;
	}

	function hasRootPermission(){
		return $this->perm->have_perm("root",$this->userid);
	}

	function hasAdminPermission(){
		return $this->perm->have_perm("admin",$this->userid);
	}

	function hasTutorPermission(){
		return $this->perm->have_perm("tutor",$this->userid);
	}

	function hasTeacherPermission(){
		return $this->perm->have_perm("dozent",$this->userid);
	}

	function hasStudentPermission(){
		return $this->perm->have_perm("autor",$this->userid);
	}

	function isStudent(){
		return $this->perm->have_perm("autor",$this->userid) && !$this->perm->have_perm("dozent",$this->userid);
	}

	function hasTeacherPermissionInPOI(){
		return $this->perm->have_studip_perm("dozent",$this->poiid,$this->userid);
	}

	function hasTutorPermissionInPOI(){
		return $this->perm->have_studip_perm("tutor",$this->poiid,$this->userid);
	}

	function hasStudentPermissionInPOI(){
		return $this->perm->have_studip_perm("autor",$this->poiid,$this->userid);
	}
}
?>
