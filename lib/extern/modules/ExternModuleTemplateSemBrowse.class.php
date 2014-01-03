<?php
# Lifter010: TODO
/**
* ExternModuleTemplateSemBrowse.class.php
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternModuleTemplateSemBrowse
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternModuleTemplateSemBrowse.class.php
//
// Copyright (C) 2008 Peter Thienel <thienel@data-quest.de>,
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


require_once $GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/ExternModule.class.php';
require_once $GLOBALS['RELATIVE_PATH_EXTERN'].'/views/extern_html_templates.inc.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/visual.inc.php';
require_once $GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/extern_functions.inc.php';
require_once 'lib/dates.inc.php';
require_once 'lib/classes/StudipSemSearch.class.php';
require_once 'lib/classes/StudipSemTreeViewSimple.class.php';
require_once 'lib/classes/StudipSemRangeTreeViewSimple.class.php';
require_once 'lib/raumzeit/SingleDate.class.php';
require_once 'lib/raumzeit/SeminarDB.class.php';


class ExternModuleTemplateSemBrowse extends ExternModule {

    var $markers = array();
    var $args = array();
    var $sem_browse_data = array();
    var $search_helper;
    var $sem_tree;
    var $range_tree;
    var $sem_number = array();
    var $group_by_fields = array();
    //var $current_level_name = ''; //only set if tree is rendered with getContentTree()!
    var $global_markers = array();
    var $approved_params = array();
    var $module_params = array();


    function ExternModuleTemplateSemBrowse ($range_id, $module_name, $config_id = NULL, $set_config = NULL, $global_id = NULL) {

        $this->data_fields = array('VeranstaltungsNummer', 'Name', 'Untertitel', 'status', 'Ort',
            'art', 'zeiten', 'dozent');
        $this->registered_elements = array(
                'ReplaceTextSemType',
                'SelectSubjectAreas',
                'LinkInternLecturedetails' => 'LinkInternTemplate',
                'LinkInternPersondetails' => 'LinkInternTemplate',
                'LinkInternSearchForm' => 'LinkInternTemplate',
                'LinkInternTree' => 'LinkInternTemplate',
                'LinkInternShowCourses' => 'LinkInternTemplate',
                'TemplateSimpleSearch' => 'TemplateGeneric',
                'TemplateExtendedSearch' => 'TemplateGeneric',
                'TemplateTree' => 'TemplateGeneric',
                'TemplateResult' => 'TemplateGeneric',
                'TemplateMain' => 'TemplateGeneric'
        );
        $this->field_names = array
        (
                _("Veranstaltungsnummer"),
                _("Name"),
                _("Untertitel"),
                _("Status"),
                _("Ort"),
                _("Art"),
                _("Zeiten"),
                _("DozentIn")
        );

        $this->approved_params = array('start_item_id', 'sem', 'do_search', 'quick_search', 'show_result', 'title', 'sub_title', 'number', 'comment', 'lecturer', 'scope', 'combination', 'type', 'qs_choose', 'withkids', 'xls_export', 'group_by', 'start');

        parent::ExternModule($range_id, $module_name, $config_id, $set_config, $global_id);
    }

    function setup () {
        $this->elements['LinkInternLecturedetails']->real_name = _("Verlinkung zum Modul Veranstaltungsdetails");
        $this->elements['LinkInternLecturedetails']->link_module_type = array(4, 13);
        $this->elements['LinkInternPersondetails']->real_name = _("Verlinkung zum Modul MitarbeiterInnendetails");
        $this->elements['LinkInternPersondetails']->link_module_type = array(2, 14);
        $this->elements['LinkInternSearchForm']->real_name = _("Ziel f�r Suchformular");
        $this->elements['LinkInternSearchForm']->link_module_type = array(15);
        $this->elements['LinkInternTree']->real_name = _("Ziel f�r Links auf Ebenen");
        $this->elements['LinkInternTree']->link_module_type = array(15);
        $this->elements['LinkInternShowCourses']->real_name = _("Ziel f�r Links auf Ebenen zur Anzeige von Veranstaltungen");
        $this->elements['LinkInternShowCourses']->link_module_type = array(15);
        $this->elements['TemplateSimpleSearch']->real_name = _("Template einfaches Suchformular");
        $this->elements['TemplateExtendedSearch']->real_name = _("Template erweitertes Suchformular");
        $this->elements['TemplateTree']->real_name = _("Template Navigation");
        $this->elements['TemplateResult']->real_name = _("Template Veranstaltungsliste");
        $this->elements['TemplateMain']->real_name = _("Haupttemplate");

    }

    function toStringEdit ($open_elements = '', $post_vars = '', $faulty_values = '', $anker = '') {

        $this->updateGenericDatafields('TemplateResult', 'sem');
        $this->elements['TemplateSimpleSearch']->markers = $this->getMarkerDescription('TemplateSimpleSearch');
        $this->elements['TemplateExtendedSearch']->markers = $this->getMarkerDescription('TemplateExtendedSearch');
        $this->elements['TemplateTree']->markers = $this->getMarkerDescription('TemplateTree');
        $this->elements['TemplateResult']->markers = $this->getMarkerDescription('TemplateResult');
        $this->elements['TemplateMain']->markers = $this->getMarkerDescription('TemplateMain');

        return parent::toStringEdit($open_elements, $post_vars, $faulty_values, $anker);
    }

    function getMarkerDescription ($element_name) {
        $markers['TemplateMain'] = array(
            array('__GLOBAL__', _("Globale Variablen (g�ltig im gesamten Template).")),
            array('###CURRENT_SEMESTER###', _("Name des aktuellen Semesters")),
            array('###CURRENT_LEVEL_NAME###', _("Name der aktuelllen Ebene")),
            array('###CURRENT_LEVEL_INFO###', _("Infotext zur aktuellen Ebene")),
            array('###TREE_LEVEL_NAME_x###', _("Name der Ebene an Stelle x des Pfades")),
            array('###TREE_LEVEL_ID_x###', _("Interne ID der Ebene an Stelle x des Pfades")),
            array('###URL_SEARCH_PARAMS###', _("Such-Parameter, die in einer URL-Query weitergreicht werden")),
            array('###URL_PERSONDETAILS###', _("URL zur Personendetailseite ohne Auswahlparameter")),
            array('###URL_LECTUREDETAILS###', _("URL zur Veranstaltungsdetailseite ohne Auswahlparameter")),
            array('###URL_LEVEL_NO_COURSES###', _("URL zur Zielseite der Ebenenlinks ohne Auswahlparameter")),
            array('###URL_LEVEL_COURSES###', _("URL zur Zielseite der Ebenenlinks mit Kursansicht ohne Auswahlparameter")),
            array('###CURRENT_RESULT_PAGE###', _("Nummer der Ergebnisseite der Suche")),
            array('###NUMBER_OF_RESULT_PAGES###', _("Anzahl der Ergenisseiten der Suche")),
            array('<!-- BEGIN SEM_BROWSER -->', ''),
            array('###SIMPLE_SEARCH###', ''),
            array('###EXTENDED_SEARCH###', ''),
            array('###TREE###', ''),
            array('###RESULT###', ''),
            array('<!-- END SEM_BROWSER -->', ''));

        $markers['TemplateSimpleSearch'] = array(
            array('<!-- BEGIN SEARCH_FORM -->', ''),
            array('###SEARCHFORM_ACTION_SELECT_SEM###', _("URL zum �ndern des Semesters, ohne eine Suche auszul�sen")),
            array('###SEARCHFORM_ACTION###', _("URL, um Suche auszul�sen")),
            array('###SELECT_FIELD###', _("Optionen f�r Suchfeld")),
            array('###SELECT_SEMESTER', _("Optionen f�r Semesterauswahl")),
            array('###INPUT_SEARCH_TERM###', _("Eingabefeld f�r Suchbegriff")),
        //  array('###AJAX_AUTOCOMPLETE###', _("JavaScript f�r Autovervollst�ndigen des Suchfeldes")),
            array('###HREF_RESET_SEARCH###', _("Link, der das Suchformular zur�cksetzt")),
            array('<!-- END SEARCH_FORM -->', ''));

        $markers['TemplateExtendedSearch'] = array(
            array('<!-- BEGIN SEARCH_FORM-->', ''),
            array('###SEARCHFORM_ACTION###', ''),
            array('###INPUT_TITLE###', _("Eingabefeld f�r Titel")),
            array('###INPUT_SUBTITLE###', _("Eingabefeld f�r Untertitel")),
            array('###INPUT_NUMBER###', _("Eingabefeld f�r Veranstaltungsnummer")),
            array('###INPUT_COMMENT###', _("Eingabefeld f�r Kommentar zur Veranstaltung")),
            array('###INPUT_LECTURER###', _("Eingabefeld f�r Dozentenname")),
            array('###INPUT_SUBJECTAREAS###', _("Eingabefeld f�r Studienbereich")),
            array('###SELECT_TYPE###', _("Optionen f�r Veranstaltungstyp")),
            array('###SELECT_SEMESTER###', _("Optionen f�r Semesterauswahl")),
            array('###SELECT_COMBINATION###', _("Optionen f�r logische Verkn�pfung")),
            array('###HREF_RESET_SEARCH###', _("Link, der das Suchformular zur�cksetzt")),
            array('<!-- END SEARCH_FORM -->', ''));

        $markers['TemplateTree'] = array(
            array('<!-- BEGIN NO_COURSES_LEVEL -->', _("Ausgabe, wenn keine Veranstaltungen auf aktueller Ebene vorhanden sind")),
            array('<!-- END NO_COURSES_LEVEL -->', ''),
            array('<!-- BEGIN NO_SUBLEVELS -->', _("Ausgabe, wenn keine Unterebenen vorhanden sind")),
            array('<!-- END NO_SUBLEVELS -->', ''),
            array('###COURSE_COUNT_LEVEL###', _("Anzahl der Veranstaltungen der aktueller Ebene")),
            array('###COURSES_LEVEL-HREF###', _("URL zur Liste mit allen Veranstaltungen der aktuellen Ebene")),
            array('###COURSE_COUNT_SUBLEVELS###', _("Anzahl der Veranstaltungen aller untergeordneten Ebenen")),
            array('###COURSES_SUBLEVELS-HREF###', _("URL zur Liste mit allen Veranstaltungen der untergeordneten Ebenen")),
            array('###ONE_LEVEL_UP-HREF###', _("URL zur �bergeordneten Ebene")),
            array('###CURRENT_LEVEL_NAME###', _("Name des aktuellen Levels")),
            array('<!-- BEGIN LEVEL_TREE -->', ''),
            array('<!-- BEGIN LEVEL_PATH -->', _("Anfang des Bereichspfades")),
            array('<!-- BEGIN LEVEL_PATH_ITEM -->', _("Bereich im Bereichspfad")),
            array('###LEVEL-HREF###', ''),
            array('###LEVEL_NAME###', _("Name des Studienbereichs/der Einrichtung")),
            array('###LEVEL_INFO###', _("Weitere Informationen")),
            array('<!-- BEGIN LEVEL_NO_INFO -->', ''),
            array('<!-- END LEVEL_NO_INFO -->', ''),
            array('<!-- BEGIN PATH_DELIMITER -->', _("Text, der zwischen den einzelnen Ebenen im Pfad ausgegeben wird (nicht nach letzter Ebene)")),
            array('<!-- END PATH_DELIMITER -->', ''),
            array('<!-- END LEVEL_PATH_ITEM -->', ''),
            array('<!-- END LEVEL_PATH -->', ''),
            array('<!-- BEGIN NO_SUBLEVELS -->', _("Dieser Inhalt wird ausgegeben, wenn keine Unterbereiche vorhanden sind")),
            array('<!-- END NO_SUBLEVELS -->', ''),
            array('<!-- BEGIN SUBLEVELS_x -->', _("Beginn der Ebene x mit allen Unterebenen, wobei x die aktuelle Ebenennummer ist (x > 0 und x <= Anzahl der angezeigten Ebenen)")),
            array('<!-- BEGIN SUBLEVEL_x -->', _("Beginn der aktuellen Ebene x")),
            array('###SUBLEVEL_RESULT_x###', _("Liste mit Veranstaltungen auf Ebene x (Template Veranstaltungsliste)")),
            array('<!-- BEGIN NO_LINK_TO_COURSES_x -->', ''),
            array('###SUBLEVEL-HREF_x###', ''),
            array('###SUBLEVEL-HREF_SHOW_COURSES_x###', ''),
            array('###SUBLEVEL_NAME_x###', ''),
            array('###SUBLEVEL_COURSE_COUNT_x###', _("Anzahl der Veranstaltungen in der Ebene x (einschlie�lich Unterebenen)")),
            array('###SUBLEVEL_NO_x###', ''),
            array('###SUBLEVEL_INFO_x###', _("Weitere Informationen zur Ebene x")),
            array('<!-- BEGIN SUBLEVEL_NO_INFO_x -->', ''),
            array('<!-- END SUBLEVEL_NO_INFO_x -->', ''),
            array('<!-- END NO_LINK_TO_COURSES_x -->', ''),
            array('<!-- BEGIN LINK_TO_COURSES_x -->', ''),
            array('###SUBLEVEL-HREF_x###', ''),
            array('###SUBLEVEL-HREF_SHOW_COURSES_x###', ''),
            array('###SUBLEVEL_NAME_x###', ''),
            array('###SUBLEVEL_COURSE_COUNT_x###', _("Anzahl der Veranstaltungen in der Ebene x (einschlie�lich Unterebenen)")),
            array('###SUBLEVEL_NO_x###', ''),
            array('<!-- END LINK_TO_COURSES_x -->', ''),
            array('<!-- END SUBLEVEL_x -->', ''),
            array('<!-- END SUBLEVELS_x -->', ''),
            array('<!-- END LEVEL_TREE -->', ''));

        $markers['TemplateResult'] = array(
            array('__GLOBAL__', _("Globale Variablen (g�ltig im gesamten Template).")),
            array('###COURSES_COUNT###', _("Anzahl der Veranstaltungen in der Liste")),
            array('###COURSES_SUBSTITUTE-GROUPED-BY###', _("Textersetzung f�r Gruppierungsart")),
            array('###COURSES_GROUPING###', _("Gruppierungsart")),
            array('###XLS_EXPORT-HREF###', _("URL zum Export der Veranstaltungsliste")),
            array('###GROUP_BY_TYPE-HREF###', _("URL f�r Gruppierung nach Typ")),
            array('###GROUP_BY_SEMESTER-HREF###', _("URL f�r Gruppierung nach Semester")),
            array('###GROUP_BY_RANGE-HREF###', _("URL f�r Gruppierung nach Studienbereich")),
            array('###GROUP_BY_LECTURER-HREF###', _("URL f�r Gruppierung nach Dozent")),
            array('###GROUP_BY_INSTITUTE-HREF###', _("URL f�r Gruppierung nach Einrichtung")),
            array('###CURRENT_RESULT_PAGE###', _("Nummer der Ergebnisseite der Suche")),
            array('###NUMBER_OF_RESULT_PAGES###', _("Anzahl der Ergenisseiten der Suche")),
            array('<!-- BEGIN NO_COURSES -->', _("Ausgabe, wenn keine Veranstaltungen gefunden wurden")),
            array('<!-- END NO_COURSES -->', ''),
            array('<!-- BEGIN RESULT -->', ''),
            array('<!-- BEGIN GROUP -->', ''),
            array('###GROUP_NAME###', ''),
            array('<!-- BEGIN NO_GROUP_NAME -->', _("Geben Sie einen Text ein, der Angezeigt wird, wenn Lehrveranstaltungen vorliegen, die keinem Bereich zugeordnet sind. Nur wirksam in Gruppierung nach Bereich.")),
            array('<!-- END NO_GROUP_NAME -->', ''),
            array('###GROUP_INFO###', _("Info-Text f�r Studienbereiche. Wird nur angezeigt bei Gruppierung nach Bereich.")),
            array('<!-- BEGIN NO_GROUP_INFO -->', _("Wird angezeigt, wenn kein Info-Text f�r Bereiche verf�gbar ist. Nur bei Gruppierung nach Bereich.")),
            array('<!-- END NO_GROUP_INFO -->', ''),
            array('###GROUP-NO###', _("Fortlaufende Gruppennummer")),
            array('<!-- BEGIN COURSE -->', ''),
            array('###TITLE###', ''),
            array('###COURSE_ID###', ''),
            array('###COURSEDETAILS-HREF###', ''),
            array('###SUBTITLE###', ''),
            array('###COURSE_NUMBER###', _("Die Veranstaltungsnummer")),
            array('###DESCRIPTION###', _("Feld Beschreibung der Veranstaltungsdaten")),
            array('###ECTS###', _("Feld ECTS der Veranstaltunsdaten")),
            array('<!-- BEGIN LECTURERS -->', ''),
            array('###FULLNAME###', ''),
            array('###LASTNAME###', ''),
            array('###FIRSTNAME###', ''),
            array('###TITLEFRONT###', ''),
            array('###TITLEREAR###', ''),
            array('###PERSONDETAILS-HREF###', ''),
            array('###LECTURER-NO###', ''),
            array('###UNAME###', _("Stud.IP-Username")),
            array('<!-- BEGIN LECTURER_DELIMITER -->', ''),
            array('<!-- END LECTURER_DELIMITER -->', ''),
            array('<!-- END LECTURERS -->', ''),
            array('<!-- BEGIN NO_LECTURERS -->', _("Wird ausgegeben, wenn keine Dozenten vorhanden sind.")),
            array('<!-- END NO_LECTURERS -->', ''),
            array('###FORM###', _("Die Veranstaltungsart")),
            array('###SEMTYPE###', ''),
            array('###SEMTYPE-SUBSTITUTE###', ''),
            array('###SEMESTER###', ''),
            array('###LOCATION###', _("Freie Raumangabe")),
            array('<!-- BEGIN DATES -->', ''),
            array('<!-- BEGIN REGULAR_DATES -->', ''),
            array('###TURNUS###', ''),
            array('<!-- BEGIN REGULAR_DATE -->', ''),
            array('###DAY_OF_WEEK###', ''),
            array('###START_TIME###', ''),
            array('###END_TIME###', ''),
            array('###START_WEEK###', ''),
            array('###CYCLE###', ''),
            array('###REGULAR_DESCRIPTION###', ''),
            array('<!-- BEGIN REGULAR_ROOMS -->', ''),
            array('<!-- BEGIN ROOMS -->', ''),
            array('###ROOM###', ''),
            array('<!-- BEGIN ROOMS_DELIMITER -->', ''),
            array('<!-- END ROOMS_DELIMITER -->', ''),
            array('<!-- END ROOMS -->', ''),
            array('<!-- BEGIN NO_ROOM -->', _("Wird ausgegeben, wenn kein Raum zum Termin angegeben ist.")),
            array('<!-- END NO_ROOM -->', ''),
            array('<!-- BEGIN FREE_ROOMS -->', ''),
            array('###FREE_ROOM###', ''),
            array('<!-- BEGIN FREE_ROOMS_DELIMITER -->', ''),
            array('<!-- END FREE_ROOMS_DELIMITER -->', ''),
            array('<!-- END FREE_ROOMS -->', ''),
        //  array('<!-- BEGIN NO_FREE_ROOM -->', _("Wird ausgegeben, wenn keine freie Raumangabe zum Termin angegeben ist")),
        //  array('<!-- END NO_FREE_ROOM -->', ''),
            array('<!-- END REGULAR_DATE -->', ''),
            array('<!-- END REGULAR_DATES -->', ''),
            array('<!-- END REGULAR_DATA -->', ''),
            array('<!-- BEGIN IRREGULAR_DATES -->', ''),
            array('<!-- BEGIN IRREGULAR_DATE -->', ''),
            array('###DAY_OF_WEEK###', ''),
            array('###START_TIME###', ''),
            array('###END_TIME###', ''),
            array('###DATE###', ''),
            array('###IRREGULAR_DESCRIPTION###', ''),
            array('###IRREGUALR_TYPE_MEETING###', _("Ausgabe des Namens des Termintyps, wenn Sitzungstermin")),
            array('###IRREGUALR_TYPE_OTHER###', _("Ausgabe des Namens des Termintyps, wenn kein Sitzungstermin")),
            array('###IRREGULAR_ROOM###', ''),
            array('<!-- BEGIN IRREGULAR_NO_ROOM -->', _("Wird ausgegeben, wenn kein Raum zum Termin angegeben ist")),
            array('<!-- END IRREGULAR_NO_ROOM -->', ''),
            array('<!-- BEGIN IRREGULAR_DELIMITER -->', ''),
            array('<!-- END IRREGULAR_DELIMITER -->', ''),
            array('<!-- END IRREGULAR_DATE -->', ''),
            array('<!-- END IRREGULAR_DATES -->',''),
            array('<!-- END DATES -->', ''),
            array('###CYCLE###', _("Kommaseparierte, zusammengefasste Temindaten")));
            $this->insertDatafieldMarkers('sem', $markers, 'TemplateResult');

        array_push($markers['TemplateResult'],
            array('<!-- END COURSE -->', ''),
            array('<!-- END GROUP -->', ''),
            array('<!-- BEGIN RESULT_BROWSER -->', ''),
            array('<!-- BEGIN RESULT_BROWSER_PAGES -->', ''),
            array('<!-- BEGIN RESULT_BROWSER_PAGE -->', ''),
            array('###RESULT_PAGE_NUMBER###', ''),
            array('###RESULT_PAGE-HREF###', ''),
            array('<!-- END RESULT_BROWSER_PAGE -->', ''),
            array('<!-- BEGIN RESULT_BROWSER_CURRENT_PAGE -->', ''),
            array('###RESULT_PAGE_NUMBER###', ''),
            array('###RESULT_PAGE-HREF###', ''),
            array('<!-- END RESULT_BROWSER_CURRENT_PAGE -->', ''),
            array('<!-- BEGIN RESULT_PAGE_DELIMITER -->', ''),
            array('<!-- END RESULT_PAGE_DELIMITER -->', ''),
            array('<!-- BEGIN RESULT_BROWSER_PAGES_SPLIT -->', ''),
            array('<!-- END RESULT_BROWSER_PAGES_SPLIT -->', ''),
            array('<!-- END RESULT_BROWSER_PAGES -->', ''),
            array('###RESULT_FIRST_PAGE-HREF###', ''),
            array('###RESULT_LAST_PAGE-HREF###', ''),
            array('###RESULT_FORWARD-HREF###', ''),
            array('###RESULT_BACKWARD-HREF###', ''),
            array('<!-- END RESULT_BROWSER -->', ''),
            array('<!-- END RESULT -->', ''));

        return $markers[$element_name];
    }

    function getContent ($args = null, $raw = false) {
        global $SEM_TYPE,$SEM_CLASS;

        $this->group_by_fields = array( array('name' => _("Semester"), 'group_field' => 'sem_number'),
                            array('name' => _("Bereich"), 'group_field' => 'bereich'),
                            array('name' => _("DozentIn"), 'group_field' => 'fullname', 'unique_field' => 'username'),
                            array('name' => _("Typ"), 'group_field' => 'status'),
                            array('name' => _("Einrichtung"), 'group_field' => 'Institut', 'unique_field' => 'Institut_id'));

        // initialise data
        $this->sem_browse_data = array(
            'start_item_id' => $this->getRootStartItemId(),
            'do_search' => '0',
            'type' => 'all',
            'sem' => 'all',
            'withkids' => '0',
            'show_result' => '0'
        );

        // Daten aus config �bernehmen
        $this->sem_browse_data['group_by'] = $this->config->getValue('Main', 'grouping');

        $level_change = $args['start_item_id'];

        $this->search_obj = new StudipSemSearchHelper(null, true);

        $semester = new SemesterData();
        $all_semester = $semester->getAllSemesterData();
        array_unshift($all_semester,0);

        $switch_time = mktime(0, 0, 0, date('m'), date('d') + 7 * $this->config->getValue('Main', 'semswitch'), date('Y'));

        // get current semester
        $current_sem = get_sem_num($switch_time) + 1;

        switch ($this->config->getValue('Main', 'semstart')) {
            case 'previous' :
                if (isset($all_semester[$current_sem - 1]))
                    $current_sem--;
                break;
            case 'next' :
                if (isset($all_semester[$current_sem + 1]))
                    $current_sem++;
                break;
            case 'current' :
                break;
            default :
                if (isset($all_semester[$this->config->getValue('Main', 'semstart')]))
                    $current_sem = $this->config->getValue('Main', 'semstart');
        }
        $this->sem_number = array($current_sem);
        $this->sem_browse_data['sem'] = $current_sem;
        $sem_classes = (array) $this->config->getValue('Main', 'semclasses');
        $sem_types_order = (array) $this->config->getValue('ReplaceTextSemType', 'order');
        $sem_types_visbility = (array) $this->config->getValue('ReplaceTextSemType', 'visibility');
        foreach ($sem_types_order as $type_id) {
            if ($sem_types_visbility[$type_id] && in_array($GLOBALS['SEM_TYPE'][$type_id]['class'], $sem_classes)) {
                $this->sem_browse_data['sem_status'][] = $type_id;
            }
        }

        $this->module_params = $this->getModuleParams($this->approved_params);
        if (!$this->module_params['reset_search']) {
            $this->sem_browse_data = array_merge($this->sem_browse_data, $this->module_params);
        }

        $sem_status = (is_array($this->sem_browse_data['sem_status'])) ? $this->sem_browse_data['sem_status'] : false;

        $params = $this->sem_browse_data;
        // delete array of semester data from the search object's parameters
        $params['sem_status'] = false;
        if ($this->config->getValue('Main', 'mode') == 'show_sem_range') {
            $params['scope_choose'] = $this->sem_browse_data['start_item_id'];
        } else {
            $params['range_choose'] = $this->sem_browse_data['start_item_id'];
        }

        if ($this->sem_browse_data['sem'] == 'all') {
            $this->sem_number = array_keys($all_semester);
        } else if (isset($this->sem_browse_data['sem'])) {
            $this->sem_number = array((int) $this->sem_browse_data['sem']);
        }
        // set params for search object
        $this->search_obj->setParams($params, true);

        if ($this->sem_browse_data['do_search'] == 1) {
            $this->search_obj->doSearch();
            $search_result = $this->search_obj->getSearchResultAsArray();
            if (count($search_result)) {
                $this->sem_browse_data['search_result'] = array_flip($search_result);
            } else {
                $this->sem_browse_data['search_result'] = array();
            }
            $this->sem_browse_data['show_result'] = '1';
            $this->sem_browse_data['show_entries'] = false;
        } else if ($this->config->getValue('Main', 'mode') == 'show_sem_range') {
            $this->get_sem_range($this->sem_browse_data['start_item_id'], $this->sem_browse_data['withkids'] == 1);
        } else { //($this->config->getValue('Main', 'mode') == 'show_sem_range_tree') {
            $this->get_sem_range_tree($this->sem_browse_data['start_item_id'], $this->sem_browse_data['withkids'] == 1);
        }

        $this->sem_dates = $all_semester;
        $this->sem_dates[0] = array('name' => sprintf(_("vor dem %s"),$this->sem_dates[1]['name']));

        // reorganize the $SEM_TYPE-array
        foreach ($GLOBALS['SEM_CLASS'] as $key_class => $class) {
            $i = 0;
            foreach ($GLOBALS['SEM_TYPE'] as $key_type => $type) {
                if ($type['class'] == $key_class) {
                    $i++;
                    $this->sem_types_position[$key_type] = $i;
                }
            }
        }

        if ($this->sem_browse_data['xls_export']) {
            $tmp_file = basename($this->createResultXls());
            if ($tmp_file) {
                ob_end_clean();
                header('Location: ' . getDownloadLink($tmp_file, _("ErgebnisVeranstaltungssuche.xls"), 4));
                page_close();
                die;
            }
        }

        $this->global_markers['URL_SEARCH_PARAMS'] = '';
        $search_params = $this->module_params;
        $param_key = 'ext_' . strtolower($this->name);
        foreach ($search_params as $key => $value) {
            $this->global_markers['URL_SEARCH_PARAMS'] .= "&{$param_key}[{$key}]=" . urlencode($value);
        }

        $this->global_markers['URL_PERSONDETAILS'] = $this->getLinkToModule('LinkInternPersondetails');
        $this->global_markers['URL_LECTUREDETAILS'] = $this->getLinkToModule('LinkInternLecturedetails');
        $this->global_markers['URL_LEVEL_NO_COURSES'] = $this->getLinkToModule('LinkInternTree');
        $this->global_markers['URL_LEVEL_COURSES'] = $this->getLinkToModule('LinkInternShowCourses');

        $this->global_markers['CURRENT_SEMESTER'] = ExternModule::ExtHtmlReady($all_semester[$this->sem_number[0]]['name']);

        if (trim($this->config->getValue('TemplateSimpleSearch', 'template'))) {
            $content['SEM_BROWSER']['SIMPLE_SEARCH'] = $this->elements['TemplateSimpleSearch']->toString(array('content' => $this->getContentSimpleSearch(), 'subpart' => 'SIMPLE_SEARCH'));
        }
        if (trim($this->config->getValue('TemplateExtendedSearch', 'template'))) {
            $content['SEM_BROWSER']['EXTENDED_SEARCH'] = $this->elements['TemplateExtendedSearch']->toString(array('content' => $this->getContentExtendedSearch(), 'subpart' => 'EXTENDED_SEARCH'));
        }
        if (trim($this->config->getValue('TemplateTree', 'template'))) {
            $content['SEM_BROWSER']['TREE'] = $this->elements['TemplateTree']->toString(array('content' => $this->getContentTree(), 'subpart' => 'TREE'));
        }
        if (trim($this->config->getValue('TemplateResult', 'template')) && $this->sem_browse_data['show_result'] == '1') {
            $content['SEM_BROWSER']['RESULT'] = $this->elements['TemplateResult']->toString(array('content' => $this->getContentResult(), 'subpart' => 'RESULT'));
        }
        // set super global markers
        $content['__GLOBAL__'] = $this->global_markers;
        return $content;
    }

    function get_sem_range ($item_id, $with_kids) {
        $tree_args = array();
        if (!is_object($this->sem_tree)) {
            $tree_args['sem_status'] = (is_array($this->sem_browse_data['sem_status'])) ? $this->sem_browse_data['sem_status'] : false;
            $tree_args['sem_number'] = $this->sem_number;
            $tree_args['visible_only'] = true;
            $this->sem_tree = TreeAbstract::GetInstance('StudipSemTree', $tree_args);
            $this->sem_tree->enable_lonely_sem = false;
        //  $this->sem_tree = new StudipSemTreeViewSimple($this->getRootStartItemId(), $this->sem_number, $sem_status, true);
        }
        $sem_ids = $this->sem_tree->getSemIds($item_id, $with_kids);

        if (is_array($sem_ids)){
            $this->sem_browse_data['search_result'] = array_flip($sem_ids);
        } else {
            $this->sem_browse_data['search_result'] = array();
        }
    }

    function get_sem_range_tree ($item_id, $with_kids) {
        $range_object = RangeTreeObject::GetInstance($item_id);
        if ($with_kids) {
            $inst_ids = $range_object->getAllObjectKids();
        }
        $inst_ids[] = $range_object->item_data['studip_object_id'];
        $db_view = new DbView();
        $db_view->params[0] = $inst_ids;
        $db_view->params[1] = ' AND c.visible=1';
        $db_view->params[1] .= (is_array($this->sem_browse_data['sem_status'])) ? " AND c.status IN('" . join("','",$this->sem_browse_data['sem_status']) ."')" : "";
        $db_view->params[2] = (is_array($this->sem_number)) ? " HAVING sem_number IN (" . join(",", $this->sem_number) .") OR (sem_number <= " . $this->sem_number[0] . "  AND (sem_number_end >= " . $this->sem_number[0] . " OR sem_number_end = -1)) " : '';
        $db_snap = new DbSnapshot($db_view->get_query("view:SEM_INST_GET_SEM"));
        if ($db_snap->numRows) {
            $sem_ids = $db_snap->getRows("Seminar_id");
            $this->sem_browse_data['search_result'] = array_flip($sem_ids);
        } else {
            $this->sem_browse_data['search_result'] = array();
        }
    }

    function getContentSimpleSearch () {
        $select_qs = '<select name="ext_templatesembrowse[qs_choose]" id="ext_templatesembrowse_qs_choose">';
        foreach (StudipSemSearchHelper::GetQuickSearchFields() as $key => $value) {
            if ($this->sem_browse_data['qs_choose'] == $key) {
                $select_qs .= "<option value=\"$key\" selected=\"selected\">$value</option>";
            } else {
                $select_qs .= "<option value=\"$key\">$value</option>";
            }
        }
        $select_qs .= '</select>';
        $content['SEARCH_FORM'] = array(
            'SELECT_FIELD' => $select_qs,
            'SELECT_SEMESTER' => $this->getSelectSem(),
            'INPUT_SEARCH_TERM' => '<input type="text" name="ext_templatesembrowse[quick_search]" id="ext_templatesembrowse_quick_search" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['quick_search'] ? $this->sem_browse_data['quick_search'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="50">',
            'SEARCHFORM_ACTION' => $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1'), true, 'LinkInternSearchForm'),
            'SEARCHFORM_ACTION_SELECT_SEM' => $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '0', 'show_result' => '1'), true, 'LinkInternSearchForm'),
            'HREF_RESET_SEARCH' => $this->getLinkToSelf(array('start_item_id' => $this->getRootStartItemId()))
        );

        return $content;
    }

    function getSelectSem () {
        $select_sem = '<select name="ext_templatesembrowse[sem]" id="ext_templatesembrowse_sem" size="1">';
        $semester = SemesterData::GetSemesterArray();
        $sem_options = array(array('name' =>_("alle"),'value' => 'all'));
        for ($i = count($semester) -1; $i >= 0; --$i) {
            $sem_options[] = array('name' => $semester[$i]['name'], 'value' => "$i");
        }
        foreach ($sem_options as $sem_option) {
            if ($this->sem_browse_data['sem'] == $sem_option['value']) {
                $select_sem .= "<option value=\"{$sem_option['value']}\" selected=\"selected\">" . ExternModule::ExtHtmlReady($sem_option['name']) . '</option>';
            } else {
                $select_sem .= "<option value=\"{$sem_option['value']}\">" . ExternModule::ExtHtmlReady($sem_option['name']) . '</option>';
            }
        }
        $select_sem .= '</select>';

        return $select_sem;
    }

    function getContentExtendedSearch () {
        $content['SEARCH_FORM']['INPUT_TITLE'] = '<input type="text" name="ext_templatesembrowse[title]" id="ext_templatesembrowse_title" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['title'] ? $this->sem_browse_data['title'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="150">';
        $content['SEARCH_FORM']['INPUT_SUBTITLE'] = '<input type="text" name="ext_templatesembrowse[sub_title]" id="ext_templatesembrowse_sub_title" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['sub_title'] ? $this->sem_browse_data['sub_title'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="150">';
        $content['SEARCH_FORM']['INPUT_NUMBER'] = '<input type="text" name="ext_templatesembrowse[number]" id="ext_templatesembrowse_number" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['number'] ? $this->sem_browse_data['number'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="50">';
        $content['SEARCH_FORM']['INPUT_COMMENT'] = '<input type="text" name="ext_templatesembrowse[comment]" id="ext_templatesembrowse_comment" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['comment'] ? $this->sem_browse_data['comment'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="150">';
        $content['SEARCH_FORM']['INPUT_LECTURER'] = '<input type="text" name="ext_templatesembrowse[lecturer]" id="ext_templatesembrowse_lecturer" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['lecturer'] ? $this->sem_browse_data['lecturer'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="150">';
        $content['SEARCH_FORM']['INPUT_SUBJECTAREAS'] = '<input type="text" name="ext_templatesembrowse[scope]" id="ext_templatesembrowsee_scope" value="' . ExternModule::ExtHtmlReady($this->sem_browse_data['scope'] ? $this->sem_browse_data['scope'] : '') . '" size="' . $this->config->getValue('Main', 'sizeinput') . '" maxlength="150">';
        $content['SEARCH_FORM']['SELECT_TYPE'] = $this->getSelectSemType();
        $content['SEARCH_FORM']['SELECT_SEMESTER'] = $this->getSelectSem();
        $content['SEARCH_FORM']['SELECT_COMBINATION'] = '<select name="ext_templatesembrowse[combination]" id="ext_templatesembrowse_combination" size="1">';
        $content['SEARCH_FORM']['SELECT_COMBINATION'] .= '<option value="AND">' . _("UND") . '</option>';
        $content['SEARCH_FORM']['SELECT_COMBINATION'] .= '<option value="OR"' . ($this->module_params['combination'] == 'OR' ? ' selected="selected"' : '') . '>' . _("ODER") . '</option></select>';
        $content['SEARCH_FORM']['SEARCHFORM_ACTION'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1', 'start' => '0'), true, 'LinkInternSearchForm');
        $content['SEARCH_FORM']['HREF_RESET_SEARCH'] = $this->getLinkToSelf(array('start_item_id' => $this->getRootStartItemId()));

        return $content;
    }

    function getSelectSemType () {
        $select = '<select name="ext_templatesembrowse[type]" id="ext_templatesembrowse_type" size="1">';
        $select .= '<option value="all"' . ($this->sem_browse_data['type'] == 'all' ? ' selected="selected"' : '') . '>' . _("alle") . '</option>';
        foreach ((array) $this->sem_browse_data['sem_status'] as $type_id) {
            $select .= '<option value="' .  $type_id;
            if ($this->sem_browse_data['type'] == $type_id) {
                $select .= '" selected="selected">';
            } else {
                $select .= '">';
            }
            $select .= ExternModule::ExtHtmlReady($GLOBALS['SEM_TYPE'][$type_id]['name'] .' (' . $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$type_id]['class']]['name']) . ')</option>';
        }
        return $select . '</select>';
    }

    function getContentTree () {
        $tree_args['sem_status'] = (is_array($this->sem_browse_data['sem_status'])) ? $this->sem_browse_data['sem_status'] : false;
        $tree_args['sem_number'] = $this->sem_number;
        $tree_args['visible_only'] = true;
        if ($this->config->getValue('Main', 'mode') == 'show_sem_range') {
            $tree = TreeAbstract::GetInstance('StudipSemTree', $tree_args);
        } else {
            $tree = TreeAbstract::GetInstance('StudipRangeTree', $tree_args);
        }
        $tree->enable_lonely_sem = false;
        $j = 0;
        if ($parents = $tree->getParents($this->sem_browse_data['start_item_id'])) {
            for ($i = count($parents) - 2; $i >= 0; --$i) {
                /*
                if ($tree->isModuleItem($parents[$i]) && $studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement')) {
                    $info = $studienmodulmanagement->getModuleDescription($parents[$i], SemesterData::GetSemesterIdByIndex($this->sem_browse_data['sem']));
                } else {
                */
                    if (trim($tree->tree_data[$parents[$i]]['info'])) {
                        $info = kill_format(trim($tree->tree_data[$parents[$i]]['info']));
                    } else {
                        $info = '';
                        $content['LEVEL_TREE']['LEVEL_PATH']['LEVEL_PATH_ITEM'][$j]['LEVEL_NO_INFO'] = true;
                    }
            //  }
                $content['LEVEL_TREE']['LEVEL_PATH']['LEVEL_PATH_ITEM'][$j] = array(
                        'LEVEL-HREF' => $this->getLinkToSelf(array('start_item_id' => $parents[$i], 'do_search' => '0', 'show_result' => (($parents[$i] == $this->getRootStartItemId()) ? '1' : '0')), true, 'LinkInternTree'),
                        'LEVEL_NAME' => ExternModule::ExtHtmlReady($tree->tree_data[$parents[$i]]['name']),
                        'LEVEL_INFO' => $info
                );
                $content['LEVEL_TREE']['LEVEL_PATH']['LEVEL_PATH_ITEM'][$j]['PATH_DELIMITER'] = true;
                $this->global_markers['TREE_LEVEL_NAME_' . ($j + 1)] = $content['LEVEL_TREE']['LEVEL_PATH']['LEVEL_PATH_ITEM'][$j]['LEVEL_NAME'];
                $this->global_markers['TREE_LEVEL_ID_' . ($j + 1)] = $parents[$i];
                $j++;
            }
            if ($j) {
                // remove last path delimiter
                unset($content['LEVEL_TREE']['LEVEL_PATH']['LEVEL_PATH_ITEM'][$j - 1]['PATH_DELIMITER']);
            }
            // set this as global marker in getContent()
            $this->global_markers['CURRENT_LEVEL_NAME'] = $tree->getValue($this->sem_browse_data['start_item_id'], 'name');
            /*
            if ($tree->isModuleItem($parents[$i]) && $studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement')) {
                $this->global_markers['CURRENT_LEVEL_NAME'] = $studienmodulmanagement->getModuleDescription($parents[$i], SemesterData::GetSemesterIdByIndex($this->sem_browse_data['sem']));
            } else {
            */
                if (trim($tree->tree_data[$this->sem_browse_data['start_item_id']]['info'])) {
                    $this->global_markers['CURRENT_LEVEL_INFO'] = ExternModule::ExtFormatReady($tree->tree_data[$this->sem_browse_data['start_item_id']]['info']);
                }
        //  }
        }

        $content['LEVEL_TREE']['LEVEL_PATH']['LEVEL_PATH_ITEM'][$j] = array(
                'LEVEL-HREF' => $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '0', 'show_result' => (($parents[$i] == $this->getRootStartItemId()) ? '1' : '0')), true, 'LinkInternTree'),
                'LEVEL_NAME' => ExternModule::ExtHtmlReady($tree->tree_data[$this->sem_browse_data['start_item_id']]['name']),
                'LEVEL_INFO' => kill_format(($tree->tree_data[$this->sem_browse_data['start_item_id']]['info']) ? $tree->tree_data[$this->sem_browse_data['start_item_id']]['info'] :  _("Keine weitere Info vorhanden"))
        );

        $content['LEVEL_TREE']['SUBLEVELS_1'] = $this->getAllTreeLevelContent($tree, $this->sem_browse_data['start_item_id'], ($this->config->getValue('Main', 'countshowsublevels') ? $this->config->getValue('Main', 'countshowsublevels') : 0));

        if ($tree->hasKids($this->sem_browse_data['start_item_id']) && ($num_entries = $tree->getNumEntries($this->sem_browse_data['start_item_id'], true))) {
            $content['__GLOBAL__']['COURSE_COUNT_SUBLEVELS'] = $num_entries;
            $content['__GLOBAL__']['COURSES_SUBLEVELS-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'show_result' => '1', 'withkids' => '1', 'do_search' => '0'), true, 'LinkInternTree');
        }

        if ($num_entries = $tree->getNumEntries($this->sem_browse_data['start_item_id'])) {
            $content['__GLOBAL__']['COURSE_COUNT_LEVEL'] = $num_entries;
            $content['__GLOBAL__']['COURSES_LEVEL-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'show_result' => '1', 'withkids' => '0', 'do_search' => '0'), true, 'LinkInternTree');
        } else {
            $content['__GLOBAL__']['NO_COURSES_LEVEL'] = true;
        }

        return $content;
    }


    function getAllTreeLevelContent (&$tree, $start_item_id, $max_level, $level = 0) {
        if (($num_kids = $tree->getNumKids($start_item_id)) && $level <= $max_level) {
            $level++;
            if ($this->config->getValue('SelectSubjectAreas', 'selectallsubjectareas')) {
                $kids = $tree->getKids($start_item_id);
            } else if (is_array($this->config->getValue('SelectSubjectAreas', 'subjectareasselected'))) {
                if ($this->config->getValue('SelectSubjectAreas', 'reverseselection')) {
                    $kids = array_diff($tree->getKids($start_item_id), $this->config->getValue('SelectSubjectAreas', 'subjectareasselected'));
                } else {
                    $kids = array_intersect($tree->getKids($start_item_id), $this->config->getValue('SelectSubjectAreas', 'subjectareasselected'));
                }
            } else {
                return false;
            }
            $count = 0;
            foreach ($kids as $kid) {
                $num_entries = $tree->getNumEntries($kid, true);
                if (!($this->config->getValue('Main', 'disableemptylevels') && $num_entries == 0)) {
                    /*
                    if ($tree->isModuleItem($kid) && $studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement')) {
                        $info = 'An dieser Stelle nicht verwendet';
                    } else {
                    */
                        if (trim($tree->tree_data[$kid]['info'])) {
                            $info = kill_format(trim($tree->tree_data[$kid]['info']));
                        } else {
                            $info = '';
                            $content[$count]['SUBLEVEL' . $level]['SUBLEVEL_NO_INFO_' . $level] = true;
                        }
                //  }
                    $level_content = array(
                            'SUBLEVEL_NAME_' . $level => ExternModule::ExtHtmlReady($tree->tree_data[$kid]['name']),
                            'SUBLEVEL_COURSE_COUNT_' . $level => $num_entries,
                            'SUBLEVEL_NO_' . $level => $count + 1,
                            'SUBLEVEL_INFO_' . $level => $info
                    );
                    $content['SUBLEVEL_' . $level][$count]['SUBLEVEL_RESULT_' . $level] = $this->elements['TemplateResult']->toString(array('content' => $this->getContentResult($kid), 'subpart' => 'RESULT'));
                    if ($this->config->getValue('LinkInternShowCourses', 'config') && $tree->getNumEntries($kid, false)) {
                        $content['SUBLEVEL_' . $level][$count]['LINK_TO_COURSES_' . $level] = $level_content;
                        $content['SUBLEVEL_' . $level][$count]['LINK_TO_COURSES_' . $level]['SUBLEVEL-HREF_SHOW_COURSES_' . $level] = $this->getLinkToSelf(array('start_item_id' => $kid, 'show_result' => '1', 'withkids' => '1', 'do_search' => '0'), true, 'LinkInternShowCourses');
                        $content['SUBLEVEL_' . $level][$count]['NO_LINK_TO_COURSES_' . $level]['SUBLEVEL-HREF_' . $level] = $this->getLinkToSelf(array('start_item_id' => $kid, 'show_result' => '1', 'withkids' => '1', 'do_search' => '0'), true, 'LinkInternTree');
                        $content['SUBLEVEL_' . $level][$count]['NO_LINK_TO_COURSES_' . $level] = false;
                    } else {
                        $content['SUBLEVEL_' . $level][$count]['NO_LINK_TO_COURSES_' . $level] = $level_content;
                        $content['SUBLEVEL_' . $level][$count]['NO_LINK_TO_COURSES_' . $level]['SUBLEVEL-HREF_' . $level] = $this->getLinkToSelf(array('start_item_id' => $kid, 'show_result' => '1', 'withkids' => '1', 'do_search' => '0'), true, 'LinkInternTree');
                        $content['SUBLEVEL_' . $level][$count]['LINK_TO_COURSES_' . $level] = false;
                    }
                    if ($sublevel = $this->getAllTreeLevelContent($tree, $kid, $max_level, $level)) {
                        $content['SUBLEVEL_' . $level][$count]['SUBLEVELS_' . ($level + 1)] = $sublevel;
                    }
                    $count++;
                }
            }
            return $content;
        }
        return false;
    }

    function getContentResult ($level_id = null) {
        global $_fullname_sql, $SEM_TYPE, $SEM_CLASS;
        $content = null;
        if (is_array($this->sem_browse_data['search_result']) && count($this->sem_browse_data['search_result'])) {
            list($group_by_data, $sem_data) = $this->getResult($level_id);
            if (count($sem_data)) {
                $content['__GLOBAL__']['COURSES_COUNT'] = count($sem_data);
                $content['__GLOBAL__']['COURSES_GROUPING'] = $this->group_by_fields[$this->sem_browse_data['group_by']]['name'];
                $group_by_name = $this->config->getValue('Main', 'aliasesgrouping');
                $content['__GLOBAL__']['COURSES_SUBSTITUTE-GROUPED-BY'] = $group_by_name[$this->sem_browse_data['group_by']];
                $content['__GLOBAL__']['XLS_EXPORT-HREF'] = $this->getLinkToSelf(array('xls_export' => '1'), true);
                $content['__GLOBAL__']['GROUP_BY_TYPE-HREF'] = $this->getLinkToSelf(array('group_by' => '3'), true);
                $content['__GLOBAL__']['GROUP_BY_SEMESTER-HREF'] = $this->getLinkToSelf(array('group_by' => '0'), true);
                $content['__GLOBAL__']['GROUP_BY_RANGE-HREF'] = $this->getLinkToSelf(array('group_by' => '1'), true);
                $content['__GLOBAL__']['GROUP_BY_LECTURER-HREF'] = $this->getLinkToSelf(array('group_by' => '2'), true);
                $content['__GLOBAL__']['GROUP_BY_INSTITUTE-HREF'] = $this->getLinkToSelf(array('group_by' => '4'), true);
                $content['__GLOBAL__'] = array_merge($content['__GLOBAL__'], $this->global_markers);
                $j = 0;
                $semester = SemesterData::GetSemesterArray();
                foreach ($group_by_data as $group_field => $sem_ids) {
                    switch ($this->sem_browse_data['group_by']) {
                        case 0:
                            ExternModule::ExtHtmlReady($content['RESULT']['GROUP'][$j]['GROUP_NAME'] = $semester[$group_field]['name']);
                        break;
                        case 1:
                            if (!is_object($this->sem_tree)) {
                                $this->sem_tree = TreeAbstract::GetInstance("StudipSemTree");
                            }
                            if ($this->sem_tree->tree_data[$group_field]) {
                                $range_path_level = $this->config->getValue('Main', 'rangepathlevel');
                                $content['RESULT']['GROUP'][$j]['GROUP_NAME'] = ExternModule::ExtHtmlReady($this->sem_tree->getShortPath($group_field, NULL, '>', $range_path_level ? $range_path_level - 1 : 0));
                                /*
                                if ($this->sem_tree->isModuleItem($group_field) && $studienmodulmanagement = PluginEngine::getPlugin('StudienmodulManagement')) {
                                    $content['RESULT']['GROUP'][$j]['GROUP_INFO'] = $studienmodulmanagement->getModuleDescription($group_field, SemesterData::GetSemesterIdByIndex($this->sem_browse_data['sem']));
                                } else {
                                */
                                    $content['RESULT']['GROUP'][$j]['NO_GROUP_INFO'] = true;
                            //  }
                            } else {
                                $content['RESULT']['GROUP'][$j]['NO_GROUP_NAME'] = true;
                            }
                        break;
                        case 3:
                            $aliases_sem_type = $this->config->getValue('ReplaceTextSemType', "class_{$SEM_TYPE[$group_field]['class']}");
                            if ($aliases_sem_type[$this->sem_types_position[$group_field] - 1]) {
                                $content['RESULT']['GROUP'][$j]['GROUP_NAME'] = $aliases_sem_type[$this->sem_types_position[$group_field] - 1];
                            } else {
                                $content['RESULT']['GROUP'][$j]['GROUP_NAME'] = ExternModule::ExtHtmlReady($SEM_TYPE[$group_field]['name'].' ('. $SEM_CLASS[$SEM_TYPE[$group_field]['class']]['name'].')');
                            }
                        break;
                        default:
                            $content['RESULT']['GROUP'][$j]['GROUP_NAME'] = ExternModule::ExtHtmlReady($group_field);
                    }
                    $content['RESULT']['GROUP'][$j]['GROUP-NO'] = $j + 1;

                    if (is_array($sem_ids['Seminar_id'])) {
                        $k = 0;
                        $semester = SemesterData::GetSemesterArray();
                        while(list($seminar_id, ) = each($sem_ids['Seminar_id'])) {
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['COURSE_ID'] = $seminar_id;
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['TITLE'] = ExternModule::ExtHtmlReady(key($sem_data[$seminar_id]['Name']));
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['COURSE-NO'] = $k + 1;
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['COURSEDETAILS-HREF'] = $this->elements['LinkInternLecturedetails']->createUrl(array('link_args' => 'seminar_id=' . $seminar_id));
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['COURSE_NUMBER'] = ExternModule::ExtHtmlReady(key($sem_data[$seminar_id]['VeranstaltungsNummer']));

                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['DESCRIPTION'] = ExternModule::ExtHtmlReady(key($sem_data[$seminar_id]['Beschreibung']), true);

                            $sem_number_start = key($sem_data[$seminar_id]["sem_number"]);
                            $sem_number_end = key($sem_data[$seminar_id]["sem_number_end"]);
                            if ($sem_number_start != $sem_number_end) {
                                $sem_name = $semester[$sem_number_start]['name'] . " - ";
                                $sem_name .= (($sem_number_end == -1) ? _("unbegrenzt") : $semester[$sem_number_end]['name']);
                            } else {
                                $sem_name = $semester[$sem_number_start]['name'];
                            }
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['SEMESTER'] = ExternModule::ExtHtmlReady($sem_name);

                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['DATES'] = $this->getDates($seminar_id, $semester[$this->sem_browse_data['sem']]['beginn'], $semester[$this->sem_browse_data['sem']]['ende']);
                            if (!sizeof($content['RESULT']['GROUP'][$j]['COURSE'][$k]['DATES'])) {
                                $content['RESULT']['GROUP'][$j]['COURSE'][$k]['NO_DATES_TEXT'] = array();
                            }

                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['SUBTITLE'] = ExternModule::ExtHtmlReady(key($sem_data[$seminar_id]['Untertitel']));
                            $aliases_sem_type = $this->config->getValue('ReplaceTextSemType', 'class_' . $SEM_TYPE[key($sem_data[$seminar_id]['status'])]['class']);
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['SEMTYPE-SUBSTITUTE'] = $aliases_sem_type[$this->sem_types_position[key($sem_data[$seminar_id]['status'])] - 1];
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['SEMTYPE'] = ExternModule::ExtHtmlReady($SEM_TYPE[key($sem_data[$seminar_id]['status'])]['name']
                                        .' ('. $SEM_CLASS[$SEM_TYPE[key($sem_data[$seminar_id]['status'])]['class']]['name'] . ')');
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LOCATION'] = ExternModule::ExtHtmlReady(trim(key($sem_data[$seminar_id]['Ort'])));
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['FORM'] = ExternModule::ExtHtmlReady(key($sem_data[$seminar_id]['art']));
                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['ECTS'] = ExternModule::ExtHtmlReady(key($sem_data[$seminar_id]['ects']));

                            // generic data fields
                            $generic_datafields = $this->config->getValue('TemplateResult', 'genericdatafields');
                            if (is_array($generic_datafields)) {
                                $localEntries = DataFieldEntry::getDataFieldEntries($seminar_id, 'sem', $SEM_TYPE[key($sem_data[$seminar_id]['status'])]['class']);
                                $m = 1;
                                foreach ($generic_datafields as $datafield) {
                                    if (isset($localEntries[$datafield]) && is_object($localEntries[$datafield])) {
                                        if ($localEntries[$datafield]->getType() == 'link') {
                                            $localEntry = ExternModule::extHtmlReady($localEntries[$datafield]->getValue());
                                        } else {
                                            $localEntry = $localEntries[$datafield]->getDisplayValue();
                                        }
                                        if ($localEntry) {
                                            $content['RESULT']['GROUP'][$j]['COURSE'][$k]['DATAFIELD_' . $m] = $localEntry;
                                        }
                                    }
                                    $m++;
                                }
                            }

                            $doz_name = array_keys($sem_data[$seminar_id]['fullname']);
                            $doz_uname = array_keys($sem_data[$seminar_id]['username']);
                            $doz_lastname = array_keys($sem_data[$seminar_id]['Nachname']);
                            $doz_firstname = array_keys($sem_data[$seminar_id]['Vorname']);
                            $doz_titlefront = array_keys($sem_data[$seminar_id]['title_front']);
                            $doz_titlerear = array_keys($sem_data[$seminar_id]['title_rear']);
                            $doz_position = array_keys($sem_data[$seminar_id]['position']);
                            if (is_array($doz_name)) {
                                if (count($doz_position) != count($doz_uname)) {
                                    $doz_position = range(1, count($doz_uname));
                                }
                                array_multisort($doz_position, $doz_name, $doz_uname);
                                $l = 0;
                                foreach ($doz_name as $index => $value) {
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['UNAME'] = $doz_uname[$index];
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['PERSONDETAILS-HREF'] = $this->elements['LinkInternPersondetails']->createUrl(array('link_args' => 'username=' . $doz_uname[$index] . '&seminar_id=' . $seminar_id));
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['FULLNAME'] = ExternModule::ExtHtmlReady($doz_name[$index]);
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['LASTNAME'] = ExternModule::ExtHtmlReady($doz_lastname[$index]);
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['FIRSTNAME'] = ExternModule::ExtHtmlReady($doz_firstname[$index]);
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['TITLEFRONT'] = ExternModule::ExtHtmlReady($doz_titlefront[$index]);
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['TITLEREAR'] = ExternModule::ExtHtmlReady($doz_titlerear[$index]);
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['LECTURER-NO'] = $l + 1;
                                    $content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l]['LECTURER_DELIMITER'] = true;
                                    $l++;
                                }
                                // remove last delimiter
                                unset($content['RESULT']['GROUP'][$j]['COURSE'][$k]['LECTURERS'][$l - 1]['LECTURER_DELIMITER']);
                            } else {
                                $content['RESULT']['GROUP'][$j]['COURSE'][$k]['NO_LECTURERS'] = true;
                            }
                            $k++;
                        }
                    }
                    $j++;
                }
                if ($this->config->getValue('Main', 'maxnumberofhits')) {
                    array_push($content['RESULT'], $this->getResultBrowser());
                }
            } else {
                $content['__GLOBAL__']['NO_COURSES'] = true;
            }
        } else {
            $content['__GLOBAL__']['NO_COURSES'] = true;
        }
        return $content;
    }

    /**
     * Generates markers and subparts for a result browser.
     *
     * @return array Array with markers and their values
     */
    function getResultBrowser()
    {
        $result_pages = ceil(sizeof($this->sem_browse_data['search_result']) / $this->config->getValue('Main', 'maxnumberofhits'));
        // only one page no result browser needed
        if ($result_pages < 2) {
            return '';
        }
        $this->global_markers['NUMBER_OF_RESULT_PAGES'] = $result_pages;
        $page_split = $result_pages;
        if ($result_pages > $this->config->getValue('Main', 'maxpagesresultbrowser')) {
            $page_split = ceil($this->config->getValue('Main', 'maxpagesresultbrowser') / 2);
        }
        $start_page = ceil($this->module_params['start']) /  $this->config->getValue('Main', 'maxnumberofhits');
        $start_page -= (($start_page < $page_split) ? ($start_page % ($page_split)) : 0);

        if ($start_page > abs($result_pages - $this->config->getValue('Main', 'maxpagesresultbrowser'))) {
            $start_page = $result_pages - $this->config->getValue('Main', 'maxpagesresultbrowser');
        }

        $splitted = false;
        $i = $start_page;
        while ($i < $result_pages) {
            if ($i < $page_split + $start_page + 1 || $i > ($result_pages - $page_split)) {
                if ($this->module_params['start'] == $i * $this->config->getValue('Main', 'maxnumberofhits')) {
                    $subpart_name = 'RESULT_BROWSER_CURRENT_PAGE';
                    $this->global_markers['CURRENT_RESULT_PAGE'] = $i + 1;
                } else {
                    $subpart_name = 'RESULT_BROWSER_PAGE';
                }
                $content['RESULT_BROWSER']['RESULT_BROWSER_PAGES'][$i][$subpart_name]['RESULT_PAGE_NUMBER'] = $i + 1;
                $content['RESULT_BROWSER']['RESULT_BROWSER_PAGES'][$i][$subpart_name]['RESULT_PAGE-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1', 'start' => $i * $this->config->getValue('Main', 'maxnumberofhits')), true, 'LinkInternSearchForm');
                $content['RESULT_BROWSER']['RESULT_BROWSER_PAGES'][$i]['RESULT_PAGE_DELIMITER'] = true;
            } else {
                if (!$splitted) {
                    $content['RESULT_BROWSER']['RESULT_BROWSER_PAGES'][$i]['RESULT_BROWSER_PAGES_SPLIT'] = true;
                    $splitted = true;
                }
            }
            $i++;
        }
        unset($content['RESULT_BROWSER']['RESULT_BROWSER_PAGES'][$i]['RESULT_PAGE_DELIMITER']);
        $start = ceil($this->module_params['start'] / $this->config->getValue('Main', 'maxnumberofhits') + 1) * $this->config->getValue('Main', 'maxnumberofhits');
        if ($start < sizeof($this->sem_browse_data['search_result'])) {
            $content['RESULT_BROWSER']['RESULT_FORWARD-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1', 'start' => $start), true, 'LinkInternSearchForm');
            $content['RESULT_BROWSER']['RESULT_LAST_PAGE-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1', 'start' => floor(sizeof($this->sem_browse_data['search_result']) / $this->config->getValue('Main', 'maxnumberofhits')) * $this->config->getValue('Main', 'maxnumberofhits')), true, 'LinkInternSearchForm');
        }
        $start = ceil($this->module_params['start'] / $this->config->getValue('Main', 'maxnumberofhits') - 1) * $this->config->getValue('Main', 'maxnumberofhits');
        if ($start >= 0) {
            $content['RESULT_BROWSER']['RESULT_BACKWARD-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1', 'start' => $start), true, 'LinkInternSearchForm');
            $content['RESULT_BROWSER']['RESULT_FIRST_PAGE-HREF'] = $this->getLinkToSelf(array('start_item_id' => $this->sem_browse_data['start_item_id'], 'do_search' => '1', 'start' => '0'), true, 'LinkInternSearchForm');
        }

        return $content;
    }

    function getDates ($seminar_id, $start_time = 0, $end_time = 0) {
        $dow_array = array(_("So"), _("Mo"), _("Di"), _("Mi"), _("Do"), _("Fr"), _("Sa"));
        $cycles_array = array(_("w�chentlich"), _("zweiw�chentlich"), _("dreiw�chentlich"));

        $cont = array();
        // irregular dates
        $meta = new MetaDate($seminar_id);
        if ($meta->getTurnus() == 1) {
            $cont['REGULAR_DATES']['TURNUS'] = true;
        }
        if ($meta->getStartWoche()) {
            $cont['REGULAR_DATES']['START_WEEK'] = $meta->getStartWoche();
        }

        //$cont['REGULAR_TYPE'] = $GLOBALS['TERMIN_TYP'][$meta->getArt()]['name'];
        $i = 0;

        $cycle_data = $meta->getCycleData();

        foreach ($cycle_data as $metadate_id => $cycle) {
            $cont['REGULAR_DATES']['REGULAR_DATE'][$i] = array(
                'DAY_OF_WEEK' => $dow_array[$cycle['day']],
                'START_TIME' => sprintf('%02d:%02d', $cycle['start_hour'], $cycle['start_minute']),
                'END_TIME' => sprintf('%02d:%02d', $cycle['end_hour'], $cycle['end_minute']),
                'START_WEEK' => $cycle['week_offset'] + 1,
                'CYCLE' => $cycles_array[(int)$cycle['cycle']],
                'REGULAR_DESCRIPTION' => ExternModule::ExtHtmlReady(trim($cycle['desc'])),
                'REGULAR_DELIMITER' => true);
            $k = 0;
            if ($GLOBALS['RESOURCES_ENABLE']) {
                if (($resource_ids = CycleDataDB::getPredominantRoomDB($metadate_id, $start_time, $end_time)) !== false) {
                    foreach ($resource_ids as $resource_id => $foo) {
                        $cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['ROOMS'][$k]['ROOM'] = ExternModule::ExtHtmlReady(trim(ResourceObject::Factory($resource_id)->getName()));
                        $cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['ROOMS'][$k]['ROOMS_DELIMITER'] = true;
                        $k++;
                    }
                    unset($cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['ROOMS'][$k - 1]['ROOMS_DELIMITER']);
                }
            }
            if (!$k) {
                if (($free_rooms = CycleDataDB::getFreeTextPredominantRoomDB($metadate_id, $start_time, $end_time)) !== false) {
                    foreach ($free_rooms as $free_room => $foo) {
                        $cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['FREE_ROOMS'][$k]['FREE_ROOM'] = ExternModule::ExtHtmlReady(trim($free_room));
                        $cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['FREE_ROOMS'][$k]['FREE_ROOMS_DELIMITER'] = true;
                        $k++;
                    }
                    unset($cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['FREE_ROOMS'][$k - 1]['FREE_ROOMS_DELIMITER']);
                } else {
                    $cont['REGULAR_DATES']['REGULAR_DATE'][$i]['NO_ROOM'] = true;
                }
            }
    //      if (!$k) {
        //      $cont['REGULAR_DATES']['REGULAR_DATE'][$i]['REGULAR_ROOMS']['NO_FREE_ROOM'] = true;
            //}
            $i++;
        }
        // remove last delimiter
        if ($i) {
            unset($cont['REGULAR_DATES']['REGULAR_DATE'][$i - 1]['REGULAR_DELIMITER']);
        }
        // regular dates
        if ($start_time && $end_time) {
            $dates = SeminarDB::getSingleDates($seminar_id, $start_time, $end_time);
        } else {
            $dates = array();
        }
        $i = 0;
        $selected_types = $this->config->getValue('Main', 'selectedeventtypes');

        foreach ($dates as $date) {
            if (in_array('all', $selected_types) || (in_array('meeting', $selected_types) && $GLOBALS['TERMIN_TYP'][$date['date_typ']]['sitzung']) || (in_array('other', $selected_types) && !$GLOBALS['TERMIN_TYP'][$date['date_typ']]['sitzung']) || in_array($date['date_typ'], $selected_types)) {
                $cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i] = array(
                    'DAY_OF_WEEK' => $dow_array[date('w', $date['date'])],
                    'START_TIME' => date('H:i', $date['date']),
                    'END_TIME' => date('H:i', $date['end_time']),
                    'DATE' => date('d.m.y', $date['date']),
                    'IRREGULAR_DESCRIPTION' => ExternModule::ExtHtmlReady(trim($date['description'])),
                    'IRREGULAR_DELIMITER' => true);
                if ($GLOBALS['TERMIN_TYP'][$date['date_typ']]['sitzung']) {
                    $cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i]['IRREGULAR_TYPE_MEETING'] = $GLOBALS['TERMIN_TYP'][$date['date_typ']]['name'];
                } else {
                    $cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i]['IRREGULAR_TYPE_OTHER'] = $GLOBALS['TERMIN_TYP'][$date['date_typ']]['name'];
                }
                if ($GLOBALS['RESOURCES_ENABLE'] && $date['resource_id']) {
                    $cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i]['IRREGULAR_ROOM'] = ExternModule::ExtHtmlReady(trim(ResourceObject::Factory($date['resource_id'])->getName()));
                } else if (trim($date['raum'])) {
                    $cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i]['IRREGULAR_ROOM'] = ExternModule::ExtHtmlReady(trim($date['raum']));
                } else {
                    $cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i]['IRREGULAR_NO_ROOM'] = true;
                }
            }
            $i++;
        }
        // remove last delimiter
        if ($i) {
            unset($cont['IRREGULAR_DATES']['IRREGULAR_DATE'][$i - 1]['IRREGULAR_DELIMITER']);
        }
        return $cont;
    }


    function getResult ($level_id = null) {
        global $_fullname_sql,$SEM_TYPE,$SEM_CLASS;
        $add_fields = '';
        $add_query = '';
        $sem_tree_query = '';
        $limit_sql = '';
        $orderby_field = ($this->config->getValue('Main', 'resultorderby') ? $this->config->getValue('Main', 'resultorderby') : 'VeranstaltungsNummer');
        if ($this->sem_browse_data['group_by'] == 1
            || (sizeof($this->config->getValue('SelectSubjectAreas', 'subjectareasselected'))
            && !($this->config->getValue('SelectSubjectAreas', 'selectallsubjectareas') || $this->sem_browse_data['start_item_id'] == 'root'))) {
            if ($this->config->getValue('Main', 'mode') == 'show_sem_range' &&  $this->sem_browse_data['start_item_id'] != 'root') {
                $allowed_ranges = array();
                if (is_null($level_id)) {
                    if (!is_object($this->sem_tree)){
                        $this->sem_tree = TreeAbstract::GetInstance('StudipSemTree');
                    }
                    if ($kids = $this->sem_tree->getKidsKids($this->sem_browse_data['start_item_id'])) {
                        $allowed_ranges = $kids;
                    }
                    $allowed_ranges[] = $this->sem_browse_data['start_item_id'];
                } else {
                    $allowed_ranges[] = $level_id;
                }

                if ($this->config->getValue('SelectSubjectAreas', 'selectallsubjectareas')) {
                    $sem_tree_query = " AND sem_tree_id IN('" . join("','", $allowed_ranges) . "') ";
                } elseif (is_array($this->config->getValue('SelectSubjectAreas', 'subjectareasselected'))) {
                    if ($this->config->getValue('SelectSubjectAreas', 'reverseselection')) {
                        $allowed_ranges = array_diff($allowed_ranges, $this->config->getValue('SelectSubjectAreas', 'subjectareasselected'));
                    } else {
                        $allowed_ranges = array_intersect($allowed_ranges, $this->config->getValue('SelectSubjectAreas', 'subjectareasselected'));
                    }
                    $sem_tree_query = " AND sem_tree_id IN('" . join("','", $allowed_ranges) . "') ";
                } else {
                    return array(array(), array());
                }
            }
            $add_fields = 'seminar_sem_tree.sem_tree_id AS bereich,';
            $add_query = "LEFT JOIN seminar_sem_tree ON (seminare.Seminar_id = seminar_sem_tree.seminar_id)";
        } else if ($this->config->getValue('Main', 'maxnumberofhits')) {
                $limit_sql = ' ORDER BY sem_number DESC, ' . $orderby_field . ' ASC LIMIT ' . ($this->module_params['start'] ? intval($this->module_params['start']) : '0') . ',' . $this->config->getValue('Main', 'maxnumberofhits');
        }
        if ($this->sem_browse_data['group_by'] == 4) {
            $add_fields = 'Institute.Name AS Institut,Institute.Institut_id,';
            $add_query = 'LEFT JOIN seminar_inst ON (seminare.Seminar_id = seminar_inst.Seminar_id)
            LEFT JOIN Institute ON (Institute.Institut_id = seminar_inst.institut_id)';
        }
        // show only selected SemTypes
        $selected_semtypes = $this->config->getValue('ReplaceTextSemType', 'visibility');
        $sem_types_array = array();
        if (count($selected_semtypes)) {
            for ($i = 0; $i < count($selected_semtypes); $i++) {
                if ($selected_semtypes[$i] == '1') {
                    $sem_types_array[] = $i + 1;
                }
            }
            $sem_types_query = "AND seminare.status IN ('" . implode("','", $sem_types_array) . "')";
        } else {
            $sem_types_query = '';
        }

        // participated institutes (or show only courses located at this faculty)
        /*
        $sem_inst_query = '';
        if (!$this->config->getValue('Main', 'allseminars')) {
            $tree = TreeAbstract::GetInstance('StudipRangeTree');
            $kidskids = $tree->getKidsKids($this->sem_browse_data['start_item_id']);
            $institute_ids = array($tree->tree_data[$this->sem_browse_data['start_item_id']]['studip_object_id']);
            foreach ($kidskids as $kid) {
                $institute_ids[] = $tree->tree_data[$kid]['studip_object_id'];
            }
            $sem_inst_query = " AND seminare.Institut_id IN ('" . join("','", $institute_ids) . "')";
        }
        */

        if (!$nameformat = $this->config->getValue('Main', 'nameformat')) {
            $nameformat = 'full_rev';
        }
        $dbv = new DbView();
        $query = "SELECT seminare.Seminar_id, VeranstaltungsNummer, seminare.status, seminare.Untertitel, seminare.Ort, seminare.art, seminare.Beschreibung, seminare.ects, IF(seminare.visible=0,CONCAT(seminare.Name, ' ". _("(versteckt)") ."'), seminare.Name) AS Name,
                $add_fields" . $_fullname_sql[$nameformat] ." AS fullname, auth_user_md5.username, title_front, title_rear, Vorname, Nachname,
                " . $dbv->sem_number_sql . " AS sem_number, " . $dbv->sem_number_end_sql . " AS sem_number_end, seminar_user.position AS position FROM seminare
                LEFT JOIN seminar_user ON (seminare.Seminar_id=seminar_user.Seminar_id AND seminar_user.status='dozent')
                LEFT JOIN auth_user_md5 USING (user_id)
                LEFT JOIN user_info USING (user_id)
                $add_query
                WHERE seminare.Seminar_id IN('" . join("','", array_keys($this->sem_browse_data['search_result'])) . "') $sem_types_query $sem_inst_query $sem_tree_query $limit_sql";
        $db = new DB_Seminar($query);
        if (!$db->num_rows()) {
            return array(array(), array());
        }
        $snap = new DbSnapshot($db);
        $group_field = $this->group_by_fields[$this->sem_browse_data['group_by']]['group_field'];
        $data_fields[0] = 'Seminar_id';
        if ($this->group_by_fields[$this->sem_browse_data['group_by']]['unique_field']) {
            $data_fields[1] = $this->group_by_fields[$this->sem_browse_data['group_by']]['unique_field'];
        }
        $group_by_data = $snap->getGroupedResult($group_field, $data_fields);
        $sem_data = $snap->getGroupedResult('Seminar_id');
        if ($this->sem_browse_data['group_by'] == 0) {
            $semester = SemesterData::GetSemesterArray();
            $group_by_duration = $snap->getGroupedResult('sem_number_end', array('sem_number', 'Seminar_id'));
            foreach ($group_by_duration as $sem_number_end => $detail) {
                if ($sem_number_end != -1 && ($detail['sem_number'][$sem_number_end] && count($detail['sem_number']) == 1)) {
                    continue;
                } else {
                    foreach ($detail['Seminar_id'] as $seminar_id => $foo) {
                        $start_sem = key($sem_data[$seminar_id]['sem_number']);
                        if ($sem_number_end == -1){
                            $sem_number_end = count($semester) - 1;
                        }
                        for ($i = $start_sem; $i <= $sem_number_end; ++$i) {
                            if ($this->sem_number === false || (is_array($this->sem_number) && in_array($i, $this->sem_number))) {
                                if ($group_by_data[$i] && !$tmp_group_by_data[$i]) {
                                    foreach($group_by_data[$i]['Seminar_id'] as $id => $bar) {
                                        $tmp_group_by_data[$i]['Seminar_id'][$id] = true;
                                    }
                                }
                                $tmp_group_by_data[$i]['Seminar_id'][$seminar_id] = true;
                            }
                        }
                    }
                }
            }
            if (is_array($tmp_group_by_data)){
                if ($this->sem_number !== false){
                    unset($group_by_data);
                }
                foreach ($tmp_group_by_data as $start_sem => $detail){
                    $group_by_data[$start_sem] = $detail;
                }
            }
        }

        //release memory
        unset($snap);
        unset($tmp_group_by_data);

        foreach ($group_by_data as $group_field => $sem_ids) {
            foreach ($sem_ids['Seminar_id'] as $seminar_id => $foo) {
                if ($orderby_field) {
                    $name = strtolower(key($sem_data[$seminar_id][$orderby_field]));
                } else {
                    $name = strtolower(key($sem_data[$seminar_id]["VeranstaltungsNummer"]));
                }
                $name = str_replace(array('�', '�', '�'), array('ae', 'oe', 'ue'), $name);
                $group_by_data[$group_field]['Seminar_id'][$seminar_id] = $name;
            }
            uasort($group_by_data[$group_field]['Seminar_id'], 'strnatcmp');
        }

        switch ($this->sem_browse_data['group_by']) {
            case 0:
            krsort($group_by_data, SORT_NUMERIC);
            break;

            case 1:
            uksort($group_by_data, create_function('$a,$b',
            '$the_tree = TreeAbstract::GetInstance("StudipSemTree", false);
            $the_tree->buildIndex();
            return (int)($the_tree->tree_data[$a]["index"] - $the_tree->tree_data[$b]["index"]);
            '));
            break;

            case 3:
            uksort($group_by_data, create_function('$a,$b',
            'global $SEM_CLASS,$SEM_TYPE;
            return strnatcasecmp($SEM_TYPE[$a]["name"]." (". $SEM_CLASS[$SEM_TYPE[$a]["class"]]["name"].")",
            $SEM_TYPE[$b]["name"]." (". $SEM_CLASS[$SEM_TYPE[$b]["class"]]["name"].")");'));
            break;
            default:
            uksort($group_by_data, 'strnatcasecmp');
            break;
        }

        return array($group_by_data, $sem_data);
    }

    function show_class(){
        if ($this->sem_browse_data['show_class'] == 'all'){
            return true;
        }
        if (!is_array($this->classes_show_class)){
            $this->classes_show_class = array();
            foreach ($GLOBALS['SEM_CLASS'] as $sem_class_key => $sem_class){
                if ($sem_class['bereiche']){
                    $this->classes_show_class[] = $sem_class_key;
                }
            }
        }
        return in_array($this->sem_browse_data['show_class'], $this->classes_show_class);
    }

    function get_sem_class(){
        $db = new DB_Seminar("SELECT Seminar_id from seminare WHERE seminare.visible=1 AND seminare.status IN ('" . join("','", $this->sem_browse_data['sem_status']) . "')");
        $snap = new DbSnapshot($db);
        $sem_ids = $snap->getRows("Seminar_id");
        if (is_array($sem_ids)){
            $this->sem_browse_data['search_result'] = array_flip($sem_ids);
        }
        $this->show_result = true;
    }

    function printout ($args) {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);

        echo $this->elements['TemplateMain']->toString(array('content' => $this->getContent(), 'subpart' => 'LECTURES'));

    }

    function printoutPreview () {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);

        echo $this->elements['TemplateMain']->toString(array('content' => $this->getContent(), 'subpart' => 'LECTURES', 'hide_markers' => FALSE));

    }

    function getRootStartItemId () {
        if ($this->config->getValue('Main', 'startitem') == 'root') {
            return 'root';
        }
        $db = DBManager::get();
        if ($this->config->getValue('Main', 'mode') == 'show_sem_range') {
            $stmt = $db->prepare("SELECT sem_tree_id AS item_id FROM sem_tree WHERE studip_object_id = ? AND parent_id = 'root'");
        } else {
            $stmt = $db->prepare("SELECT item_id FROM range_tree WHERE studip_object_id = ? AND parent_id = 'root'");
        }
        $stmt->execute(array($this->config->range_id));
        if ($result = $stmt->fetch()) {
            return $result['item_id'];
        }
        return false;
    }

    function createResultXls () {
        require_once "vendor/write_excel/OLEwriter.php";
        require_once "vendor/write_excel/BIFFwriter.php";
        require_once "vendor/write_excel/Worksheet.php";
        require_once "vendor/write_excel/Workbook.php";

        global $_fullname_sql, $SEM_TYPE, $SEM_CLASS, $TMP_PATH;

        if (is_array($this->sem_browse_data['search_result']) && count($this->sem_browse_data['search_result'])) {
            if (!is_object($this->sem_tree)){
                $the_tree = TreeAbstract::GetInstance("StudipSemTree");
            } else {
                $the_tree =& $this->sem_tree;
            }
            list($group_by_data, $sem_data) = $this->getResult();
            $tmpfile = $TMP_PATH . '/' . md5(uniqid('write_excel',1));
            // Creating a workbook
            $workbook = new Workbook($tmpfile);
            $head_format =& $workbook->addformat();
            $head_format->set_size(12);
            $head_format->set_bold();
            $head_format->set_align("left");
            $head_format->set_align("vcenter");

            $head_format_merged =& $workbook->addformat();
            $head_format_merged->set_size(12);
            $head_format_merged->set_bold();
            $head_format_merged->set_align("left");
            $head_format_merged->set_align("vcenter");
            $head_format_merged->set_merge();
            $head_format_merged->set_text_wrap();

            $caption_format =& $workbook->addformat();
            $caption_format->set_size(10);
            $caption_format->set_align("left");
            $caption_format->set_align("vcenter");
            $caption_format->set_bold();
            //$caption_format->set_text_wrap();

            $data_format =& $workbook->addformat();
            $data_format->set_size(10);
            $data_format->set_align("left");
            $data_format->set_align("vcenter");

            $caption_format_merged =& $workbook->addformat();
            $caption_format_merged->set_size(10);
            $caption_format_merged->set_merge();
            $caption_format_merged->set_align("left");
            $caption_format_merged->set_align("vcenter");
            $caption_format_merged->set_bold();


            // Creating the first worksheet
            $worksheet1 =& $workbook->addworksheet(_("Veranstaltungen"));
            $worksheet1->set_row(0, 20);
            $worksheet1->write_string(0, 0, _("Stud.IP Veranstaltungen") . ' - ' . $GLOBALS['UNI_NAME_CLEAN'] ,$head_format);
            $worksheet1->set_row(1, 20);
            $worksheet1->write_string(1, 0, sprintf(_(" %s Veranstaltungen gefunden %s, Gruppierung: %s"),count($sem_data),
                (($this->sem_browse_data['do_search']) ? _("(Suchergebnis)") : ''),
                $this->group_by_fields[$this->sem_browse_data['group_by']]['name']), $caption_format);

            $worksheet1->write_blank(0,1,$head_format);
            $worksheet1->write_blank(0,2,$head_format);
            $worksheet1->write_blank(0,3,$head_format);

            $worksheet1->write_blank(1,1,$head_format);
            $worksheet1->write_blank(1,2,$head_format);
            $worksheet1->write_blank(1,3,$head_format);

            $worksheet1->set_column(0, 0, 70);
            $worksheet1->set_column(0, 1, 25);
            $worksheet1->set_column(0, 2, 25);
            $worksheet1->set_column(0, 3, 50);

            $row = 2;

            foreach ($group_by_data as $group_field => $sem_ids){
                switch ($this->sem_browse_data["group_by"]){
                    case 0:
                    $semester = SemesterData::GetSemesterArray();
                    $headline = $semester[$group_field]['name'];
                    break;

                    case 1:
                    if ($the_tree->tree_data[$group_field]) {
                        $headline = $the_tree->getShortPath($group_field);
                    } else {
                        $headline =  _("keine Studienbereiche eingetragen");
                    }
                    break;

                    case 3:
                    $headline = $SEM_TYPE[$group_field]["name"]." (". $SEM_CLASS[$SEM_TYPE[$group_field]["class"]]["name"].")";
                    break;

                    default:
                    $headline = $group_field;
                    break;

                }
                ++$row;
                $worksheet1->write_string($row, 0 , $headline, $caption_format);
                $worksheet1->write_blank($row,1, $caption_format);
                $worksheet1->write_blank($row,2, $caption_format);
                $worksheet1->write_blank($row,3, $caption_format);
                ++$row;
                if (is_array($sem_ids['Seminar_id'])) {
                    $semester = SemesterData::GetSemesterArray();
                    while(list($seminar_id,) = each($sem_ids['Seminar_id'])){
                        $sem_name = key($sem_data[$seminar_id]["Name"]);
                        $seminar_number = key($sem_data[$seminar_id]['VeranstaltungsNummer']);
                        $sem_number_start = key($sem_data[$seminar_id]["sem_number"]);
                        $sem_number_end = key($sem_data[$seminar_id]["sem_number_end"]);
                        if ($sem_number_start != $sem_number_end) {
                            $sem_name .= ' (' . $semester[$sem_number_start]['name'] . ' - ';
                            $sem_name .= (($sem_number_end == -1) ? _("unbegrenzt") : $semester[$sem_number_end]['name']) . ')';
                        } elseif ($this->sem_browse_data['group_by']) {
                            $sem_name .= ' (' . $semester[$sem_number_start]['name'] . ")";
                        }
                        $worksheet1->write_string($row, 0, $sem_name, $data_format);
                        //create Turnus field
                        $temp_turnus_string = Seminar::GetInstance($seminar_id)->getFormattedTurnus(true);
                        //Shorten, if string too long (add link for details.php)
                        if (strlen($temp_turnus_string) > 245) {
                            $temp_turnus_string = substr($temp_turnus_string, 0, strpos(substr($temp_turnus_string, 245, strlen($temp_turnus_string)), ",") + 246);
                            $temp_turnus_string .= "...(mehr)";
                        }
                        $worksheet1->write_string($row, 1, $seminar_number, $data_format);
                        $worksheet1->write_string($row, 2, $temp_turnus_string, $data_format);

                        $doz_name = array_keys($sem_data[$seminar_id]['fullname']);
                        $doz_position = array_keys($sem_data[$seminar_id]['position']);
                        if (is_array($doz_name)){
                            if(count($doz_position) != count($doz_name)) $doz_position = range(1, count($doz_name));
                            array_multisort($doz_position, $doz_name);
                            $worksheet1->write_string($row, 3, join(', ', $doz_name), $data_format);
                        }
                        ++$row;
                    }
                }
            }
            $workbook->close();
        }
        return $tmpfile;
    }

    public function getAllDates ($seminar, $start, $end) {
        $data = $seminar->getUndecoratedData();
        $date = array();

        $i = 0;
        if (is_array($data['regular']['turnus_data'])) {
            foreach ($data['regular']['turnus_data'] as $cycle_id => $cycle) {
                $date[$i]['time'] = sprintf('%02d:%02d - %02d:%02d', $cycle['start_hour'], $cycle['start_minute'], $cycle['end_hour'], $cycle['end_minute']);
                $date[$i]['interval'] = (empty($data['regular']['turnus']) ? '' : _("14-t�glich"));
                if ($GLOBALS['RESOURCES_ENABLE']) {
                    if ($room_ids = $seminar->metadate->cycles[$cycle_id]->getPredominantRoom($start, $end)) {
                        foreach ($room_ids as $room_id) {
                            $res_obj = ResourceObject::Factory($room_id);
                            $room_names[] = $res_obj->getName();
                        }
                        $date[$i]['room'] = implode(', ', $room_names);
                    } else {
                        $date[$i]['room'] = trim($seminar->metadate->cycles[$cycle_id]->getFreeTextPredominantRoom($start, $end));
                    }
                    $date[$i]['dow'] = getWeekDay($cycle['day']);
                }
                $i++;
            }
        }
        if (sizeof( (array) $data['irregular'])) {
            foreach ($data['irregular'] as $irregular_date) {
                if ($irregular_date['start_time'] >= $start && $irregular_date['start_time'] <= $end) {
                    $date[$i]['time'] = date('H:i', $irregular_date['start_time']) . date(' - H:i', $irregular_date['end_time']);
                    $date[$i]['date'] = strftime('%x', $irregular_date['start_time']);
                    $date[$i]['dow'] = getWeekDay(date('w', $irregular_date['start_time']));
                    if ($GLOBALS['RESOURCES_ENABLE'] && $irregular_date['resource_id']) {
                        $res_obj = ResourceObject::Factory($irregular_date['resource_id']);
                        $date[$i]['room'] = $res_obj->getName();
                    } else {
                        $date[$i]['room'] = trim($irregular_date['raum']);
                    }
                    $i++;
                }
            }
        }

        return $date;
    }

}

?>
