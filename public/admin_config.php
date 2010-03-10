<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* admin_config.php
*
* backend for administration of new db-based config values for studip
*
*
* @author       Cornelis Kater <info@ckater.de>
* @access       public
* @module       admin_config.php
* @modulegroup      admin
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_modules.php
// Module fuer Veranstaltungen und Einrichtungen (definiert in Modules.class.php) an/abschalten
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once('lib/msg.inc.php');    //messages
require_once('config.inc.php'); //Settings....
require_once 'lib/functions.php';   //whatever ;)
require_once('lib/visual.inc.php'); //visuals
require_once('lib/classes/Config.class.php');   //Acces to config-values
require_once('lib/classes/UserConfig.class.php');   //Acces to userconfig-values

$db = new DB_Seminar();
$cssSw=new cssClassSwitcher;
$sess->register("admin_config_data");
//$admin_config_data["range_id"] = '';

$CURRENT_PAGE = _("Verwaltung von Konfigurationsvariablen");
Navigation::activateItem('/admin/config/settings');

if ($_REQUEST["select_username"] && !isset($_REQUEST['reset_search_x'])) {
    $admin_config_data["range_id"] = get_userid ($_REQUEST["select_username"]);
}

if ($_REQUEST["reset_range"]) {
    $admin_config_data["range_id"] = '';
}

if ($_REQUEST["range_id"]) {
    $admin_config_data["range_id"] = $_REQUEST["range_id"];
}

if (!$admin_config_data["range_id"])
    $cfg = new Config;
else
    $cfg = new UserConfig($admin_config_data["range_id"]);

if ($_REQUEST["change_config"]) {
    foreach ($_REQUEST["change_config"] as $key=>$val) {
        if ($val === "FALSE")
            $val = FALSE;
        elseif ($val === "TRUE")
            $val = TRUE;
        //set value for userconfig
        if ($admin_config_data["range_id"]) {
            $cfg->setValue($val, $admin_config_data["range_id"], $key, $_REQUEST["change_comment"][$key]);
        //else global (systemconfig)
        } else {
            $cfg->setValue($val, $key, $_REQUEST["change_comment"][$key]);
        }
    }
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank" valign="top">
            <?
            if (isset($msg)) {
            ?>
                <table border="0">
                <?parse_msg($msg);?>
                </table>
            <? } ?>
            <br>
            <blockquote>
            <b><?=_("Systemkonfiguration") ?></b><br><br>
            <?=_("Sie k&ouml;nnen hier einen Teil der Systemkonfiguration direkt ver&auml;ndern. Sie k&ouml;nnen sowohl auf System- als auch Nutzervariablen zugreifen.")?> <br>
            <?=_("Beachten Sie: Bisher ist nur ein kleiner Teil der Werte hier verf&uuml;gbar. Zuk&uuml;nftige Stud.IP-Versionen werden einen umfangreichen Zugriff auf s&auml;mtliche Systemeinstellungen zulassen. ")?> <br><br>
            </blockquote>
        </td>
        <td class="blank" align="right" valign="top"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="10" width="5"><br>
            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/modules.jpg" border="0"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="10" width="10">
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">
            <?
            $out[] = '<form method="post" action="'.$PHP_SELF.'">';
            $out[] = '<table width="95%" border=0 cellpadding=0 cellspacing=0 align="center">';
            $out[] = '<tr><td width="50%"style="border: dotted 1px black; background: url(\''.$GLOBALS['ASSETS_URL'].'images/steel1.jpg\')" valign="top">';
            $out[] = '<font size="-1">&nbsp;'._("Gew&auml;hlter Konfigurations-Bereich:").'&nbsp;<br>';
            if (!$admin_config_data["range_id"]) {
                $out[] = '&nbsp;<b>'._("Systemkonfiguration und -defaults").'</b>';
            } else {
                $out[] = '&nbsp;<b>'.htmlReady(get_fullname($admin_config_data["range_id"])).'</b>';
                $out[] = '&nbsp;<a href="'.$PHP_SELF.'?reset_range=1"><img src="'.$GLOBALS['ASSETS_URL'].'images/rewind.gif" '.tooltip(_("Gewählten Bereich löschen und zurück zu Systemkonfiguration")).' border="0"></a>';
            }
            $out[] = '</font></td>';
            $out[] = '<td width="50%"style="border: dotted 1px black; background: url(\''.$GLOBALS['ASSETS_URL'].'images/steel1.jpg\')">';
            $out[] = '<font size="-1">&nbsp;'._("einen anderen Bereich (Nutzer) w&auml;hlen:").'&nbsp;';
            if (($_REQUEST["search_exp"]) && ($search_user_x)) {
                $db->query ("SELECT username, ". $_fullname_sql['full_rev'] ." AS fullname FROM auth_user_md5 LEFT JOIN user_info USING(user_id) WHERE (username LIKE '%".$_REQUEST["search_exp"]."%' OR Vorname LIKE '%".$_REQUEST["search_exp"]."%' OR Nachname LIKE '%".$_REQUEST["search_exp"]."%') ORDER BY Nachname");
                if ($db->num_rows()) {
                    $out[] = '<a name="a"></a>';
                    $out[] = sprintf ('<br>&nbsp;<font size=-1><b>%s</b> '._("NutzerInnen gefunden:").'<br>', $db->num_rows());
                    $out[] = '&nbsp;<select style="font-size: 8pt" name="select_username">';
                    while ($db->next_record()) {
                        $out[].= sprintf ('<option value="%s">%s </option>', $db->f("username"), htmlReady(my_substr($db->f("fullname").' ('.$db->f("username").')', 0, 30)));
                    }
                    $out[].= '</select></font>';
                    $out[] = '&nbsp;<input type="IMAGE" src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif" '.tooltip(_("Den/die BenutzerIn hinzufügen")).' border="0" name="send_user_id">';
                    $out[] = '&nbsp;<input type="IMAGE" src="'.$GLOBALS['ASSETS_URL'].'images/rewind.gif" '.tooltip(_("neue Suche starten")).' border="0" name="reset_search">';
                }
            }
            if ((!$_REQUEST["search_exp"]) || (($_REQUEST["search_exp"]) && (!$db->num_rows()))) {
                $out[] = '<font size=-1>';
                if (($_REQUEST["search_exp"]) && (!$db->num_rows()))
                    $out[] = _("KeineN NutzerIn gefunden.").'<a name="a"></a>';
                $out[] = '</font><br>';
                $out[] = '&nbsp;<input type="TEXT" size="30" maxlength="255" name="search_exp">&nbsp;';
                $out[] = '<input type="IMAGE" src="'.$GLOBALS['ASSETS_URL'].'images/suchen.gif"'.tooltip(_("Suche starten")).' border="0" name="search_user"><br>';
                $out[] = '&nbsp;<font size=-1>'._("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein.").'</font>';
            }
            $out[] = '</td></tr></table>';
            $out[] = '</form>';
            print implode("\n", $out);
            $out ='';
            ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>
        &nbsp;
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>
            <table width="99%" border=0 cellpadding=0 cellspacing=3>
            <?
            $config_values = $cfg->getAll();
                $out[] = '<form method="post" action="'.$PHP_SELF.'#anker">';
                foreach ($config_values as $key => $val) {
                    $out[] = '<tr><td width="5%">&nbsp;</td>';
                    $out[] = '<td width="95%" style="border: solid 1px black; background: url(\''.$GLOBALS['ASSETS_URL'].'images/steel1.jpg\')" align="right">';
                    $out[] = '<table width="100%" border=0 cellpadding=0 cellspacing=0>';
                    $out[] = '<tr><td><b><font size="-1">&nbsp;'.$key.'</font></b></td>';
                    $out[] = '<td width="3%" class="steelgraulight" nowrap valign="top">';
                    if ($_REQUEST["edit_config"] == $key) {
                        $out[].= '<input type="IMAGE" src="'.$GLOBALS['ASSETS_URL'].'images/GruenerHakenButton.png">';
                    } else {
                        $out[].= '<a href="'.$PHP_SELF.'?edit_config='.$key.'#anker">';
                        $out[].= '&nbsp;<img src="'.$GLOBALS['ASSETS_URL'].'images/edit_transparent.gif">';
                    }
                    $out[] = '<td width="20%" class="steelgraulight" align="center"><font size="-1">';
                    if ($_REQUEST["edit_config"] == $key || isset($_REQUEST['change_config'][$key])) $out[] = '<a name="anker"> </a>';
                    if ($_REQUEST["edit_config"] == $key) {
                        if ($val["type"] == "boolean") {
                            $out[].= '<select style="font-size: 8pt" name="change_config['.$key.']">';
                            $out[].= '<option value="TRUE" '.(($val["value"]) ? "selected" : "").'>TRUE</option>';
                            $out[].= '<option value="FALSE" '.((!$val["value"]) ? "selected" : "").'>FALSE</option>';
                            $out[].= '</select>';
                        } elseif ($val["type"] == "integer") {
                            $out[].= '<input type="TEXT" style="font-size: 8pt" maxlength=20 size=20 name="change_config['.$key.']" value="'.htmlReady($val["value"]).'">';
                        } else {
                            $out[].= '<textarea style="font-size: 8pt;width:100%;" wrap="virtual" rows="4" name="change_config['.$key.']">'.htmlReady($val["value"]).'</textarea>';
                        }
                        $out[].= '<br><font size="-1">'. _("Kommentar:") .'</font><br>';
                        $out[].= '<textarea style="font-size: 8pt;width:100%;" wrap="virtual" rows="4" name="change_comment['.$key.']">'.htmlReady($val["comment"]).'</textarea>';
                    } elseif ($val["type"] == "boolean") {
                        if ($val["value"]) {
                            $out[].= '<img src="'.$GLOBALS['ASSETS_URL'].'images/haken_transparent.gif">';
                        } else {
                            $out[].= '<img src="'.$GLOBALS['ASSETS_URL'].'images/x_transparent.gif">';
                        }
                    } else {
                        $out[].= '<i>'.htmlReady($val["value"]).'</i>';
                    }

                    $out[].= '</font></td>';
                    $out[] = '<td width="10%"><font size="-1">&nbsp;'.$val["type"].'</font></td>';
                    $out[] = '<td width="30%" class="steelgraulight"><font size="-1">&nbsp;'.($val["description"] ? htmlReady($val["description"]) : _("Keine Beschreibung vorhanden")).'</font></td>';
                    $out[] = '</tr>';
                    $out[] = '</table></td></tr>';
                }
                $out[] = '<input type="HIDDEN" name="range_id" value="'.$admin_config_data["range_id"].'">';
                $out[] = '</form>';
            print implode("\n", $out);
            $out ='';
            ?>
            </table>
        </td>
    </tr>
</table>
<?php
    include ('lib/include/html_end.inc.php');
    page_close();
?>
