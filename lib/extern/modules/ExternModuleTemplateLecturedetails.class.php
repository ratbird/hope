<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternModuleTemplateLecturedetails.class.php
*
*
*
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternModuleTemplateLecturedetails
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternModuleTemplateLecturedetails.class.php
//
// Copyright (C) 2007 Peter Thienel <thienel@data-quest.de>,
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
require_once('lib/user_visible.inc.php');
require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/extern_functions.inc.php');
require_once('lib/language.inc.php');
require_once('lib/visual.inc.php');
require_once('lib/dates.inc.php');
require_once('lib/functions.php');
global $_fullname_sql;


class ExternModuleTemplateLecturedetails extends ExternModule {

    var $markers = array();
    var $args = array('seminar_id');

    /**
    *
    */
    function ExternModuleTemplateLecturedetails ($range_id, $module_name, $config_id = NULL, $set_config = NULL, $global_id = NULL) {

        $this->data_fields = array('subtitle', 'lecturer', 'art', 'status', 'description',
            'location', 'semester', 'time', 'number', 'teilnehmer', 'requirements',
            'lernorga', 'leistung', 'range_path', 'misc', 'ects');
        $this->registered_elements = array(
                'ReplaceTextSemType',
                'LinkInternPersondetails' => 'LinkInternTemplate',
                'TemplateLectureData' => 'TemplateGeneric',
                'TemplateStudipData' => 'TemplateGeneric'
        );
        $this->field_names = array
        (
                _("Untertitel"),
                _("DozentIn"),
                _("Veranstaltungsart"),
                _("Veranstaltungstyp"),
                _("Beschreibung"),
                _("Ort"),
                _("Semester"),
                _("Zeiten"),
                _("Veranstaltungsnummer"),
                _("TeilnehmerInnen"),
                _("Voraussetzungen"),
                _("Lernorganisation"),
                _("Leistungsnachweis"),
                _("Bereichseinordnung"),
                _("Sonstiges"),
                _("ECTS-Kreditpunkte")
        );

        parent::ExternModule($range_id, $module_name, $config_id, $set_config, $global_id);
    }

    function setup () {
        // extend $data_fields if generic datafields are set
    //  $config_datafields = $this->config->getValue("Main", "genericdatafields");
    //  $this->data_fields = array_merge((array)$this->data_fields, (array)$config_datafields);

        // setup module properties
    //  $this->elements["LinkIntern"]->link_module_type = 2;
    //  $this->elements["LinkIntern"]->real_name = _("Link zum Modul MitarbeiterInnendetails");

        $this->elements['LinkInternPersondetails']->real_name = _("Verlinkung zum Modul MitarbeiterInnendetails");
        $this->elements['LinkInternPersondetails']->link_module_type = array(2, 14);
        $this->elements['TemplateLectureData']->real_name = _("Haupttemplate");
        $this->elements['TemplateStudipData']->real_name = _("Template für statistische Daten aus Stud.IP");

    }

    function toStringEdit ($open_elements = '', $post_vars = '',
            $faulty_values = '', $anker = '') {

        $this->updateGenericDatafields('TemplateLectureData', 'sem');
        $this->elements['TemplateLectureData']->markers = $this->getMarkerDescription('TemplateLectureData');
        $this->elements['TemplateStudipData']->markers = $this->getMarkerDescription('TemplateStudipData');

        return parent::toStringEdit($open_elements, $post_vars, $faulty_values, $anker);
    }

    function getMarkerDescription ($element_name) {
        $markers['TemplateLectureData'][] = array('__GLOBAL__', _("Globale Variablen (gültig im gesamten Template)."));
        $markers['TemplateLectureData'][] = array('###STUDIP-EDIT-HREF###', '');
        $markers['TemplateLectureData'][] = array('###STUDIP-REGISTER-HREF###', '');

        $markers['TemplateLectureData'][] = array('<!-- BEGIN LECTUREDETAILS -->', '');
        $markers['TemplateLectureData'][] = array('###TITLE###', '');
        $markers['TemplateLectureData'][] = array('###SUBTITLE###', '');
        $markers['TemplateLectureData'][] = array('###SEMESTER###', '');
        $markers['TemplateLectureData'][] = array('###CYCLE###', '');
        $markers['TemplateLectureData'][] = array('###ROOM###', '');
        $markers['TemplateLectureData'][] = array('###NUMBER###', _("Die Veranstaltungsnummer"));

        $markers['TemplateLectureData'][] = array('<!-- BEGIN LECTURERS -->', '');
        $markers['TemplateLectureData'][] = array('<!-- BEGIN LECTURER -->', '');
        $markers['TemplateLectureData'][] = array('###FULLNAME###', '');
        $markers['TemplateLectureData'][] = array('###LASTNAME###', '');
        $markers['TemplateLectureData'][] = array('###FIRSTNAME###', '');
        $markers['TemplateLectureData'][] = array('###TITLEFRONT###', '');
        $markers['TemplateLectureData'][] = array('###TITLEREAR###', '');
        $markers['TemplateLectureData'][] = array('###PERSONDETAILS-HREF###', '');
        $markers['TemplateLectureData'][] = array('###LECTURER-NO###', '');
        $markers['TemplateLectureData'][] = array('###UNAME###', '');
        $markers['TemplateLectureData'][] = array('<!-- END LECTURER -->', '');
        $markers['TemplateLectureData'][] = array('<!-- END LECTURERS -->', '');

        $markers['TemplateLectureData'][] = array('<!-- BEGIN TUTORS -->', '');
        $markers['TemplateLectureData'][] = array('<!-- BEGIN TUTOR -->', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_FULLNAME###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_LASTNAME###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_FIRSTNAME###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_TITLEFRONT###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_TITLEREAR###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_PERSONDETAILS-HREF###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR-NO###', '');
        $markers['TemplateLectureData'][] = array('###TUTOR_UNAME###', '');
        $markers['TemplateLectureData'][] = array('<!-- END TUTOR -->', '');
        $markers['TemplateLectureData'][] = array('<!-- END TUTORS -->', '');

        $markers['TemplateLectureData'][] = array('###PRELIM-DISCUSSION###', '');
        $markers['TemplateLectureData'][] = array('###SEMTYPE-SUBSTITUTE###', '');
        $markers['TemplateLectureData'][] = array('###SEMTYPE###', '');
        $markers['TemplateLectureData'][] = array('###FORM###', _("Die Veranstaltungsart"));
        $markers['TemplateLectureData'][] = array('###PARTICIPANTS###', '');
        $markers['TemplateLectureData'][] = array('###DESCRIPTION###', '');
        $markers['TemplateLectureData'][] = array('###MISC###', _("Sonstiges"));
        $markers['TemplateLectureData'][] = array('###REQUIREMENTS###', '');
        $markers['TemplateLectureData'][] = array('###ORGA###', _("Organisationsform"));
        $markers['TemplateLectureData'][] = array('###LEISTUNGSNACHWEIS###', _("Leistungsnachweis"));
        $markers['TemplateLectureData'][] = array('###FORM###', '');
        $markers['TemplateLectureData'][] = array('###ECTS###', '');
        $markers['TemplateLectureData'][] = array('###PRELIM-DISCUSSION###', '');
        $markers['TemplateLectureData'][] = array('###FIRST-MEETING###', '');

        $this->insertDatafieldMarkers('sem', $markers, 'TemplateLectureData');

        $markers['TemplateLectureData'][] = array('###STUDIP-DATA###', 'Inhalt aus dem Template für statistische Daten aus Stud.IP');

        $markers['TemplateLectureData'][] = array('<!-- BEGIN RANGE-PATHES -->', '');
        $markers['TemplateLectureData'][] = array('<!-- BEGIN RANGE-PATH -->', '');
        $markers['TemplateLectureData'][] = array('###PATH###', '');
        $markers['TemplateLectureData'][] = array('<!-- END RANGE-PATH -->', '');
        $markers['TemplateLectureData'][] = array('<!-- END RANGE-PATHES -->', '');

        $markers['TemplateLectureData'][] = array('<!-- END LECTUREDETAILS -->');

        $markers['TemplateStudipData'][] = array('<!-- BEGIN STUDIP-DATA -->', '');
        $markers['TemplateStudipData'][] = array('###HOME-INST-NAME###', '');
        $markers['TemplateStudipData'][] = array('###HOME-INST-HREF###', '');
        $markers['TemplateStudipData'][] = array('###COUNT-USER###', '');
        $markers['TemplateStudipData'][] = array('###COUNT-POSTINGS###', '');
        $markers['TemplateStudipData'][] = array('###COUNT-DOCUMENTS###', '');

        $markers['TemplateStudipData'][] = array('<!-- BEGIN INVOLVED-INSTITUTES -->', '');
        $markers['TemplateStudipData'][] = array('<!-- BEGIN INVOLVED-INSTITUTE -->', '');
        $markers['TemplateStudipData'][] = array('###INVOLVED-INSTITUTE_HREF###', '');
        $markers['TemplateStudipData'][] = array('###INVOLVED-INSTITUTE_NAME###', '');
        $markers['TemplateStudipData'][] = array('<!-- END INVOLVED-INSTITUTE -->', '');
        $markers['TemplateStudipData'][] = array('<!-- END INVOLVED-INSTITUTES -->', '');

        $markers['TemplateStudipData'][] = array('<!-- END STUDIP-DATA -->', '');

        return $markers[$element_name];
    }

    function getContent ($args = NULL, $raw = FALSE) {
        $this->seminar_id = $args["seminar_id"];
        $seminar = Seminar::getInstance($this->seminar_id);

        $this->db = new DB_Seminar();
        $query = "SELECT * FROM seminare WHERE Seminar_id='$this->seminar_id'";
        $this->db->query($query);
        $visible = $this->config->getValue("Main", "visible");

        $j = -1;
        if ($seminar->visible == 1) {
            $content['LECTUREDETAILS']['TITLE'] = ExternModule::ExtHtmlReady($seminar->getName());
            if (trim($seminar->seminar_number)) {
                $content['LECTUREDETAILS']['NUMBER'] = ExternModule::ExtHtmlReady($seminar->seminar_number);
            }
            if (trim($seminar->subtitle)) {
                $content['LECTUREDETAILS']['SUBTITLE'] = ExternModule::ExtHtmlReady($seminar->subtitle);
            }
            if (trim($seminar->description)) {
                $content['LECTUREDETAILS']['DESCRIPTION'] = ExternModule::ExtHtmlReady($seminar->description, TRUE);
            }
            if (trim($seminar->misc)) {
                $content['LECTUREDETAILS']['MISC'] = ExternModule::ExtHtmlReady($seminar->misc, TRUE);
            }
            if (trim($seminar->participants)) {
                $content['LECTUREDETAILS']['PARTICIPANTS'] = ExternModule::ExtHtmlReady($seminar->participants);
            }
            if (trim($seminar->requirements)) {
                $content['LECTUREDETAILS']['REQUIREMENTS'] = ExternModule::ExtHtmlReady($seminar->requirements);
            }
            if (trim($seminar->orga)) {
                $content['LECTUREDETAILS']['ORGA'] = ExternModule::ExtHtmlReady($seminar->orga);
            }
            if (trim($seminar->leistungsnachweis)) {
                $content['LECTUREDETAILS']['LEISTUNGSNACHWEIS'] = ExternModule::ExtHtmlReady($seminar->leistungsnachweis);
            }
            if (trim($seminar->form)) {
                $content['LECTUREDETAILS']['FORM'] = ExternModule::ExtHtmlReady($seminar->form);
            }
            if (trim($seminar->ects)) {
                $content['LECTUREDETAILS']['ECTS'] = ExternModule::ExtHtmlReady($seminar->ects);
            }

            if (!$name_sql = $this->config->getValue("Main", "nameformat")) {
                $name_sql = "full";
            }

            $lecturers = array_keys($seminar->getMembers('dozent'));
            $db_lecturer = new DB_Seminar();
            $l = 0;
            foreach ($lecturers as $lecturer) {
                $db_lecturer->query("SELECT {$GLOBALS['_fullname_sql'][$name_sql]} AS name, username, Vorname, Nachname, title_rear, title_front FROM auth_user_md5 aum LEFT JOIN user_info ui USING(user_id) WHERE aum.user_id = '$lecturer'");
                if ($db_lecturer->next_record()) {
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['PERSONDETAILS-HREF'] = $this->elements['LinkInternPersondetails']->createUrl(array('link_args' => 'username=' . $db_lecturer->f('username')));
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['FULLNAME'] = ExternModule::ExtHtmlReady($db_lecturer->f('name'));
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['FIRSTNAME'] = ExternModule::ExtHtmlReady($db_lecturer->f('Vorname'));
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['LASTNAME'] = ExternModule::ExtHtmlReady($db_lecturer->f('Nachname'));
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['TITLEFRONT'] = ExternModule::ExtHtmlReady($db_lecturer->f('title_front'));
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['TITLEREAR'] = ExternModule::ExtHtmlReady($db_lecturer->f('title_rear'));
                    $content['LECTUREDETAILS']['LECTURERS']['LECTURER'][$l]['UNAME'] = $db_lecturer->f('username');
                    $l++;
                }
            }

            $tutors = array_keys($seminar->getMembers('tutor'));
            $db_tutor = new DB_Seminar();
            $l = 0;
            foreach ($tutors as $tutor) {
                $db_tutor->query("SELECT {$GLOBALS['_fullname_sql'][$name_sql]} AS name, username, Vorname, Nachname, title_rear, title_front FROM auth_user_md5 aum LEFT JOIN user_info ui USING(user_id) WHERE aum.user_id = '$tutor'");
                if ($db_tutor->next_record()) {
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_PERSONDETAILS-HREF'] = $this->elements['LinkInternPersondetails']->createUrl(array('link_args' => 'username=' . $db_tutor->f('username')));
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_FULLNAME'] = ExternModule::ExtHtmlReady($db_tutor->f('name'));
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_FIRSTNAME'] = ExternModule::ExtHtmlReady($db_tutor->f('Vorname'));
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_LASTNAME'] = ExternModule::ExtHtmlReady($db_tutor->f('Nachname'));
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_TITLEFRONT'] = ExternModule::ExtHtmlReady($db_tutor->f('title_front'));
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_TITLEREAR'] = ExternModule::ExtHtmlReady($db_tutor->f('title_rear'));
                    $content['LECTUREDETAILS']['TUTORS']['TUTOR'][$l]['TUTOR_UNAME'] = $db_tutor->f('username');
                    $l++;
                }
            }

            // reorganize the $SEM_TYPE-array
            foreach ($GLOBALS["SEM_CLASS"] as $key_class => $class) {
                $i = 0;
                foreach ($GLOBALS["SEM_TYPE"] as $key_type => $type) {
                    if ($type["class"] == $key_class) {
                        $i++;
                        $sem_types_position[$key_type] = $i;
                    }
                }
            }
            $aliases_sem_type = $this->config->getValue("ReplaceTextSemType",
                    "class_" . $GLOBALS["SEM_TYPE"][$seminar->status]['class']);
            if ($aliases_sem_type[$sem_types_position[$seminar->status] - 1]) {
                $content['LECTUREDETAILS']['SEMTYPE-SUBSTITUTE'] = $aliases_sem_type[$sem_types_position[$seminar->status] - 1];
            } else {
                $content['LECTUREDETAILS']['SEMTYPE-SUBSTITUTE'] = ExternModule::ExtHtmlReady($GLOBALS["SEM_TYPE"][$seminar->status]["name"]);
            }
            $content['LECTUREDETAILS']['SEMTYPE'] = ExternModule::ExtHtmlReady($GLOBALS["SEM_TYPE"][$seminar->status]["name"]);
            $room = trim(Seminar::getInstance($this->seminar_id)->getDatesTemplate('dates/seminar_export_location'));
            if ($room) {
                $content['LECTUREDETAILS']['ROOM'] = ExternModule::ExtHtmlReady($room);
            }
            $content['LECTUREDETAILS']['SEMESTER'] = get_semester($this->seminar_id);
            $content['LECTUREDETAILS']['CYCLE'] = ExternModule::ExtHtmlReady(Seminar::getInstance($this->seminar_id)->getDatesExport());
            if ($vorbesprechung = vorbesprechung($this->seminar_id, 'export')) {
                $content['LECTUREDETAILS']['PRELIM-DISCUSSION'] = ExternModule::ExtHtmlReady($vorbesprechung);
            }
            if ($veranstaltung_beginn = Seminar::getInstance($this->seminar_id)->getFirstDate('export')) {
                $content['LECTUREDETAILS']['FIRST-MEETING'] = ExternModule::ExtHtmlReady($veranstaltung_beginn);
            }

            $range_path_level = $this->config->getValue('Main', 'rangepathlevel');
            $pathes = get_sem_tree_path($this->seminar_id, $range_path_level);
            if (is_array($pathes)) {
                $i = 0;
                foreach ($pathes as $foo => $path) {
                    $content['LECTUREDETAILS']['RANGE-PATHES']['RANGE-PATH'][$i]['PATH'] = ExternModule::ExtHtmlReady($path);
                    $i++;
                }
            }

            // generic data fields
            if ($generic_datafields = $this->config->getValue('Main', 'genericdatafields')) {
                $localEntries = DataFieldEntry::getDataFieldEntries($this->seminar_id, 'sem');
                $k = 1;
                foreach ($generic_datafields as $datafield) {
                    if (isset($localEntries[$datafield]) && is_object($localEntries[$datafield])) {
                        $localEntry = trim($localEntries[$datafield]->getDisplayValue());
                        if ($localEntry) {
                            $content['LECTUREDETAILS']["DATAFIELD_$k"] = $localEntry;
                        }
                    }
                    $k++;
                }
            }

            $content['__GLOBAL__']['STUDIP-EDIT-HREF'] = "{$GLOBALS['ABSOLUTE_URI_STUDIP']}seminar_main.php?auswahl={$this->seminar_id}&again=1&redirect_to=dispatch.php/course/basicdata/view/".$this->seminar_id."&login=true&new_sem=TRUE";
            $content['__GLOBAL__']['STUDIP-REGISTER-HREF'] = "{$GLOBALS['ABSOLUTE_URI_STUDIP']}details.php?again=1&sem_id={$this->seminar_id}";
        }

        return $content;
    }

    function getStudipData () {
        $this->db->query("SELECT i.Institut_id, i.Name, i.url FROM seminare LEFT JOIN Institute i USING(institut_id) WHERE Seminar_id='{$this->seminar_id}'");
        $this->db->next_record();
        $own_inst = $this->db->f('Institut_id');

        $content['STUDIP-DATA']['HOME-INST-NAME'] = ExternModule::ExtHtmlReady($this->db->f('Name'));

        if ($this->db->f("url")) {
            $link_inst = htmlReady($this->db->f("url"));
            if (!preg_match('{^https?://.+$}', $link_inst)) {
                $link_inst = "http://$link_inst";
            }
            $content['STUDIP-DATA']['HOME-INST-HREF'] = $link_inst;
        }

        $this->db->query("SELECT Name, url FROM seminar_inst LEFT JOIN Institute i USING(institut_id) WHERE seminar_id='{$this->seminar_id}' AND i.institut_id!='$own_inst'");
        $involved_insts = NULL;
        $i = 0;
        while ($this->db->next_record()) {
            if ($this->db->f('url')) {
                $link_inst = htmlReady($this->db->f('url'));
                if (!preg_match('{^https?://.+$}', $link_inst)) {
                    $link_inst = "http://$link_inst";
                }
                $content['STUDIP-DATA']['INVOLVED-INSTITUES']['INVOLVED-INSTITUTE'][$i]['INVOLVED-INSTITUTE_HREF'] = $link_inst;
            }
            $content['STUDIP-DATA']['INVOLVED-INSTITUTES']['INVOLVED-INSTITUTE'][$i]['INVOLVED-INSTITUTE_NAME'] = ExternModule::ExtHtmlReady($this->db->f('Name'));
            $i++;
        }

        $this->db->query("SELECT count(*) as count_user FROM seminar_user WHERE Seminar_id='{$this->seminar_id}'");
        $this->db->next_record();

        if ($this->db->f('count_user')) {
            $content['STUDIP-DATA']['COUNT-USER'] = $this->db->f('count_user');
        } else {
            $content['STUDIP-DATA']['COUNT-USER'] = '0';
        }

        $this->db->query("SELECT count(*) as count_postings FROM px_topics WHERE Seminar_id='{$this->seminar_id}'");
        $this->db->next_record();

        if ($this->db->f('count_postings')) {
            $content['STUDIP-DATA']['COUNT-POSTINGS'] = $this->db->f('count_postings');
        } else {
            $content['STUDIP-DATA']['COUNT-POSTINGS'] = '0';
        }

        $this->db->query("SELECT count(*) as count_documents FROM dokumente WHERE seminar_id='{$this->seminar_id}'");
        $this->db->next_record();

        if ($this->db->f('count_documents')) {
            $content['STUDIP-DATA']['COUNT-DOCUMENTS'] = $this->db->f("count_documents");
        } else {
            $content['STUDIP-DATA']['COUNT-DOCUMENTS'] = '0';
        }

        return $this->elements['TemplateStudipData']->toString(array('content' => $content, 'subpart' => 'STUDIP-DATA'));
    }

    function printout ($args) {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);

        echo $this->elements['TemplateLectureData']->toString(array('content' => $this->getContent($args), 'subpart' => 'LECTUREDETAILS'));

    }

    function printoutPreview () {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);

        echo $this->elements['TemplateLectureData']->toString(array('content' => $this->getContent(array()), 'subpart' => 'LECTUREDETAILS', 'hide_markers' => FALSE));

    }

    function addContentStudipInfo (&$content) {

    }
}

?>
