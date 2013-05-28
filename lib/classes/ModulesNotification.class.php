<?php
# Lifter002: DONE - no html output in this file
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: DONE - no html output in this file
/**
* ModulesNotification.class.php
*
* check for modules (global and local for institutes and Veranstaltungen), read and write
*
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      core
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// Modules.class.php
// Checks fuer Module (global und lokal fuer Veranstaltungen und Einrichtungen), Schreib-/Lesezugriff
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

require_once 'lib/meine_seminare_func.inc.php';
require_once 'lib/deputies_functions.inc.php';

class ModulesNotification extends Modules {

    var $registered_notification_modules = array();
    var $subject;

    function ModulesNotification () {
        parent::Modules();
        $this->registered_notification_modules['news'] = array(
                'id' => 25, 'const' => '', 'sem' => TRUE, 'inst' => TRUE,
                'mes' => TRUE, 'name' => _("Ank�ndigungen"));
        $this->registered_notification_modules['votes'] = array(
                'id' => 26, 'const' => '', 'sem' => TRUE, 'inst' => FALSE,
                'mes' => TRUE, 'name' => _("Umfragen und Tests"));
        $this->registered_notification_modules['basic_data'] = array(
                'id' => 27, 'const' => '', 'sem' => TRUE, 'inst' => FALSE,
                'mes' => TRUE, 'name' => _("Grunddaten der Veranstaltung"));
        $this->subject = _("Stud.IP Benachrichtigung");
        $extend_modules = array(
                "forum" => array('mes' => TRUE, 'name' =>  _("Forum")),
                "documents" => array('mes' => TRUE, 'name' => _("Dateiordner")),
                "schedule" => array('mes' => TRUE, 'name' => _("Ablaufplan")),
                "participants" => array('mes' => TRUE, 'name' => _("TeilnehmerInnen")),
                "personal" => array('mes' => FALSE, 'name' => _("Personal")),
                "literature" => array('mes' => TRUE, 'name' => _("Literatur")),
                "wiki" => array('mes' => TRUE, 'name' => _("Wiki-Web")),
                "scm" => array('mes' => TRUE, 'name' => _("Freie Informationsseite")),
                "elearning_interface" => array('mes' => TRUE, 'name' => _("Lernmodule")));
        $this->registered_modules = array_merge_recursive($this->registered_modules,
                $extend_modules);
    }

    function getGlobalEnabledNotificationModules ($range) {
        $enabled_modules = array();
        foreach ($this->registered_modules as $name => $data) {
            if ($data[$range] && $data['mes'] && $this->checkGlobal($name)) {
                $enabled_modules[$name] = $data;
            }
        }
        foreach ($this->registered_notification_modules as $name => $data) {
            if ($data[$range]) {
                $enabled_modules[$name] = $data;
            }
        }
        return sizeof($enabled_modules) ? $enabled_modules : FALSE;
    }

    function getAllModules () {
        return $this->registered_modules + $this->registered_notification_modules;
    }

    function getAllNotificationModules () {
        $modules = array();
        foreach ($this->registered_modules as $name => $data) {
            if ($data['mes']) {
                $modules[$name] = $data;
            }
        }
        return $modules + $this->registered_notification_modules;
    }

    function setModuleNotification ($m_array, $range = NULL, $user_id = NULL) {
        if (!is_array($m_array)) {
            return FALSE;
        }
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }
        if (is_null($range)) {
            reset($m_array);
            $range = get_object_type(key($m_array));
        }

        $query = "UPDATE seminar_user SET notification = ? WHERE Seminar_id = ? AND user_id = ?";
        $update_seminar_user = DBManager::get()->prepare($query);

        $query = "UPDATE deputies SET notification = ? WHERE range_id = ? AND user_id = ?";
        $update_deputies = DBManager::get()->prepare($query);

        foreach ($m_array as $range_id => $value) {
            $sum = array_sum($value);
            if ($sum > 0xffffffff) {
                return FALSE;
            }
            if ($range == 'sem') {
                $update_seminar_user->execute(array($sum, $range_id, $user_id));
                if (get_config('DEPUTIES_ENABLE') && !$update_seminar_user->rowCount()) {
                    $update_deputies->execute(array($sum, $range_id, $user_id));
                }
            } else {
                return FALSE;
            //  $this->db->query("UPDATE user_inst SET mod_message = $sum
            //          WHERE Institut_id = '$range_id' AND user_id = '$user_id'");
            }
        }
        return TRUE;
    }

    function getModuleNotification ($range = 'sem', $user_id = NULL) {
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }
        if ($range != 'sem') {
            return false;
        }

        $settings = array();

        $query = "SELECT Seminar_id, notification FROM seminar_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['Seminar_id']] = $row['notification'];
        }

        if (get_config('DEPUTIES_ENABLE')) {
            $query = "SELECT d.range_id, d.notification "
                   . "FROM deputies d "
                   . "JOIN seminare s ON (d.range_id=s.Seminar_id) "
                   . "WHERE d.user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['range_id']] = $row['notification'];
            }
        }
        return $settings;
    }

    // only range = 'sem' is implemented
    function getAllNotifications ($user_id = NULL) {

        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }

        $my_sem = array();
        $query = "SELECT s.Seminar_id, s.Name, s.chdate, s.start_time, s.modules, s.status as sem_status, su.status,s.admission_prelim, su.notification, IFNULL(visitdate, 0) AS visitdate "
               . "FROM seminar_user su "
               . "LEFT JOIN seminare s USING (Seminar_id) "
               . "LEFT JOIN object_user_visits ouv ON (ouv.object_id = su.Seminar_id AND ouv.user_id = :user_id AND ouv.type = 'sem') "
               . "WHERE su.user_id = :user_id AND su.status != 'user' AND su.notification <> 0";
        if (get_config('DEPUTIES_ENABLE')) {
            $query .= " UNION SELECT s.Seminar_id, CONCAT(s.Name, ' [Vertretung]') as Name, s.chdate, s.start_time, s.modules, s.status as sem_status, 'dozent' as status, s.admission_prelim, d.notification, IFNULL(visitdate, 0) AS visitdate "
               . "FROM deputies d "
               . "LEFT JOIN seminare s ON (d.range_id = s.Seminar_id) "
               . "LEFT JOIN object_user_visits ouv ON (ouv.object_id = d.range_id AND ouv.user_id = :user_id AND ouv.type = 'sem') "
               . "WHERE d.user_id = :user_id AND d.notification <> 0";
        }
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $seminar_id = $row['Seminar_id'];
            $modules = $this->getLocalModules($seminar_id, 'sem', $row['modules'], $row['sem_status']);
            $modulesInt = array_sum($modules); //korrigiert wg. SemClass::isSlotMandatory() Kram
            $my_sem[$seminar_id] = array(
                    'name'       => $row['Name'],
                    'chdate'     => $row['chdate'],
                    'start_time' => $row['start_time'],
                    'modules'    => $modules,
                    'modulesInt' => $modulesInt,
                    'visitdate'  => $row['visitdate'],
                    'obj_type'   => 'sem',
                    'notification'=> $row['notification'],
                    'sem_status' => $row['sem_status'],
                    'status' => $row['status'],
                    'prelim'     => $row['admission_prelim'],
                    );
            unset( $seminar_id );
            unset( $modules );
            unset( $modulesInt );
        }
        $m_enabled_modules = $this->getGlobalEnabledNotificationModules('sem');
        $m_extended = 0;
        foreach ($this->registered_notification_modules as $m_data) {
            $m_extended += pow(2, $m_data['id']);
        }

        get_my_obj_values($my_sem, $user_id);

        $news = array();
        foreach ($my_sem as $seminar_id => $s_data) {
            $m_notification = ($s_data['modulesInt'] + $m_extended)
                    & $s_data['notification'];
            $n_data = array();
            foreach ($m_enabled_modules as $m_name => $m_data) {
                if ($this->isBit($m_notification, $m_data['id'])) {
                    $data = $this->getModuleText($m_name, $seminar_id, $s_data, 'sem');
                    if ($data) {
                        $n_data[] = $data;
                    }
                }
            }
            if (count($n_data)) {
                $news[$s_data['name']] = $n_data;
            }
        }
        if (count($news)) {
            $template = $GLOBALS['template_factory']->open('mail/notification_html');
            $template->set_attribute('lang', getUserLanguagePath($user_id));
            $template->set_attribute('news', $news);

            $template_text = $GLOBALS['template_factory']->open('mail/notification_text');
            $template_text->set_attribute('news', $news);
            return array('text' => $template_text->render(), 'html' => $template->render());;
        } else {
            return FALSE;
        }
    }

    // only range = 'sem' is implemented
    function getModuleText ($m_name, $range_id, $r_data, $range) {
        global $SEM_CLASS, $SEM_TYPE;
        $text = '';
        $sem_class = $SEM_CLASS[$SEM_TYPE[$r_data['sem_status']]["class"]];
        $slot_mapper = array(
                'files' => "documents",
                'elearning' => "elearning_interface"
            );
        if ($sem_class) {
            $slot = isset($slot_mapper[$m_name]) ? $slot_mapper[$m_name] : $m_name;
            $module = $sem_class->getModule($slot);
            if (is_a($module, "StandardPlugin")) {
                $base_url = UrlHelper::setBaseURL();
                $nav = $module->getIconNavigation($range_id, $r_data['visitdate'], $GLOBALS['user']->id);
                UrlHelper::setBaseURl($base_url);
                if (isset($nav) && $nav->isVisible(true)) {
                    if ($nav->getBadgeNumber()) {
                        $url = 'seminar_main.php?again=yes&auswahl=' . $range_id . '&redirect_to=' . strtr($nav->getURL(), '?', '&');
                        $image = $nav->getImage();
                        $icon = $image['src'];
                        $tab = $module->getTabNavigation();
                        if (isset($tab) && $tab->isVisible()) {
                            $text = $tab->getTitle();
                        } else {
                            $text = $this->registered_modules[$m_name]['name'];
                        }
                        if ($nav->getBadgeNumber() == 1) {
                            $text .= ' - ' . _("Ein neuer Beitrag:");
                        } else {
                            $text .= ' - ' . sprintf(_("%s neue Beitr�ge:"), $nav->getBadgeNumber());
                        }
                        return compact('text', 'url', 'icon', 'range_id');
                    } else {
                        return null;
                    }
                }
            }
        }
        switch ($m_name) {
            case 'participants' :
                if (in_array($r_data['status'], words('dozent tutor'))) {
                    if ($r_data['new_accepted_participants'] > 1) {
                        $text = sprintf(_("%s neue vorl�ufige TeilnehmerInnen, "), $r_data['newparticipants']);
                    } else if ($r_data['new_accepted_participants'] > 0) {
                        $text = _("1 neuer vorl�ufiger TeilnehmerIn, ");
                    }
                    if ($r_data['newparticipants'] > 1) {
                        $text = sprintf(_("%s neue TeilnehmerInnen:"), $r_data['newparticipants']);
                    } else if ($r_data['newparticipants'] > 0) {
                        $text = _("1 neuer TeilnehmerIn:");
                    }
                    if ($sem_class['studygroup_mode']) {
                        $redirect = '&redirect_to=dispatch.php/course/studygroup/members/';
                    } else {
                        $redirect = '&redirect_to=dispatch.php/course/member/index';
                    }
                    $icon = "icons/16/blue/persons.png";
                }
                break;
            case 'documents' :
                if ($r_data['neuedokumente'] > 1) {
                    $text = sprintf(_("%s neue Dokumente hochgeladen:"), $r_data['neuedokumente']);
                } else if ($r_data['neuedokumente'] > 0) {
                    $text = _("1 neues Dokument hochgeladen:");
                }
                $redirect = '&redirect_to=folder.php&cmd=all';
                $icon = "icons/16/blue/files.png";
                break;
            case 'schedule' :
                if ($r_data['neuetermine'] > 1) {
                    $text = sprintf(_("%s neue Termine angelegt:"), $r_data['neuetermine']);
                } else if ($r_data['neuetermine'] > 0) {
                    $text = _("1 neuer Termin angelegt:");
                }
                $redirect = '&redirect_to=dates.php#a';
                $icon = "icons/16/blue/date.png";
                break;
            case 'literature' :
                if ($r_data['neuelitlist'] > 1) {
                    $text = sprintf(_("%s neue Literaturlisten angelegt"), $r_data['neuelitlist']);
                } else if ($r_data['neuelitlist'] > 0) {
                    $text = _("1 neue Literaturliste angelegt");
                }
                $redirect = '&redirect_to=literatur.php';
                $icon = "icons/16/blue/literature.png";
                break;
            case 'elearning_interface' :
                if (get_config('ELEARNING_INTERFACE_ENABLE')) {
                    if ($r_data['neuecontentmodule'] > 1) {
                        $text = sprintf(_("%s neue Content-Module angelegt"), $r_data['neuecontentmodule']);
                    } else if ($r_data['neuecontentmodule'] > 0) {
                        $text = _("1 neues Content-Modul angelegt");
                    }
                    $redirect = "&redirect_to=elearning_interface.php&seminar_id=$range_id&view=show";
                    $icon = "icons/16/blue/learnmodule.png";
                }
                break;
            case 'wiki' :
                if ($r_data['neuewikiseiten'] > 1) {
                    $text = sprintf(_("%s Wikiseiten wurden angelegt oder bearbeitet:"), $r_data['neuewikiseiten']);
                } else if ($r_data['neuewikiseiten'] > 0) {
                    $text = _("1 Wikiseite wurde angelegt oder bearbeitet:");
                }
                $redirect = '&redirect_to=wiki.php&view=listnew';
                $icon = "icons/16/blue/wiki.png";
                break;
            case 'scm' :
                if ($r_data['neuscmcontent']) {
                    $text = sprintf(_("Die Seite \"%s\" wurde neu angelegt oder bearbeitet:"), $r_data['scmtabname']);
                }
                $redirect = '&redirect_to=dispatch.php/course/scm';
                $icon = "icons/16/blue/infopage.png";
                break;
            case 'votes' :
                if (get_config('VOTE_ENABLE')) {
                    if ($r_data['neuevotes'] > 1) {
                        $text = sprintf(_("%s neue Umfragen oder Evaluationen wurden angelegt:"), $r_data['neuevotes']);
                    } else if ($r_data['neuevotes'] > 0) {
                        $text = _("1 neue Umfrage oder Evaluation wurde angelegt:");
                    }
                }
                $redirect = '#votes';
                $icon = "icons/16/blue/vote.png";
                break;
            case 'news' :
                if ($r_data['neuenews'] > 1) {
                    $text = sprintf(_("%s neue Ank�ndigungen wurden angelegt:"), $r_data['neuenews']);
                } else if ($r_data['neuenews']) {
                    $text = _("Eine neue Ank�ndigung wurde angelegt:");
                }
                $redirect = '';
                $icon = "icons/16/blue/news.png";
                break;
            case 'basic_data' :
                if ($r_data['chdate'] > $r_data['visitdate']) {
                    $text = _("Die Grunddaten wurden ge�ndert:");
                }
                $redirect = '&redirect_to=details.php';
                $icon = "icons/16/blue/home.png";
                break;
            default :
                $redirect = '';
        }
        if ($range == 'sem' && $text != '') {
            $url = 'seminar_main.php?again=yes&auswahl='.$range_id.$redirect;
            return compact('text', 'url', 'icon', 'range_id');
        }
        return $text;
    }

    function generateModulesArrayFromModulesInteger( $bitmaskint ){
        $array = array();
        $bitmask = str_split( strrev( decbin( $bitmaskint ) ) );
        foreach( $this->registered_modules as $name => $module ){
            $array[ $name ] = ( $bitmask[ $module[ "id" ] ] == "1" );
        }
        return $array;
    }

}
?>
