<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* admin_extern.inc.php
*
*
*
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       extern
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_extern.inc.php
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


include('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once('config.inc.php');         //wir brauchen die Seminar-Typen
require_once($RELATIVE_PATH_EXTERN . '/extern_config.inc.php');
require_once($RELATIVE_PATH_EXTERN . '/lib/extern_functions.inc.php');
require_once($RELATIVE_PATH_EXTERN . '/lib/ExternConfig.class.php');
require_once($RELATIVE_PATH_EXTERN . '/lib/ExternModule.class.php');
require_once 'lib/admin_search.inc.php';

// -- here you have to put initialisations for the current page

if ($_REQUEST['view'] == 'extern_global') {
    $range_id = 'studip';
    URLHelper::addLinkParam('view', 'extern_global');
} else {
    $range_id = $SessSemName[1] ? $SessSemName[1] : '';
    URLHelper::addLinkParam('view', 'extern_inst');
}
URLHelper::addLinkParam('cid', $range_id);

// when downloading a config, do it here and stop afterwards
if ($_REQUEST['com'] == 'download_config') {
    if ($range_id) {
        download_config($range_id, $_REQUEST['config_id'], $_REQUEST['module']);
        page_close();
        exit;
    }
}

$CURRENT_PAGE = _("Verwaltung externer Seiten");

if ($links_admin_data["topkat"] == "inst") {
    if (Request::get('section') == 'extern') {
        UrlHelper::bindLinkParam('section', $section);
        Navigation::activateItem('/course/admin/extern');
    } else {
        Navigation::activateItem('/admin/institute/external');
    }
} else {
    Navigation::activateItem('/admin/config/external');
}

//Change header_line if open object
$header_line = getHeaderLine($range_id);
if ($header_line) {
    $CURRENT_PAGE = $header_line." - ".$CURRENT_PAGE;
    foreach ($EXTERN_MODULE_TYPES as $key => $type) {
        if ($type["module"] == $mod) {
            $CURRENT_PAGE = $CURRENT_PAGE . " ({$EXTERN_MODULE_TYPES[$key]['name']})";
            break;
        }
    }
}

// upload of configuration
if ($_REQUEST['com'] == "do_upload_config") {
    $file_content = file_get_contents($the_file);
    $file_content_wo_tabs = preg_replace("/\t/", "  ", $file_content);

    $jsonconfig = json_decode($file_content_wo_tabs, true);

    if (!check_config($jsonconfig, $_REQUEST['check_module'])) {
        $msg ="error�". _("Die Konfigurationsdatei hat den falschen Modultyp!"). "�";
    } else if (!store_config($range_id, $_REQUEST['config_id'], $jsonconfig)) {
        $msg ="error�". _("Die Konfigurationsdatei konnte nicht hochgeladen werden!"). "�";
    } else {
        $msg = "info�". _("Die Datei wurde erfolgreich &uuml;bertragen!"). "�";
    }
}

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

require_once('lib/msg.inc.php'); //Funktionen f&uuml;r Nachrichtenmeldungen
require_once('lib/classes/cssClassSwitcher.inc.php');
require_once('lib/language.inc.php');

echo "<table border=\"0\" class=\"blank\" align=\"center\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n";
echo "<tr><td class=\"blank\" colspan=\"2\">&nbsp;</td></tr>\n";
if ($_REQUEST['com'] != "info") {
    echo "<tr><td class=\"blank\" align=\"center\" valign=\"top\" width=\"90%\">\n";
} else {
    echo "<tr><td class=\"blank\" align=\"center\" valign=\"top\" width=\"90%\" colspan=\"2\">\n";
}
echo "<table width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\">\n";

// copy existing configuration
if ($_REQUEST['com'] == 'copyconfig') {
    if ($_REQUEST['copyinstid'] && $_REQUEST['copyconfigid']) {
        $config = ExternConfig::GetInstance($_REQUEST['copyinstid'], '', $_REQUEST['copyconfigid']);
        $config_copy = $config->copy($range_id);
        my_msg(sprintf(_("Die Konfiguration wurde als \"%s\" nach Modul \"%s\" kopiert."), htmlReady($config_copy->getConfigName()), htmlReady($GLOBALS['EXTERN_MODULE_TYPES'][$config_copy->getTypeName()]['name'])), 'blank', 1, false);
    } else {
        $_REQUEST['com'] = '';
    }
}

if ($_REQUEST['com'] == 'delete') {
    $config = ExternConfig::GetInstance($range_id, '', $config_id);
    if ($config->deleteConfiguration()) {
        my_msg(sprintf(_("Konfiguration <strong>\"%s\"</strong> f�r Modul <strong>\"%s\"</strong> gel�scht!"), htmlReady($config->getConfigName()), htmlReady($GLOBALS['EXTERN_MODULE_TYPES'][$config->getTypeName()]['name'])), 'blank', 1, false);
    } else {
        my_error(_("Konfiguration konnte nicht gel�scht werden"));
    }
}

echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";

if ($_REQUEST['com'] == 'delete_sec') {
    $config = ExternConfig::GetConfigurationMetaData($range_id, $config_id);

    $message = sprintf(_("Wollen Sie die Konfiguration <b>&quot;%s&quot;</b> des Moduls <b>%s</b> wirklich l&ouml;schen?"), $config["name"], $GLOBALS["EXTERN_MODULE_TYPES"][$config["type"]]["name"]);
    $message .= '<br><br><a href="' . URLHelper::getLink('?com=delete&config_id='.$config_id) . '">';
    $message .= makeButton("ja2") . "</a>&nbsp; &nbsp;";
    $message .= '<a href="' . URLHelper::getLink('?list=TRUE&view=extern_inst') . '">';
    $message .= makeButton("nein") . "</a>";
    my_info($message, "blank", 1);
    print_footer();
    page_close();
    exit;
}

$css_switcher = new cssClassSwitcher();

if ($_REQUEST['com'] == 'info') {
    include($RELATIVE_PATH_EXTERN . "/views/extern_info_module.inc.php");
    print_footer();
    page_close();
    exit;
}

$element_command = FALSE;
if ($edit) {
    $element_commands = array('show', 'hide', 'move_left', 'move_right', 'show_group', 'hide_group', 'do_search_x');
    foreach ($element_commands as $element_command) {
        $element_command_form = $edit . "_" . $element_command;
        if ($_POST[$element_command_form]) {
            if (is_array($_POST[$element_command_form])) {
                $pos_tmp = array_keys($_POST[$element_command_form]);
                $pos = $pos_tmp[0];
            }
            $execute_command = $element_command;
            $_REQUEST['com'] = 'store';
        }
    }
}

if ($_REQUEST['com'] == 'new' || $_REQUEST['com'] == 'edit' || $_REQUEST['com'] == 'open' ||
        $_REQUEST['com'] == 'close' || $_REQUEST['com'] == 'store') {

    require_once($RELATIVE_PATH_EXTERN . "/views/extern_edit_module.inc.php");
    print_footer();
    page_close();
    exit;
}

// Some browsers don't reload the site by clicking the same link twice again.
// So it's better to use different commands to do the same job.
if ($_REQUEST['com'] == 'set_default' || $_REQUEST['com'] == 'unset_default') {
    if (!ExternConfig::SetStandardConfiguration($range_id, $config_id)) {
        page_close();
        exit;
    }
}

if ($EXTERN_SRI_ENABLE_BY_ROOT && $_REQUEST['com'] == 'enable_sri'
        && $perm->have_perm('root')) {
    enable_sri($range_id, $_REQUEST['sri_enable']);
}

echo "<table class=\"blank\" border=\"0\" width=\"95%\" ";
echo "align=\"left\" cellspacing=\"0\" cellpadding=\"0\">\n";

// messages
echo "<tr><td class=\"blank\" colspan=\"0\">";
echo parse_msg($msg);
echo "</td></tr>";

if ($EXTERN_SRI_ENABLE_BY_ROOT && $perm->have_perm('root')) {
    echo "<tr><td class=\"blank\">\n";
    echo '<form method="post" action="' . URLHelper::getLink('?com=enable_sri') . '">';
    echo '<blockquote><font size="2">';
    echo _("SRI-Schnittstelle freigeben");
    echo ' <input type="checkbox" name="sri_enable" value="1"';
    if (sri_is_enabled($range_id)) {
        echo ' checked="checked"';
    }
    echo '/> &nbsp;<input type="image" border="0" align="absmiddle" ';
    echo makeButton('uebernehmen', 'src');
    echo "></font></blockquote></form>\n</td></tr>\n";
} else {
    echo "<tr><td class=\"blank\">&nbsp;</td></tr>\n";
}
echo "<tr><td class=\"blank\">\n";

$configurations = ExternConfig::GetAllConfigurations($range_id);
$module_types_ordered = ExternModule::GetOrderedModuleTypes();

$choose_module_form = '';
// remove global configuration
array_shift($module_types_ordered);
foreach ($module_types_ordered as $i) {
    if ((sizeof($configurations[$GLOBALS['EXTERN_MODULE_TYPES'][$i]['module']]) < $EXTERN_MAX_CONFIGURATIONS)
        && ExternModule::HaveAccessModuleType($_REQUEST['view'], $i)) {
        $choose_module_form .= "<option value=\"{$EXTERN_MODULE_TYPES[$i]['module']}\">"
                . $EXTERN_MODULE_TYPES[$i]['name'] . "</option>\n";
    }
    if (isset($configurations[$EXTERN_MODULE_TYPES[$i]["module"]])) {
        $have_config = TRUE;
    }
}
// add global configuration on first position
array_unshift($module_types_ordered, 0);
// check for global configurations
if (isset($configurations[$EXTERN_MODULE_TYPES[0]["module"]])) {
    $have_config = TRUE;
}

if ($_REQUEST['com'] != 'copychoose') {
    echo "<blockquote><font size=\"2\">";
    echo _("Neue globale Konfiguration anlegen.");
    echo '&nbsp; <a href="' . URLHelper::getLink('?com=new&mod=Global') . '">' . makeButton("neuanlegen") . "</a>\n";
    echo "</blockquote>";
}

if ($choose_module_form != '') {
    if ($_REQUEST['com'] != 'copychoose') {
        echo '<form method="post" action="' . URLHelper::getLink('?com=new') . '">';
        echo "<blockquote><font size=\"2\">";
        $choose_module_form = "<select name=\"mod\">\n$choose_module_form</select>\n";
        printf(_("Neue Konfiguration f&uuml;r Modul %s anlegen."), $choose_module_form);
        echo "&nbsp; <input type=\"image\" " . makeButton("neuanlegen", "src") . " border=\"0\" align=\"absmiddle\">";
        echo "</font></blockquote>\n";
        echo "</form>\n";

        $conf_institutes = ExternConfig::GetInstitutesWithConfigurations(($GLOBALS['perm']->have_perm('root') && $_REQUEST['view'] == 'extern_global') ? 'global' : array('inst', 'fak'));
        if (sizeof($conf_institutes)) {
            echo '<form method="post" action="' . URLHelper::getLink('?com=copychoose') . '">';
            echo "<blockquote><font size=\"2\">";
            $choose_institute_copy = "<select name=\"copychooseinst\">\n";
            foreach ($conf_institutes as $conf_institute) {
                $choose_institute_copy .= sprintf("<option value=\"%s\" style=\"%s\">%s</option>\n", $conf_institute['institut_id'], ($conf_institute['fakultaets_id'] == $conf_institute['institut_id'] ? '"font-weight:bold;' : ''), htmlReady(strlen($conf_institute['name']) > 60 ? substr_replace($conf_institute['name'], '[...]', 30, -30) : $conf_institute['name']));
            }
            $choose_institute_copy .= "</select>\n";
            printf(_("Konfiguration aus Einrichtung %s kopieren."), $choose_institute_copy);
            echo "&nbsp; <input type=\"image\" " . makeButton("weiter", "src") . " border=\"0\" align=\"absmiddle\">";
            echo "</font></blockquote>\n";
            echo "</form>\n";
        }
    } else {
        if ($_REQUEST['com'] == 'copychoose') {
            $choose_module_select = "<select name=\"copyconfigid\">\n";
            $configurations_copy = ExternConfig::GetAllConfigurations($_REQUEST['copychooseinst']);
            foreach ($module_types_ordered as $module_type) {
                $print_module_name = TRUE;

                if (is_array($configurations_copy[$GLOBALS['EXTERN_MODULE_TYPES'][$module_type]['module']])) {
                    foreach ($configurations_copy[$GLOBALS['EXTERN_MODULE_TYPES'][$module_type]['module']] as $config_id_copy => $config_data_copy) {
                        if ($print_module_name) {
                            $choose_module_select .= '<option value="" style="font-weight: bold;">' . htmlReady($GLOBALS['EXTERN_MODULE_TYPES'][$module_type]['name']) . '</option>';
                        }
                        $choose_module_select .= '<option value="' . $config_id_copy . '">&nbsp;&nbsp;' . htmlReady($config_data_copy['name']) . '</option>';
                        $print_module_name = FALSE;
                    }
                }
            }

            echo '<form method="post" action="' . URLHelper::getLink('?com=copyconfig') . '">';
            echo "<blockquote><font size=\"2\">";
            printf(_("Konfiguration %s aus Einrichtung kopieren."), $choose_module_select . '</select>');
            echo "&nbsp; <input type=\"image\" " . makeButton('kopieren', 'src') . " border=\"0\" align=\"absmiddle\">&nbsp; &nbsp";
            echo '<a href="' . URLHelper::getLink('?list=TRUE&view=extern_inst') . '">' . makeButton('zurueck', 'img') . '</a>';
            echo "</font></blockquote>\n";
            echo "<input type=\"hidden\" name=\"copyinstid\" value=\"" . htmlReady($_REQUEST['copychooseinst']) . "\" />\n";
            echo "</form>\n";

        }
    }
}
else {
    echo "<blockquote><font size=\"2\">";
    echo _("Sie haben bereits f�r alle Module die maximale Anzahl von Konfigurationen angelegt. Um eine neue Konfiguration anzulegen, m&uuml;ssen Sie erst eine bestehende im gew&uuml;nschten Modul l&ouml;schen.");
    echo "</font></blockquote>\n";
}

echo "</td></tr>\n";

if (!$have_config) {
    echo "<tr><td class=\"blank\">\n<blockquote>\n<font size=\"2\">";
    echo _("Es wurden noch keine Konfigurationen angelegt.");
    echo "</font>\n</blockquote>\n</td></tr>\n";
} else {
    echo "<tr><td height=\"20\" class=\"". $css_switcher->getHeaderClass() . "\" valign=\"bottom\">\n";
    echo "<font size=\"2\"><b>&nbsp;";
    echo _("Angelegte Konfigurationen");
    echo "</b></font>\n</td></tr>\n";
    $css_switcher->switchClass();
    echo "<tr><td" . $css_switcher->getFullClass() . ">&nbsp;</td></tr>\n";
    echo "<tr><td" . $css_switcher->getFullClass() . " valign=\"top\">\n";
    echo "<table width=\"90%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
    echo "<tr><td" . $css_switcher->getFullClass();
    echo ">&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
    echo "<td" . $css_switcher->getFullClass() . ">\n";

    $css_switcher_2 = new CssClassSwitcher("", "topic");

    foreach ($module_types_ordered as $order) {
        $module_type = $GLOBALS['EXTERN_MODULE_TYPES'][$order];
    //foreach ($EXTERN_MODULE_TYPES as $module_type) {
        if (isset($configurations[$module_type["module"]])) {
            $css_switcher_2->switchClass();
            echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
            echo "<tr>\n<td class=\"" . $css_switcher_2->getHeaderClass() . "\">";
            echo "<font size=\"2\"><b>&nbsp; ";

            if (isset($configurations[$module_type["module"]][$config_id])) {
                echo "<a name=\"anker\"></a>\n";
            }
            echo $module_type["name"];

            echo "</b></font>\n</td></tr>\n";
            echo "<tr><td width=\"100%\" style=\"border-style:solid; border-width:1px; border-color:#000000;\">\n";

            echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
            $css_switcher_2->resetClass();

            foreach ($configurations[$module_type["module"]] as $configuration) {
                $css_switcher_2->switchClass();
                echo "<tr><td" . $css_switcher_2->getFullClass() . " width=\"65%\"><font size=\"2\">";
                echo "&nbsp;" . $configuration["name"] . "</font></td>\n";

                ?>
                <td <?= $css_switcher_2->getFullClass() ?> width="5%">
                    <a href="<?= URLHelper::getLink('?com=download_config&config_id='. $configuration['id'] .'&module='. $module_type["module"]) ?>">
                        <?= Assets::img('download', array('alt' => _("Konfigurationsdatei herunterladen"), 'title' => _("Konfigurationsdatei herunterladen"))) ?>
                    </a>
                </td>

                <td <?= $css_switcher_2->getFullClass() ?> width="5%">
                    <a href="<?= URLHelper::getLink('?com=upload_config&config_id='. $configuration['id']) ?>">
                        <?= Assets::img('upload', array('alt' => _("Konfigurationsdatei hochladen"), 'title' => _("Konfigurationsdatei hochladen"))) ?>
                    </a>
                </td>
                <?

                echo "<td" . $css_switcher_2->getFullClass() . " width=\"5%\">";
                $tooltip = _("weitere Informationen anzeigen");
                echo '<a href="' . URLHelper::getLink('?com=info&config_id=' . $configuration['id']) . '"><img src="';
                echo $GLOBALS['ASSETS_URL']."images/i.gif\" border=\"0\"" . tooltip($tooltip) . "></a>\n</td>\n";
                echo "<td" . $css_switcher_2->getFullClass() . " width=\"5%\">";

                // Switching for the is_default option. Read the comment above.
                if ($configuration["is_default"]) {
                    echo '<a href="' . URLHelper::getLink('?com=unset_default&config_id=' . $configuration['id']) . '#anker">';
                    $tooltip = _("Standard entziehen");
                    echo '<img src="';
                    echo $GLOBALS['ASSETS_URL']."images/on_small.gif\" border=\"0\"" . tooltip($tooltip) . ">\n";
                }
                else {
                    echo '<a href="' . URLHelper::getLink('?com=set_default&config_id=' . $configuration['id']) . '#anker">';
                    $tooltip = _("Standard zuweisen");
                    echo '<img src="'.$GLOBALS['ASSETS_URL']."images/off_small_blank.gif\" border=\"0\"" . tooltip($tooltip) . ">";
                }

                echo "</a>\n</td>\n";
                echo "<td" . $css_switcher_2->getFullClass() . " align=\"center\" width=\"5%\">\n";
                echo '<a href="' . URLHelper::getLink('?com=delete_sec&config_id=' . $configuration['id']) . '#anker">';
                echo '<img src="' . $GLOBALS['ASSETS_URL'];
                $tooltip = _("Konfiguration l�schen");
                echo "images/trash.gif\" border=\"0\"" . tooltip($tooltip) . "></a>\n</td>\n";
                echo "<td" . $css_switcher_2->getFullClass() . " align=\"right\" width=\"20%\" ";
                echo ">\n";
                echo '<a href="' . URLHelper::getLink('?com=edit&mod=' . $module_type['module'] . '&config_id=' . $configuration['id']) . '"><img ';
                echo makeButton("bearbeiten", "src") . " border=\"0\"";
                $tooltip = _("Konfiguration bearbeiten");
                echo tooltip($tooltip) . "></a>&nbsp;\n</td></tr>\n";

                if ($_REQUEST['com'] == 'upload_config' && $_REQUEST['config_id'] == $configuration['id']) {
                    $template = $GLOBALS['template_factory']->open('extern/upload_form');
                    $template->set_attribute('class', $css_switcher_2->getFullClass());
                    $template->set_attribute('module', $module_type['module']);
                    $template->set_attribute('config_id', $configuration['id']);
                    $template->set_attribute('max_filesize', 1024 * 100); // currently 100kb

                    echo $template->render();
                }
            }

            $css_switcher_2->resetClass();
            echo "</table>\n";
            echo "</td></tr>\n";
            $css_switcher_2->switchClass();
            echo "<tr><td" . $css_switcher_2->getFullClass() . ">&nbsp;</td></tr>";
            echo "</table>\n";
        }

    }
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "</td></tr><tr><td" . $css_switcher->getFullClass() . " colspan=\"2\">&nbsp;</td></tr>\n";
}
echo "</table></td></tr>\n";
echo "</table>\n</td>\n";
echo "<td class=\"blank\" width=\"10%\" valign=\"top\">\n";
echo "<table width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\">\n";
echo "<tr><td class=\"blank\" width=\"100%\" valign=\"top\">\n";
$info_max_configs = sprintf(_("Sie k�nnen pro Modul maximal %s Konfigurationen anlegen."),
        $EXTERN_MAX_CONFIGURATIONS);

if (sizeof($configurations)) {
    $info_set_default = _("Klicken Sie auf diesen Button, um eine Konfiguration zur Standard-Konfiguration zu erkl�ren.");
    $info_no_default = _("Wenn Sie keine Konfiguration als Standard ausgew&auml;hlt haben, wird die Stud.IP-Konfiguration verwendet.");
    $info_is_default = _("Dieses Symbol kennzeichnet die Standard-Konfiguration, die zur Formatierung herangezogen wird, wenn sie beim Aufruf dieses Moduls keine Konfiguration angeben.");
    $info_further_info = _("Klicken Sie auf diesen Button um weitere Informationen �ber diese Konfiguration zu erhalten. Hier finden Sie auch die Links, �ber die Sie die Module in Ihrer Website einbinden k�nnen.");
    $info_content = array(
                                    array("kategorie" => "Information:",
                                                "eintrag" => array(
                                                    array("icon" => "ausruf_small.gif",
                                                                "text" => $info_max_configs
                                                    ),
                                                    array("icon" => "on_small.gif",
                                                                "text" => $info_is_default
                                                    ),
                                                    array("icon" => "ausruf_small.gif",
                                                                "text" => $info_no_default
                                                    )
                                    )),
                                    array("kategorie" => "Aktion:",
                                            "eintrag" => array(
                                                    array("icon" => "i.gif",
                                                                "text" => $info_further_info,
                                                    ),
                                                    array("icon" => "off_small_blank.gif",
                                                                "text" => $info_set_default
                                                    ))
                                    ));
} else {
    $info_content = array(
                                    array("kategorie" => "Information:",
                                                "eintrag" => array(
                                                    array("icon" => "ausruf_small.gif",
                                                                "text" => $info_max_configs
                                                    )
                                    )));
}

print_infobox($info_content, "infoboxes/einrichtungen.jpg");
print_footer();
