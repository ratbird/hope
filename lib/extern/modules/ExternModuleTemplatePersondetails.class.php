<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter010: TODO
/**
* ExternModuleTemplatePersondetails.class.php
*
*
*
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternModuleTemplatePersondetails
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternModuleTemplatePersondetails.class.php
//
// Copyright (C) 2007 Peter Thienel <pthienel@web.de>,
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


require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/ExternModule.class.php');
require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/views/extern_html_templates.inc.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/Avatar.class.php');
require_once('lib/visual.inc.php');
require_once('lib/user_visible.inc.php');
require_once('lib/statusgruppe.inc.php');
require_once('lib/dates.inc.php');
require_once('lib/classes/SemesterData.class.php');
require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/extern_functions.inc.php');
if ($GLOBALS["CALENDAR_ENABLE"]) {
    require_once($GLOBALS["RELATIVE_PATH_CALENDAR"]
            . "/lib/DbCalendarEventList.class.php");
}
global $_fullname_sql;


class ExternModuleTemplatePersondetails extends ExternModule {

    public $markers = array();
    private $user_id;
    private $user_perm;
    private $visibilities;

    /**
    *
    */
    public function __construct ($range_id, $module_name, $config_id = NULL, $set_config = NULL, $global_id = NULL) {
        $this->data_fields = array();
        if (get_config('CALENDAR_ENABLE')) {
            $this->registered_elements = array(
                'PersondetailsLectures' => 'PersondetailsLecturesTemplate',
                'LinkInternLecturedetails' => 'LinkInternTemplate',
                'LitList',
                'TemplateMain' => 'TemplateGeneric',
                'TemplateLectures' => 'TemplateGeneric',
                'TemplateNews' => 'TemplateGeneric',
                'TemplateAppointments' => 'TemplateGeneric',
                'TemplateLitList' => 'TemplateGeneric',
                'TemplateOwnCategories' => 'TemplateGeneric'
            );
        } else {
            $this->registered_elements = array(
                'PersondetailsLectures' => 'PersondetailsLecturesTemplate',
                'LinkInternLecturedetails' => 'LinkInternTemplate',
                'LitList',
                'TemplateMain' => 'TemplateGeneric',
                'TemplateLectures' => 'TemplateGeneric',
                'TemplateNews' => 'TemplateGeneric',
                'TemplateLitList' => 'TemplateGeneric',
                'TemplateOwnCategories' => 'TemplateGeneric'
            );
        }
        if (in_array(get_object_type($range_id), array('fak', 'global'))) {
            array_unshift($this->registered_elements, 'SelectInstitutes');
        }
        $this->field_names = array();
        $this->args = array('username', 'seminar_id');

        parent::ExternModule($range_id, $module_name, $config_id, $set_config, $global_id);

    }

    public function setup () {

        // setup module properties
        $this->elements['LinkInternLecturedetails']->real_name = _("Link zum Modul Veranstaltungsdetails");
        $this->elements['LinkInternLecturedetails']->link_module_type = array(4, 13);
        $this->elements['PersondetailsLectures']->real_name = _("Einstellungen für Lehrveranstaltungen");
        $this->elements['LitList']->real_name = _("Einstellungen für Literaturlisten");
        $this->elements['TemplateMain']->real_name = _("Haupttemplate");
        $this->elements['TemplateLectures']->real_name = _("Template für Lehrveranstaltungen");
        $this->elements['TemplateNews']->real_name = _("Template für News");
        if (get_config('CALENDAR_ENABLE')) {
            $this->elements['TemplateAppointments']->real_name = _("Template für Termine");
        }
        $this->elements['TemplateLitList']->real_name = _("Template für Literaturlisten");
        $this->elements['TemplateOwnCategories']->real_name = _("Template für eigene Kategorien");
        if (in_array(get_object_type($this->config->range_id), array('fak', 'global'))) {
            $this->elements['SelectInstitutes']->real_name = _("Einschränkung auf Institute/Einrichtungen");
        }
    }

    public function toStringEdit ($open_elements = '', $post_vars = '',
            $faulty_values = '', $anker = '') {

        $this->updateGenericDatafields('TemplateMain', 'user');
        $this->elements['TemplateMain']->markers = $this->getMarkerDescription('TemplateMain');
        $this->elements['TemplateLectures']->markers = $this->getMarkerDescription('TemplateLectures');
        $this->elements['TemplateLitList']->markers = $this->getMarkerDescription('TemplateLitList');
        if (get_config('CALENDAR_ENABLE')) {
            $this->elements['TemplateAppointments']->markers = $this->getMarkerDescription('TemplateAppointments');
        }
        $this->elements['TemplateNews']->markers = $this->getMarkerDescription('TemplateNews');
        $this->elements['TemplateOwnCategories']->markers = $this->getMarkerDescription('TemplateOwnCategories');

        return parent::toStringEdit($open_elements, $post_vars, $faulty_values, $anker);

    }

    public function getMarkerDescription ($element_name) {
        $markers['TemplateMain'][] = array('__GLOBAL__', _("Globale Variablen (gültig im gesamten Template)."));
        $markers['TemplateMain'][] = array('###STUDIP-EDIT-HREF###', '');

        $markers['TemplateMain'][] = array('<!-- BEGIN PERSONDETAILS -->', '');
        $markers['TemplateMain'][] = array('###FULLNAME###', '');
        $markers['TemplateMain'][] = array('###LASTNAME###', '');
        $markers['TemplateMain'][] = array('###FIRSTNAME###', '');
        $markers['TemplateMain'][] = array('###TITLEFRONT###', '');
        $markers['TemplateMain'][] = array('###TITLEREAR###', '');
        $markers['TemplateMain'][] = array('###USERNAME###', '');
        $markers['TemplateMain'][] = array('###STATUSGROUPS###', _("Kommaseparierte Liste mit Statusgruppen"));
        $markers['TemplateMain'][] = array('###IMAGE-HREF###', '');
        $markers['TemplateMain'][] = array('###INST-NAME###', '');
        $markers['TemplateMain'][] = array('###INST-HREF###', '');
        $markers['TemplateMain'][] = array('###STREET###', '');
        $markers['TemplateMain'][] = array('###ZIPCODE###', '');
        $markers['TemplateMain'][] = array('###EMAIL###', '');
        $markers['TemplateMain'][] = array('###EMAIL-LOCAL###', _("Der local-part der E-Mail-Adresse (vor dem @-Zeichen)"));
        $markers['TemplateMain'][] = array('###EMAIL-DOMAIN###', _("Der domain-part der E-Mail-Adresse (nach dem @-Zeichen)"));
        $markers['TemplateMain'][] = array('###ROOM###', '');
        $markers['TemplateMain'][] = array('###PHONE###', '');
        $markers['TemplateMain'][] = array('###FAX###', '');
        $markers['TemplateMain'][] = array('###HOMEPAGE-HREF###', '');
        $markers['TemplateMain'][] = array('###OFFICE-HOURS###', '');
        $markers['TemplateMain'][] = array('###RESEARCH-INTERESTS###', '');
        $markers['TemplateMain'][] = array('###CV###', _("Lebenslauf"));
        $markers['TemplateMain'][] = array('###PUBLICATIONS###', '');
        $markers['TemplateMain'][] = array('###OFFICE-HOURS###', '');
        $this->insertDatafieldMarkers('user', $markers, 'TemplateMain');
        $this->insertPluginMarkers('HomepagePlugin', $markers, 'TemplateMain');
        $markers['TemplateMain'][] = array('###LECTURES###', _("Inhalt aus dem Template für Veranstaltungen"));
        $markers['TemplateMain'][] = array('###NEWS###', _("Inhalt aus dem Template für News"));
        $markers['TemplateMain'][] = array('###LITERATURE###', _("Inhalt aus dem Template für Literaturlisten"));
        $markers['TemplateMain'][] = array('###APPOINTMENTS###', _("Inhalt aus dem Template für Termine"));
        $markers['TemplateMain'][] = array('###OWNCATEGORIES###', _("Inhalt aus dem Template für eigene Kategorien"));
        $markers['TemplateMain'][] = array('<!-- END PERSONDETAILS -->', '');

        $markers['TemplateLectures'][] = array('<!-- BEGIN LECTURES -->', '');
        $markers['TemplateLectures'][] = array('<!-- BEGIN SEMESTER -->', '');
        $markers['TemplateLectures'][] = array('###NAME###', '');
        $markers['TemplateLectures'][] = array('<!-- BEGIN LECTURE -->', '');
        $markers['TemplateLectures'][] = array('###TITLE###', '');
        $markers['TemplateLectures'][] = array('###SUBTITLE###', '');
        $markers['TemplateLectures'][] = array('###NUMBER###', _("Die Veranstaltungsnummer"));
        $markers['TemplateLectures'][] = array('###LECTUREDETAILS-HREF###', '');
        $markers['TemplateLectures'][] = array('<!-- END LECTURE -->', '');
        $markers['TemplateLectures'][] = array('<!-- END SEMESTER -->', '');
        $markers['TemplateLectures'][] = array('<!-- END LECTURES -->', '');

        $markers['TemplateNews'][] = array('<!-- BEGIN NEWS -->', '');
        $markers['TemplateNews'][] = array('<!-- BEGIN NO-NEWS -->', '');
        $markers['TemplateNews'][] = array('###NEWS_NO-NEWS-TEXT###', '');
        $markers['TemplateNews'][] = array('<!-- END NO-NEWS -->', '');
        $markers['TemplateNews'][] = array('<!-- BEGIN ALL-NEWS -->', '');
        $markers['TemplateNews'][] = array('<!-- BEGIN SINGLE-NEWS -->', '');
        $markers['TemplateNews'][] = array('###NEWS_TOPIC###', '');
        $markers['TemplateNews'][] = array('###NEWS_BODY###', '');
        $markers['TemplateNews'][] = array('###NEWS_DATE###', '');
        $markers['TemplateNews'][] = array('###NEWS_ADMIN-MESSAGE###', '');
        $markers['TemplateNews'][] = array('###NEWS_NO###', '');
        $markers['TemplateNews'][] = array('<!-- END SINGLE-NEWS -->', '');
        $markers['TemplateNews'][] = array('<!-- END ALL-NEWS -->', '');
        $markers['TemplateNews'][] = array('<!-- END NEWS -->', '');

        if (get_config('CALENDAR_ENABLE')) {
            $markers['TemplateAppointments'][] = array('<!-- BEGIN APPOINTMENTS -->', '');
            $markers['TemplateAppointments'][] = array('###LIST-START###', _("Startdatum der Terminliste"));
            $markers['TemplateAppointments'][] = array('###LIST-END###', _("Enddatum der Terminliste"));
            $markers['TemplateAppointments'][] = array('<!-- BEGIN NO-APPOINTMENTS -->', '');
            $markers['TemplateAppointments'][] = array('###NO-APPOINTMENTS-TEXT###', '');
            $markers['TemplateAppointments'][] = array('<!-- END NO-APPOINTMENTS -->', '');
            $markers['TemplateAppointments'][] = array('<!-- BEGIN ALL-APPOINTMENTS -->', '');
            $markers['TemplateAppointments'][] = array('<!-- BEGIN SINGLE-APPOINTMENT -->', '');
            $markers['TemplateAppointments'][] = array('###DATE###', _("Start und Endzeit oder ganztügig"));
            $markers['TemplateAppointments'][] = array('###BEGIN###', '');
            $markers['TemplateAppointments'][] = array('###END###', '');
            $markers['TemplateAppointments'][] = array('###TITLE###', '');
            $markers['TemplateAppointments'][] = array('###DESCRIPTION###', '');
            $markers['TemplateAppointments'][] = array('###LOCATION###', '');
            $markers['TemplateAppointments'][] = array('###REPETITION###', '');
            $markers['TemplateAppointments'][] = array('###CATEGORY###', '');
            $markers['TemplateAppointments'][] = array('###PRIORITY###', '');
            $markers['TemplateAppointments'][] = array('<!-- END SINGLE-APPOINTMENT -->', '');
            $markers['TemplateAppointments'][] = array('<!-- END ALL-APPOINTMENTS -->', '');
            $markers['TemplateAppointments'][] = array('<!-- END APPOINTMENTS -->', '');
        }

        $markers['TemplateLitList'] = $this->elements['LitList']->getMarkerDescription('LitList');

        $markers['TemplateOwnCategories'][] = array('<!-- BEGIN OWNCATEGORIES -->', '');
        $markers['TemplateOwnCategories'][] = array('<!-- BEGIN OWNCATEGORY -->', '');
        $markers['TemplateOwnCategories'][] = array('###OWNCATEGORY_TITLE###', '');
        $markers['TemplateOwnCategories'][] = array('###OWNCATEGORY_CONTENT###', '');
        $markers['TemplateOwnCategories'][] = array('###OWNCATEGORY_NO###', _("Laufende Nummer"));
        $markers['TemplateOwnCategories'][] = array('<!-- END OWNCATEGORY -->', '');
        $markers['TemplateOwnCategories'][] = array('<!-- END OWNCATEGORIES -->', '');

        return $markers[$element_name];
    }

    private function getContent ($args = NULL, $raw = FALSE) {
        $instituts_id = $this->config->range_id;
        $username = $args['username'];
        $sem_id = $args['seminar_id'];

        if (!$nameformat = $this->config->getValue('Main', 'nameformat')) {
            $nameformat = 'full';
        }

        $row = false;
        $global_view = false;
        $dbv = new DbView();
        if (in_array(get_object_type($this->config->range_id), array('fak', 'global'))) {
            $global_view = true;
            $selected_item_ids = $this->config->getValue('SelectInstitutes', 'institutesselected');
            // at least one institute has to be selected in the configuration
            if (!is_array($selected_item_ids)) {
                return array();
            }
            // is user lecturer ?
            if ($this->config->getValue('Main', 'onlylecturers')) {
                $current_semester = get_sem_num(time());
                $stm = DBManager::get()->prepare(sprintf(
                    "SELECT aum.user_id "
                    . "FROM auth_user_md5 aum "
                    . "LEFT JOIN seminar_user su USING(user_id) "
                    . "LEFT JOIN seminare s USING (seminar_id) "
                    . "LEFT JOIN user_inst ui ON aum.user_id = ui.user_id "
                    . "WHERE aum.username = ? "
                    . "AND su.status = 'dozent' "
                    . "AND s.visible = 1 "
                    . "AND ((%s) = %s OR ((%s) <= %s  AND ((%s) >= %s OR (%s) = -1))) "
                    . "AND ui.Institut_id IN ('%s') "
                    . "AND ui.inst_perms = 'dozent' "
                    . "AND ui.externdefault = 1 "
                    . "AND %s",
                    $dbv->sem_number_sql,
                    $current_semester,
                    $dbv->sem_number_sql,
                    $current_semester,
                    $dbv->sem_number_end_sql,
                    $current_semester,
                    $dbv->sem_number_end_sql,
                    implode("','", $selected_item_ids),
                    get_ext_vis_query()));
                $stm->execute(array($username));
                // user is not a lecturer
                if (!$row = $stm->fetch()) {
                    return array();
                }
            } else {
                // have user the status dozent at an institute in the list of accepted institutes
                $stm = DBManager::get()->prepare(sprintf(
                    "SELECT aum.user_id "
                    . "FROM auth_user_md5 aum "
                    . "LEFT JOIN user_inst ui USING(user_id) "
                    . "WHERE aum.username = ? "
                    . "AND ui.Institut_id IN ('%s') "
                    . "AND ui.externdefault = 1 "
                    . "AND %s",
                    implode("','", $selected_item_ids), get_ext_vis_query()));
                $stm->execute(array($username));
                // user is not dozent at an institute that is in the list of accepted institutes
                if (!$row = $stm->fetch()) {
                    return array();
                }
            }
        }

        $row = false;

        // Mitarbeiter/in am Institut
        $stm_inst = DBManager::get()->prepare(
            "SELECT i.Institut_id "
            . "FROM Institute i "
            . "LEFT JOIN user_inst ui USING(Institut_id) "
            . "LEFT JOIN auth_user_md5 aum USING(user_id) "
            . "WHERE i.Institut_id = ? "
            . "AND aum.username = ? AND ui.inst_perms IN ('autor','tutor','dozent') AND " . get_ext_vis_query());
        $stm_inst->execute(array($instituts_id, $username));

        // Mitarbeiter/in am Heimatinstitut des Seminars
        if (!$row = $stm_inst->fetch(PDO::FETCH_ASSOC) && $sem_id) {
            $stm_inst = DBManager::get()->prepare(
                "SELECT s.Institut_id "
                . "FROM seminare s "
                . "LEFT JOIN user_inst ui USING(Institut_id) "
                . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                . "WHERE s.Seminar_id = ? "
                . "AND aum.username = ? AND ui.inst_perms = 'dozent' AND " . get_ext_vis_query());
            $stm_inst->execute(array($sem_id, $username));
            if ($row = $stm_inst->fetch(PDO::FETCH_ASSOC)) {
                $instituts_id = $row['Institut_id'];
            }
        }

        // an beteiligtem Institut Dozent(in)
        if (!$row && $sem_id) {
            $stm_inst = DBManager::get()->prepare(
                "SELECT si.institut_id "
                . "FROM seminare s "
                . "LEFT JOIN seminar_inst si ON(s.Seminar_id = si.seminar_id) "
                . "LEFT JOIN user_inst ui ON(si.institut_id = ui.Institut_id) "
                . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                . "WHERE s.Seminar_id = ? "
                . "AND si.institut_id != ? AND ui.inst_perms = 'dozent' AND aum.username = ? AND " . get_ext_vis_query());
            $stm_inst->execute(array($sem_id, $intituts_id, $username));
            if ($row = $stm_inst->fetch(PDO::FETCH_ASSOC)) {
                $instituts_id = $row['institut_id'];
            }
        }

        // ist zwar global Dozent, aber an keinem Institut eingetragen
        if (!$row && $sem_id) {
            $stm = DBManager::get()->prepare(sprintf(
                "SELECT aum.*, %s AS fullname "
                . "FROM auth_user_md5 aum "
                . "LEFT JOIN user_info USING(user_id) "
                . "LEFT JOIN seminar_user su "
                . "WHERE username = ? "
                . "AND perms = 'dozent' AND su.seminar_id = ? AND su.status = 'dozent' AND %s"
                , $GLOBALS['_fullname_sql'][$nameformat], get_ext_vis_query()));
            $stm->execute(array($username, $sem_id));
            $row = $stm->fetch(PDO::FETCH_ASSOC);
        } elseif ($global_view || $this->config->getValue('Main', 'defaultaddr')) {
            $stm = DBManager::get()->prepare(sprintf(
                "SELECT i.Institut_id, i.Name, i.Strasse, i.Plz, i.url, ui.*, aum.*, "
                . "%s AS fullname, uin.user_id, uin.lebenslauf, uin.publi, uin.schwerp, "
                . "uin.Home, uin.title_front, uin.title_rear "
                . "FROM Institute i "
                . "LEFT JOIN user_inst ui USING(Institut_id) "
                . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                . "LEFT JOIN user_info uin USING (user_id) "
                . "WHERE ui.inst_perms IN ('autor','tutor','dozent') "
                . "AND aum.username = ? AND ui.externdefault = 1 AND %s"
                , $GLOBALS['_fullname_sql'][$nameformat], get_ext_vis_query()));
            $stm->execute(array($username));
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $stm = DBManager::get()->prepare(sprintf(
                    "SELECT i.Institut_id, i.Name, i.Strasse, i.Plz, i.url, ui.*, aum.*, "
                    . "%s AS fullname, uin.user_id, uin.lebenslauf, uin.publi, uin.schwerp, "
                    . "uin.Home, uin.title_front, uin.title_rear "
                    . "FROM Institute i "
                    . "LEFT JOIN user_inst ui USING(Institut_id) "
                    . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                    . "LEFT JOIN user_info uin USING (user_id) "
                    . "WHERE ui.inst_perms IN ('autor','tutor','dozent') "
                    . "AND aum.username = ? AND i.Institut_id = ? AND %s"
                    , $GLOBALS['_fullname_sql'][$nameformat], get_ext_vis_query()));
                $stm->execute(array($username, $instituts_id));
                $row = $stm->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $stm = DBManager::get()->prepare(sprintf(
                "SELECT i.Institut_id, i.Name, i.Strasse, i.Plz, i.url, ui.*, aum.*, "
                . "%s AS fullname, uin.user_id, uin.lebenslauf, uin.publi, uin.schwerp, "
                . "uin.Home, uin.title_front, uin.title_rear "
                . "FROM Institute i "
                . "LEFT JOIN user_inst ui USING(Institut_id) "
                . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                . "LEFT JOIN user_info uin USING (user_id) "
                . "WHERE ui.inst_perms IN ('autor','tutor','dozent') "
                . "AND aum.username = ? AND i.Institut_id = ? AND %s"
                , $GLOBALS['_fullname_sql'][$nameformat], get_ext_vis_query()));
            $stm->execute(array($username, $instituts_id));
            $row = $stm->fetch(PDO::FETCH_ASSOC);
        }

        // the user with the given username does not fulfill the conditions above
        if (!$row) {
            return array();
        }

        $this->user_id = $row['user_id'];

        $visibilities = get_local_visibility_by_id($this->user_id, 'homepage', true);
        $this->user_perm = $visibilities['perms'];
        $this->visibilities = json_decode($visibilities['homepage'], true);

        $content['__GLOBAL__']['STUDIP-EDIT-HREF'] = "{$GLOBALS['ABSOLUTE_URI_STUDIP']}edit_about.php?login=yes&view=Daten&username=$username";

        $content['PERSONDETAILS']['FULLNAME'] = ExternModule::ExtHtmlReady($row['fullname']);
        $content['PERSONDETAILS']['LASTNAME'] = ExternModule::ExtHtmlReady($row['Nachname']);
        $content['PERSONDETAILS']['FIRSTNAME'] = ExternModule::ExtHtmlReady($row['Vorname']);
        $content['PERSONDETAILS']['TITLEFRONT'] = ExternModule::ExtHtmlReady($row['title_front']);
        $content['PERSONDETAILS']['TITLEREAR'] = ExternModule::ExtHtmlReady($row['title_rear']);
        if ($statusgroups = GetRoleNames(GetAllStatusgruppen($instituts_id, $this->user_id))) {
            $content['PERSONDETAILS']['STATUSGROUPS'] = ExternModule::ExtHtmlReady(join(', ', array_values($statusgroups)));
        }
        $content['PERSONDETAILS']['USERNAME'] = $row['username'];
        if (is_element_visible_externally($this->user_id, $this->user_perm, 'picture', $this->visibilities['picture'])) {
            $avatar = Avatar::getAvatar($this->user_id);
        } else {
            $avatar = Avatar::getNobody();
        }
        if ($avatar->is_customized()) {
            $content['PERSONDETAILS']['IMAGE-HREF'] = $avatar->getURL(Avatar::NORMAL);
        }

        $gruppen = GetRoleNames(GetAllStatusgruppen($this->config->range_id, $row['user_id']));
        for ($i = 0; $i < sizeof($gruppen); $i++) {
            $content['PERSONDETAILS']['GROUPS'][$i]['GROUP'] = ExternModule::ExtHtmlReady($gruppen[$i]);
        }

        $content['PERSONDETAILS']['INST-NAME'] = ExternModule::ExtHtmlReady($row['Name']);
        $content['PERSONDETAILS']['INST-HREF'] = ExternModule::ExtHtmlReady(trim($row['url']));
        $content['PERSONDETAILS']['STREET'] = ExternModule::ExtHtmlReady($row['Strasse']);
        $content['PERSONDETAILS']['ZIPCODE'] = ExternModule::ExtHtmlReady($row['Plz']);
        $email = get_visible_email($this->user_id);
        $content['PERSONDETAILS']['EMAIL'] = ExternModule::ExtHtmlReady($email);
        $content['PERSONDETAILS']['EMAIL-LOCAL'] = array_shift(explode('@', $content['PERSONDETAILS']['EMAIL']));
        $content['PERSONDETAILS']['EMAIL-DOMAIN'] = array_pop(explode('@', $content['PERSONDETAILS']['EMAIL']));
        $content['PERSONDETAILS']['ROOM'] = ExternModule::ExtHtmlReady($row['raum']);
        $content['PERSONDETAILS']['PHONE'] = ExternModule::ExtHtmlReady($row['Telefon']);
        $content['PERSONDETAILS']['FAX'] = ExternModule::ExtHtmlReady($row['Fax']);
        if (is_element_visible_externally($this->user_id, $this->user_perm, 'Home', $this->visibilities['Home'])) {
            $content['PERSONDETAILS']['HOMEPAGE-HREF'] = ExternModule::ExtHtmlReady(trim($row['Home']));
        }
        $content['PERSONDETAILS']['OFFICE-HOURS'] = ExternModule::ExtHtmlReady($row['sprechzeiten']);

        // generic data fields
        if ($generic_datafields = $this->config->getValue('Main', 'genericdatafields')) {
            $localEntries = DataFieldEntry::getDataFieldEntries($this->user_id, 'user');
            $k = 1;
            foreach ($generic_datafields as $datafield) {
                if (isset($localEntries[$datafield]) &&
                        is_object($localEntries[$datafield]) &&
                        is_element_visible_externally($this->user_id,
                            $uthis->ser_perm, $localEntries[$datafield]->getId(),
                            $this->visibilities[$localEntries[$datafield]->getId()])) {
                    $localEntry = $localEntries[$datafield]->getDisplayValue();
                    if ($localEntry) {
                        $content['PERSONDETAILS']["DATAFIELD_$k"] = $localEntry;
                    }
                }
                $k++;
            }
        }

        // homepage plugins
        $plugins = PluginEngine::getPlugins('HomepagePlugin');

        foreach ($plugins as $plugin) {
            $template = $plugin->getHomepageTemplate($this->user_id);

            if ($template) {
                $keyname = 'PLUGIN_' . strtoupper($plugin->getPluginName());
                $content['PERSONDETAILS'][$keyname] = $template->render();
            }
        }

        if (is_element_visible_externally($this->user_id, $this->user_perm, 'lebenslauf', $this->visibilities['lebenslauf']) && trim($row['lebenslauf']) != '') {
            $content['PERSONDETAILS']['CV'] = ExternModule::ExtFormatReady($row['lebenslauf']);
        }
        if (is_element_visible_externally($this->user_id, $this->user_perm, 'schwerp', $this->visibilities['schwerp']) && trim($row['schwerp']) != '') {
            $content['PERSONDETAILS']['RESEARCH-INTERESTS'] = ExternModule::ExtFormatReady($row['schwerp']);
        }
        if (is_element_visible_externally($this->user_id, $this->user_perm, 'lebenslauf', $this->visibilities['publi']) && trim($row['publi']) != '') {
            $content['PERSONDETAILS']['PUBLICATIONS'] = ExternModule::ExtFormatReady($row['publi']);
        }

        $content['PERSONDETAILS']['LECTURES'] = $this->elements['TemplateLectures']->toString(array('content' => $this->getContentLectures(), 'subpart' => 'LECTURES'));
        if (is_element_visible_externally($this->user_id, $this->user_perm, 'news', $this->visibilities['news'])) {
            $content['PERSONDETAILS']['NEWS'] = $this->elements['TemplateNews']->toString(array('content' => $this->getContentNews(), 'subpart' => 'NEWS'));
        }
        if (get_config('CALENDAR_ENABLE') && is_element_visible_externally($this->user_id, $this->user_perm, 'dates', $this->visibilities['dates'])) {
            $content['PERSONDETAILS']['APPOINTMENTS'] = $this->elements['TemplateAppointments']->toString(array('content' => $this->getContentAppointments(), 'subpart' => 'APPOINTMENTS'));
        }
        if (is_element_visible_externally($this->user_id, $this->user_perm, 'literature', $this->visibilities['literature'])) {
            $content['PERSONDETAILS']['LITERATURE'] = $this->elements['TemplateLitList']->toString(array('content' => $this->elements['LitList']->getContent(array('user_id' => $this->user_id)), 'subpart' => 'LITLISTS'));
        }
        $content['PERSONDETAILS']['OWNCATEGORIES'] = $this->elements['TemplateOwnCategories']->toString(array('content' => $this->getContentOwnCategories(), 'subpart' => 'OWNCATEGORIES'));

        return $content;
    }

    private function getContentOwnCategories () {
        $stm = DBManager::get()->prepare(
            "SELECT kategorie_id, name, content "
            . "FROM kategorien "
            . "WHERE range_id = ? "
            . "ORDER BY priority");
        $stm->execute(array($this->user_id));
        $i = 0;
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            if (is_element_visible_externally($this->user_id, $this->user_perm, 'kat_'.$row['kategorie_id'], $this->visibilities['kat_'.$row['kategorie_id']])) {
                $content['OWNCATEGORIES']['OWNCATEGORY'][$i]['OWNCATEGORY_TITLE'] = ExternModule::ExtHtmlReady($row['name']);
                $content['OWNCATEGORIES']['OWNCATEGORY'][$i]['OWNCATEGORY_CONTENT'] = ExternModule::ExtFormatReady($row['content']);
                $content['OWNCATEGORIES']['OWNCATEGORY'][$i]['OWNCATEGORY_NO'] = $i + 1;
                $i++;
            }
        }
        return $content;
    }

    private function getContentNews () {
        $news =& StudipNews::GetNewsByRange($this->user_id, TRUE);
        if (!count($news)) {
            $content['NEWS']['NO-NEWS']['NEWS_NO-NEWS-TEXT'] = $this->config->getValue('Main', 'nodatatext');
        } else {
            $i = 0;
            foreach ($news as $news_id => $news_detail) {
                list($news_content, $admin_msg) = explode("<admin_msg>", $news_detail['body']);
                if ($admin_msg) {
                    $content['NEWS']['ALL-NEWS']['SINGLE-NEWS'][$i]['NEWS_ADMIN-MESSAGE'] = preg_replace('# \(.*?\)#', '', $admin_msg);
                    $content['NEWS']['ALL-NEWS']['SINGLE-NEWS'][$i]['NEWS_BODY'] = ExternModule::ExtFormatReady($news_content);
                } else {
                    $content['NEWS']['ALL-NEWS']['SINGLE-NEWS'][$i]['NEWS_BODY'] = ExternModule::ExtFormatReady($news_detail['body']);
                }
                $content['NEWS']['ALL-NEWS']['SINGLE-NEWS'][$i]['NEWS_DATE'] = strftime($dateform, $news_detail['date']);
                $content['NEWS']['ALL-NEWS']['SINGLE-NEWS'][$i]['NEWS_TOPIC'] = ExternModule::ExtHtmlReady($news_detail['topic']);
                $content['NEWS']['ALL-NEWS']['SINGLE-NEWS'][$i]['NEWS_NO'] = $i + 1;
                $i++;
            }
        }
        return $content;
    }

    private function getContentAppointments () {
        if (get_config('CALENDAR_ENABLE')) {
            $event_list = new DbCalendarEventList($this->user_id);
            $content['APPOINTMENTS']['LIST-START'] = ExternModule::ExtHtmlReady(strftime($this->config->getValue('Main', 'dateformat') . ' %X', $event_list->getStart()));
            $content['APPOINTMENTS']['LIST-END'] = ExternModule::ExtHtmlReady(strftime($this->config->getValue('Main', 'dateformat') . ' %X', $event_list->getEnd()));
            if ($event_list->existEvent()) {
                $i = 0;
                while ($event = $event_list->nextEvent()) {
                    if ($event->isDayEvent()) {
                        $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['DATE'] = ExternModule::ExtHtmlReady(strftime($this->config->getValue('Main', 'dateformat'), $event->getStart()) . ' (' . _("ganztügig") . ')');
                    } else {
                        $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['DATE'] = ExternModule::ExtHtmlReady(strftime($this->config->getValue('Main', 'dateformat') . " %X", $event->getStart()));
                        if (date("dmY", $event->getStart()) == date("dmY", $event->getEnd())) {
                            $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['DATE'] .= ExternModule::ExtHtmlReady(strftime(" - %X", $event->getEnd()));
                        } else {
                            $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['DATE'] .= ExternModule::ExtHtmlReady(strftime(" - " . $this->config->getValue('Main', 'dateformat') . " %X", $event->getEnd()));
                        }
                    }
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['TITLE'] = ExternModule::ExtHtmlReady($event->getTitle());
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['DESCRIPTION'] = ExternModule::ExtHtmlReady($event->getDescription());
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['LOCATION'] = ExternModule::ExtHtmlReady($event->getLocation());
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['REPETITION'] = ExternModule::ExtHtmlReady($event->toStringRecurrence());
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['CATEGORY'] = ExternModule::ExtHtmlReady($event->toStringCategories());
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['PRIORITY'] = ExternModule::ExtHtmlReady($event->toStringPriority());
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['START'] = ExternModule::ExtHtmlReady(strftime($this->config->getValue('Main', 'dateformat') . " %X", $event->getStart()));
                    $content['APPOINTMENTS']['ALL-APPOINTMENTS']['SINGLE-APPOINTMENT'][$i]['END'] = ExternModule::ExtHtmlReady(strftime($this->config->getValue('Main', 'dateformat') . " %X", $event->getEnd()));
                    $i++;
                }
            } else {
                $content['APPOINTMENTS']['NO-APPOINTMENTS']['NO-APPOINTMENTS_TEXT'] = $this->config->getValue('Main', 'noappointmentstext');
            }
            return $content;
        }
        return NULL;
    }

    private function getContentLectures () {
        global $attr_text_td, $end, $start;
        $semester = new SemesterData();
        $all_semester = $semester->getAllSemesterData();
        // old hard coded $SEMESTER-array starts with index 1
        array_unshift($all_semester, 0);

        $types = array();
        $semclass = $this->config->getValue('PersondetailsLectures', 'semclass');
        if (is_null($semclass)) {
            $semclass = array(1);
        }
            if (in_array($type["class"], $semclass)) {
            }
        $switch_time = mktime(0, 0, 0, date("m"), date("d") + 7 * $this->config->getValue("PersondetailsLectures", "semswitch"), date("Y"));
        // get current semester
        $current_sem = get_sem_num($switch_time) + 1;

        switch ($this->config->getValue("PersondetailsLectures", "semstart")) {
            case "previous" :
                if (isset($all_semester[$current_sem - 1])) {
                    $current_sem--;
                }
                break;
            case "next" :
                if (isset($all_semester[$current_sem + 1])) {
                    $current_sem++;
                }
                break;
            case "current" :
                break;
            default :
                if (isset($all_semester[$this->config->getValue("PersondetailsLectures", "semstart")])) {
                    $current_sem = $this->config->getValue("PersondetailsLectures", "semstart");
                }
        }

        $last_sem = $current_sem + $this->config->getValue("PersondetailsLectures", "semrange") - 1;
        if ($last_sem < $current_sem) {
            $last_sem = $current_sem;
        }
        if (!isset($all_semester[$last_sem])) {
            $last_sem = sizeof($all_semester) - 1;
        }

        $types = array();
        $semclass = $this->config->getValue('PersondetailsLectures', 'semclass');
        if (is_null($semclass)) {
            $semclass = array(1);
        }
        foreach ($GLOBALS["SEM_TYPE"] as $key => $type) {
            if (in_array($type["class"], $semclass)) {
                $types[] = $key;
            }
        }
        $stm = DBManager::get()->prepare(sprintf(
            "SELECT s.Name, s.Seminar_id, s.Untertitel, s.VeranstaltungsNummer "
            . "FROM seminar_user su "
            . "LEFT JOIN seminare s USING(seminar_id) "
            . "WHERE user_id = ? AND su.status LIKE 'dozent' "
            . "AND ((start_time >= ? AND start_time <= ?) "
            . "OR (start_time <= ? AND duration_time = -1)) "
            . "AND s.status IN ('%s') AND s.visible = 1 "
            . "ORDER BY s.mkdate DESC"
            , implode("','", $types)));

        $i = 0;
        for (;$current_sem <= $last_sem; $last_sem--) {
            $stm->execute(array($this->user_id, $all_semester[$last_sem]['beginn'], $all_semester[$last_sem]['beginn'],$all_semester[$last_sem]['ende']));
            $result = $stm->fetchAll();

            if ($result && sizeof($result)) {
                if (!($this->config->getValue('PersondetailsLectures', 'semstart') == 'current' && $this->config->getValue('PersondetailsLectures', 'semrange') == 1)) {
                    $month = date('n', $all_semester[$last_sem]['beginn']);
                    if ($month > 9) {
                        $content['LECTURES']['SEMESTER'][$i]['NAME'] = $this->config->getValue('PersondetailsLectures', 'aliaswise') . date(' Y/', $all_semester[$last_sem]['beginn']) . date('y', $all_semester[$last_sem]['ende']);
                    } else if ($month > 3 && $month < 10) {
                        $content['LECTURES']['SEMESTER'][$i]['NAME'] = $this->config->getValue('PersondetailsLectures', 'aliassose') . date(' Y', $all_semester[$last_sem]['beginn']);
                    }
                }
                $k = 0;
                foreach ($result as $row) {
                    $content['LECTURES']['SEMESTER'][$i]['LECTURE'][$k]['TITLE'] = ExternModule::ExtHtmlReady($row['Name']);
                    $content['LECTURES']['SEMESTER'][$i]['LECTURE'][$k]['LECTUREDETAILS-HREF'] = $this->elements['LinkInternLecturedetails']->createUrl(array('link_args' => 'seminar_id=' . $row['Seminar_id']));
                    if (trim($row['Untertitel']) != '') {
                        $content['LECTURES']['SEMESTER'][$i]['LECTURE'][$k]['SUBTITLE'] = ExternModule::ExtHtmlReady($row['Untertitel']);
                    }
                    if (trim($row['VeranstaltungsNummer']) != '') {
                        $content['LECTURES']['SEMESTER'][$i]['LECTURE'][$k]['NUMBER'] = ExternModule::ExtHtmlReady($row['VeranstaltungsNummer']);
                    }
                    $k++;
                }
            }
            $i++;
        }
        return $content;
    }

    public function printout ($args) {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);

        echo $this->elements['TemplateMain']->toString(array('content' => $this->getContent($args), 'subpart' => 'PERSONDETAILS'));

    }

    public function printoutPreview () {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);

        echo $this->elements['TemplateMain']->toString(array('content' => $this->getContent(), 'subpart' => 'PERSONDETAILS', 'hide_markers' => FALSE));

    }

}
?>
