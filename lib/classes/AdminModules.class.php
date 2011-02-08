<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* AdminModules.class.php
* 
* administrate modules (global and local for institutes and Veranstaltungen)
* 
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      core
* @module       AdminModules.class.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// AdminModules.class.php
// Administration fuer Module (global und lokal fuer Veranstaltungen und Einrichtungen)
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

global $RELATIVE_PATH_ELEARNING_INTERFACE;

require_once 'lib/functions.php';
require_once ('lib/forum.inc.php');
require_once ('config.inc.php');
require_once ('lib/datei.inc.php');
require_once ('lib/dates.inc.php');
require_once ('lib/classes/ModulesNotification.class.php');
require_once ('lib/classes/StudipLitList.class.php');
require_once ('lib/classes/StudipDocumentTree.class.php');
require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ObjectConnections.class.php");
require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ELearningUtils.class.php");

class AdminModules extends ModulesNotification {
    var $db;
    var $db2;
    
    function AdminModules() {
        parent::ModulesNotification();
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;
        //please add here the special messages for modules you need consistency checks (defined below in this class)
        $this->registered_modules["forum"]["msg_warning"] = _("Wollen Sie wirklich das Forum deaktivieren und damit alle Diskussionbeitr&auml;ge l&ouml;schen?");
        $this->registered_modules["forum"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren des Forums werden <b>%s</b> Postings ebenfalls gel&ouml;scht!");
        $this->registered_modules["forum"]["msg_activate"] = _("Das Forum kann jederzeit aktiviert werden.");
        $this->registered_modules["forum"]["msg_deactivate"] = _("Das Forum kann jederzeit deaktiviert werden.");


        $this->registered_modules["documents"]["msg_warning"] = _("Wollen Sie wirklich den Dateiordner deaktivieren und damit alle hochgeladenen Dokumente und alle Ordner l&ouml;schen?");
        $this->registered_modules["documents"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren des Dateiordners werden <b>%s</b> Dateien und Ordner ebenfalls gel&ouml;scht!");
        $this->registered_modules["documents"]["msg_activate"] = _("Der Dateiordner kann jederzeit aktiviert werden.");
        $this->registered_modules["documents"]["msg_deactivate"] = _("Der Dateiordner kann jederzeit deaktiviert werden.");

        $this->registered_modules["schedule"]["msg_activate"] = _("Die Ablaufplanverwaltung kann jederzeit aktiviert werden.");
        $this->registered_modules["schedule"]["msg_deactivate"] = _("Die Ablaufplanverwaltung kann jederzeit deaktiviert werden.");

        $this->registered_modules["participants"]["msg_activate"] = _("Die TeilnehmerInnenverwaltung kann jederzeit aktiviert werden.");
        $this->registered_modules["participants"]["msg_deactivate"] = _("Die TeilnehmerInnenverwaltung kann jederzeit deaktiviert werden. Bachten Sie, dass Sie dann keine normalen Teilnehmer verwalten k&ouml;nnen!");

        $this->registered_modules["personal"]["msg_activate"] = _("Die Personalliste kann jederzeit aktiviert werden.");
        $this->registered_modules["personal"]["msg_deactivate"] = _("Die Personalliste kann jederzeit deaktiviert werden.");

        $this->registered_modules["literature"]["msg_warning"] = _("Wollen Sie wirklich die Literaturverwaltung deaktivieren und damit die erfassten Literaturlisten l&ouml;schen?");
        $this->registered_modules["literature"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren der Literaturverwaltung werden <b>%s</b> &ouml;ffentliche / nicht &ouml;ffentliche Literaturlisten ebenfalls gel&ouml;scht!");
        $this->registered_modules["literature"]["msg_activate"] = _("Die Literaturverwaltung kann jederzeit aktiviert werden.");
        $this->registered_modules["literature"]["msg_deactivate"] = _("Die Literaturverwaltung kann jederzeit deaktiviert werden.");

        $this->registered_modules["chat"]["msg_activate"] = _("Der Chat kann jederzeit aktiviert werden.");
        $this->registered_modules["chat"]["msg_deactivate"] = _("Der Chat kann jederzeit deaktiviert werden.");

        $this->registered_modules["wiki"]["msg_warning"] = _("Wollen Sie wirklich das Wiki deaktivieren und damit alle Seitenversionen l&ouml;schen?");
        $this->registered_modules["wiki"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren des Wiki-Webs werden <b>%s</b> Seitenversionen ebenfalls gel&ouml;scht!");
        $this->registered_modules["wiki"]["msg_activate"] = _("Das Wiki-Web kann jederzeit aktiviert werden.");
        $this->registered_modules["wiki"]["msg_deactivate"] = _("Das Wiki-Web kann jederzeit deaktiviert werden.");

        $this->registered_modules["scm"]["msg_activate"] = _("Die freie Informationsseite kann jederzeit aktiviert werden.");
        $this->registered_modules["scm"]["msg_warning"] = _("Wollen Sie wirklich die freie Informationsseite deaktivieren und damit den erfassten Inhalt l&ouml;schen?");
        $this->registered_modules["scm"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren der freien Informationsseite werden die eingestellten Inhalte gel&ouml;scht!");
        $this->registered_modules["scm"]["msg_deactivate"] = _("Die freie Informationsseite kann jederzeit deaktiviert werden.");

        $this->registered_modules["elearning_interface"]["name"] = _("Lernmodul-Schnittstelle");
        $this->registered_modules["elearning_interface"]["msg_warning"] = _("Wollen Sie wirklich die Schnittstelle f&uuml;r die Integration von Content-Modulen deaktivieren und damit alle bestehenden Verkn&uuml;pfungen mit Lernmodulen l&ouml;schen? (Alle erstellten Inhalte im angebundenen System werden gel&ouml;scht).");
        $this->registered_modules["elearning_interface"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren der Schnittstelle f&uuml;r die Integration von Content-Modulen werden <b>%s</b> Verkn&uuml;pfungen mit Lernmodulen aufgel&ouml;st!");
        $this->registered_modules["elearning_interface"]["msg_activate"] = _("Die Schnittstelle f&uuml;r die Integration von Content-Modulen kann jederzeit aktiviert werden.");
        $this->registered_modules["elearning_interface"]["msg_deactivate"] = _("Die Schnittstelle f&uuml;r die Integration von Content-Modulen kann jederzeit deaktiviert werden.");
        
        $this->registered_modules["documents_folder_permissions"]['name'] = _("Dateiordnerberechtigungen");
        $this->registered_modules["documents_folder_permissions"]["msg_activate"] = _("Die Dateiordnerberechtigungen k&ouml;nnen jederzeit aktiviert werden.");
        $this->registered_modules["documents_folder_permissions"]["msg_warning"] = _("Wollen Sie wirklich die Dateiordnerberechtigungen deaktivieren und damit eventuell versteckte Inhalte zug&auml;nglich machen?");
        $this->registered_modules["documents_folder_permissions"]["msg_deactivate"] = _("Die Dateiordnerberechtigungen k&ouml;nnen jederzeit deaktiviert werden.");
        $this->registered_modules["documents_folder_permissions"]["msg_pre_warning"] = _("Achtung: Beim Deaktivieren der Dateiordnerberechtigungen werden <b>%s</b> gesch&uuml;tzte Ordner zug&auml;nglich!");
        $this->registered_modules["documents_folder_permissions"]['preconditions'] = array('documents');
    }
    
    function getModuleForumExistingItems($range_id) {
        $query = sprintf ("SELECT COUNT(topic_id) as items FROM px_topics WHERE Seminar_id = '%s' ", $range_id);

        $this->db->query($query);
        $this->db->next_record();
        
        return $this->db->f("items");
    }

    function moduleForumDeactivate($range_id) {
        $db = new DB_Seminar;
        $db2 = new DB_Seminar;
        
        $query = sprintf ("SELECT topic_id FROM px_topics WHERE Seminar_id='%s'", $range_id);
        $db->query($query);
        
        while ($db->next_record()) {
            $query2 = sprintf ("DELETE FROM px_topics WHERE topic_id='%s'", $db->f("topic_id"));
            $db2->query($query2);
        
            $query2 = sprintf ("UPDATE termine SET topic_id = NULL WHERE topic_id='%s'", $db->f("topic_id"));
            $db2->query($query2);
        }
    }
    
    function moduleForumActivate($range_id) {
        global $user;

        //create a default folder
        CreateTopic(_("Allgemeine Diskussionen"), get_fullname($user->id), _("Hier ist Raum für allgemeine Diskussionen"), 0, 0, $range_id);
    }   
    
    function getModuleDocumentsExistingItems($range_id) {
        $query = sprintf ("SELECT COUNT(dokument_id) as items FROM dokumente WHERE seminar_id = '%s' ", $range_id);

        $this->db->query($query);
        $this->db->next_record();
        
        $items = $this->db->f("items"); 
                                    
        $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $range_id));

        $items += $folder_tree->getNumKidsKids('root') - $folder_tree->getNumKids('root');
        return $items; 
    }

    function moduleDocumentsDeactivate($range_id) {
        delete_all_documents($range_id);
        //Örgs, warum immer ich...
        $this->clearBit($GLOBALS['admin_modules_data']["changed_bin"], $this->registered_modules['documents_folder_permissions']['id']);
    }
    
    function moduleDocumentsActivate($range_id) {
        global $user;
        
        $db = new DB_Seminar;

        //create a default folder
        $db->query("INSERT INTO folder SET folder_id='".md5(uniqid("sommervogel"))."', range_id='".$range_id."', user_id='".$user->id."', name='"._("Allgemeiner Dateiordner")."', description='"._("Ablage für allgemeine Ordner und Dokumente der Veranstaltung")."', mkdate='".time()."', chdate='".time()."'");
    }   

    function getModuleLiteratureExistingItems($range_id) {
        $list_count = StudipLitList::GetListCountByRange($range_id);
        return ($list_count["visible_list"] || $list_count["invisible_list"]) ? $list_count["visible_list"] . "/" . $list_count["invisible_list"] : false;
    }

    function moduleLiteratureDeactivate($range_id) {
        return StudipLitList:: DeleteListsByRange($range_id);
    }

    function getModuleWikiExistingItems($range_id) {
        $query = sprintf ("SELECT COUNT(keyword) as items FROM wiki WHERE range_id = '%s' ", $range_id);

        $this->db->query($query);
        $this->db->next_record();
        
        return $this->db->f("items");
    }

    function moduleWikiDeactivate($range_id) {
        $query = sprintf ("DELETE FROM wiki WHERE range_id='%s'", $range_id);
        $this->db->query($query);

        $query = sprintf ("DELETE FROM wiki_links WHERE range_id='%s'", $range_id);
        $this->db->query($query);

        $query = sprintf ("DELETE FROM wiki_locks WHERE range_id='%s'", $range_id);
        $this->db->query($query);
    }

    function getModuleScmExistingItems($range_id) {
        $query = sprintf ("SELECT COUNT(scm_id) as items FROM scm WHERE range_id = '%s' ", $range_id);

        $this->db->query($query);
        $this->db->next_record();

        return $this->db->f("items");
    }

    function moduleScmDeactivate($range_id) {
        $query = sprintf ("DELETE FROM scm WHERE range_id='%s'", $range_id);
        $this->db->query($query);
    }

    function moduleScmActivate($range_id) {
        global $user, $SCM_PRESET;

        //create a default folder
        $this->db->query("INSERT IGNORE INTO scm SET scm_id='".md5(uniqid("simplecontentmodule"))."', range_id='".$range_id."', user_id='".$user->id."', tab_name='".$SCM_PRESET[1]["name"]."', content='', mkdate='".time()."', chdate='".time()."'");
    }

    function getModuleElearning_interfaceExistingItems($range_id) {
        $object_connections = new ObjectConnections($range_id);
        return count($object_connections->getConnections());
    }

    function moduleElearning_interfaceDeactivate($range_id) {
        global $connected_cms;
        foreach(ObjectConnections::GetConnectedSystems($range_id) as $system){
            ELearningUtils::loadClass($system);
            $connected_cms[$system]->deleteConnectedModules($range_id);
        }
    }
    
    function moduledocuments_folder_permissionsDeactivate($range_id){
        $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $range_id));
        foreach($folder_tree->getKidsKids('root') as $folder_id){
            $folder_tree->setDefaultPermission($folder_id);
        }
    }
    
    function moduledocuments_folder_permissionsActivate($range_id){
    }
    
    function getModuledocuments_folder_permissionsExistingItems($range_id) {
        $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $range_id));
        return count($folder_tree->getUnreadableFolders('xxx', true));
    }
    
    function moduledocuments_folder_permissionsPreconditions($range_id, $args){
        if (is_array($args)){
            $must_activate = array();
            foreach($args as $m){
                if (!$this->getStatus($m, $range_id)){
                    $must_activate[] = $this->registered_modules[$m]['name'];
                }
            }
            if (count($must_activate)){
                return sprintf(_("Die Dateiordnerberechtigungen erfordern die Aktivierung von: <b>%s</b>"), join(', ',$must_activate));
            }
        }
        return null;
    }
    
    
}
