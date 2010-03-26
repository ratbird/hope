<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Frontend for the db integrity checks
* 
* 
*
* @author       André Noack <andre.noack@gmx.net>
* @access       public
* @modulegroup  admin_modules
* @module       admin_db_integrity
* @package      Admin
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_db_integrity.php
// Integrity checks for the Stud.IP database
// 
// Copyright (c) 2002 André Noack <noack@data-quest.de> 
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");

require_once 'lib/msg.inc.php'; 
require_once 'lib/visual.inc.php';

$CURRENT_PAGE = _("Überprüfen der Datenbank-Integrität");
Navigation::activateItem('/admin/tools/db_integrity');

include 'lib/seminar_open.php'; //hier werden die sessions initialisiert
include 'lib/include/html_head.inc.php';
include 'lib/include/header.php';   //hier wird der "Kopf" nachgeladen 

//global variables
$_integrity_plugins = array("User","Seminar","Institut","Archiv","Studiengang");
$_csw = new cssClassSwitcher();

?>
<table class="blank" cellspacing="0" cellpadding="2" border="0" width="100%">
    <tr><td  align="center">
        
<?
//check, if a plugin is activated
if($_REQUEST['plugin'] AND in_array($_REQUEST['plugin'],$_integrity_plugins)) {
    
    include_once $RELATIVE_PATH_ADMIN_MODULES."/IntegrityCheck".$_REQUEST['plugin'].".class.php";
    $plugin_name = "IntegrityCheck".$_REQUEST['plugin'];
    $plugin_obj = new $plugin_name;
    
    //query the user, if he really wants to delete
    if($_REQUEST['cmd'] == "assure" AND isset($_REQUEST['checkid'])) {
        $result = $plugin_obj->doCheck($_REQUEST['checkid']);
        $anzahl = $result->num_rows();
        $msg = "info§" . sprintf(_("Sie beabsichtigen %s Datens&auml;tze der Tabelle <b>%s</b> zu l&ouml;schen."), $anzahl, $plugin_obj->getCheckDetailTable($_REQUEST['checkid'])) . "<br>"
        ._("Dieser Schritt kann <u>nicht</u> r&uuml;ckg&auml;ngig gemacht werden! Sind sie sicher?") . " <br />\n"
        ."<br><a href=\"$PHP_SELF?plugin={$_REQUEST['plugin']}&cmd=delete&checkid={$_REQUEST['checkid']}\">" . makeButton("ja2", "img") . "</a>&nbsp;"
        ."<a href=\"$PHP_SELF\">" . makeButton("nein", "img") . "</a>\n";
        ?><table border="0" width="80%" cellpadding="2" cellspacing="0" class="steel1">
        <tr><td class="blank">&nbsp; </td></tr>
        <?
        parse_msg($msg,"§","steel1",1,FALSE);
        ?>
        <tr><td class="blank">&nbsp; </td></tr>
        </table><?
    
    //delete the rows in the according table
    } elseif($_REQUEST['cmd'] == "delete" AND isset($_REQUEST['checkid'])) {
        $result = $plugin_obj->doCheckDelete($_REQUEST['checkid']);
        if ($result === false) {
            $msg = "error§" . _("Beim L&ouml;schen der Datens&auml;tze trat ein Fehler auf!");
        } else {
            $msg = "msg§" . sprintf(_("Es wurden %s Datens&auml;tze der Tabelle <b>%s</b> gelöscht!"), $result, $plugin_obj->getCheckDetailTable($_REQUEST['checkid']));
        }
        unset($_REQUEST['plugin']);
    
    //show the found rows in the according table
    } elseif($_REQUEST['cmd'] == "show" AND isset($_REQUEST['checkid'])) {
        ?>
        <table border="0" width="80%" cellpadding="2" cellspacing="2">
        <tr><td class="blank" colspan="2">&nbsp; </td></tr>
        <tr><td class="blank"><b>
        <?
        printf(_("Bereich: <i>%s</i> Datens&auml;tze der Tabelle %s</b></td>"), $_REQUEST['plugin'], $plugin_obj->getCheckDetailTable($_REQUEST['checkid']));
        printf("<td class=\"blank\" align=\"center\"><a href=\"%s?plugin=%s&cmd=assure&checkid=%s\">", $PHP_SELF, $_REQUEST['plugin'], $_REQUEST['checkid']);
        print(makeButton("loeschen", "img")) . "</a> ";
        printf("<a href=\"%s\">", $PHP_SELF);
        print(makeButton("abbrechen", "img")) . "</a></td></tr>";
        ?> 
        <tr><td class="blank" colspan="2">&nbsp; </td></tr>
        <tr><td class="steel1" align="center" colspan="2">
        <?
        $db = $plugin_obj->getCheckDetailResult($_REQUEST['checkid']);
        ?><table border=1 class="steelgraulight" style="font-size:smaller" align="center"><tr><?
        $meta = $db->metadata();
        for($i = 0;$i < count($meta);++$i){ 
            echo "<th>" . $meta[$i]['name'] . "</th>";
        }
        echo "</tr>";
        while ($db->next_record()) {
            echo"<tr>";
            for($i = 0;$i < count($meta);++$i){ 
                echo "<td>&nbsp;".htmlReady(substr($db->f($i),0,50))."</td>";
                }
            echo"</tr>";
        }
        ?></table></td></tr>
        <tr><td class="blank" colspan="2">&nbsp; </td></tr>
        </table><?
    
    //no command is given, do all checks of the activated plugin
    } else {
        ?>
        <table border="0" width="80%" cellpadding="2" cellspacing="0">
        <tr><td class="blank" colspan="3">&nbsp; </td></tr>
        <tr><td class="blank" colspan="2"><b>
        <?
        printf(_("Bereich: <i>%s</i> der Datenbank wird gepr&uuml;ft!"), $_REQUEST['plugin']);
        ?>
        </b></td>
        <td class="blank" align="center"><a href="<?=$PHP_SELF?>"><?=makeButton("abbrechen", "img")?></a></td> </tr>
        <tr><td class="blank" colspan="3">&nbsp; </td></tr>
        <tr><th width="20%"><?=_("Tabelle")?></th><th width="60%"><?=_("Ergebnis")?></th><th width="20%"><?=_("Aktion")?></th></tr>
        <?
        for($i=0; $i < $plugin_obj->getCheckCount(); ++$i){
            echo "\n<tr><td ".$_csw->getFullClass().">".$plugin_obj->getCheckDetailTable($i)."</td>";
            echo "\n<td ".$_csw->getFullClass().">";
            $result = $plugin_obj->doCheck($i);
            $anzahl = $result->num_rows();
            printf("\n" . _("%s Datensätze gefunden") . "</td>", $anzahl);
            echo "\n<td ".$_csw->getFullClass().">";
            echo ($anzahl==0) ? "&nbsp;" : "<a href=\"{$PHP_SELF}?plugin={$_REQUEST['plugin']}&cmd=show&checkid={$i}\">"
                . makeButton("anzeigen", "img") . "</a>&nbsp;"
                ."<a href=\"{$PHP_SELF}?plugin={$_REQUEST['plugin']}&cmd=assure&checkid={$i}\">"
                . makeButton("loeschen", "img") . "</a></td></tr>";
            $_csw->switchClass();
        }
        ?><tr><td colspan="3">&nbsp;</td></tr></table><?
    }
}

//show all available plugins
if(!$_REQUEST['plugin']) {
    for($i=0; $i < count($_integrity_plugins); ++$i){
        include_once $RELATIVE_PATH_ADMIN_MODULES."/IntegrityCheck".$_integrity_plugins[$i].".class.php";
    }
    ?>
    <table border="0" width="80%" cellpadding="2" cellspacing="0">
    <?if ($msg) {
        echo "<tr><td class=\"blank\" colspan=\"4\">&nbsp; </td></tr>";
        parse_msg($msg,"§","blank", 4, FALSE);
    }
    ?>
    <tr><td class="blank" colspan="4">&nbsp; </td></tr>
    <tr><td class="blank" colspan="4"><b><?=_("Folgende Bereiche der Datenbank k&ouml;nnen gepr&uuml;ft werden:")?></b><br />&nbsp; </td></tr>
    <tr><th width="20%"><?=_("Bereich")?></th><th width="60%"><?=_("Beschreibung")?></th><th width="10%"><?=_("Anzahl")?></th><th width="10%"><?=_("Aktion")?></th></tr>
    <?
    for($i=0; $i < count($_integrity_plugins); ++$i) {
        $plugin_name = "IntegrityCheck".$_integrity_plugins[$i];
        $plugin_obj = new $plugin_name;
        echo "\n<tr><td ".$_csw->getFullClass().">&nbsp; ".$_integrity_plugins[$i]."</td>";
        echo "\n<td ".$_csw->getFullClass()." style=\"font-size:smaller\">" . _("&Uuml;berpr&uuml;ft Tabelle:") . " <b>".$plugin_obj->getCheckMasterTable()
            ."</b> " . _("gegen") . " <i>".join(", ",$plugin_obj->getCheckDetailList())."</i></td>";
        echo "\n<td align=\"center\" ".$_csw->getFullClass().">".$plugin_obj->getCheckCount()."</td>";
        echo "\n<td align=\"center\" ".$_csw->getFullClass()."><a href=$PHP_SELF?plugin=".$_integrity_plugins[$i]
            ."><img " . makeButton("jetzttesten", "src") . " border=\"0\" align=\"middle\" hspace=\"10\" vspace=\"10\"></a></td></tr>";
        $_csw->switchClass();
    }
    ?><tr><td colspan="3">&nbsp;</td></tr></table>
    <?php
}
echo '</td></tr></table>';
include ('lib/include/html_end.inc.php');
page_close();
?>
