<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
statusgruppen.php - Statusgruppen-Anzeige von Stud.IP.
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>

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


require '../lib/bootstrap.php';
unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

require_once ('lib/visual.inc.php');
require_once ('lib/statusgruppe.inc.php');
require_once 'lib/functions.php';

checkObject();
checkObjectModule("participants");

PageLayout::setHelpKeyword("Basis.InVeranstaltungGruppen");
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Funktionen / Gruppen"));
Navigation::activateItem('/course/members/view_groups');

ob_start();

// Hilfsfunktionen

// groupmail:
// create mailto:-Link fpr
// - groups (filter-argument ignored)
// - seminars (filter=empty or =all: Mail to all accepted participants)
//            (filter=prelim: Mail to all preliminarily accepted partic.)
//            (filter=waiting: Mail to all waiting or claiming partic.)
function groupmail($range_id, $filter="") {
    $type = get_object_type($range_id);
    if ($type == "group") {
        $db=new DB_Seminar;
        $db->query ("SELECT Email FROM statusgruppe_user LEFT JOIN auth_user_md5 USING(user_id) WHERE statusgruppe_id = '$range_id'");
        while ($db->next_record()) {
            $mailpersons .= ";".$db->f("Email");
        }
        $mailpersons = substr($mailpersons,1);
        return $mailpersons;
    }
    if ($type == "sem") {
        $db=new DB_Seminar;
        if ($filter=="" || $filter=="all") {
            $db->query ("SELECT Email FROM seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE Seminar_id = '$range_id'");
        } else if ($filter=="prelim") {
            $db->query ("SELECT Email FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = '$range_id' AND status='accepted'");

        } else if ($filter=="waiting") {
            $db->query ("SELECT Email FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = '$range_id' AND (status='awaiting' OR status='claiming')");
        } else {
            echo "<p>ERROR: unknown filter: $filter</p>";
        }
        while ($db->next_record()) {
            $mailpersons .= ";".$db->f("Email");
        }
        $mailpersons = substr($mailpersons,1);
        return $mailpersons;
    }
}


function PrintAktualStatusgruppen ($roles) {
    global $_fullname_sql,$SessSemName, $rechte, $user, $opened_groups;

    $db2 = new DB_Seminar();


    if (is_array($roles))
    foreach ($roles as $role_id => $data) {
        $title = $data['role']->getName();
        $size = $data['size'];
        echo "<table width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\" border=\"0\"><tr>";
        echo '<td width="90%" class="steel" style="height: 25px"><font size="-1">';
        $voll = CountMembersPerStatusgruppe ($role_id);
        if (Request::option('toggle_group') == $role_id) {
            echo '<a name="anker"></a>';
        }
        printf ("<b>%s&nbsp;<a href=\"%s\" class=\"tree\">%s&nbsp;%s</a></b></font>",
            CheckAssignRights($role_id,$user->id, $SessSemName[1])?"&nbsp;<a href=\"".URLHelper::getLink("?assign=$role_id#anker")."\">". Assets::img('icons/16/yellow/arr_2right.png', array('style' => 'vertical-align:bottom', 'title' => _("In diese Gruppe eintragen")))."</a>":"",
            UrlHelper::getLink('?#anker', array('toggle_group' => $role_id ,'bla' => rand())),
            isset($opened_groups[$role_id]) ? Assets::img('icons/16/blue/arr_1down.png', array('style' => 'vertical-align:bottom')) : Assets::img('icons/16/blue/arr_1right.png',array('style' => 'vertical-align:bottom')),
            htmlReady($title) . ' (' . (int)$voll . ')'
        );

        $limit = GetStatusgruppeLimit($role_id);
        if ($limit!=FALSE && ($data['role']->getSelfassign()  == '1' || $data['role']->getSelfassign()  == '2')) {
            if ($voll >= $limit)
                $limitcolor = "#CC0000";
            else
                $limitcolor = "008800";
            echo "<font size=\"-1\" color=$limitcolor>&nbsp;&nbsp;-&nbsp;&nbsp;";
            printf ("%s von %s Plätzen belegt",$voll, $limit);
            echo "&nbsp;</font>";
        }
        echo '</font></td><td width="10%" class="steel" valign="bottom" align="right" nowrap>';

        if ((CheckUserStatusgruppe($role_id, $user->id) || $rechte) && ($folder_id = CheckStatusgruppeFolder($role_id)) ){
            echo "<a href=\"".URLHelper::getLink("folder.php?cmd=tree&open=$folder_id#anker")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/files.png\" ".tooltip(_("Dateiordner vorhanden"))."></a>&nbsp;";
        }

        if ($rechte || CheckUserStatusgruppe($role_id, $user->id)) {  // nicht alle duerfen Gruppenmails/Gruppensms verschicken
            echo "&nbsp;<a href=\"".URLHelper::getLink("sms_send.php?sms_source_page=statusgruppen.php&group_id=".$role_id."&emailrequest=1&subject=".rawurlencode($SessSemName[0]))."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/move_right/mail.png\" " . tooltip(_("Systemnachricht mit Emailweiterleitung an alle Gruppenmitglieder verschicken")) . " border=\"0\"></a>&nbsp;";
            echo "&nbsp;<a href=\"".URLHelper::getLink("sms_send.php?sms_source_page=statusgruppen.php&group_id=".$role_id."&subject=".rawurlencode($SessSemName[0]))."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\" " . tooltip(_("Systemnachricht an alle Gruppenmitglieder verschicken")) . " border=\"0\"></a>&nbsp;";
        } else {
            echo "&nbsp;";
        }
        echo "</td>";
        echo "</tr>";
        if (isset($opened_groups[$role_id])) {
            if (!$rechte) {
                $db2->query("SELECT user_id, visible FROM seminar_user WHERE Seminar_id = '".$SessSemName[1]."'");
                while ($db2->next_record()) {
                    $visio[$db2->f('user_id')] = ($db2->f('visible') == 'yes') ? true : false;
                }
            }

            $db2->query ("SELECT statusgruppe_user.user_id, " . $_fullname_sql['full'] ." AS fullname, username, seminar_user.visible
                          FROM statusgruppe_user
                          INNER JOIN seminar_user USING (user_id)
                          LEFT JOIN auth_user_md5 USING(user_id)
                          LEFT JOIN user_info USING (user_id) WHERE statusgruppe_id = '$role_id' AND seminar_user.seminar_id = '{$SessSemName[1]}' ORDER BY statusgruppe_user.position ASC, Nachname ASC");
            $k = 1;

            while ($db2->next_record()) {
                if ($k % 2) {
                    $class="steel1";
                } else {
                    $class="steelgraulight";
                }
                echo '<tr>';
                echo '<td width="90%" class="'.$class.'">';
                if ($db2->f('visible') == 'yes' || ($db2->f('user_id') == $user->id) || $rechte) {
                    echo "<font size=\"-1\"><a href=\"".URLHelper::getLink("about.php?username=".$db2->f("username"))."\">&nbsp;".htmlReady($db2->f("fullname"))."</a>";
                    if  (($db2->f('user_id') == $user->id) && !($db2->f('visible') == 'yes') && !$rechte) {
                        echo ' (unsichtbar)';
                    }
                    echo '</font>';
                } else {
                    echo '<font size="-1" color="#666666">&nbsp;'. _("(unsichtbareR NutzerIn)"). '</font>';
                }

                echo '</td>';
                echo "<td width=\"10%\" class=\"$class\" align=\"right\">";
                if ((($data['role']->getSelfAssign() == '1')|| ($data['role']->getSelfassign()  == '2')) && $user->id == $db2->f("user_id")) {
                    echo "<a href=\"".URLHelper::getLink("?delete_id=".$role_id)."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/trash.png\" " . tooltip(_("Aus dieser Gruppe austragen")) . " border=\"0\"></a>&nbsp; ";
                }

                if (($visio[$db2->f('user_id')] || $rechte) && ($db2->f('user_id') != $user->id)) {
                    echo "<a href=\"".URLHelper::getLink("sms_send.php?sms_source_page=teilnehmer.php&rec_uname=".$db2->f("username"))."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\" " . tooltip(_("Systemnachricht an Benutzer verschicken")) . " border=\"0\"></a>";
                }
                echo "&nbsp;</td>";
                echo "</tr>";
                $k++;
            }
        }
        echo "</table><br><br>";

    }

}

function PrintNonMembers ($range_id)
{
    global $_fullname_sql, $rechte, $user, $opened_groups;
    $bereitszugeordnet = GetAllSelected($range_id);
    $db=new DB_Seminar;
    $query = "SELECT seminar_user.user_id, username, " . $_fullname_sql['full'] ." AS fullname, perms, seminar_user.visible FROM seminar_user  LEFT JOIN auth_user_md5 USING(user_id) LEFT JOIN user_info USING (user_id) WHERE Seminar_id = '$range_id' ORDER BY Nachname ASC";
    $db->query ($query);
    $nicht_zugeordnet = ($db->num_rows() - sizeof($bereitszugeordnet));
    if ($db->num_rows() > sizeof($bereitszugeordnet)) { // there are non-grouped members
        echo "<table width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\" border=\"0\"><tr>";
        echo "<td width=\"100%\" colspan=\"2\" class=\"steel\" style=\"height: 25px\"><font size=\"-1\">";
        if (Request::option('toggle_group') == 'non_members') {
            echo '<a name="anker"></a>';
        }
        printf ("<b>&nbsp;<a href=\"%s\" class=\"tree\">%s&nbsp;%s</a></b></font>",
            UrlHelper::getLink('?#anker', array('toggle_group' => 'non_members' ,'bla' => rand())),
            isset($opened_groups['non_members']) ? Assets::img('icons/16/blue/arr_1down.png', array('style' => 'vertical-align:bottom')) : Assets::img('icons/16/blue/arr_1right.png',array('style' => 'vertical-align:bottom')),
            _("keiner Funktion oder Gruppe zugeordnet") . ' (' . $nicht_zugeordnet . ')'
        );
        echo "</td></tr>";
        $k = 1;
        if (isset($opened_groups['non_members'])) {
            while ($db->next_record()) {
                if (!in_array($db->f("user_id"), $bereitszugeordnet)) {
                    if ($k % 2) {
                        $class="steel1";
                    } else {
                        $class="steelgraulight";
                    }
                    printf ("<tr>");
                    if ($rechte || $db->f("visible")=="yes" || $db->f("user_id")==$user->id) {
                        echo "<td width=\"90%\" class=\"$class\"><font size=\"-1\"><a href=\"".URLHelper::getLink("about.php?username=".$db->f("username"))."\">&nbsp;".htmlReady($db->f("fullname"))."</a>".($db->f("user_id") == $user->id && $db->f("visible") != "yes" ? " "._("(unsichtbar)") : '')."</font></td>";
                        echo "<td width=\"10%\" class=\"$class\" align=\"right\">";
                        echo "<a href=\"".URLHelper::getLink("sms_send.php?sms_source_page=teilnehmer.php&rec_uname=".$db->f("username"))."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\" " . tooltip(_("Systemnachricht an Benutzer verschicken")) . " border=\"0\"></a>";
                        echo "&nbsp;</td>";
                    } else {
                        echo "<td width=\"90%\" class=\"$class\"><font size=\"-1\" color=\"#666666\">". _("(unsichtbareR NutzerIn)"). "</font></td>";
                        echo "<td width=\"10%\" class=\"$class\" align=\"right\">";
                        echo "&nbsp;</td>";
                    }
                    echo "  </tr>";
                    $k++;
                }
            }
        }
    echo "</table><br><br>";
    }
    if ($nicht_zugeordnet > 1) {
        $Memberstatus = 1;
    } else {
        $Memberstatus = 2;
    }
    if (!sizeof($bereitszugeordnet)) {
        $Memberstatus = 0;
    }
    return $Memberstatus;
}

// Command-Parsing
UrlHelper::bindLinkParam('opened_groups', $opened_groups);

if ($assign = Request::option('assign')) {
    if (GetRangeOfStatusgruppe($assign)==$SessSemName[1] && CheckAssignRights($assign, $user->id, $SessSemName[1])) {
        InsertPersonStatusgruppe($user->id, $assign);
        $opened_groups[$assign] = true;
    }
}

if ($delete_id = Request::option('delete_id')) {
    if (GetRangeOfStatusgruppe($delete_id)==$SessSemName[1] && CheckUserStatusgruppe($delete_id, $user->id)){
        RemovePersonStatusgruppe($user->username, $delete_id);
    }
}

if (Request::option('toggle_group')) {
    if (isset($opened_groups[Request::option('toggle_group')])) {
        unset($opened_groups[Request::option('toggle_group')]);
    } else {
        $opened_groups[Request::option('toggle_group')] = true;
    }
}
// Beginn Darstellungsteil

if ($_SESSION['sms_msg']) {
    parse_msg ($sms_msg);
    unset($_SESSION['sms_msg']) ;
}

PrintAktualStatusgruppen(GetAllStatusgruppen($SessSemName[1], $user->id));
$anzahltext = PrintNonMembers($SessSemName[1]);

if ($anzahltext == 1) {
    $Memberstatus = _("Nicht alle Personen sind einer Funktion / Gruppe zugeordnet.");
}
if ($anzahltext == 2) {
    $Memberstatus = _("Alle Personen sind mindestens einer Funktion / Gruppe zugeordnet.");
}
if ($anzahltext == 0) {
    $Memberstatus = _("Niemand ist einer Funktion / Gruppe zugeordnet.");
}
if (!$perm->have_studip_perm('tutor', $SessSemName[1])) {
    $my_groups = GetGroupsByCourseAndUser($SessSemName[1], $user->id);
    if (count($my_groups)) {
        $Memberstatus = _("Sie selbst sind diesen Gruppen zugeordnet:");
        $Memberstatus .= '<div style="font-weight:bold">' . join('</div><div style="font-weight:bold">', array_map('htmlReady', $my_groups)) . '</div';
    } else {
        $Memberstatus = _("Sie sind noch keiner Gruppe zugeordnet.");
    }

}

list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($SessSemName[1]);

$infobox = array    (
    array  ("kategorie"  => _("Information:"),
        "eintrag" => array  (
            array ( "icon" => "icons/16/black/info.png",
                "text"  => $Memberstatus
                )
            )
        )
    );

if($self_assign_exclusive){
    $infobox[0]["eintrag"][] = array ("icon" => "icons/16/black/info.png" ,
        "text"  => _("In dieser Veranstaltung können Sie sich nur in eine der möglichen Gruppen eintragen.")
        );

}

$infobox[1]["kategorie"] = _("Aktionen:");
$infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/mail.png" ,
    "text"  => _("Um Personen eine systeminterne Kurznachricht zu senden, benutzen Sie bitte das normale Briefsymbol.")
    );
$infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/arr_2right.png" ,
    "text"  => _("In Gruppen mit diesem Symbol können Sie sich selbst eintragen. Klicken Sie auf das jeweilige Symbol um sich einzutragen.")
    );
$infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/trash.png" ,
    "text"  => _("Aus diesen Gruppen können Sie sich selbst austragen.")
    );
if ($rechte) {
    $adr_all=groupmail($SessSemName[1], "all");
    $adr_prelim=groupmail($SessSemName[1], "prelim");
    $adr_waiting=groupmail($SessSemName[1], "waiting");
    $link_mail_all = $adr_all ? "<a href=\"".URLHelper::getLink("sms_send.php?course_id=".$SessSemName[1]."&emailrequest=1&subject=".rawurlencode($SessSemName[0])."&filter=all")."\">" : NULL;
    $link_mail_prelim = $adr_prelim ? "<a href=\"".URLHelper::getLink("sms_send.php?course_id=".$SessSemName[1]."&emailrequest=1&subject=".rawurlencode($SessSemName[0])."&filter=prelim")."\">" : NULL;
    $link_mail_waiting = $adr_waiting ? "<a href=\"".URLHelper::getLink("sms_send.php?course_id=".$SessSemName[1]."&emailrequest=1&subject=".rawurlencode($SessSemName[0])."&filter=waiting")."\">" : NULL;
    #$link_mail_all = $adr_all ? "<a href=\"mailto:".$adr_all."?subject=".rawurlencode($SessSemName[0])."\">" : NULL;
    #$link_mail_prelim = $adr_prelim ?  "<a href=\"mailto:".$adr_prelim."?subject=".rawurlencode($SessSemName[0])."\">" : NULL;
    #$link_mail_waiting = $adr_waiting ? "<a href=\"mailto:".$adr_waiting."?subject=".rawurlencode($SessSemName[0])."\">" : NULL;
    if ($link_mail_all) {
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/mail.png" ,
            "text"  => sprintf(_("Um eine E-Mail an alle TeilnehmerInnen der Veranstaltung zu versenden, klicken Sie %shier%s."), $link_mail_all, "</a>")
            );
    }
    if ($link_mail_waiting) {
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/mail.png" ,
            "text"  => sprintf(_("Um eine E-Mail an alle TeilnehmerInnen auf der Warteliste zu versenden, klicken Sie %shier%s."), $link_mail_waiting, "</a>")
            );
    }
    if ($link_mail_prelim) {
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/mail.png" ,
            "text"  => sprintf(_("Um eine E-Mail an alle vorläufig akzeptierten TeilnehmerInnen zu versenden, klicken Sie %shier%s."), $link_mail_prelim, "</a>")
            );
    }
    if (get_config('EXPORT_ENABLE') && $perm->have_studip_perm("tutor", $SessSemName[1])) {
        include_once($PATH_EXPORT . "/export_linking_func.inc.php");
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/blue/file-text.png" ,
            "text"  => export_link($SessSemName[1], "person", _("Gruppenliste") .' ' . $SessSemName[0], "rtf", "rtf-gruppen", "status",  _("Gruppen exportieren als rtf Dokument"), 'passthrough')
            );
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/blue/file-xls.png" ,
            "text"  => export_link($SessSemName[1], "person", _("Gruppenliste") .' ' . $SessSemName[0], "csv", "csv-gruppen", "status",  _("Gruppen exportieren als csv Dokument"), 'passthrough')
            );
    }
}

$layout = $GLOBALS['template_factory']->open('layouts/base.php');

$layout->infobox = array('content' => $infobox, 'picture' => "infobox/groups.jpg");
$layout->content_for_layout = ob_get_clean();

echo $layout->render();
page_close();
