<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter003: TODO
# Lifter005: TEST
# Lifter010: TODO
/*
folder.php - Anzeige und Verwaltung des Ordnersystems
Copyright (C) 2001 Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

ob_start();
page_open(array("sess" => "Seminar_Session",
    "auth" => "Seminar_Auth",
    "perm" => "Seminar_Perm", "" .
    "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once('lib/datei.inc.php');
require_once('lib/msg.inc.php');
require_once('lib/visual.inc.php');
require_once('config.inc.php');
require_once 'lib/functions.php';
require_once('lib/classes/StudipDocumentTree.class.php');
require_once 'lib/raumzeit/Issue.class.php';

$open = Request::option('open');
$close = Request::option('close');
$check_all = Request::option('check_all');

//Switch fuer die Ansichten
URLHelper::bindLinkParam('data', $folder_system_data);
if (Request::option('cmd') == 'tree') {
    URLHelper::removeLinkParam('data');
    $folder_system_data = array();
    $folder_system_data['cmd'] = 'tree';
    URLHelper::addLinkParam('data', $folder_system_data);
} elseif (Request::option('cmd') == 'all') {
    URLHelper::removeLinkParam('data');
    $folder_system_data = array();
    $folder_system_data['cmd'] = 'all';
    URLHelper::addLinkParam('data', $folder_system_data);
} elseif(!isset($folder_system_data['cmd'])) {
    $folder_system_data['cmd'] = 'all';
}

if (Request::option('orderby')) {
    $folder_system_data['orderby'] = Request::option('orderby');
}

///////////////////////////////////////////////////////////
//Zip-Download-Funktionen
///////////////////////////////////////////////////////////
if (Request::get('folderzip')) {
    $zip_file_id = createFolderZip(Request::option('folderzip'), true, true);
    if($zip_file_id){
        $query = "SELECT name FROM folder WHERE folder_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::option('folderzip')));
        $name = $statement->fetchColumn();

        $zip_name = prepareFilename(_('Dateiordner') . '_' . $name . '.zip');
        header('Location: ' . getDownloadLink($zip_file_id, $zip_name, 4));
        page_close();
        die;
    }
}

if (Request::get('zipnewest')) {
    //Abfrage der neuen Dateien
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessSemName[1]));
    $query = "SELECT range_id, dokument_id, url
              FROM dokumente
              WHERE seminar_id = ? AND user_id != ?
                AND GREATEST(mkdate, IFNULL(chdate, 0)) > IFNULL(?, UNIX_TIMESTAMP())";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $SessSemName[1],
        $user->id,
        Request::get('zipnewest') ?: null,
    ));
    $download_ids = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach($download_ids as $key => $dl_id) {
        if ($folder_tree->isReadable($dl_id['range_id'], $user->id)
            && check_protected_download($dl_id['dokument_id']) && $dl_id['url'] == "") {
            $download_ids[$key] = $dl_id['dokument_id'];
        } else {
            unset($download_ids[$key]);
        }
    }
    if (count($download_ids)>0) {
        $zip_file_id = createSelectedZip($download_ids, true, true);
        if($zip_file_id){
            $zip_name = prepareFilename($SessSemName[0].'-'._("Neue Dokumente").'.zip');
            header('Location: ' . getDownloadLink( $zip_file_id, $zip_name, 4));
            page_close();
            die;
        }
    }
}

if (Request::submitted('download_selected')) {
    $download_ids = Request::optionArray('download_ids');
    if (count($download_ids)  > 0) {
        $zip_file_id = createSelectedZip($download_ids, true, true);
        if($zip_file_id){
            $zip_name = prepareFilename($SessSemName[0].'-'._("Dokumente").'.zip');
            header('Location: ' . getDownloadLink( $zip_file_id, $zip_name, 4));
            page_close();
            die;
        }
    }
}

checkObject();
checkObjectModule('documents');
object_set_visit_module('documents');

    // add skip links
    SkipLinks::addIndex(Navigation::getItem('/course/files/all')->getTitle(), 'main_content', 100);
    SkipLinks::addIndex(Navigation::getItem('/course/files/tree')->getTitle(), 'main_content', 100);
$folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessSemName[1]));

$question = $msg = '';

if($zip_file_id === false){
    $msg = 'error�'
    . sprintf(_("Der Zip Download ist fehlgeschlagen. Bitte beachten Sie das Limit von maximal %s Dateien und die maximale Gr��e der zu zippenden Dateien von %s MB."),
    (int)Config::GetInstance()->getValue('ZIP_DOWNLOAD_MAX_FILES'),
    (int)Config::GetInstance()->getValue('ZIP_DOWNLOAD_MAX_SIZE') )
    . '�';
}

//obskuren id+_?_ string zerpfl�cken
if (strpos($open, "_") !== false){
    list($open_id, $open_cmd) = explode('_', $open);
}

//Wenn nicht Rechte und Operation uebermittelt: Ist das mein Dokument und ist der Ordner beschreibbar?
if (!$rechte && $open_cmd) {
    $query = "SELECT user_id, range_id FROM dokumente WHERE dokument_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($open_id));
    $result = $statement->fetch();

    $owner = (($result['user_id'] == $user->id)
           && ($result['user_id'] != 'nobody')
           && $folder_tree->isWritable($result['range_id'], $user->id)); 
} else {
    $owner = FALSE;
}

if(!$rechte && in_array($open_cmd, array('n','d','c','sc','m','co')) && $SemUserStatus == "autor"){
    $create_folder_perm = $folder_tree->checkCreateFolder($open_id, $user->id);
} else {
    $create_folder_perm = false;
}
//verschiebemodus abbrechen, wenn andere Aktion ausgew�hlt wurde
if($folder_system_data["mode"] != '' && ($open_cmd && !in_array($open_cmd, array('n','md')))){
    $folder_system_data["move"]='';
    $folder_system_data["mode"]='';
}
//bei edit und upload Aktionen alle anderen Objekte schlie�en
if (in_array($open_cmd, words('n a c rfu led u z l'))) {
    unset($folder_system_data["open"]);
}


if ($rechte || $owner || $create_folder_perm) {
    //wurde Code fuer Anlegen von Ordnern ubermittelt (=id+"_n_"), wird entsprechende Funktion aufgerufen
    if ($open_cmd == 'n' && (!Request::submitted("cancel"))) {
        $change = create_folder(_("Neuer Ordner"), '', $open_id );
        $open_id = $change;
        //$open_cmd = null;
    }

    //wurde Code fuer Anlegen von Ordnern der obersten Ebene ubermittelt (=id+"_a_"),
    //wird entsprechende Funktion aufgerufen
    if ($open_cmd == 'a') {
        $permission = 7;
        if ($open_id == $SessSemName[1]) {
            $titel=_("Allgemeiner Dateiordner");
            $description= sprintf(_("Ablage f�r allgemeine Ordner und Dokumente der %s"), $SessSemName["art_generic"]);
        } else if ($open_id == md5('new_top_folder')){
            $titel = Request::get('top_folder_name') ? Request::get('top_folder_name') : _("Neuer Ordner");
            $open_id = md5($SessSemName[1] . 'top_folder');
        } elseif($titel = GetStatusgruppeName($open_id)) {
            $titel = _("Dateiordner der Gruppe:") . ' ' . $titel;
            $description = _("Ablage f�r Ordner und Dokumente dieser Gruppe");
            $permission = 15;
        } else if ($data = SingleDateDB::restoreSingleDate($open_id)) {
            // If we create a folder which has not yet an issue, we just create one
            $issue = new Issue(array('seminar_id' => $SessSemName[1]));
            $issue->setTitle(_("Ohne Titel"));
            $termin = new SingleDate($open_id);
            $termin->addIssueID($issue->getIssueID());
            $issue->store();
            $termin->store();

            $open_id = $issue->getIssueID();
            $titel = $issue->getTitle();
            $description= _("Themenbezogener Dateiordner");
        } else {
            $query = "SELECT title FROM themen WHERE issue_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($open_id));
            if ($result = $statement->fetch()) {
                $titel = $result["title"];
                $description= _("Themenbezogener Dateiordner");
            }
        }
        $change = create_folder(addslashes($titel), $description, $open_id, $permission);
        $folder_system_data["open"][$change] = TRUE;
        $folder_system_data['open']['anker'] = $change;
    }

    //wurde Code fuer Loeschen von Ordnern ubermittelt (=id+"_d_"), wird entsprechende Funktion aufgerufen
    if ($open_cmd == 'd') {
        if ( ($count = doc_count($open_id)) ){
            $question = createQuestion(sprintf(_('Der ausgew�hlte Ordner enth�lt %s Datei(en). Wollen Sie den Ordner wirklich l�schen?'), $count), array('open' => $open_id.'_rd_'));
        } else {
            delete_folder($open_id, true);
            $open_id = $folder_tree->getParents($open_id);
            $open_id = $open_id[0];
            $folder_tree->init();
        }
    }

    //Loeschen von Ordnern im wirklich-ernst Mode
    if ($open_cmd == 'rd') {
        delete_folder($open_id, true);
        $open_id = $folder_tree->getParents($open_id);
        $open_id = $open_id[0];
        $folder_tree->init();
    }

    //wurde Code fuer Loeschen von Dateien ubermittelt (=id+"_fd_"), wird erstmal nachgefragt
    if ($open_cmd == 'fd') {
        $query = "SELECT filename, {$_fullname_sql['full']} AS fullname, username
                  FROM dokumente
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $result = $statement->fetch();
        if (getLinkPath($open_id)) {
            $question = createQuestion(sprintf(_('Wollen Sie die Verlinkung zu "%s" von %s wirklich l�schen?'), $result['filename'], $result['fullname']), array('open' => $open_id.'_rl_'));
        } else {
            $question = createQuestion(sprintf(_('Wollen Sie die Datei "%s" von %s wirklich l�schen?'), $result['filename'], $result['fullname']), array('open' => $open_id.'_rm_'));
        }
    }

    //Loeschen von Dateien im wirklich-ernst Mode
    if ($open_cmd == 'rm') {
        if (delete_document($open_id))
            $msg.="msg�" . _("Die Datei wurde gel&ouml;scht") . "�";
        else
            $msg.="error�" . _("Die Datei konnte nicht gel&ouml;scht werden") . "�";
        }

    //Loeschen von verlinkten Dateien im wirklich-ernst Mode
    if ($open_cmd == 'rl') {
        if (delete_link($open_id))
            $msg.="msg�" . _("Die Verlinkung wurde gel�scht") . "�";
        else
            $msg.="error�" . _("Die Verlinkung konnte nicht gel�scht werden") . "�";
    }

    //wurde Code fuer Aendern des Namens und der Beschreibung von Ordnern oder Dokumenten ubermittelt (=id+"_c_"), wird entsprechende Funktion aufgerufen
    if ($open_cmd ==  'c') {
        $change=$open_id;
    }

    //wurde Code fuer Speichern von Aenderungen uebermittelt (=id+"_sc_"), wird entsprechende Funktion aufgerufen
    if ($open_cmd == 'sc' && (!Request::submitted("cancel"))) {
        edit_item($open_id, Request::int('type'), Request::get('change_name'), Request::get('change_description'), Request::int('change_protected', 0));
    }

    //wurde Code fuer Verschieben-Vorwaehlen uebermittelt (=id+"_m_"), wird entsprechende Funktion aufgerufen
    if ($open_cmd == 'm' && (!Request::submitted("cancel"))) {
        $folder_system_data["move"]=$open_id;
        $folder_system_data["mode"]='move';
    }

    //wurde Code fuer Hoch-Schieben einer Datei (=id+"_mfu_") in der Darstellungsreihenfolge ausgew�hlt?
    if (($open_cmd == 'mfu') && !Request::submitted('cancel')) {
        $query = "SELECT range_id FROM dokumente WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $temp_id = $statement->fetchColumn();

        $query = "SELECT dokument_id FROM dokumente WHERE range_id = ? ORDER BY priority, chdate";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($temp_id));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        for ($i = 1; $i < count($result); $i += 1) {
            if ($result[$i] == $open_id) {
                $result[$i]     = $result[$i - 1];
                $result[$i - 1] = $open_id;
            }
        }

        $query = "UPDATE dokumente SET priority = ? WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);

        for ($i = 0; $i < count($result); $i += 1) {
            $statement->execute(array($i + 1, $result[$i]));
        }
        unset($open_id);
    }

    //wurde Code fuer Runter-Schieben einer Datei (=id+"_mfu_") in der Darstellungsreihenfolge ausgew�hlt?
    if (($open_cmd == 'mfd') && !Request::submitted('cancel')) {
        $query = "SELECT range_id FROM dokumente WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $temp_id = $statement->fetchColumn();

        $query = "SELECT dokument_id FROM dokumente WHERE range_id = ? ORDER BY priority, chdate";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($temp_id));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        for ($i = count($result) - 1; $i >= 0; $i -= 1) {
            if ($result[$i] == $open_id) {
                $result[$i]     = $result[$i + 1];
                $result[$i + 1] = $open_id;
            }
        }

        $query = "UPDATE dokumente SET priority = ? WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);

        for ($i = 0; $i < count($result); $i += 1) {
            $statement->execute(array($i + 1, $result[$i]));
        }
        unset($open_id);
    }

    //wurde Code fuer Hoch-Schieben eines Ordners (=id+"_mfou_") in der Darstellungsreihenfolge ausgew�hlt?
    if (($open_cmd == 'mfou') && (!Request::submitted("cancel"))) {
        $query = "SELECT range_id FROM folder WHERE folder_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $temp_id = $statement->fetchColumn();

        $query = "SELECT folder_id FROM folder WHERE range_id = ? ORDER BY priority, chdate";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($temp_id));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        for ($i = 1; $i < count($result); $i += 1) {
            if ($result[$i] == $open_id) {
                $result[$i]     = $result[$i - 1];
                $result[$i - 1] = $open_id;
            }
        }

        $query = "UPDATE folder SET priority = ? WHERE folder_id = ?";
        $statement = DBManager::get()->prepare($query);

        for ($i = 0; $i < count($result); $i += 1) {
            $statement->execute(array($i + 1, $result[$i]));
        }
        unset($open_id);
    }

    //wurde Code fuer Runter-Schieben einer Datei (=id+"_mfu_") in der Darstellungsreihenfolge ausgew�hlt?
    if (($open_cmd == 'mfod') && (!Request::submitted("cancel"))) {
        $query = "SELECT range_id FROM folder WHERE folder_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $temp_id = $statement->fetchColumn();

        $query = "SELECT folder_id FROM folder WHERE range_id = ? ORDER BY priority, chdate";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($temp_id));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        for ($i = count($result) - 1; $i >= 0; $i -= 1) {
            if ($result[$i] == $open_id) {
                $result[$i]     = $result[$i + 1];
                $result[$i + 1] = $open_id;
            }
        }

        $query = "UPDATE folder SET priority = ? WHERE folder_id = ?";
        $statement = DBManager::get()->prepare($query);

        for ($i = 0; $i < count($result); $i += 1) {
            $statement->execute(array($i + 1, $result[$i]));
        }
        unset($open_id);
    }

    //wurde Code f�r alphabetisches Sortieren (=id+"_az_") fuer Ordner id ausgew�hlt?
    if (($open_cmd == 'az') && (!Request::submitted("cancel"))) {
        $query = "SELECT dokument_id FROM dokumente WHERE range_id = ? ORDER BY name, chdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        $query = "UPDATE dokumente SET priority = ? WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);

        for ($i = 0; $i < count($result); $i += 1) {
            $statement->execute(array($i + 1, $result[$i]));
        }

        $query = "SELECT folder_id FROM folder WHERE range_id = ? ORDER BY name, chdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $result = $statement->fetchAll(PDO::FETCH_COLUMN);

        $query = "UPDATE folder SET priority = ? WHERE folder_id = ?";
        $statement = DBManager::get()->prepare($query);

        for ($i = 0; $i < count($result); $i += 1) {
            $statement->execute(array($i + 1, $result[$i]));
        }
    }

    //wurde Code fuer Kopieren-Vorwaehlen uebermittelt (=id+"_co_"), wird entsprechende Funktion aufgerufen
    if ($open_cmd == 'co' && (!Request::submitted("cancel"))) {
        $folder_system_data["move"]=$open_id;
        $folder_system_data["mode"]='copy';
        }

    //wurde Code fuer Aktualisieren-Hochladen uebermittelt (=id+"_rfu_"), wird entsprechende Variable gesetzt
    if ($open_cmd == 'rfu' && (!Request::submitted("cancel"))) {
        $folder_system_data["upload"]=$open_id;
        $folder_system_data["refresh"]=$open_id;
        unset($folder_system_data["zipupload"]);
    }

    //wurde Code fuer Aktualisieren-Verlinken uebermittelt (=id+"_led_"), wird entsprechende Variable gesetzt
    if ($open_cmd == 'led' && (!Request::submitted("cancel"))) {
        $folder_system_data["link"]=$open_id;
        $folder_system_data["update_link"]=TRUE;
    }
}

//Upload, Check auf Konsistenz mit Seminar-Schreibberechtigung
if (($SemUserStatus == "autor") || ($rechte)) {
    //wurde Code fuer Hochladen uebermittelt (=id+"_u_"), wird entsprechende Variable gesetzt
    if ($open_cmd == 'u' && (!Request::submitted("cancel"))) {
        $folder_system_data["upload"]=$open_id;
        unset($folder_system_data["refresh"]);
        unset($folder_system_data["zipupload"]);
    }
    if ($open_cmd == 'z' && $rechte  && !Request::submitted("cancel")) {
        $folder_system_data["upload"]=$open_id;
        $folder_system_data["zipupload"]=$open_id;
    }


    //wurde Code fuer Verlinken uebermittelt (=id+"_l_"), wird entsprechende Variable gesetzt
    if ($open_cmd == 'l' && (!Request::submitted("cancel"))) {
        $folder_system_data["link"]=$open_id;
    }

    //wurde eine Datei hochgeladen/aktualisiert?
    $cmd = Request::get("cmd");
    if (($cmd=="upload") && (!Request::submitted("cancel")) && ($folder_system_data["upload"])) {
        if (!$folder_system_data["zipupload"]){
            upload_item ($folder_system_data["upload"], TRUE, FALSE, $folder_system_data["refresh"]);
            $open = $dokument_id;
            $close = $folder_system_data["refresh"];
            $folder_system_data["upload"]='';
            $folder_system_data["refresh"]='';
        } elseif ($rechte && get_config('ZIP_UPLOAD_ENABLE')) {
            upload_zip_item();
            $folder_system_data["upload"]='';
            $folder_system_data["zipupload"]='';
        }
        unset($cmd);
        }

    //wurde eine Datei verlinkt?
    if (($cmd=="link") && (!Request::submitted("cancel")) && ($folder_system_data["link"])) {
        if (link_item ($folder_system_data["link"], TRUE, FALSE, $folder_system_data["refresh"],FALSE)) {
            $open = $dokument_id;
            $close = $folder_system_data["refresh"];
            $folder_system_data["link"]='';
            $folder_system_data["refresh"]='';
            $folder_system_data["update_link"]='';
            unset($cmd);
        } else {
            $folder_system_data["linkerror"]=TRUE;
        }
    }

    //wurde ein Link aktualisiert?
    if (($cmd=="link_update") && (!Request::submitted("cancel")) && ($folder_system_data["link"])) {
        if (link_item ($range_id, TRUE, FALSE, FALSE, Request::option('link_update'))) {
            $open = $link_update;
            $close = $folder_system_data["refresh"];
            $folder_system_data["link"]='';
            $folder_system_data["refresh"]='';
            $folder_system_data["update_link"]='';
            unset($cmd);
        } else {
            $folder_system_data["linkerror"]=TRUE;
        }
    }
    //verschieben / kopieren in andere Veranstaltung
    if ($rechte && Request::submittedSome('move_to_sem', 'move_to_inst', 'move_to_top_folder')){
        if (!Request::submitted('move_to_top_folder')){
            $new_sem_id = Request::submitted('move_to_sem') ? Request::getArray('sem_move_id') : Request::getArray('inst_move_id');
        } else {
            $new_sem_id = array($SessSemName[1]);
        }
        if ($new_sem_id) {
            foreach($new_sem_id as $sid) {
                $new_range_id = md5($sid . 'top_folder');
                if ($folder_system_data["mode"] == 'move'){
                    $done = move_item($folder_system_data["move"], $new_range_id, $sid);
                    if (!$done){
                        $msg .= "error�" . _("Verschiebung konnte nicht durchgef�hrt werden. Eventuell wurde im Ziel der Allgemeine Dateiordner nicht angelegt.") . "�";
                    } else {
                        $msg .= "msg�" . sprintf(_("%s Ordner, %s Datei(en) wurden verschoben."), $done[0], $done[1]) . '�';
                    }
                } else {
                    $done = copy_item($folder_system_data["move"], $new_range_id, $sid);
                    if (!$done){
                        $msg .= "error�" . _("Kopieren konnte nicht durchgef�hrt werden. Eventuell wurde im Ziel der Allgemeine Dateiordner nicht angelegt.") . "�";
                    } else {
                        $s_name = get_object_name($sid, Request::submitted('move_to_sem') ? "sem" : "inst");
                        $msg .= "msg�" . $s_name['name'] . ": " . sprintf(_("%s Ordner, %s Datei(en) wurden kopiert."), $done[0], $done[1]) . '�';
                    }
                }
            }
        }
        $folder_system_data["move"]='';
        $folder_system_data["mode"]='';
    }

    if (Request::submitted("cancel"))  {
        $folder_system_data["upload"]='';
        $folder_system_data["refresh"]='';
        $folder_system_data["link"]='';
        $folder_system_data["update_link"]='';
        $folder_system_data["move"]='';
        $folder_system_data["mode"]='';
        $folder_system_data["zipupload"]='';
        unset($cmd);
    }
}

//verschieben / kopieren innerhalb der Veranstaltung
//wurde Code fuer Starten der Verschiebung uebermittelt (=id+"_md_"), wird entsprechende Funktion aufgerufen (hier kein Rechtecheck noetig, da Dok_id aus Sess_Variable.
if ($open_cmd == 'md' && $folder_tree->isWritable($open_id, $user->id) && !Request::submitted("cancel") && (!$folder_tree->isFolder($folder_system_data["move"]) || ($folder_tree->isFolder($folder_system_data["move"]) && $folder_tree->checkCreateFolder($open_id, $user->id)))) {
    if ($folder_system_data["mode"] == 'move'){
        $done = move_item($folder_system_data["move"], $open_id);
        if (!$done){
            $msg .= "error�" . _("Verschiebung konnte nicht durchgef�hrt werden.") . "�";
        } else {
            $msg .= "msg�" . sprintf(_("%s Ordner, %s Datei(en) wurden verschoben."), $done[0], $done[1]) . '�';
        }
    } else {
        $done = copy_item($folder_system_data["move"], $open_id);
        if (!$done){
            $msg .= "error�" . _("Kopieren konnte nicht durchgef�hrt werden.") . "�";
        } else {
            $msg .= "msg�" . sprintf(_("%s Ordner, %s Datei(en) wurden kopiert."), $done[0], $done[1]) . '�';
        }
    }
    $folder_system_data["move"]='';
    $folder_system_data["mode"]='';
}

//wurde ein weiteres Objekt aufgeklappt?
if (isset($open)) {
    if (!isset($open_id)) {
        $open_id = $open;
    }
    $folder_system_data["open"][$open_id] = true;
    $folder_system_data["open"]['anker'] = $open_id;
    //�bergeordnete Ordner mit�ffnen - das ergibt Sinn
    if (!($path = $folder_tree->getParents($open_id))) {
        //Und falls $open ein Dokument sein sollte:
        $query = "SELECT range_id FROM dokumente WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($open_id));
        $path = $statement->fetchColumn();

        $folder_system_data['open'][$path] = true;
        $path = $folder_tree->getParents($path);
    }
    for ($i=0; $i < count($path); $i++) {
        if ($path[$i] != 'root') {
            $folder_system_data['open'][$path[$i]] = true;
        }
    }
}
//wurde ein Objekt zugeklappt?
if ($close) {
    unset($folder_system_data["open"][$close]);
    $folder_system_data["open"]['anker'] = $close;
}
if ($rechte && Request::submitted('delete_selected')) {
    $download_ids = Request::optionArray('download_ids');
    if (count($download_ids) > 0) {
        $files_to_delete = array_map(function($f) {return htmlReady(StudipDocument::find($f)->filename) . '<input type="hidden" name="download_ids[]" value="' . $f . '">';}, $download_ids);
        $template = $template_factory->open('usermanagement/question_form.php');
        $template->set_attribute('question', _('M�chten Sie die ausgew�hlten Dateien wirklich l�schen?'));
        $template->set_attribute('elements', array('<ul><li>' . join('</li><li>', $files_to_delete) . '</li></ul>'));
        $template->set_attribute('approvalbutton', Button::createAccept(_('JA!'), 'delete'));
        $template->set_attribute('disapprovalbutton', Button::createCancel(_('NEIN!')));
        $template->set_attribute('action', URLHelper::getLink());
        $question = $template->render();
    }
}

if ($rechte && Request::submitted('delete') && count(Request::optionArray('download_ids'))) {
    CSRFProtection::verifyUnsafeRequest();
    $deleted = 0;
    foreach (Request::optionArray('download_ids') as $one) {
        $deleted += delete_document($one);
    }
    if ($deleted) {
        $msg .= "msg�" . sprintf(_("Es wurden %s Dateien gel�scht."), $deleted) . '�';
    }

}


///////////////////////////////////////////////////////////
//Ajax-Funktionen
///////////////////////////////////////////////////////////
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    ob_end_clean();
    ob_start();
    //Frage den Dateienk�rper ab

    if (Request::get("getfilebody")) {
        $query = "SELECT {$_fullname_sql['full']} AS fullname, username, a.user_id, a.*,
                         IF(IFNULL(a.name, '') = '', a.filename, a.name) AS t_name
                  FROM dokumente AS a
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE a.dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::get("getfilebody")));
        $datei = $statement->fetch(PDO::FETCH_ASSOC);
        if ($folder_tree->isReadable($datei['range_id'] , $user->id)){
            $all = $folder_system_data['cmd']=='tree' ? FALSE : TRUE;
            display_file_body($datei, null, $folder_system_data["open"], null, $folder_system_data["move"], $folder_system_data["upload"], $all, $folder_system_data["refresh"], $folder_system_data["link"]);
        }
    }

    //Frage den Ordnerk�rper ab
    if (Request::quoted("getfolderbody")) {
        if ($folder_tree->isExecutable(Request::quoted("getfolderbody") , $user->id)) {
            display_folder_body(Request::quoted("getfolderbody"), $folder_system_data["open"], null, $folder_system_data["move"], null, null, null, null);
        }
    }

    //Dateien eines Ordners sollen sortiert werden nach einem Array
    if (Request::quoted("folder_sort")) {
        if (($rechte) && (Request::quoted("folder_sort") == "root")) {

        } else {
            if (($rechte) || ($folder_tree->isWriteable(Request::quoted("folder_sort") , $user->id))) {
                $file_order = explode(",", Request::get('file_order'));
                $sorttype = "";
                if ($file_order) {
                    $query = "SELECT 1 FROM dokumente WHERE dokument_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($file_order[0]));
                    $result = $statement->fetchColumn();
                    if ($result) {
                        $sorttype = 'file';
                    } else {
                        $query = "SELECT 1 FROM folder WHERE folder_id = ?";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($file_order[0]));
                        $result = $statement->fetchColumn();
                        if ($result) {
                            $sorttype = "folder";
                        }
                    }
                }
                if ($sorttype == "file") {
                    //Dateien werden sortiert:
                    $query = "UPDATE dokumente SET priority = ? WHERE dokument_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    for ($i = 0; $i < count($file_order); $i += 1) {
                        $statement->execute(array($i + 1, $file_order[$i]));
                    }
                } elseif ($sorttype == "folder") {
                    //Ordner werden sortiert:
                    $query = "UPDATE folder SET priority = ? WHERE folder_id = ?";
                    $statement = DBManager::get()->prepare($query);

                    for ($i = 0; $i < count($file_order); $i += 1) {
                        $statement->execute(array($i + 1, $file_order[$i]));
                    }
                }
            }
        }
    }

    //Datei soll in einen Ordner verschoben werden

    if ((Request::get("moveintofolder")) && (Request::get("movefile"))) {
        $query = "SELECT range_id FROM dokumente WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::get("movefile")));
        $temp_id = $statement->fetchColumn();

        if (($rechte) || (($folder_tree->isWriteable($temp_id , $user->id))
        && ($folder_tree->isWriteable(Request::get("moveintofolder") , $user->id)))) {
            $query = "UPDATE dokumente
                      SET range_id = ?, priority = 0
                      WHERE dokument_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(Request::get("moveintofolder"),Request::get("movefile")));
        }
    }

    //Datei soll in einen Ordner kopiert werden
    if ((Request::get("copyintofolder")) && (Request::get("copyfile"))) {
        $query = "SELECT name, description, filename, mkdate, filesize, 
                         autor_host, url, protected
                  FROM dokumente
                  WHERE dokument_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::get("copyfile")));
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (($rechte) || ($folder_tree->isWriteable(Request::get("copyintofolder") , $user->id))) {
            $doc = new StudipDocument();
            $doc->setData(
                array(
                    'range_id'    => Request::get("copyintofolder"),
                    'user_id'     => $user->id,
                    'seminar_id'  => $SessSemName[1],
                    'name'        => $result['name'],
                    'description' => $result['description'],
                    'filename'    => $result['filename'],
                    'mkdate'      => $result['mkdate'],
                    'chdate'      => time(),
                    'filesize'    => $result['filesize'],
                    'autor_host'  => $result['autor_host'],
                    'download'    => 0,
                    'url'         => $result['url'],
                    'protected'   => $result['protected'],
                    'priority'    => 0
                ));
            $doc->store();
        }
    }
    $output = ob_get_clean();
    print studip_utf8encode($output);
    page_close();
    die();
}
///////////////////////////////////////////////////////////
//Ende Ajax-Funktionen
///////////////////////////////////////////////////////////

// Start of Output

PageLayout::setHelpKeyword("Basis.Dateien");
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Dateien"));

if ($folder_system_data['cmd'] == 'all') {
    Navigation::activateItem('/course/files/all');
} else {
    Navigation::activateItem('/course/files/tree');
}

$config = Config::get();
if ($config['FILESYSTEM_MULTICOPY_ENABLE']) {
    PageLayout::addStylesheet('jquery-ui-multiselect.css');
    PageLayout::addScript('ui.multiselect.js');
}

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

// Hauptteil

 if (!isset($range_id))
    $range_id = $SessSemName[1] ;

//JS Routinen einbinden, wenn benoetigt. Wird in der Funktion gecheckt, ob noetig...
JS_for_upload();
//we need this <body> tag, sad but true :)
echo "\n<body onUnLoad=\"STUDIP.OldUpload.upload_end()\">";
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%" id="main_content">

<?
if ($msg) {
    echo "<tr><td class='blank' colspan=3>&nbsp;";
    parse_msg($msg);
    echo "</td></tr>";
}
if ($question) {
    echo $question;
}

    //Ordner die fehlen, anlegen: Allgemeiner, wenn nicht da, Ordner zu Terminen, die keinen Ordner haben
    if ($rechte){
        if ($folder_system_data['mode']){
            $module_check = new Modules();
            $my_sem = $my_inst = array();
            foreach(search_range('%') as $key => $value){
                if ($module_check->getStatus('documents', $key, $value['type']) && $key != $SessSemName[1]){
                    if ($value['type'] == 'sem'){
                        $my_sem[$key] = $value['name'];
                    } else {
                        $my_inst[$key] = $value['name'];
                    }
                }
            }
            asort($my_sem, SORT_STRING);
            asort($my_inst, SORT_STRING);
            $button_name = $folder_system_data["mode"] == 'move' ? _('verschieben') : _('kopieren');
            echo '<form action="'.URLHelper::getLink('').'" method="post">';
            echo CSRFProtection::tokenTag();
            echo "\n" . '<tr><td class="blank" colspan="3" width="100%" style="font-size:80%;">';
            echo "\n" . '<div style="margin-left:25px;">';
            echo "\n<b>" . ($folder_system_data["mode"] == 'move' ? _("Verschiebemodus") : _("Kopiermodus")) . "</b><br>";
            if(!$folder_tree->isFolder($folder_system_data["move"])){
                echo _("Ausgew�hlte Datei in den Allgemeinen Dateiordner einer anderen Veranstaltung oder einer anderen Einrichtung verschieben / kopieren:");
            } else {
                echo _("Ausgew�hlten Ordner in eine andere Veranstaltung, eine andere Einrichtung oder auf die obere Ebene verschieben / kopieren:");
            }
            echo "\n</div></td></tr><tr>";
            if ($folder_tree->isFolder($folder_system_data["move"])) {
                echo "\n" . '<td class="blank">&nbsp;</td>';
                echo "\n" . '<td class="blank" width="60%" style="font-size:80%;">';
                echo "\n" . '<input type="image" border="0" src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/yellow/arr_2right.png" class="middle" name="move_to_top_folder" ' . tooltip(_("Auf die obere Ebene verschieben / kopieren")) . '>';
                echo '&nbsp;' . _("Auf die obere Ebene verschieben / kopieren") . '</td>';
                echo "\n" . '<td class="blank">';
                echo Button::create($button_name, "move_to_top_folder");
                echo "\n</td></tr><tr>";
            }
            echo "\n" .'<td class="blank" width="20%" style="font-size:80%;">';
            echo "\n" . '<div style="margin-left:25px;">';
            echo _("Veranstaltung") .':';
            echo '</div></td><td class="blank" width="60%" style="white-space: nowrap;">';
            echo "\n" . '<input type="image" border="0" src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/yellow/arr_2right.png" class="middle" name="move_to_sem" id="move_to_sem_arrow" ' . tooltip(_("In diese Veranstaltung verschieben / kopieren")) . '>';
            echo "\n" . '<select id="sem_move_id" name="sem_move_id[]" style="width:60%">';
            foreach ($my_sem as $id => $name){
                echo "\n" . '<option value="'.$id.'">' . htmlReady(my_substr($name,0,70)) . '</option>';
            }
            echo "\n" . '</select>';
            if ($config['FILESYSTEM_MULTICOPY_ENABLE'] && $open_cmd != 'm') {
                echo "\n<a href=\"\" onClick=\"STUDIP.MultiSelect.create('#sem_move_id', 'Veranstaltungen'); $(this).hide(); return false\">".Assets::img("icons/16/blue/add.png", array('title' => _("Mehrere Veranstaltungen ausw�hlen"), "class" => "middle"))."</a>";
            }
            echo "\n</td>";
            echo "\n" . '<td class="blank">';
            echo Button::create($button_name, "move_to_sem");
            echo "\n</td></tr><tr>";
            echo "\n" .'<td class="blank" width="20%"  style="font-size:80%;">';
            echo "\n" . '<div style="margin-left:25px;">';
            echo _("Einrichtung").':';
            echo '</div></td><td class="blank" width="60%" style="white-space: nowrap;">';
            echo "\n" . '<input type="image" border="0" src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/yellow/arr_2right.png" class="middle" id="move_to_inst_arrow" class="middle" name="move_to_inst" ' . tooltip(_("In diese Einrichtung verschieben / kopieren")) . '>';
            echo "\n" . '<select id="inst_move_id" name="inst_move_id[]" style="width:60%">';
            foreach ($my_inst as $id => $name){
                echo "\n" . '<option value="'.$id.'">' . htmlReady(my_substr($name,0,70)) . '</option>';
            }
            echo "\n" . '</select>';
            if ($config['FILESYSTEM_MULTICOPY_ENABLE'] && $open_cmd != 'm') {
                echo "\n<a href=\"\" onClick=\"STUDIP.MultiSelect.create('#inst_move_id', 'Institute'); $(this).hide(); return false\">".Assets::img("icons/16/blue/add.png", array('title' => _("Mehrere Einrichtungen ausw�hlen"), "class" => "middle"))."</a>";
            }
            echo "\n</td>";
            echo "\n" . '<td class="blank">';
            echo Button::create($button_name, "move_to_inst");
            echo "\n</td></tr><tr>";
            echo "\n" . '<td class="blank" align="center" colspan="3" width="100%" >';
            echo Button::createCancel(_("Verschieben / Kopieren abbrechen"), 'cancel');
            echo "\n" . '</td></tr></form>';


        } elseif($folder_system_data["cmd"]=="tree") {
            $select = '<option value="' . md5("new_top_folder") . '_a_">' . _("ausw&auml;hlen oder wie Eingabe").' --&gt;</option>';
            $query = "SELECT SUM(1) FROM folder WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($range_id));
            $result2 = $statement->fetchColumn();
            if ($result2 == 0) {
                $select.="\n<option value=\"".$range_id."_a_\">" . _("Allgemeiner Dateiordner") . "</option>";
            }


            if($SessSemName['class'] == 'sem'){
                $query = "SELECT statusgruppen.name, statusgruppe_id
                          FROM statusgruppen
                          LEFT JOIN folder ON (statusgruppe_id = folder.range_id)
                          WHERE statusgruppen.range_id = ? AND folder_id IS NULL
                          ORDER BY position";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($range_id));
                $result2 = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result2 as $row2) {
                    $select.="\n<option value=\"".$row2['statusgruppe_id']."_a_\">" . sprintf(_("Dateiordner der Gruppe: %s"), htmlReady($row2['name'])) . "</option>";
                }

                $issues = array();
                $shown_dates = array();

                $query = "SELECT themen_termine.issue_id, termine.date, folder.name, termine.termin_id, date_typ
                          FROM termine
                          LEFT JOIN themen_termine USING (termin_id)
                          LEFT JOIN folder ON (themen_termine.issue_id = folder.range_id)
                          WHERE termine.range_id = ? AND folder.folder_id IS NULL
                          ORDER BY termine.date, name";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($range_id));
                $result2 = $statement->fetchAll(PDO::FETCH_ASSOC);

                foreach ($result2 as $row2) {
                    if (!$row2["name"]) {
                        $issue_name = false;
                        if ($row2['issue_id']) {
                            if (!$issues[$row2['issue_id']]) {
                                $issues[$row2['issue_id']] = new Issue(array('issue_id' => $row2['issue_id']));
                            }
                            $issue_name = $issues[$row2['issue_id']]->toString();
                            $issue_name = htmlReady(my_substr($issue_name, 0, 20));
                            $option_id = $row2['issue_id'];
                        } else {
                            $option_id = $row2['termin_id'];
                        }

                        $select .= "\n".sprintf('<option value="%s_a_">%s</option>',
                            $option_id,
                            sprintf(_("Ordner f�r %s [%s]%s"),
                                date("d.m.Y", $row2["date"]),
                                $TERMIN_TYP[$row2["date_typ"]]["name"],
                                ($issue_name ? ', '.$issue_name : '')
                            )
                        );

                    }
                }

            }

            if ($select) {
                ?>
                <tr>
                <td class="blank" colspan="3" width="100%" style="padding-left:10px;">
                <form action="<? echo URLHelper::getLink('#anker') ?>" method="POST">
                    <?= CSRFProtection::tokenTag() ?>
                    <select name="open" style="vertical-align:middle" aria-label="<?= _("Name f�r neuen Ordner ausw�hlen") ?>">
                        <? echo $select ?>
                    </select>
                    <input type="text" name="top_folder_name" size="50" aria-label="<?= _("Name f�r neuen Ordner eingeben") ?>">
                    <?= Button::create(_("Neuer Ordner"), "anlegen") ?>
                </form>
                <?
                }
            }
    } elseif($folder_system_data['mode']){
        echo "\n" . '<td class="blank" align="center" colspan="3" width="100%" >';
        echo "\n" . '<span style="margin:25px;font-weight:bold;">';
        echo "\n" . ($folder_system_data["mode"] == 'move' ? _("Verschiebemodus") : _("Kopiermodus")) . "</span>";
        echo LinkButton::create(_("Abbrechen"), URLHelper::getURL('?cmd=tree'));
        echo "\n" . '</td></tr>';
    }

    //when changing, uploading or show all (for download selector), create a form
    if ((($change) || ($folder_system_data["cmd"]=="all")) && (!$folder_system_data["upload"])) {
        echo "<form method=\"post\" action=\"".URLHelper::getLink('#anker')."\">";
        echo CSRFProtection::tokenTag();
        }

    print "<tr><td class=\"blank\" colspan=\"3\" width=\"100%\">";


    if ($folder_system_data["cmd"]=="all") {
        print "<p class=\"info\">";
        printf (_("Hier sehen Sie alle Dateien, die zu dieser %s eingestellt wurden. Wenn Sie eine neue Datei einstellen m&ouml;chten, w&auml;hlen Sie bitte die Ordneransicht und &ouml;ffnen den Ordner, in den Sie die Datei einstellen wollen."), $SessSemName["art_generic"]);
        print "</p>";
    }

    $lastvisit = object_get_visit($SessSemName[1], "documents");
    $query = "SELECT COUNT(*)
              FROM dokumente
              WHERE seminar_id = ? AND user_id != ?
                AND GREATEST(mkdate, IFNULL(chdate, 0)) > IFNULL(?, UNIX_TIMESTAMP())";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $range_id, $user->id, $lastvisit ?: null
    ));
    $result = $statement->fetchColumn();
    if ($result > 0) {
        print "<p class=\"info\">";
        print _("Es gibt ");
        print "<b>".($result > 1 ? $result : _('eine'))."</b>";
        print _(" neue/ge�nderte Dateie(n). Jetzt ");
        echo LinkButton::create(_("Herunterladen"), URLHelper::getURL("?zipnewest=".$lastvisit));
        print "</p>";
    }

    print "<div id=\"filesystem_area\">";
    //Treeview in Ordnerstruktur
    if ($folder_system_data["cmd"]=="tree") {

        print "<style>
div.droppable {
    border: 1pt solid white;
    margin-top: 0;
    margin-bottom: 0;
}
div.droppable.hover {
    border: 1pt solid red;
    margin-top: 0;
    margin-bottom: 0;
}
</style>";

        print '<table border=0 cellpadding=0 cellspacing=0 width="100%"><tr>';
        print "<td class=\"blank\" valign=\"top\" nowrap width=1px>&nbsp;</td>";
        print "<td>";
        print "<div class=\"\" id=\"folder_subfolders_root\">"; //class = "folder_container" for sorting
        //Seminar...
        //Algemeiner Dateienordner
        $query = "SELECT folder_id FROM folder WHERE range_id = ? ORDER BY name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        while ($general_folder = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($folder_tree->isExecutable($general_folder["folder_id"], $user->id) || $rechte) {
                display_folder($general_folder["folder_id"],
                        $folder_system_data["open"],
                        $change,
                        $folder_system_data["move"],
                        $folder_system_data["upload"],
                        $folder_system_data["refresh"],
                        $folder_system_data["link"],
                        $open_id,
                        NULL,
                        false);
            }
        }
        $statement->closeCursor();

        //Weitere Ordner:
        $statement->execute(array(
            md5($SessSemName[1] . 'top_folder')
        ));
        while ($general_folder = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($folder_tree->isExecutable($general_folder['folder_id'], $user->id) || $rechte) {
                display_folder($general_folder["folder_id"],
                        $folder_system_data["open"],
                        $change,
                        $folder_system_data["move"],
                        $folder_system_data["upload"],
                        $folder_system_data["refresh"],
                        $folder_system_data["link"],
                        $open_id,
                        NULL,
                        false);
            }
        }

        // Themenordner zu Terminen:
        if($SessSemName['class'] == 'sem') {
            $query = "SELECT DISTINCT folder_id
                      FROM themen AS th
                      LEFT JOIN themen_termine AS tt ON (th.issue_id = tt.issue_id) 
                      LEFT JOIN termine AS t ON (t.termin_id = tt.termin_id)
                      INNER JOIN folder ON (th.issue_id = folder.range_id)
                      WHERE th.seminar_id = ?
                      ORDER BY t.date, th.priority";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($range_id));
            $result = $statement->fetchAll();
            foreach ($result as $row) {
                if ($folder_tree->isExecutable($row['folder_id'], $user->id) || $rechte) {
                  display_folder($row['folder_id'],
                      $folder_system_data["open"],
                      $change,
                      $folder_system_data["move"],
                      $folder_system_data["upload"],
                      $folder_system_data["refresh"],
                      $folder_system_data["link"],
                      $open_id,
                      NULL,
                      true);
                }
            }

        //Gruppenordner:
            // Prepare folder statement
            $query = "SELECT folder_id FROM folder WHERE range_id = ?";
            $folder_statement = DBManager::get()->prepare($query);

            $parameters = array($range_id);
            if ($rechte) {
                $query = "SELECT sg.statusgruppe_id
                          FROM statusgruppen AS sg
                          INNER JOIN folder ON sg.statusgruppe_id = folder.range_id
                          WHERE sg.range_id = ?
                          ORDER BY sg.position";
            } else {
                $query = "SELECT sg.statusgruppe_id
                          FROM statusgruppen AS sg
                          INNER JOIN statusgruppe_user AS sgu
                             ON sgu.statusgruppe_id = sg.statusgruppe_id AND sgu.user_id = ?
                          INNER JOIN folder ON sg.statusgruppe_id = folder.range_id
                          WHERE sg.range_id = ?
                          ORDER BY sg.position";
                array_unshift($parameters, $user->id);
            }
            $statement = DBManager::get()->prepare($query);
            $statement->execute($parameters);
            $result2 = $statement->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result2 as $row2) {
                $folder_statement->execute(array($row2['statusgruppe_id']));
                $folders = $folder_statement->fetchAll(PDO::FETCH_ASSOC);
                $folder_statement->closeCursor();

                foreach ($folders as $folder) {
                    if ($folder_tree->isExecutable($folder["folder_id"], $user->id) || $rechte) {
                        display_folder($folder["folder_id"],
                            $folder_system_data["open"],
                            $change,
                            $folder_system_data["move"],
                            $folder_system_data["upload"],
                            $folder_system_data["refresh"],
                            $folder_system_data["link"],
                            $open_id,
                            NULL,
                            false);
                    }
                }
            }
          print "</div>";
          print '</td><td width=1px>&nbsp;</td></tr></table>';
        }
    }   else {
        //Flatview ohne Ordnerstruktur
        if (!$folder_system_data['orderby']) {
            $folder_system_data['orderby'] = "date_rev";
        }

        //Ordnen nach: Typ, Name, Gr��e, Downloads, Autor, Alter
        $query = "SELECT a.*, {$_fullname_sql['full']} AS fullname, username,
                         IF(IFNULL(a.name,'')='', a.filename,a.name) AS t_name
                  FROM dokumente AS a
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE seminar_id = ?";
        if ($folder_system_data['orderby'] == 'type') {
            $query .= " ORDER BY SUBSTRING_INDEX(a.filename, '.', -1) ASC";
        } else if ($folder_system_data['orderby'] == 'type_rev') {
            $query .= " ORDER BY SUBSTRING_INDEX(a.filename, '.', -1) DESC";
        } else if ($folder_system_data['orderby'] == 'filename') {
            $query .= " ORDER BY t_name ASC, a.chdate DESC";
        } else if ($folder_system_data['orderby'] == 'filename_rev') {
            $query .= " ORDER BY t_name DESC, a.chdate ASC";
        } else if ($folder_system_data['orderby'] == 'size') {
            $query .= " ORDER BY a.filesize ASC";
        } else if ($folder_system_data['orderby'] == 'size_rev') {
            $query .= " ORDER BY a.filesize DESC";
        } else if ($folder_system_data['orderby'] == 'downloads') {
            $query .= " ORDER BY a.downloads ASC, t_name DESC, a.chdate ASC";
        } else if ($folder_system_data['orderby'] == 'downloads_rev') {
            $query .= " ORDER BY a.downloads DESC, t_name ASC, a.chdate DESC";
        } else if ($folder_system_data['orderby'] == 'autor') {
            $query .= " ORDER BY ". $_fullname_sql['no_title_rev'] ." ASC";
        } else if ($folder_system_data['orderby'] == 'autor_rev') {
            $query .= " ORDER BY ". $_fullname_sql['no_title_rev'] ." DESC";
        } else if ($folder_system_data['orderby'] == 'date') {
            $query .= " ORDER BY a.chdate ASC";
        } else if ($folder_system_data['orderby'] == 'date_rev') {
            $query .= " ORDER BY a.chdate DESC";
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        $result2 = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($result2)) {

            print '<table border=0 cellpadding=0 cellspacing=0 width="100%">';
            print "<tr><td class=\"blank\"></td><td class=\"blank\"><div align=\"right\">";
            echo LinkButton::create(isset($check_all) ? _("Keine ausw�hlen") :_("Alle ausw�hlen"),
                                    URLHelper::getURL(isset($check_all) ? "" : "?check_all=TRUE"));
            echo Button::create(_("Herunterladen"), "download_selected");
            if ($rechte) {
                echo Button::create(_("L�schen"), "delete_selected");
            }
            echo "</div>" .
                "</td><td class=\"blank\"></td></tr> <tr><td></td><td class=\"blank\">&nbsp;</td><td class=\"blank\"></td></tr>";
            $dreieck_runter = "dreieck_down.png";
            $dreieck_hoch = "dreieck_up.png";
            print "<tr><td></td><td><table border=0 cellpadding=0 cellspacing=0 width=\"100%\">" .
                    "<tr>" .
                    "<td class=\"content_seperator\">&nbsp;&nbsp;&nbsp;";

            print "<a href=\"".URLHelper::getLink((($folder_system_data['orderby'] != "type") ? "?orderby=type" : "?orderby=type_rev"))."\">";
            print "<b>"._("Typ")."</b>".
                ($folder_system_data['orderby'] == "type"
                    ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_hoch\">"
                    : ($folder_system_data['orderby'] == "type_rev" ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_runter\">" : "")).
                "</a>&nbsp;&nbsp; ";


            print "<a href=\"".URLHelper::getLink((($folder_system_data['orderby'] != "filename") ? "?orderby=filename" : "?orderby=filename_rev"))."\">";
            print "<b>"._("Name")."</b>".
                ($folder_system_data['orderby'] == "filename"
                    ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_hoch\">"
                    : ($folder_system_data['orderby'] == "filename_rev" ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_runter\">" : "")).
                "</a>&nbsp;&nbsp; ";

            print "<a href=\"".URLHelper::getLink((($folder_system_data['orderby'] != "size_rev") ? "?orderby=size_rev" : "?orderby=size"))."\">";
            print "<b>"._("Gr��e")."</b>".
                ($folder_system_data['orderby'] == "size"
                    ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_hoch\">"
                    : ($folder_system_data['orderby'] == "size_rev" ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_runter\">" : "")).
                "</a>&nbsp;&nbsp; ";

            print "<a href=\"".URLHelper::getLink((($folder_system_data['orderby'] != "downloads_rev") ? "?orderby=downloads_rev" : "?orderby=downloads"))."\">";
            print "<b>"._("Downloads")."</b>".
                ($folder_system_data['orderby'] == "downloads"
                    ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_hoch\">"
                    : ($folder_system_data['orderby'] == "downloads_rev" ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_runter\">" : "")).
                "</a>&nbsp;&nbsp; ";

            print "</td><td class=\"content_seperator\" align=right>";

            print "<a href=\"".URLHelper::getLink((($folder_system_data['orderby'] != "autor") ? "?orderby=autor" : "?orderby=autor_rev"))."\">";
            print "<b>"._("Autor")."</b>".
                ($folder_system_data['orderby'] == "autor"
                    ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_hoch\">"
                    : ($folder_system_data['orderby'] == "autor_rev" ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_runter\">" : "")).
                "</a>&nbsp;&nbsp; ";

            print "<a href=\"".URLHelper::getLink((($folder_system_data['orderby'] != "date_rev") ? "?orderby=date_rev" : "?orderby=date"))."\">";
            print "<b>"._("Datum")."</b>".
                (($folder_system_data['orderby'] == "date")
                    ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_hoch\">"
                    : (($folder_system_data['orderby'] == "date_rev") ? "<img style=\"vertical-align:middle\" border=0 src=\"".$GLOBALS['ASSETS_URL']."images/$dreieck_runter\">" : "")).
                "</a>&nbsp;&nbsp; ";

            print "</td</tr></table></td><td>";
            print '<tr>';
            print "<td class=\"blank\" valign=\"top\" nowrap width=1px>&nbsp;</td>";
            print "<td id=\"folder_1\">";

            foreach ($result2 as $datei) {
                if ($folder_tree->isDownloadFolder($datei['range_id'], $user->id)) {
                    display_file_line($datei,
                        $range_id,
                        $folder_system_data["open"],
                        $change,
                        $folder_system_data["move"],
                        $folder_system_data["upload"],
                        TRUE,
                        $folder_system_data["refresh"],
                        $folder_system_data["link"],
                        $open_id);
                }
            }
        } else {
            //Infomeldung, wenn keine Dateien existieren:
            $msg = _("Es existieren noch keine Dateien in dieser Veranstaltung.");
            echo MessageBox::info($msg, $rechte ? array(sprintf(_("Klicken Sie auf %sOrdneransicht%s, um welche hochzuladen oder zu verlinken."), "<a href=\"".URLHelper::getLink('?cmd=tree')."\">", "</a>")) : array());
        }
    }

    //und Form wieder schliessen
    if ($change)
        echo "\n</form>";

    $folder_system_data["linkerror"]="";

    if ($folder_system_data["cmd"]=="tree") {
?>
        <br>
        </td>
    </tr>
</table>
<? if ($rechte) : ?>
    <script type="text/javascript">
    //Initialisierung der Ordner und Dateien und verschwinden lassen der gelben Pfeile durch Anfasser:
    STUDIP.Filesystem.unsetarrows();
    STUDIP.Filesystem.setdraggables();
    STUDIP.Filesystem.setdroppables();
    </script>
<? endif; ?>
<?php
    } else if (count($result2)) { //if $all
        if (!$folder_system_data["upload"] && !$folder_system_data["link"]) {
            print "<tr><td class=\"blank\">&nbsp;</td><td>";
            print " <table border=0 cellpadding=0 cellspacing=0 width=\"100%\">";
            print " <tr><td class=\"blank\"></td><td class=\"blank\" style=\"font-size: 4px;\">&nbsp;</td><td class=\"blank\"></td></tr>";
            print " <tr><td class=\"content_seperator\">&nbsp;";
            print " </td><td class=\"content_seperator\" align=right>";
            print " &nbsp;</td></tr></table>";
            print "</td><td class=\"blank\">&nbsp;</td></tr>";
            print "<tr><td class=\"blank\"></td><td class=\"blank\"><div align=\"right\"><br>";
            echo LinkButton::create(isset($check_all) ? _("Keine ausw�hlen") : _("Alle ausw�hlen"),
                                    URLHelper::getURL(isset($check_all) ? "" : "?check_all=TRUE"));
            echo Button::create(_("Herunterladen"), "download_selected");
            if ($rechte) {
                echo Button::create(_("L�schen"), "delete_selected");
            }
            echo "</div></td><td class=\"blank\"></td></tr> <tr><td class=\"blank\"></td>"
                ."<td class=\"blank\">&nbsp;</td><td class=\"blank\"></td></tr>";
        }
    }
    print "</table></form>";

    print "     <br>
        </td>
    </tr>
</table>";

?>
<br>
<div id="fehler_seite"></div>

<?php
include ('lib/include/html_end.inc.php');

page_close();
