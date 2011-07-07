<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternModuleTemplatePersons.class.php
* 
* 
* 
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternModuleTemplatePersons
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternModuleTemplatePersons.class.php
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
require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/extern_functions.inc.php');
global $_fullname_sql;


class ExternModuleTemplatePersons extends ExternModule {

    var $markers = array();
    
    /**
    *
    */
    function ExternModuleTemplatePersons ($range_id, $module_name, $config_id = NULL, $set_config = NULL, $global_id = NULL) {
        $this->data_fields = array(
                'Nachname', 'Telefon', 'raum', 'Email', 'sprechzeiten'
        );
        $this->registered_elements = array(
                'LinkInternTemplate',
                'TemplateGeneric'
        );
        
        $this->field_names = array
        (
                _("Name"),
                _("Telefon"),
                _("Raum"),
                _("Email"),
                _("Sprechzeiten")
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
    
        $this->elements['TemplateGeneric']->real_name = _("Template");
        // Set internal link to module 'staff details'
        $this->elements['LinkInternTemplate']->link_module_type = array(2, 14);
        $this->elements['LinkInternTemplate']->real_name = _("Verlinkung zum Modul MitarbeiterInnendetails");
    
    }
    
    function toStringEdit ($open_elements = '', $post_vars = '',
            $faulty_values = '', $anker = '') {
        
        $this->updateGenericDatafields('TemplateGeneric', 'user');
        $this->elements['TemplateGeneric']->markers = $this->getMarkerDescription('TemplateGeneric');
        
        return parent::toStringEdit($open_elements, $post_vars, $faulty_values, $anker);
    }
    
    function getMarkerDescription ($element_name) {
        $markers['TemplateGeneric'][] = array('<!-- BEGIN PERSONS -->', '');
        
        $markers['TemplateGeneric'][] = array('<!-- BEGIN NO-PERSONS -->', '');
        $markers['TemplateGeneric'][] = array('###NO-LECTURES-TEXT###', '');
        $markers['TemplateGeneric'][] = array('<!-- END NO-PERSONS -->', '');
        
        $markers['TemplateGeneric'][] = array('<!-- BEGIN GROUP -->', '');
        $markers['TemplateGeneric'][] = array('###GROUPTITLE###', '');
        $markers['TemplateGeneric'][] = array('###GROUPTITLE-SUBSTITUTE###', '');
        $markers['TemplateGeneric'][] = array('###GROUP-NO###', '');
        
        $markers['TemplateGeneric'][] = array('<!-- BEGIN PERSON -->', '');
        $markers['TemplateGeneric'][] = array('###FULLNAME###', '');
        $markers['TemplateGeneric'][] = array('###LASTNAME###', '');
        $markers['TemplateGeneric'][] = array('###FIRSTNAME###', '');
        $markers['TemplateGeneric'][] = array('###TITLEFRONT###', '');
        $markers['TemplateGeneric'][] = array('###TITLEREAR###', '');
        $markers['TemplateGeneric'][] = array('###PERSONDETAIL-HREF###', '');
        $markers['TemplateGeneric'][] = array('###USERNAME###', '');
        $markers['TemplateGeneric'][] = array('###IMAGE-URL-NORMAL###', _('Nutzerbild (groß)'));
        $markers['TemplateGeneric'][] = array('###IMAGE-URL-MEDIUM###', _('Nutzerbild (mittel)'));
        $markers['TemplateGeneric'][] = array('###IMAGE-URL-SMALL###', _('Nutzerbild (klein)'));
        $markers['TemplateGeneric'][] = array('###PHONE###', '');
        $markers['TemplateGeneric'][] = array('###ROOM###', '');
        $markers['TemplateGeneric'][] = array('###EMAIL###', '');
        $markers['TemplateGeneric'][] = array('###EMAIL-LOCAL###', _("Der local-part der E-Mail-Adresse (vor dem @-Zeichen)"));
        $markers['TemplateGeneric'][] = array('###EMAIL-DOMAIN###', _("Der domain-part der E-Mail-Adresse (nach dem @-Zeichen)"));
        $markers['TemplateGeneric'][] = array('###OFFICEHOURS###', '');
        $markers['TemplateGeneric'][] = array('###PERSON-NO###', '');
        $this->insertDatafieldMarkers('user', $markers, 'TemplateGeneric');
        $markers['TemplateGeneric'][] = array('<!-- END PERSON -->', '');
        
        $markers['TemplateGeneric'][] = array('<!-- END GROUP -->', '');
        $markers['TemplateGeneric'][] = array('<!-- END PERSONS -->', '');
    
        return $markers[$element_name];
    }
    
    function getContent ($args = NULL, $raw = FALSE) {
        if ($raw) {
            $this->setRawOutput();
        }
        
        if (!$all_groups = get_all_statusgruppen($this->config->range_id)) {
            die($GLOBALS["EXTERN_ERROR_MESSAGE"]);
        } else {
            $all_groups = array_keys($all_groups);
        }
        
        if (!$group_ids = $this->config->getValue('Main', 'groupsvisible')) {
            die($GLOBALS["EXTERN_ERROR_MESSAGE"]);
        } else {
            $group_ids = array_intersect($all_groups, $group_ids);
        }
        
        if (!is_array($group_ids)) {
            die($GLOBALS["EXTERN_ERROR_MESSAGE"]);
        }

        if (!$visible_groups = get_statusgruppen_by_id($this->config->range_id, $group_ids)) {
            die($GLOBALS["EXTERN_ERROR_MESSAGE"]);
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
        
        $db = new DB_Seminar();
        $grouping = $this->config->getValue("Main", "grouping");
        if (!$nameformat = $this->config->getValue('Main', 'nameformat')) {
            $nameformat = 'full_rev';
        }
        
        
        
        
        if(!$grouping) {
            $groups_ids = implode("','", $this->config->getValue("Main", "groupsvisible"));

            $query = "SELECT DISTINCT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms, Email, aum.user_id, username, ";
            $query .= $GLOBALS['_fullname_sql'][$nameformat] . " AS fullname, aum.Nachname ";
            if ($query_order != '') {
                $query .= "FROM statusgruppe_user LEFT JOIN auth_user_md5 aum USING(user_id) ";
                $query .= "LEFT JOIN user_info USING(user_id) LEFT JOIN user_inst ui USING(user_id) ";
                $query .= "WHERE statusgruppe_id IN ('$groups_ids') AND Institut_id = '" . $this->config->range_id
                        . "' AND ".get_ext_vis_query()."$query_order";
            } else {
                $query .= "FROM statusgruppen s LEFT JOIN statusgruppe_user su USING(statusgruppe_id) ";
                $query .= "LEFT JOIN auth_user_md5 aum USING(user_id) ";
                $query .= "LEFT JOIN user_info USING(user_id) LEFT JOIN user_inst ui USING(user_id) ";
                $query .= "WHERE su.statusgruppe_id IN ('$groups_ids') AND Institut_id = '" . $tis->config->range_id;
                $query .= "' AND ".get_ext_vis_query()." ORDER BY ";
                $query .= "s.position ASC, su.position ASC";
            }

            $db->query($query);
            $visible_groups = array("");
        }
        
        // generic data fields
        $generic_datafields = $this->config->getValue('TemplateGeneric', 'genericdatafields');
        
        $data['data_fields'] = $this->data_fields;
        $defaultaddress = $this->config->getValue('Main', 'defaultadr');
        if ($defaultaddress) {
            $db_defaultaddress = new DB_Seminar();
            $db_out =& $db_defaultaddress;
        } else {
            $db_out =& $db;
        }
        
        $out = '';
        $i = 0;
        foreach ($visible_groups as $group_id => $group) {

            if ($grouping) {
                if ($query_order == '') {
                    $query_order = ' ORDER BY su.position';
                }
                $query = 'SELECT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms, Email, aum.user_id, ';
                $query .= 'username, aum.Vorname, title_front, title_rear, ';
                $query .= $GLOBALS['_fullname_sql'][$nameformat] . " AS fullname, aum.Nachname ";
                $query .= 'FROM statusgruppe_user su LEFT JOIN auth_user_md5 aum USING(user_id) ';
                $query .= 'LEFT JOIN user_info USING(user_id) LEFT JOIN user_inst ui USING(user_id) ';
                $query .= "WHERE su.statusgruppe_id='$group_id' AND ".get_ext_vis_query()." AND Institut_id = '" . $this->config->range_id . "'$query_order";
                
                $db->query($query);

                $position = array_search($group_id, $all_groups);
                if($aliases_groups[$position]) {
                    $group = $aliases_groups[$position];
                }
            }

        
            if ($db->num_rows()) {
                $aliases_groups = $this->config->getValue('Main', 'groupsalias');
                if($aliases_groups[$position]) {
                    $content['PERSONS']['GROUP'][$i]['GROUPTITLE-SUBSTITUTE'] = ExternModule::ExtHtmlReady($aliases_groups[$position]);
                }
                $content['PERSONS']['GROUP'][$i]['GROUPTITLE'] = ExternModule::ExtHtmlReady($group);
                $content['PERSONS']['GROUP'][$i]['GROUP-NO'] = $i + 1;
                
                $j = 0;
                while ($db->next_record()) {

                    $visibilities = get_local_visibility_by_id($db->f('user_id'), 'homepage', true);
                    $user_perm = $visibilities['perms'];
                    $visibilities = json_decode($visibilities['homepage'], true);

                    if ($defaultaddress) {
                        $query = 'SELECT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms,  Email, ';
                        $query .= 'title_front, title_rear, ';
                        $query .= 'aum.user_id, username, ' . $GLOBALS['_fullname_sql'][$nameformat];
                        $query .= ' AS fullname, aum.Nachname, aum.Vorname FROM auth_user_md5 aum LEFT JOIN ';
                        $query .= 'user_info USING(user_id) LEFT JOIN ';
                        $query .= "user_inst ui USING(user_id) WHERE aum.user_id = '" . $db->f('user_id');
                        $query .= "' AND ".get_ext_vis_query().' AND externdefault = 1';
                        $db_defaultaddress->query($query);
                        // no default
                        if (!$db_defaultaddress->next_record()) {
                            $query = 'SELECT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms,  Email, ';
                            $query .= 'title_front, title_rear, ';
                            $query .= 'aum.user_id, username, ' . $GLOBALS['_fullname_sql'][$nameformat];
                            $query .= ' AS fullname, aum.Nachname, aum.Vorname FROM auth_user_md5 aum LEFT JOIN ';
                            $query .= 'user_info USING(user_id) LEFT JOIN ';
                            $query .= "user_inst ui USING(user_id) WHERE aum.user_id = '" . $db->f('user_id');
                            $query .= "' AND ".get_ext_vis_query()." AND Institut_id = '" . $this->config->range_id . "'";
                            $db_defaultaddress->query($query);
                            $db_defaultaddress->next_record();
                        }
                    }
                    
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['FULLNAME'] = ExternModule::ExtHtmlReady($db_out->f('fullname'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['LASTNAME'] = ExternModule::ExtHtmlReady($db_out->f('Nachname'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['FIRSTNAME'] = ExternModule::ExtHtmlReady($db_out->f('Vorname'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['TITLEFRONT'] = ExternModule::ExtHtmlReady($db_out->f('title_front'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['TITLEREAR'] = ExternModule::ExtHtmlReady($db_out->f('title_rear'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['PERSONDETAIL-HREF'] = $this->elements['LinkInternTemplate']->createUrl(array('link_args' => 'username=' . $db_out->f('username')));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['USERNAME'] = $db_out->f('username');

                    if (is_element_visible_externally($db->f('user_id'), $user_perm, 'picture', $visibilities['picture'])) {
                        $avatar = Avatar::getAvatar($db_out->f('user_id'));
                    } else {
                        $avatar = Avatar::getNobody();
                    }
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['IMAGE-URL-SMALL'] = $avatar->getURL(Avatar::SMALL);
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['IMAGE-URL-MEDIUM'] = $avatar->getURL(Avatar::MEDIUM);
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['IMAGE-URL-NORMAL'] = $avatar->getURL(Avatar::NORMAL);

                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['PHONE'] = ExternModule::ExtHtmlReady($db_out->f('Telefon'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['ROOM'] = ExternModule::ExtHtmlReady($db_out->f('raum'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['EMAIL'] = get_visible_email($db->f('user_id'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['EMAIL-LOCAL'] = array_shift(explode('@', $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['EMAIL']));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['EMAIL-DOMAIN'] = array_pop(explode('@', $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['EMAIL']));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['OFFICEHOURS'] = ExternModule::ExtHtmlReady($db_out->f('sprechzeiten'));
                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['PERSON-NO'] = $j + 1;

                    // generic data fields
                    if (is_array($generic_datafields)) {
                        $localEntries = DataFieldEntry::getDataFieldEntries($db_out->f('user_id'), 'user');
                        #$datafields = $datafields_obj->getLocalFields($db_out->f('user_id'));
                        $k = 1;
                        foreach ($generic_datafields as $datafield) {
                            if (isset($localEntries[$datafield]) && 
                                    is_object($localEntries[$datafield] && 
                                    is_element_visible_externally($db_out->f('user_id'), 
                                        $user_perm, $localEntries[$datafield]->getId(), 
                                        $visibilities[$localEntries[$datafield]->getId()]))) {
                                $localEntry = $localEntries[$datafield]->getDisplayValue();
                                if ($localEntry) {
                                    $content['PERSONS']['GROUP'][$i]['PERSON'][$j]['DATAFIELD_' . $k] = $localEntry;
                                }
                            }
                            $k++;
                        }
                    }
                    $j++;
                }
            }
            $i++;
        }
        return $content;
    }

    function printout ($args) {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);
        echo $this->elements['TemplateGeneric']->toString(array('content' => $this->getContent($args), 'subpart' => 'PERSONS'));
        
    }
    
    function printoutPreview () {
        if (!$language = $this->config->getValue("Main", "language"))
            $language = "de_DE";
        init_i18n($language);
        
        echo $this->elements['TemplateGeneric']->toString(array('content' => $this->getContent(), 'subpart' => 'PERSONS', 'hide_markers' => FALSE));
        
    }
    
}

?>
