<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* extern_edit_module.inc.php
*
*
*
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       extern_edit_module
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// extern_edit_module.inc.php
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


require_once($RELATIVE_PATH_EXTERN.'/lib/ExternModule.class.php');
require_once($RELATIVE_PATH_EXTERN.'/lib/ExternConfig.class.php');
require_once('lib/msg.inc.php');

echo "<table class=\"blank\" border=\"0\" width=\"100%\" ";
echo "align=\"left\" cellspacing=\"0\" cellpadding=\"0\">\n";
// it's forbidden to use the command "new" with a given config_id
if ($_REQUEST['com'] == 'new') {
    $config_id = '';
}

$module = FALSE;
if ($_REQUEST['com'] == 'new') {
    foreach ($GLOBALS['EXTERN_MODULE_TYPES'] as $key => $type) {
        if ($type['module'] == $_REQUEST['mod']) {
            $configurations = ExternConfig::GetAllConfigurations($range_id, $key);
            if (!isset($configurations[$type['module']]) || sizeof($configurations[$type['module']]) < $GLOBALS['EXTERN_MAX_CONFIGURATIONS']) {
                $module = ExternModule::GetInstance($range_id, $type['module'], '', 'NEW');
            }
            else {
                $message = sprintf(_("Es wurden bereits %s Konfigurationen angelegt. Sie k&ouml;nnen f&uuml;r dieses Module keine weiteren Konfigurationen anlegen.")
                        , $GLOBALS['EXTERN_MAX_CONFIGURATIONS']);
                my_error($message, "blank", 1);
                echo "<tr><td class=\"blank\" align=\"center\">\n";
                echo '<a href="' . URLHelper::getLink('?list=TRUE') . '">';
                echo makeButton("zurueck");
                echo "</a>\n</td></tr>\n</table>\n";
                print_footer();
                page_close();
                exit;
            }
        }
    }
}
else {
    foreach ($GLOBALS['EXTERN_MODULE_TYPES'] as $type) {
        if ($type["module"] == $mod) {
            $module = ExternModule::GetInstance($range_id, $mod, $config_id);
        }
    }
}

if (!$module)
    die("Unknown module type");

// execute commands they modify attributes of given element
if ($execute_command)
    $module->executeCommand($edit, $execute_command, $pos);

$elements = $module->getAllElements();

// the first parameter of printOutEdit() has to be an array, because it is
// possible to open more than one element form
$edit_open = "";

foreach ($elements as $element) {
    if ($edit == $element->getName()) {
        $edit_open = array("$edit" => ($_REQUEST['com'] != 'close'));
    }
}
if ($_REQUEST['com'] == 'new' || $_REQUEST['com'] == 'edit' || $_REQUEST['com'] == 'open' || $_REQUEST['com'] == 'close') {
    echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";
    $module->printoutEdit($edit_open, $_POST, "", $edit);
}

if ($_REQUEST['com'] == 'store') {

    $faulty_values = $module->checkFormValues($edit);
    $fault = FALSE;
    foreach ($faulty_values as $faulty) {
        if (in_array(TRUE, $faulty)) {
            $message = sprintf(_("Bitte korrigieren Sie die mit %s gekennzeichneten Werte!"),
                    "<font color=\"#ff0000\" size=\"+1\"><b>*</b></font>");
            my_info($message);
            echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";
            $module->printoutEdit($edit_open, $_POST,
                    $faulty_values, $edit);
            $fault = TRUE;
            break;
        }
    }
    if (!$fault) {
        // This is the right place to trigger some functions by special
        // POST_VARS-values. At the moment there is only one: If the name of the
        // configuration was changed, setup the extern_config table.
        if ($edit == "Main" && $_POST["Main_name"] != $module->config->config_name) {
            if (!ExternConfig::ChangeName($module->config->range_id, $module->getType(), $module->config->getId(),
                    $module->config->config_name, $_POST["Main_name"])) {
                $message = _("Der Konfigurationsname wurde bereits für eine Konfiguration dieses Moduls vergeben. Bitte geben Sie einen anderen Namen ein.");
                my_error($message, "blank", 1);
                echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";
                $module->printoutEdit($edit_open, "$_POST", "", $edit);
            }
            $module->store($edit, $_POST);
            $message = _("Die eingegebenen Werte wurden übernommen und der Name der Konfiguration geändert.");
            my_msg($message, "blank", 1);
            echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";
            $module->printoutEdit($edit_open, "", "", $edit);
        }
        else {
            $module->store($edit, $_POST);
            $message = _("Die eingegebenen Werte wurden übernommen.");
            my_msg($message, "blank", 1);
            echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";
            $module->printoutEdit($edit_open, "", "", $edit);
        }
    }
}

echo "</td></tr>\n";
if (!$edit_open[$edit]) {
    echo "<tr><td class=\"blank\">&nbsp;</td></tr>\n";
    echo "<tr><td class=\"blank\" align=\"center\">";
    echo '<a href="' . URLHelper::getLink('?list=TRUE') . '">';
    echo "<img " . makeButton("zurueck", "src");
    echo " border=\"0\" align=\"absmiddle\"></a>\n</td></tr>\n";
}
echo "</table></td></tr></table>\n</td>\n<td width=\"10%\" class=\"blank\" valign=\"top\">\n";
echo "<table class=\"blank\" border=\"0\" width=\"100%\" ";
echo "align=\"left\" cellspacing=\"0\" cellpadding=\"5\">\n";
echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";

$info_edit_element = _("Um die Werte eines einzelnen Elements zu &auml;ndern, klicken Sie bitte den &quot;&Uuml;bernehmen&quot;-Button innerhalb des jeweiligen Elements.");
// the type of this module is not Global
if ($module->getType() != 0) {
    $info_preview = _("Um eine Vorschau der Seite zu erhalten, klicken Sie bitte hier:");
    $info_preview .= "<br>&nbsp;<div align=\"center\">";
    $info_preview .= '<a target="_blank" href="' . $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . 'extern.php';
    $info_preview .= "?module=" . $module->getName() . "&range_id=" . $module->config->range_id;
    $info_preview .= "&preview=1&config_id=" . $module->config->getId();
    if ($global_config = ExternConfig::GetGlobalConfiguration($module->config->range_id))
        $info_preview .= "&global_id=$global_config";
    $info_preview .= "\">";
    $info_preview .= makeButton("vorschau") . "</a></div><br>";
    $info_preview .= _("Die Vorschau wird in einem neuen Fenster ge&ouml;ffnet.") . "<br>";
    $info_preview .= _("Es werden eventuell nicht alle Einstellungen in der Vorschau angezeigt.");

    $info_content = array(
                                    array("kategorie" => "Information:",
                                                "eintrag" => array(
                                                    array("icon" => "ausruf_small.gif",
                                                                "text" => $info_edit_element
                                                    )
                                    )),
                                    array("kategorie" => "Aktion:",
                                            "eintrag" => array(
                                                    array("icon" => "ausruf_small.gif",
                                                                "text" => $info_preview,
                                                    )
                                    )));
}
// the type is Global -> no preview
else {
    $info_content = array(
                                    array("kategorie" => "Information:",
                                                "eintrag" => array(
                                                    array("icon" => "ausruf_small.gif",
                                                                "text" => $info_edit_element
                                                    )
                                    )));
}

print_infobox($info_content, "einrichtungen.jpg");

?>
