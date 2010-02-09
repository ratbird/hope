<?
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ResourcesExternTree.class.php
// 
// 
// Copyright (c) 2005 André Noack <noack@data-quest.de> 
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once("lib/classes/TreeAbstract.class.php");

class ResourcesExternTree extends TreeAbstract {
	
	var $viewable_property_id;
	
	/**
	* constructor
	*
	* do not use directly, call &GetInstance()
	* @access private
	*/ 
	function ResourcesExternTree($viewable_property_id) {
		$this->viewable_property_id = $viewable_property_id;
		parent::TreeAbstract();
	}

	/**
	* initializes the tree
	*
	* stores all tree items in array $tree_data
	* must be overriden
	* @access public
	*/
	function init(){
		parent::init();
		$snap = new DbSnapshot($this->view->get_query("SELECT ro.resource_id, name, parent_id,rop.resource_id as viewable FROM resources_objects ro 
								INNER JOIN resources_objects_properties rop 
								ON(ro.resource_id=rop.resource_id AND property_id='{$this->viewable_property_id}' AND state='on')"));
		if ($snap->numRows){
			while($snap->nextRow()){
				$parent_id = $snap->getField('parent_id') ? $snap->getField('parent_id') : 'root';
				$this->storeItem($snap->getField('resource_id'),$parent_id ,$snap->getField('name'),1);
				$this->tree_data[$snap->getField('resource_id')]['viewable'] = true;
				$this->checkParent($parent_id);
			}
		}
	}
	
	function checkParent($parent_id){
		while(!$this->tree_data[$parent_id]){
			$rs = $this->view->get_query("SELECT resource_id, name, parent_id FROM resources_objects WHERE resource_id='$parent_id'");
			$rs->next_record();
			$parent_id = $rs->f('parent_id') ? $rs->f('parent_id') : 'root';
			$this->storeItem($rs->f('resource_id'),$parent_id ,$rs->f('name'),1);
		}
	}
	
}
/*
echo "<pre>";
$test =& TreeAbstract::GetInstance('ResourcesExternTree','539dd9e5bea93208b7e6b5415a01f661' );
print_r($test->tree_data);
*/
?>
