<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* persons.inc.php
*
*
*
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       persons
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// persons.inc.php
//
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
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

require_once('lib/visual.inc.php');
require_once('lib/user_visible.inc.php');
require_once("lib/classes/DataFieldEntry.class.php");
require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/extern_functions.inc.php');

global $_fullname_sql;

$range_id = $this->config->range_id;

//$all_groups = $this->config->getValue("Main", "groups");
if (!$all_groups = get_all_statusgruppen($range_id))
    die($GLOBALS["EXTERN_ERROR_MESSAGE"]);
else
    $all_groups = array_keys($all_groups);

if (!$group_ids = $this->config->getValue("Main", "groupsvisible"))
    die($GLOBALS["EXTERN_ERROR_MESSAGE"]);
else
    $group_ids = array_intersect($all_groups, $group_ids);

if (!is_array($group_ids))
    die($GLOBALS["EXTERN_ERROR_MESSAGE"]);

if (!$visible_groups = get_statusgruppen_by_id($range_id, $group_ids))
    die($GLOBALS["EXTERN_ERROR_MESSAGE"]);

$aliases_groups = $this->config->getValue("Main", "groupsalias");
$order = $this->config->getValue("Main", "order");
$sort = $this->config->getValue("Main", "sort");

$query_order = "";
foreach ($sort as $key => $position) {
    if ($position > 0)
        $query_order[$position] = $this->data_fields[$key];
}
if ($query_order) {
    ksort($query_order, SORT_NUMERIC);
    $query_order = " ORDER BY " . implode(",", $query_order);
}

$db = new DB_Seminar();
$grouping = $this->config->getValue("Main", "grouping");
if (!$nameformat = $this->config->getValue("Main", "nameformat"))
    $nameformat = "full_rev";
if(!$grouping) {
    $groups_ids = implode("','", $this->config->getValue("Main", "groupsvisible"));

    $query = "SELECT DISTINCT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms, Email, aum.user_id, username, ";
    $query .= $_fullname_sql[$nameformat] . " AS fullname, aum.Nachname ";
    if ($query_order) {
        $query .= "FROM statusgruppe_user LEFT JOIN auth_user_md5 aum USING(user_id) ";
        $query .= "LEFT JOIN user_info USING(user_id) LEFT JOIN user_inst ui USING(user_id) ";
        $query .= "WHERE statusgruppe_id IN ('$groups_ids') AND Institut_id = '$range_id' AND ";
        $query .= get_ext_vis_query() . $query_order;
    }
    else {
        $query .= "FROM statusgruppen s LEFT JOIN statusgruppe_user su USING(statusgruppe_id) ";
        $query .= "LEFT JOIN auth_user_md5 aum USING(user_id) ";
        $query .= "LEFT JOIN user_info USING(user_id) LEFT JOIN user_inst ui USING(user_id) ";
        $query .= "WHERE su.statusgruppe_id IN ('$groups_ids') AND Institut_id = '$range_id' AND ".get_ext_vis_query()." ORDER BY ";
        $query .= "s.position ASC, su.position ASC";
    }

    $db->query($query);
    $visible_groups = array("");
}

// generic data fields
$generic_datafields = $this->config->getValue("Main", "genericdatafields"); 
//  $datafields_obj = new DataFields();
//}

$repeat_headrow = $this->config->getValue("Main", "repeatheadrow");
$link_persondetails = $this->getModuleLink("Persondetails",
        $this->config->getValue("LinkIntern", "config"), $this->config->getValue("LinkIntern", "srilink"));
$data["data_fields"] = $this->data_fields;
$defaultadr = $this->config->getValue('Main', 'defaultadr');
if ($defaultadr) {
    $db_defaultadr = new DB_Seminar();
    $db_out = 'db_defaultadr';
}
else
    $db_out = 'db';

$out = "";
$first_loop = TRUE;
foreach ($visible_groups as $group_id => $group) {

    if ($grouping) {
        if (!$query_order) {
            $query_order = ' ORDER BY su.position';
        }
        $query = "SELECT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms,  Email, aum.user_id, username, ";
        $query .= $_fullname_sql[$nameformat] . " AS fullname, aum.Nachname ";
        $query .= "FROM statusgruppe_user su LEFT JOIN auth_user_md5 aum USING(user_id) ";
        $query .= "LEFT JOIN user_info USING(user_id) LEFT JOIN user_inst ui USING(user_id) ";
        $query .= "WHERE su.statusgruppe_id='$group_id' AND ".get_ext_vis_query()." AND Institut_id = '$range_id'$query_order";

        $db->query($query);

        $position = array_search($group_id, $all_groups);
        if($aliases_groups[$position])
            $group = $aliases_groups[$position];
    }

    if ($db->num_rows()) {

        if ($grouping && $repeat_headrow == "beneath")
            $out .= $this->elements["TableGroup"]->toString(array("content" => htmlReady($group)));

        if($repeat_headrow || $first_loop)
            $out .= $this->elements["TableHeadrow"]->toString();


        if ($grouping && $repeat_headrow != "beneath")
            $out .= $this->elements["TableGroup"]->toString(array("content" => htmlReady($group)));

        while ($db->next_record()) {
            if ($defaultadr) {
                $query = "SELECT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms,  Email, ";
                $query .= "aum.user_id, username, " . $_fullname_sql[$nameformat];
                $query .= " AS fullname, aum.Nachname FROM auth_user_md5 aum LEFT JOIN ";
                $query .= " user_info USING(user_id) LEFT JOIN ";
                $query .= "user_inst ui USING(user_id) WHERE aum.user_id = '" . $db->f('user_id');
                $query .= "' AND ".get_ext_vis_query()." AND externdefault = 1";
                $db_defaultadr->query($query);
                // no default
                if (!$db_defaultadr->next_record()) {
                    $query = "SELECT ui.raum, ui.sprechzeiten, ui.Telefon, inst_perms,  Email, ";
                    $query .= "aum.user_id, username, " . $_fullname_sql[$nameformat];
                    $query .= " AS fullname, aum.Nachname FROM auth_user_md5 aum LEFT JOIN ";
                    $query .= " user_info USING(user_id) LEFT JOIN ";
                    $query .= "user_inst ui USING(user_id) WHERE aum.user_id = '" . $db->f('user_id');
                    $query .= "' AND ".get_ext_vis_query()." AND Institut_id = '$range_id'";
                    $db_defaultadr->query($query);
                    $db_defaultadr->next_record();
                }
            }

            $email = get_visible_email($db->f('user_id'));
            $data["content"] = array(
                "Nachname"          => $this->elements["LinkIntern"]->toString(array("content" =>
                                                        htmlReady($$db_out->f("fullname")), "module" => "Persondetails",
                                                        "link_args" => "username=" . $$db_out->f("username"))),

                "Telefon"               => htmlReady($$db_out->f("Telefon")),

                "sprechzeiten"  => htmlReady($$db_out->f("sprechzeiten")),

                "raum"                  => htmlReady($$db_out->f("raum")),

                "Email"                 => $this->elements["Link"]->toString(array("content" =>
                                                        htmlReady($email),
                                                        "link" => "mailto:" . htmlReady($email)))
            );

            // generic data fields
            if (is_array($generic_datafields)) {
                $localEntries = DataFieldEntry::getDataFieldEntries($$db_out->f('user_id'));
//              $datafields = $datafields_obj->getLocalFields($$db_out->f("user_id"));
                foreach ($generic_datafields as $id) {
                    $data['content'][$id] = is_object($localEntries[$id]) ? $localEntries[$id]->getDisplayValue() : '';
                }
            }
            $out .= $this->elements["TableRow"]->toString($data);
        }
        $first_loop = FALSE;
    }
}

$this->elements["TableHeader"]->printout(array("content" => $out));

?>
