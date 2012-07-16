<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
scm.php - Simple Content Module von Stud.IP
Copyright (C) 2000 André Noack <anoack@mcis.de>, Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>, 2003 Tobias Thelen <tthelen@uni-osnabrueck.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

use Studip\Button, Studip\LinkButton;
require '../lib/bootstrap.php';

unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(Request::get('again') && ($auth->auth["uid"] == "nobody"));

PageLayout::setHelpKeyword("Basis.Informationsseite");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/classes/StudipScmEntry.class.php');
require_once 'lib/functions.php';
require_once('lib/msg.inc.php');
require_once('lib/visual.inc.php');
require_once('lib/classes/Table.class.php');

checkObject(); // do we have an open object?
checkObjectModule("scm");
object_set_visit_module("scm");

$scms = array_values(StudipScmEntry::GetSCMEntriesForRange($SessSemName[1]));

$msg = ""; // Message to display

if ($_SESSION['scm-flash']) {
    $msg = $_SESSION['scm-flash'];
    unset($_SESSION['scm-flash']);
}

$i_view = Request::option('i_view');
$_show_scm = Request::option('show_scm', $scms[0]['scm_id']);

if ($perm->have_studip_perm('tutor', $SessSemName[1]) && in_array($i_view, words('change kill first_position'))) {
    if ($i_view == 'change') {
        $_show_scm = scm_change_content($_show_scm, $SessSemName[1], Request::get('scm_name'), Request::int('scm_preset'), Request::get('content'));
        $msg = "msg§"._("Die Änderungen wurden übernommen.");
    } else if ($i_view == 'kill') {
        $scm = new StudipScmEntry($_show_scm);
        if (!$scm->isNew() && $scm->getValue('range_id') == $SessSemName[1]){
            $scm->delete();
            $msg = "msg§" . _("Der Eintrag wurde gelöscht.");
        }
        $scms = array_values(StudipScmEntry::GetSCMEntriesForRange($SessSemName[1]));
        $_show_scm = $scms[0]['scm_id'];
    } else if ($i_view == 'first_position') {
        $scm = new StudipScmEntry($_show_scm);
        if (!$scm->isNew() && $scm->getValue('range_id') == $SessSemName[1]){
            $query = "SELECT MIN(mkdate) - 1 FROM scm WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($scm->getValue('range_id')));
            $minmkdate = $statement->fetchColumn();

            $query = "UPDATE scm SET mkdate = ? WHERE scm_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($minmkdate, $scm->getId()));
            if ($statement->rowCount() > 0) {
                $msg = "msg§" . _("Der Eintrag wurde an die erste Position verschoben.");
            }
        }
        $scms = array_values(StudipScmEntry::GetSCMEntriesForRange($SessSemName[1]));
        $_show_scm = $scms[0]['scm_id'];
    }
    $_SESSION['scm-flash'] = $msg;
    header('Location: ' . UrlHelper::getUrl('scm.php', array('show_scm' => $_show_scm)));
    page_close();
    die();
}

$scm = new StudipScmEntry($_show_scm);

PageLayout::setTitle($SessSemName["header_line"]. " - " . $scm->getValue('tab_name'));
Navigation::activateItem('/course/scm/' . $_show_scm);

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if ($i_view == 'edit') {
    scm_edit_content($SessSemName[1], $_show_scm);
} else {
    scm_show_content($SessSemName[1], $msg, $_show_scm);
}

function scm_max_cols()
{
    global $auth;
    //maximale spaltenzahl berechnen
    if ($auth->auth["jscript"]) {
        return round($auth->auth["xres"] / 12 );
    } else {
        return 64 ; //default für 640x480
    }
}

function scm_seminar_header($range_id, $site_name)
{
    $t=new Table();
    $t->setTableWidth("100%");
    echo $t->open();
    echo $t->openRow();
    echo $t->blankCell(array("class"=>"blank"));
    echo $t->openRow();
    echo $t->openCell(array("class"=>"blank"));
    return $t; // Cell is left open, content will be printed elsewhere
}

function scm_seminar_footer($table) {
    echo $table->close(); // close open cell, row and table
}

function scm_change_header($table, $titel, $user_id, $chdate) {
    $zusatz = "<font size=-1>";
    $zusatz .= sprintf(_("Zuletzt ge&auml;ndert von %s am %s"), "</font><a href=\"".URLHelper::getLink("about.php?username=".get_username($user_id))."\"><font size=-1 color=\"#333399\">".get_fullname ($user_id,'full',true)."</font></a><font size=-1>", date("d.m.Y, H:i",$chdate)."<font size=-1>&nbsp;"."</font>");
    $icon = Assets::img('icons/16/grey/infopage.png', array('class' => 'text-top'));

    echo $table->openRow();
    echo $table->openCell(array("colspan"=>"2"));
    $head_table=new Table(array("width"=>"100%"));
    echo $head_table->openRow();
    printhead(0, 0, false, "open", FALSE, $icon, $titel, $zusatz);
    echo $head_table->close();
    echo $table->closeRow();
}

function scm_show_content($range_id, $msg, $scm_id) {
    global $rechte, $SessSemName;

    $scm = new StudipScmEntry($scm_id);

    if ($scm_id == 'new_entry') $scm_id = null;

    $header_table = scm_seminar_header($range_id, $scm->getValue("tab_name"));

    $frame_table=new Table();
    $frame_table->setTableWidth("100%");
    $frame_table->setCellClass("blank");
    echo $frame_table->openCell();

    $content_table=new Table();
    $content_table->setTableWidth("99%");
    $content_table->setTableAlign("center");
    $content_table->setCellClass("printcontent");
    echo $content_table->open();
    if ($msg) {
        parse_msg($msg);
    }

    if (!$scm->isNew()) {
        scm_change_header($content_table, htmlReady($scm->getValue("tab_name")), $scm->getValue("user_id"), $scm->getValue("chdate"));
        echo $content_table->openRow();
        echo $content_table->openCell();
        $printcontent_table=new Table(array("width"=>"100%"));
        echo $printcontent_table->open();
        if ($rechte) {
            if(StudipScmEntry::GetNumSCMEntriesForRange($range_id) > 1){
                $edit .= LinkButton::create(_('Nach vorne'), URLHelper::getURL("?i_view=first_position&show_scm=$scm_id"), array('title' => _("Diese Seite an die erste Position setzen"))) . "&nbsp;";
            }
            $edit .= LinkButton::create(_('Bearbeiten'), URLHelper::getURL("?i_view=edit&show_scm=$scm_id"));
            if(StudipScmEntry::GetNumSCMEntriesForRange($range_id) > 1){
                $edit .= "&nbsp;". LinkButton::create(_('Löschen'), URLHelper::getURL("?i_view=kill&show_scm=$scm_id"));
            }
        } else {
            $edit = "&nbsp;";
        }
        printcontent(0,0, formatReady($scm->getValue("content")), $edit);
        echo $printcontent_table->close();
        echo $content_table->closeRow();
    } else {
        parse_msg("info§<font size=-1><b>". _("In diesem Bereich wurden noch keine Inhalte erstellt.") . "</b></font>", "§", "steel1", 2, FALSE);
    }
    echo $content_table->close();
    echo $frame_table->row(array("&nbsp;"));
    echo $frame_table->close();
    echo $header_table->close();
}

function scm_edit_content($range_id, $scm_id) {
    global $SCM_PRESET;

    if ($scm_id == 'new_entry') $scm_id = null;

    $scm = new StudipScmEntry($scm_id);

    if ($scm->isNew()){
        $scm->setValue('user_id', $GLOBALS['user']->id);
        $scm->setValue('chdate', time());
        $scm_id = 'new_entry';
    }

    $max_col = scm_max_cols();

    $header_table = scm_seminar_header($range_id, $scm->getValue("tab_name"));

    print("<form action=\"".URLHelper::getLink('')."\" method=\"POST\">");
    echo CSRFProtection::tokenTag();

    $frame_table=new Table();
    $frame_table->setTableWidth("100%");
    $frame_table->setCellClass("blank");
    echo $frame_table->openCell();

    print("<blockquote>");
    print(_("Hier k&ouml;nnen Sie eine Seite mit Zusatzinformationen zu Ihrer Veranstaltung gestalten. Sie können Links normal eingeben, diese werden anschlie&szlig;end automatisch als Hyperlinks dargestellt."));
    print("</blockquote>");

    $content_table=new Table();
    $content_table->setTableWidth("99%");
    $content_table->setTableAlign("center");
    $content_table->setCellClass("printcontent");
    echo $content_table->open();
    $titel="</b><input style=\"font-size:8 pt;\" type=\"TEXT\" name=\"scm_name\" value=\"".htmlReady($scm->getValue("tab_name"))."\" maxlength=\"20\" size=\"20\">";
    $titel.="</font size=\"-1\">&nbsp;"._("oder w&auml;hlen Sie hier einen Namen aus:")."&nbsp;\n";
    $titel.="<select style=\"font-size:8 pt;\" name=\"scm_preset\">";
    $titel.="<option value=\"0\">- "._("Vorlagen")." -</option>\n";
    foreach ($SCM_PRESET as $key=>$val)
        $titel.=sprintf("<option value=\"%s\">%s</option>\n", $key, htmlReady($val["name"]));
    $titel.="</select>";

    scm_change_header($content_table, $titel, $scm->getValue("user_id"), $scm->getValue("chdate"));

    $content = "<textarea class=\"add_toolbar\" name=\"content\" style=\"width: 90%\" cols=$max_col rows=10 wrap=virtual >".htmlReady($scm->getValue("content"))."</textarea>\n";
    $content.= "<input type=\"HIDDEN\" name=\"show_scm\" value=\"$scm_id\">";
    $content.= "<input type=\"HIDDEN\" name=\"i_view\" value=\"change\">";

    $edit = Button::create(_('Übernehmen'), 'send_scm', array('title' => _('Änderungen vornehmen')));   
    $edit.="&nbsp;" . LinkButton::createCancel(_('Abbrechen'));
    $edit .= "<font size=\"-1\">&nbsp;&nbsp;<a href=\"".URLHelper::getLink('dispatch.php/smileys')."\" target=\"_blank\">";

    $help_url = format_help_url("Basis.VerschiedenesFormat");
    $edit .= "Smileys</a>&nbsp;&nbsp;<a href=\"".$help_url."\" ";
    $edit .= "target=\"_blank\">Formatierungshilfen</a></font>\n";

    echo $content_table->openRow();
    echo $content_table->openCell();
    $printcontent_table=new Table(array("width"=>"100%"));
    echo $printcontent_table->open();
    printcontent(0,0, $content, $edit);
    echo $printcontent_table->close();
    echo $content_table->closeRow();
    echo $content_table->close();
    echo $frame_table->row(array("&nbsp;"));
    echo $frame_table->close();
    echo $header_table->close();

    print("</form>");
}

function scm_change_content($scm_id, $range_id, $name, $preset, $content) {
    global $user, $SCM_PRESET;

    if ($scm_id == 'new_entry') $scm_id = null;

    $scm = new StudipScmEntry($scm_id);

    if ($preset)
        $tab_name = $SCM_PRESET[$preset]["name"];
    else if (trim($name) != '')
        $tab_name = $name;
    else
        $tab_name = _('[kein Titel]');

    $scm->setValue('tab_name', $tab_name);
    $scm->setValue('content', $content);
    $scm->setValue('user_id', $user->id);
    $scm->setValue('range_id', $range_id);

    if ($scm->store() !== false) {
        return $scm->getId();
    } else {
        return false;
    }
}

echo "</td></tr></table>";

include ('lib/include/html_end.inc.php');
  // Save data back to database.
  page_close();

?>
