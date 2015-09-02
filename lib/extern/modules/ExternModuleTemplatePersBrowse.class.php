<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternModuleTemplatePersBrowser.class.php
* 
* 
* 
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternModuleTemplatePersBrowser
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternModuleTemplatePersBrowser.class.php
// 
// Copyright (C) 2009 Peter Thienel <thienel@data-quest.de>,
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


require_once $GLOBALS['RELATIVE_PATH_EXTERN'] . '/views/extern_html_templates.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/dates.inc.php';

class ExternModuleTemplatePersBrowse extends ExternModule {

    public $markers = array();
    private $approved_params = array();
    private $range_tree;
    private $global_markers = array();
    
    /**
    *
    */
    public function __construct ($range_id, $module_name, $config_id = NULL, $set_config = NULL, $global_id = NULL) {
        $this->data_fields = array(
                'Nachname', 'Telefon', 'raum', 'Email', 'sprechzeiten'
        );
        $this->registered_elements = array(
                'SelectInstitutes',
                'LinkInternListCharacters' => 'LinkInternTemplate',
                'LinkInternListInstitutes' => 'LinkInternTemplate',
                'LinkInternPersondetails' => 'LinkInternTemplate',
                'TemplateListCharacters' => 'TemplateGeneric',
                'TemplateListInstitutes' => 'TemplateGeneric',
                'TemplateListPersons' => 'TemplateGeneric',
                'TemplateMain' => 'TemplateGeneric'
        );
        
        $this->field_names = array
        (
                _("Name"),
                _("Telefon"),
                _("Raum"),
                _("Email"),
                _("Sprechzeiten")
        );
        
        $this->approved_params = array('item_id', 'initiale');
        
        $this->range_tree = TreeAbstract::GetInstance('StudipRangeTree');
        
        parent::ExternModule($range_id, $module_name, $config_id, $set_config, $global_id);
    }
    
    public function setup () {
        $this->elements['LinkInternListCharacters']->real_name = _("Verlinkung der alpabetischen Liste zur Personenliste");
        $this->elements['LinkInternListCharacters']->link_module_type = array(16);
        $this->elements['LinkInternListInstitutes']->real_name = _("Verlinkung der Einrichtungsliste zur Personenliste");
        $this->elements['LinkInternListInstitutes']->link_module_type = array(16);
        $this->elements['LinkInternPersondetails']->real_name = _("Verlinkung der Personenliste zum Modul MitarbeiterInnendetails");
        $this->elements['LinkInternPersondetails']->link_module_type = array(2, 14);
        $this->elements['TemplateMain']->real_name = _("Haupttemplate");
        $this->elements['TemplateListInstitutes']->real_name = _("Einrichtungsliste");
        $this->elements['TemplateListPersons']->real_name = _("Personenliste");
        $this->elements['TemplateListCharacters']->real_name = _("Liste mit Anfangsbuchstaben der Nachnamen");
        
    }
    
    public function toStringEdit ($open_elements = '', $post_vars = '', $faulty_values = '', $anker = '') {
        
        $this->updateGenericDatafields('TemplateListPersons', 'user');
        $this->elements['TemplateMain']->markers = $this->getMarkerDescription('TemplateMain');
        $this->elements['TemplateListInstitutes']->markers = $this->getMarkerDescription('TemplateListInstitutes');
        $this->elements['TemplateListPersons']->markers = $this->getMarkerDescription('TemplateListPersons');
        $this->elements['TemplateListCharacters']->markers = $this->getMarkerDescription('TemplateListCharacters');
        
        return parent::toStringEdit($open_elements, $post_vars, $faulty_values, $anker);
    }
    
    public function getMarkerDescription ($element_name) {
    
        $markers['TemplateMain'] = array(
            array('__GLOBAL__', _("Globale Variablen (g�ltig im gesamten Template).")),
            array('###CHARACTER###', ''),
            array('###INSTNAME###', ''),
            array('<!-- BEGIN PERS_BROWSER -->', ''),
            array('###LISTCHARACTERS###', _("Auflistung der Anfangsbuchstaben")),
            array('###LISTINSTITUTES###', _("Auflistung der Einrichtungen")),
            array('###LISTPERSONS###', _("Auflistung der gefundenen Personen")),
            array('<!-- END PERS_BROWSER -->', '')
        );
        
        $markers['TemplateListInstitutes'] = array(
            array('<!-- BEGIN LIST_INSTITUTES -->', ''),
            array('<!-- BEGIN INSTITUTE -->', ''),
            array('###INSTITUTE_NAME###', _("Name der Einrichtung (erster Level im Einrichtungsbaum)")),
            array('###INSTITUTE_COUNT_USER###', _("Anzahl der Personen innerhalb der Einrichtung (und untergeordneten Einrichtungen)")),
            array('###URL_LIST_PERSONS###', _("URL zur Personenlist mit diesem Anfangsbuchstaben")),
            array('<!-- END INSTITUTE -->', ''),
            array('<!-- END LIST_INSTITUTES -->', '')
        );
        
        $markers['TemplateListCharacters'] = array(
            array('<!-- BEGIN LIST_CHARACTERS -->', ''),
            array('<!-- BEGIN CHARACTER -->', ''),
            array('###CHARACTER_USER###', _("Anfangsbuchstabe der Namen zur Verlinkung nach alpabetische �bersicht")),
            array('###CHARACTER_COUNT_USER###', _("Anzahl der Personennamen mit diesem Anfangsbuchstaben")),
            array('###URL_LIST_PERSONS###', _("URL zur Personenlist mit diesem Anfangsbuchstaben")),
            array('<!-- END CHARACTER -->', ''),
            array('<!-- END LIST_CHARACTERS -->', '')
        );
        
        $markers['TemplateListPersons'] = array(
            array('<!-- BEGIN LIST_PERSONS -->', ''),
            array('<!-- BEGIN NO-PERSONS -->', ''),
            array('<!-- END NO-PERSONS -->', ''),
            array('<!-- BEGIN PERSONS -->', ''),
            array('<!-- BEGIN PERSON -->', ''),
            array('###FULLNAME###', ''),
            array('###LASTNAME###', ''),
            array('###FIRSTNAME###', ''),
            array('###TITLEFRONT###', ''),
            array('###TITLEREAR###', ''),
            array('###PERSONDETAIL-HREF###', ''),
            array('###USERNAME###', ''),
            array('###INSTNAME###', ''),
            array('###PHONE###', ''),
            array('###ROOM###', ''),
            array('###EMAIL###', ''),
            array('###EMAIL-LOCAL###', _("Der local-part der E-Mail-Adresse (vor dem @-Zeichen)")),
            array('###EMAIL-DOMAIN###', _("Der domain-part der E-Mail-Adresse (nach dem @-Zeichen)")),
            array('###OFFICEHOURS###', ''),
            array('###PERSON-NO###', ''),
            $this->insertDatafieldMarkers('user', $markers, 'TemplateList'),
            array('<!-- END PERSON -->', ''),
            array('<!-- END PERSONS -->', ''),
            array('<!-- END LIST_PERSONS -->', '')
        );
    
        return $markers[$element_name];
    }
    
    private function getContent ($args = null, $raw = false) {
        if ($raw) {
            $this->setRawOutput();
        }
        
        if (trim($this->config->getValue('TemplateListInstitutes', 'template'))) {
            $content['PERS_BROWSER']['LISTINSTITUTES'] = $this->elements['TemplateListInstitutes']->toString(array('content' => $this->getContentListInstitutes(), 'subpart' => 'LIST_INSTITUTES'));
        }
        if (trim($this->config->getValue('TemplateListCharacters', 'template'))) {
            $content['PERS_BROWSER']['LISTCHARACTERS'] = $this->elements['TemplateListCharacters']->toString(array('content' => $this->getContentListCharacters(), 'subpart' => 'LIST_CHARACTERS'));
        }
        if (trim($this->config->getValue('TemplateListPersons', 'template'))) {
            $content['PERS_BROWSER']['LISTPERSONS'] = $this->elements['TemplateListPersons']->toString(array('content' => $this->getContentListPersons(), 'subpart' => 'LIST_PERSONS'));
        }
        // set super global markers
        $content['__GLOBAL__'] = $this->global_markers;
        return $content;
    }
    
    private function getContentListPersons () {
        if (!$nameformat = $this->config->getValue('Main', 'nameformat')) {
            $nameformat = 'full_rev';
        }
        
        $selected_item_ids = $this->config->getValue('SelectInstitutes', 'institutesselected');
        // at least one institute has to be selected in the configuration
        if (!is_array($selected_item_ids)) {
            return array();
        }
        
        $sort = $this->config->getValue('Main', 'sort');
        $query_order = '';
        foreach ($sort as $key => $position) {
            if ($position > 0) {
                $query_order[$position] = $this->data_fields[$key];
            }
        }
        if ($query_order) {
            ksort($query_order, SORT_NUMERIC);
            $query_order = ' ORDER BY ' . implode(',', $query_order);
        }
        
        $module_params = $this->getModuleParams($this->approved_params);
        
        $db = new DB_Seminar();
        
        $dbv = DbView::getView('sem_tree');
        
        if ($module_params['initiale']) {
            if ($this->config->getValue('Main', 'onlylecturers')) {
                $current_semester = get_sem_num(time());
                $query = sprintf("SELECT ui.Institut_id, su.user_id "
                . "FROM seminar_user su "
                . "LEFT JOIN seminare s USING (seminar_id) "
                . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                . "LEFT JOIN user_inst ui USING(user_id) "
                . "WHERE LOWER(LEFT(TRIM(aum.Nachname), 1)) = LOWER('%s') "
                . "AND su.status = 'dozent' "
                . "AND s.visible = 1 "
                . "AND ((%s) = %s OR ((%s) <= %s  AND ((%s) >= %s OR (%s) = -1))) "
                . "AND ui.Institut_id IN ('%s') "
                . "AND ui.inst_perms = 'dozent' "
                . "AND ui.externdefault = 1 ",
                substr($module_params['initiale'], 0, 1),
                $dbv->sem_number_sql,
                $current_semester,
                $dbv->sem_number_sql,
                $current_semester,
                $dbv->sem_number_end_sql,
                $current_semester,
                $dbv->sem_number_end_sql,
                implode("','", $selected_item_ids));
            } else {
                    // get only users with the given status
                $query = sprintf("SELECT ui.Institut_id, ui.user_id "
                    . "FROM user_inst ui "
                    . "LEFT JOIN auth_user_md5 aum USING(user_id) "
                    . "WHERE LOWER(LEFT(TRIM(aum.Nachname), 1)) = LOWER('%s') "
                    . "AND ui.inst_perms IN('%s') "
                    . "AND ui.Institut_id IN ('%s') "
                    . "AND ui.externdefault = 1 ",
                    substr($module_params['initiale'], 0, 1),
                    implode("','", $this->config->getValue('Main', 'instperms')),
                    implode("','", $selected_item_ids));
            }
        // item_id is given and it is in the list of item_ids selected in the configuration
        } else if ($module_params['item_id'] && in_array($module_params['item_id'], $selected_item_ids)) {
            if ($this->config->getValue('Main', 'onlylecturers')) {
                $current_semester = get_sem_num(time());
                // get only users with status dozent in an visible seminar in the current semester
                $query = sprintf("SELECT ui.Institut_id, ui.user_id "
                    . "FROM user_inst ui "
                    . "LEFT JOIN seminar_user su USING(user_id) "
                    . "LEFT JOIN seminare s USING (seminar_id) "
                    . "WHERE ui.Institut_id = '%s' "
                    . "AND ui.inst_perms = 'dozent' "
                    . "AND ui.externdefault = 1 "
                    . "AND su.status = 'dozent' "
                    . "AND s.visible = 1 "
                    . "AND ((%s) = %s OR ((%s) <= %s  AND ((%s) >= %s OR (%s) = -1))) ",
                    $module_params['item_id'],
                    $dbv->sem_number_sql,
                    $current_semester,
                    $dbv->sem_number_sql,
                    $current_semester,
                    $dbv->sem_number_end_sql,
                    $current_semester,
                    $dbv->sem_number_end_sql);
            } else {
                // get only users with the given status
                $query = sprintf("SELECT ui.Institut_id, ui.user_id "
                    . "FROM user_inst ui "
                    . "WHERE ui.Institut_id = '%s' "
                    . "AND ui.inst_perms IN('%s') "
                    . "AND ui.externdefault = 1 ",
                    $module_params['item_id'],
                    implode("','", $this->config->getValue('Main', 'instperms')));
            }
        } else {
            return array();
        }
            
        $db->query($query);
        
        $user_list = array();
        while ($db->next_record()) {
            if (!isset($user_list[$db->f('user_id')])) {
                $user_list[$db->f('user_id')] = $db->f('user_id') . $db->f('Institut_id');
            }
        }
        
        if (sizeof($user_list) == 0) {
            return array();
        }
        
        $query = sprintf(
            "SELECT ui.Institut_id, ui.raum, ui.sprechzeiten, ui.Telefon, "
            . "inst_perms,  i.Name, aum.Email, aum.user_id, username, "
            . "%s AS fullname, aum.Nachname, aum.Vorname "
            . "FROM user_inst ui "
            . "LEFT JOIN Institute i USING(Institut_id) "
            . "LEFT JOIN auth_user_md5 aum USING(user_id)"
            . "LEFT JOIN user_info uin USING(user_id) "
            . "WHERE CONCAT(ui.user_id, ui.Institut_id) IN ('%s') "
            . "ORDER BY aum.Nachname ",
            $GLOBALS['_fullname_sql'][$nameformat],
            implode("','", $user_list));
        $db->query($query);
        
        $j = 0;
        while ($db->next_record()) {
            $content['PERSONS']['PERSON'][$j]['FULLNAME'] = ExternModule::ExtHtmlReady($db->f('fullname'));
            $content['PERSONS']['PERSON'][$j]['LASTNAME'] = ExternModule::ExtHtmlReady($db->f('Nachname'));
            $content['PERSONS']['PERSON'][$j]['FIRSTNAME'] = ExternModule::ExtHtmlReady($db->f('Vorname'));
            $content['PERSONS']['PERSON'][$j]['TITLEFRONT'] = ExternModule::ExtHtmlReady($db->f('title_front'));
            $content['PERSONS']['PERSON'][$j]['TITLEREAR'] = ExternModule::ExtHtmlReady($db->f('title_rear'));
            $content['PERSONS']['PERSON'][$j]['PERSONDETAIL-HREF'] = $this->elements['LinkInternPersondetails']->createUrl(array('link_args' => 'username=' . $db->f('username')));
            $content['PERSONS']['PERSON'][$j]['USERNAME'] = $db->f('username');
            $content['PERSONS']['PERSON'][$j]['INSTNAME'] = ExternModule::ExtHtmlReady($db->f('Name'));
            $content['PERSONS']['PERSON'][$j]['PHONE'] = ExternModule::ExtHtmlReady($db->f('Telefon'));
            $content['PERSONS']['PERSON'][$j]['ROOM'] = ExternModule::ExtHtmlReady($db->f('raum'));
            $content['PERSONS']['PERSON'][$j]['EMAIL'] = ExternModule::ExtHtmlReady($db->f('Email'));
            $content['PERSONS']['PERSON'][$j]['EMAIL-LOCAL'] = array_shift(explode('@', $content['PERSONS']['PERSON'][$j]['EMAIL']));
            $content['PERSONS']['PERSON'][$j]['EMAIL-DOMAIN'] = array_pop(explode('@', $content['PERSONS']['PERSON'][$j]['EMAIL']));
            $content['PERSONS']['PERSON'][$j]['OFFICEHOURS'] = ExternModule::ExtHtmlReady($db->f('sprechzeiten'));
            $content['PERSONS']['PERSON'][$j]['PERSON-NO'] = $j + 1;
            
            // generic data fields
            if (is_array($generic_datafields)) {
                $localEntries = DataFieldEntry::getDataFieldEntries($db->f('user_id'), 'user');
                $k = 1;
                foreach ($generic_datafields as $datafield) {
                    if (isset($localEntries[$datafield]) && is_object($localEntries[$datafield])) {
                        if ($localEntries[$datafield]->getType() == 'link') {
                            $localEntry = ExternModule::extHtmlReady($localEntries[$datafield]->getValue());
                        } else {
                            $localEntry = $localEntries[$datafield]->getDisplayValue();
                        }
                        if ($localEntry) {
                            $content['PERSONS']['PERSON'][$j]['DATAFIELD_' . $k] = $localEntry;
                        }
                    }
                    $k++;
                }
            }
            $j++;
        }
        if (!$module_params['initiale']) {
            $this->global_markers['INSTNAME'] = $content['PERSONS']['PERSON'][0]['INSTNAME'];
        } else {
            $this->global_markers['CHARACTER'] = substr($module_params['initiale'], 0, 1);
        }
        
        return $content;
    }
    
    
    private function getContentListCharacters () {
        $selected_item_ids = $this->config->getValue('SelectInstitutes', 'institutesselected');
        // at least one institute has to be selected in the configuration
        if (!is_array($selected_item_ids)) {
            return array();
        }
        $content = array();

        // at least one institute has to be selected in the configuration
        if (!is_array($selected_item_ids)) {
            return array();
        }
        $db = new DB_Seminar();
        $dbv = DbView::getView('sem_tree');
        if ($this->config->getValue('Main', 'onlylecturers')) {
            $current_semester = get_sem_num(time());
                $query = sprintf("SELECT COUNT(DISTINCT aum.user_id) as count_user, "
                . "UPPER(LEFT(TRIM(aum.Nachname),1)) AS initiale "
                . "FROM user_inst ui "
                . "LEFT JOIN seminar_user su ON ui.user_id = su.user_id "
                . "LEFT JOIN seminare s ON su.Seminar_id = s.Seminar_id "
                . "LEFT JOIN auth_user_md5 aum ON su.user_id = aum.user_id "
                . "WHERE su.status = 'dozent' AND s.visible = 1 "
                . "AND ((%s) = %s OR ((%s) <= %s  AND ((%s) >= %s OR (%s) = -1))) "
                . "AND TRIM(aum.Nachname) != '' "
                . "AND ui.Institut_id IN ('%s') "
                . "AND ui.externdefault = 1 "
                . "GROUP BY initiale",
                $dbv->sem_number_sql,
                $current_semester,
                $dbv->sem_number_sql,
                $current_semester,
                $dbv->sem_number_end_sql,
                $current_semester,
                $dbv->sem_number_end_sql,
                implode("','", $selected_item_ids));
        } else {
            $query = sprintf("SELECT COUNT(DISTINCT ui.user_id) as count_user, "
                . "UPPER(LEFT(TRIM(aum.Nachname),1)) AS initiale "
                . "FROM user_inst ui "
                . "LEFT JOIN auth_user_md5 aum USING (user_id) "
                . "WHERE ui.inst_perms IN ('%s') "
                . "AND ui.Institut_id IN ('%s') "
                . "AND ui.externdefault = 1 "
                . "AND TRIM(aum.Nachname) != '' "
                . "GROUP BY initiale",
                implode("','", $this->config->getValue('Main', 'instperms')),
                implode("','", $selected_item_ids));
        }
        
        $db->query($query);
        while ($db->next_record()) {
            $content['LIST_CHARACTERS']['CHARACTER'][] = array(
                'CHARACTER_USER' => ExternModule::ExtHtmlReady($db->f('initiale')),
                'CHARACTER_COUNT_USER' => ExternModule::ExtHtmlReady($db->f('count_user')),
                'URL_LIST_PERSONS' => $this->getLinkToModule('LinkInternListCharacters', array('initiale' => $db->f('initiale'))));
        }
        return $content;
    }
    
    private function getContentListInstitutes () {
        $selected_item_ids = $this->config->getValue('SelectInstitutes', 'institutesselected');
        // at least one institute has to be selected in the configuration
        if (!is_array($selected_item_ids)) {
            return array();
        }
        $content = array();
        
        $first_levels = $this->range_tree->getKids('root');
    //  var_dump($first_levels);
        $current_semester = get_sem_num(time());
        
        $db_count = new DB_Seminar();
        $dbv = DbView::getView('sem_tree');
        $mrks = str_repeat('?,', count($selected_item_ids) - 1) . '?';
        $query = "SELECT Institut_id, Name "
            . "FROM Institute "
            . "WHERE Institut_id IN ($mrks) "
            . "AND fakultaets_id != Institut_id "
            . "ORDER BY Name ASC";
        $parameters = $selected_item_ids;

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($this->config->getValue('Main', 'onlylecturers')) {
                // get only users with status dozent in an visible seminar in the current semester
                $query = sprintf("SELECT COUNT(DISTINCT(su.user_id)) AS count_user "
                    . "FROM user_inst ui "
                    . "LEFT JOIN seminar_user su USING(user_id) "
                    . "LEFT JOIN seminare s USING (seminar_id) "
                    . "LEFT JOIN auth_user_md5 aum ON su.user_id = aum.user_id "
                    . "WHERE ui.Institut_id = '%s' "
                    . "AND su.status = 'dozent' "
                    . "AND ui.externdefault = 1 "
                    . "AND ui.inst_perms = 'dozent' "
                    . "AND ((%s) = %s OR ((%s) <= %s  AND ((%s) >= %s OR (%s) = -1)))",
                    $row['Institut_id'],
                    $dbv->sem_number_sql,
                    $current_semester,
                    $dbv->sem_number_sql,
                    $current_semester,
                    $dbv->sem_number_end_sql,
                    $current_semester,
                    $dbv->sem_number_end_sql);
            } else {
                // get only users with the given status
                $query = sprintf("SELECT COUNT(DISTINCT(ui.user_id)) AS count_user "
                    . "FROM user_inst ui "
                    . "WHERE ui.Institut_id = '%s' "
                    . "AND ui.inst_perms IN('%s') "
                    . "AND ui.externdefault = 1 ",
                    $row['Institut_id'],
                    implode("','", $this->config->getValue('Main', 'instperms')));
            }
            
           
            $state = DBManager::get()->prepare($query);
            $state->execute($parameters);
            while ($row_count = $state->fetch(PDO::FETCH_ASSOC)) {
            
                if ($row_count['count_user'] > 0) {
                    $content['LIST_INSTITUTES']['INSTITUTE'][] = array(
                        'INSTITUTE_NAME' => ExternModule::ExtHtmlReady($row['Name']),
                        'INSTITUTE_COUNT_USER' => $row_count['count_user'],
                        'URL_LIST_PERSONS' => $this->getLinkToModule('LinkInternListInstitutes', array('item_id' => $row['Institut_id'])));
                }
            }
        }
        
        return $content;
    }
    
    public function printout ($args) {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);
        
        echo $this->elements['TemplateMain']->toString(array('content' => $this->getContent($args), 'subpart' => 'PERS_BROWSE'));
        
    }
    
    public function printoutPreview () {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);
        
        echo $this->elements['TemplateMain']->toString(array('content' => $this->getContent(), 'subpart' => 'PERS_BROWSE', 'hide_markers' => FALSE));
        
    }
    
}

?>
