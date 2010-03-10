<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* ResourcesUserRoots.class.php
* 
* provides all the individual resources-roots for an user
* 
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       ResourcesUserRoots.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ResourcesUserRoots.class.php
// Stellt die individuellen Wurzeln innerhalb der Hierarchie fuer einen Nutzer zuer Verfuegung
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

/*****************************************************************************
ResourcesUserRoots, stellt Stamm-Ressourcen zur Verfuegung
/*****************************************************************************/

class ResourcesUserRoots {
    var $user_global_perm;          //Globaler Status des Users, fuer den Klasse initiert wird
    var $range_id;                  //the id of the User (could be a Person, Einrichtung oder Veranstaltung)
    var $my_roots;                  //Alle meine Ressourcen-Staemme
    
    //Konstruktor
    function ResourcesUserRoots($range_id='') {
        global $user, $perm, $auth;
        
        if($range_id){
            $this->range_id = $range_id;
        }
        
        if (!$this->range_id)
            $this->range_id=$user->id;

        $db=new DB_Seminar;
        
        
        if (get_object_type($this->range_id) == "user") {
            //load the global perms in the resources-system (check if the user ist resources-root)
            $this->resources_global_perm=getGlobalPerms($this->range_id);
            //load the global studip perms (check, if user id root)
            $this->user_global_perm=get_global_perm($this->range_id);
        
            if ($this->resources_global_perm == "admin")
                $global_perm="root";
            else
                $global_perm=$this->user_global_perm;
        }

        //root or resoures root are able to see all resources (roots in tree)
        if ($global_perm == "root") {
            $db->query("SELECT resource_id FROM resources_objects WHERE resource_id = root_id");
            while ($db->next_record())
                $this->my_roots[$db->f("resource_id")]=$db->f("resource_id");
        } else {
            $my_objects=search_administrable_objects();
            $my_objects[$user->id]=TRUE;
            $my_objects["global"]=TRUE;

            //create the clause with all my id's
            $i=0;
            $clause = " (";
            foreach ($my_objects as $key=>$val) {
                if ($i)
                    $clause .= ", ";
                $clause .= "'$key'";
                $i++;
            }
            $clause .= ") ";
            
            //all objects where I have owner perms...
            $query = sprintf ("SELECT resource_id, parent_id, root_id, level FROM resources_objects WHERE owner_id IN %s ORDER BY level DESC", $clause);
            $db->query($query);
            while ($db->next_record()) {
                $my_resources[$db->f("resource_id")]=array("root_id" =>$db->f("root_id"), "parent_id" =>$db->f("parent_id"), "level" =>$db->f("level"));
                $roots[$db->f("root_id")][]=$db->f("resource_id");
            }
            
            //...and all objects where I have add perms...
            $query = sprintf ("SELECT resources_objects.resource_id, parent_id, root_id, level FROM resources_user_resources LEFT JOIN resources_objects USING (resource_id) WHERE user_id IN %s OR user_id = 'all' ORDER BY level DESC", $clause);
            $db->query($query);
            while ($db->next_record()) {
                $my_resources[$db->f("resource_id")]=array("root_id" =>$db->f("root_id"), "parent_id" =>$db->f("parent_id"), "level" =>$db->f("level"));
                $roots[$db->f("root_id")][]=$db->f("resource_id");
            }

            if (is_array($my_resources)) foreach ($my_resources as $key => $val) {
                if (!$this->checked[$key]) {
                    if (sizeof($roots[$val["root_id"]]) == 1)
                        $this->my_roots[$key] = $key;
                    //there are more than 2 resources in one thread...
                    else {
                        $query = sprintf ("SELECT resource_id, parent_id, name FROM resources_objects WHERE resource_id = '%s' ", $key);
                        $db->query($query);
                        $db->next_record();
                        $superordinated_id=$db->f("parent_id");
                        $top=FALSE;
                        $last_found=$key;
                        while ((!$top) && ($superordinated_id)) {
                            $query = sprintf ("SELECT resource_id, parent_id, name FROM resources_objects WHERE resource_id = '%s' ", $db->f("parent_id"));
                            $db->query($query);
                            $db->next_record();
    
                            if ($my_resources[$db->f("resource_id")]) {
                                $checked[$last_found]=TRUE;
                                $last_found= $db->f("resource_id");
                            }
    
                            $superordinated_id=$db->f("parent_id");
                            if ($db->f("parent_id") == "0")
                                $top = TRUE;
                            
                        }
    
                        $this->my_roots[$last_found] = $last_found;
                    }
                }
            }
        }
    
    }
    
    //public
    function getRoots() {
        return $this->my_roots;
    }
}
?>
