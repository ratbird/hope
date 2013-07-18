<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/*
inst_admin.php - Instituts-Mitarbeiter-Verwaltung von Stud.IP
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once("lib/msg.inc.php"); //Ausgaberoutinen an den Benutzer
require_once("config/config.inc.php"); //Grunddaten laden
require_once("lib/visual.inc.php"); //htmlReady
require_once ("lib/statusgruppe.inc.php");  //Funktionen der Statusgruppen
require_once ("lib/classes/DataFieldEntry.class.php");
require_once('lib/classes/searchtypes/SQLSearch.class.php');

// if we are not in admin_view, we get the proper set variable from institut_members.php
$admin_view = Request::option('admin_view',false);

if ($perm->have_studip_perm('tutor', $SessSemName[1])) {
    $rechte = true;
}

// this page is used for administration (if the user has the proper rights)
// or for just displaying the workers and their roles
if ($admin_view) {
    PageLayout::setTitle(_("Verwaltung der MitarbeiterInnen"));
    Navigation::activateItem('/admin/institute/faculty');
    $perm->check("admin");
} else {
    PageLayout::setTitle(_("Liste der MitarbeiterInnen"));
    Navigation::activateItem('/course/faculty/view');
    $perm->check("autor");
}

require_once 'lib/admin_search.inc.php';

//get ID from a open Institut. We have to wait until a links_*.inc.php has opened an institute (necessary if we jump directly to this page)
if ($SessSemName[1])
    $inst_id=$SessSemName[1];

if (!$admin_view) {
    checkObject();
    checkObjectModule("personal");
}

if ($admin_view && !$perm->have_studip_perm('admin', $inst_id)) {
    $admin_view = false;
}

//Change header_line if open object
$header_line = getHeaderLine($inst_id);
if ($header_line)
  PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

// Start of Output
include ("lib/include/html_head.inc.php"); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

if ($admin_view || !isset($inst_id)) {
    include 'lib/include/admin_search_form.inc.php';
}

// check the given parameters or initialize them
if ($perm->have_studip_perm("admin", $inst_id)) {
  $accepted_columns = array("Nachname", "inst_perms");
} else {
  $accepted_columns = array("Nachname");
}
$sortby = Request::option('sortby');
$extend = Request::option('extend');
if (!in_array($sortby, $accepted_columns)) {
  $sortby = "Nachname";
  $statusgruppe_user_sortby = "position";
} else {
  $statusgruppe_user_sortby = $sortby;
}
$direction = Request::option('direction');
if ($direction == "ASC") {
  $new_direction = "DESC";
} else if ($direction == "DESC") {
  $new_direction = "ASC";
} else {
    $direction = "ASC";
    $new_direction = "DESC";
}
$show = Request::option('show');
if (!isset($show)) {
    $show = "funktion";
}
URLHelper::addLinkParam('admin_view', $admin_view);
URLHelper::addLinkParam('sortby', $sortby);
URLHelper::addLinkParam('direction', $direction);
URLHelper::addLinkParam('show', $show);
URLHelper::addLinkParam('extend', $extend);

$groups = GetAllStatusgruppen($inst_id);
$group_list = GetRoleNames($groups, 0, '', true);

$cmd = Request::option('cmd');
$role_id = Request::option('role_id');
$username = Request::quoted('username');
if ($cmd == 'removeFromGroup' && $perm->have_studip_perm('admin', $inst_id)) {
    $query = "DELETE FROM statusgruppe_user
              WHERE statusgruppe_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($role_id, get_userid($username)));
}

if ($cmd == 'removeFromInstitute' && $perm->have_studip_perm('admin', $inst_id)) {
    $del_user_id = get_userid($username);
    if (is_array($group_list) && count($group_list) > 0) {
        $query = "DELETE FROM statusgruppe_user
                  WHERE statusgruppe_id IN (?) AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(array_keys($group_list), $del_user_id));
    }

    $query = "DELETE FROM user_inst
              WHERE user_id = ? AND Institut_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($del_user_id, $inst_id));

    log_event('INST_USER_DEL', $inst_id, $del_user_id);
}


function table_head ($structure) {
    echo "<colgroup>\n";
    foreach ($structure as $key => $field) {
        if ($key != 'statusgruppe') {
            printf("<col width=\"%s\">", $field["width"]);
        }
    }
    echo "\n</colgroup>\n";

    echo "<tr>\n";

    $begin = TRUE;
    foreach ($structure as $key => $field) {
        if ($begin) {
            printf ("<th width=\"%s\">", $field["width"]);
            $begin = FALSE;
        }
        else
            printf ("<th width=\"%s\" align=\"left\" valign=\"bottom\" ".($key == 'nachricht' ? 'colspan="2"':'').">", $field["width"]);

        if ($field["link"]) {
            printf("<a href=\"%s\">", URLHelper::getLink($field["link"]));
            printf("<font size=\"-1\"><b>%s&nbsp;</b></font>\n", htmlReady($field["name"]));
            echo "</a>\n";
        }
        else
            printf("<font size=\"-1\" color=\"black\"><b>%s&nbsp;</b></font>\n", htmlReady($field["name"]));
        echo "</td>\n";
    }
    echo "</tr>\n";
}


function table_body ($members, $range_id, $structure) {
    global $datafields_list, $group_list, $admin_view;

    $cells = sizeof($structure);

    foreach ($members as $member) {

        $pre_cells = 0;

        $default_entries = DataFieldEntry::getDataFieldEntries(array($member['user_id'], $range_id));

        if ($member['statusgruppe_id']) {
            $role_entries = DataFieldEntry::getDataFieldEntries(array($member['user_id'], $member['statusgruppe_id']));
        }

        print "<tr>\n";
        if ($member['fullname']) {
            print "<td>";
            echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"2\" height=\"1\">";
            echo '<font size="-1">';
            if ($admin_view) {
                printf("<a href=\"%s\">%s</a>\n",
                URLHelper::getLink("dispatch.php/settings/statusgruppen?username={$member['username']}&open={$range_id}#{$range_id}"), htmlReady($member['fullname']));
            } else {
                echo '<a href="'.URLHelper::getLink('dispatch.php/profile?username='.$member['username']).'">'. htmlReady($member['fullname']) .'</a>';
            }
            echo '</font></td>';
        }
        else
            print "<td>&nbsp;</td>";

        if ($structure["status"]) {
            if ($member['inst_perms']) {
                printf("<td align=\"left\"><font size=\"-1\">%s</font></td>\n",
                    htmlReady($member['inst_perms']));
            } else { // It is actually impossible !
                print "<td align=\"left\"><font size=\"-1\">&nbsp;</font></td>\n";
            }
            $pre_cells++;
        }

        if ($structure["statusgruppe"]) {
            print "<td align=\"left\"><font size=\"-1\">&nbsp;</font></td>\n";
        }

        foreach ($datafields_list as $entry) {
            if ($structure[$entry->getId()]) {
                $value = '';
                if ($role_entries[$entry->getId()]) {
                    if ($role_entries[$entry->getId()]->getValue() == 'default_value') {
                        $value = $default_entries[$entry->getId()]->getDisplayValue();
                    } else {
                        $value = $role_entries[$entry->getId()]->getDisplayValue();
                    }
                } else {
                    if ($default_entries[$entry->getId()]) {
                        $value = $default_entries[$entry->getId()]->getDisplayValue();
                    }
                }

                printf("<td align=\"left\"><font size=\"-1\">%s</font></td>\n", $value);
            }
        }

        if (sizeof($GLOBALS['dview']) == 0) {
            if ($structure['raum']) echo '<td>'. htmlReady($member['raum']) .'</td>';
            if ($structure['sprechzeiten']) echo '<td>'. htmlReady($member['sprechzeiten']) .'</td>';
            if ($structure['telefon']) echo '<td>'. htmlReady($member['Telefon']) .'</td>';
            if ($structure['email']) echo '<td>'. htmlReady($member['Email']) .'</td>';
            if ($structure['homepage']) echo '<td>'. htmlReady($member['Home']) .'</td>';
        }

        if ($structure["nachricht"]) {
            print "<td align=\"left\" width=\"1%%\"".(($admin_view) ? "" : " colspan=\"2\""). " nowrap>\n";
            printf("<a href=\"%s\">", URLHelper::getLink("sms_send.php?sms_source_page=" . ($admin_view == true ? "inst_admin.php" : "institut_members.php") . "&rec_uname=".$member['username']));
            printf("<img src=\"" . Assets::image_path('icons/16/blue/mail.png') . "\" alt=\"%s\" ", _("Nachricht an Benutzer verschicken"));
            printf("title=\"%s\" border=\"0\" valign=\"baseline\"></a>", _("Nachricht an Benutzer verschicken"));
            echo '</td>';

            if ($admin_view && !LockRules::Check($range_id, 'participants')) {
                echo '<td width="1%" nowrap>';
                if ($member['statusgruppe_id']) {    // if we are in a view grouping by statusgroups
                    echo '&nbsp;<a href="'.URLHelper::getLink('?cmd=removeFromGroup&username='.$member['username'].'&role_id='. $member['statusgruppe_id']).'">';
                } else {
                    echo '&nbsp;<a href="'.URLHelper::getLink('?cmd=removeFromInstitute&username='.$member['username']).'">';
                }
                echo Assets::img('icons/16/blue/trash.png', array('class' => 'text-top'));
                echo "</a>&nbsp\n</td>\n";
            }
        }

        echo "</tr>\n";

        // Statusgruppen kommen in neue Zeilen
        if ($structure["statusgruppe"]) {
            $statusgruppen = GetStatusgruppenForUser($member['user_id'], array_keys((array)$group_list));
            if (is_array($statusgruppen)) {
                foreach ($statusgruppen as $id) {
                    $entries = DataFieldEntry::getDataFieldEntries(array($member['user_id'], $id));

                    echo '<tr>';
                    for ($i = 0; $i <= $pre_cells; $i++) {
                        echo '<td>&nbsp;</td>';
                    }

                    echo '<td><font size="-1">';

                    if ($admin_view) {
                        echo '<a href="'.URLHelper::getLink('admin_statusgruppe.php?role_id='.$id.'&cmd=displayRole').'">'.htmlReady($group_list[$id]).'</a>';
                    } else {
                        echo htmlReady($group_list[$id]);
                    }

                    echo '</font></td>';

                    if (sizeof($entries) > 0) {
                        foreach ($entries as $e_id => $entry) {
                            if (in_array($e_id, $GLOBALS['dview']) === TRUE) {
                                echo '<td><font size="-1">';
                                if ($entry->getValue() == 'default_value') {
                                    echo $default_entries[$e_id]->getDisplayValue();
                                } else {
                                    echo $entry->getDisplayValue();
                                }
                                echo '</font></td>';
                            }
                        }
                    } else {
                        for ($i = 0; $i < sizeof($GLOBALS['struct']); $i++) {
                            echo '<td>&nbsp;</td>';
                        }
                    }
                    if ($admin_view && !LockRules::Check($range_id, 'participants')) {
                        echo '<td>';
                        echo '<a href="'.URLHelper::getLink('dispatch.php/settings/statusgruppen/switch/' . $id . '?username='.$member['username']).'"><font size="-1">';
                        echo Assets::img('icons/16/blue/edit.png');
                        echo '</font></a></td>';

                        echo '<td>';
                        echo '&nbsp;<a href="'.URLHelper::getLink('?cmd=removeFromGroup&username='.$member['username'].'&role_id='.$id).'">';
                        echo Assets::img('icons/16/blue/trash.png', array('class' => 'text-top'));
                        echo '</a>&nbsp</td>';
                    }
                    elseif ($structure["nachricht"]) {
                        echo '<td colspan=\"2\">&nbsp;</td>';
                    }
                    echo '</tr>', "\n";
                }
            }
        }

    }
}

?>
<table class="blank" border="0" align="center" cellspacing="0" cellpadding="0" width="100%">

<?


// Jemand soll ans Institut...
$u_id = Request::option('u_id');
$ins_id = Request::option('ins_id');
if (Request::submitted('berufen') && $ins_id != "" && $u_id != "") {
    $query = "SELECT inst_perms FROM user_inst WHERE Institut_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($ins_id, $u_id));
    $inst_perms = $statement->fetchColumn();

    if ($inst_perms && $inst_perms != 'user') {
        // der Admin hat Tomaten auf den Augen, der Mitarbeiter sitzt schon im Institut
        my_error("<b>" . _("Die Person ist bereits in der Einrichtung eingetragen. Um Rechte etc. zu &auml;ndern folgen Sie dem Link zu den Nutzerdaten der Person!") . "</b>");
    } else {  // mal nach dem globalen Status sehen
        $query = "SELECT {$_fullname_sql['full']} AS fullname, perms
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($u_id));
        $user_info = $statement->fetch(PDO::FETCH_ASSOC);

        $Fullname = $user_info['fullname'];
        $perms    = $user_info['perms'];

        if ($perms == 'root') {
            my_error("<b>" . _("ROOTs k&ouml;nnen nicht berufen werden!") . "</b>");
        } elseif ($perms == 'admin') {
            if ($perm->have_perm('root') || (!$SessSemName["is_fak"] && $perm->have_studip_perm("admin",$SessSemName["fak"]))) {
                // Emails schreiben...
                if (Request::option('enable_mail_admin') == 'admin' && Request::option('enable_mail_dozent') == 'dozent') {
                    $in = array('admin', 'dozent');
                    $wem = 'Admins und Dozenten';
                } else if(Request::option('enable_mail_admin') == 'admin'){
                    $in = array('admin');
                    $wem = 'Admins';
                } else if(Request::option('enable_mail_dozent') == 'dozent') {
                    $in = array('dozent');
                    $wem = 'Dozenten';
                }
                if (!empty($in)) {
                    $notin = array();
                    $mails_sent = 0;

                    $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($ins_id));
                    $instname = $statement->fetchColumn();

                    $vorname = $Fullname;
                    $nachname = ''; // siehe $vorname

                    $query = "SELECT user_id, Vorname, Nachname, Email
                              FROM user_inst
                              INNER JOIN auth_user_md5 USING (user_id)
                              WHERE Institut_id = ? AND inst_perms IN (?)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($ins_id, $in));

                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $user_language = getUserLanguagePath($row['user_id']);
                        include("locale/$user_language/LC_MAILS/new_admin_mail.inc.php");
                        StudipMail::sendMessage($row['Email'], $subject, $mailbody);
                        $notin[] = $row['user_id'];

                        $mails_sent += 1;
                    }
                    if (!(count($in) == 1 && reset($in) == 'dozent')) {
                        $notin[] = $u_id;
                        //Noch ein paar Mails f�r die Fakult�tsadmins
                        $query = "SELECT user_id, Vorname, Nachname, Email
                                  FROM user_inst
                                  INNER JOIN auth_user_md5 USING (user_id)
                                  WHERE user_id NOT IN (?) AND inst_perms = 'admin'
                                    AND Institut_id IN (
                                            SELECT fakultaets_id
                                            FROM Institute
                                            WHERE Institut_id = ? AND Institut_id != fakultaets_id
                                        )";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($notin, $ins_id));

                        while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                            $user_language = getUserLanguagePath($row['user_id']);
                            include("locale/$user_language/LC_MAILS/new_admin_mail.inc.php");
                            StudipMail::sendMessage($row['Email'], $subject, $mailbody);

                            $mails_sent += 1;
                        }
                    }
                    my_msg("<b>" . sprintf(_("Es wurden ingesamt %s Mails an die %s der Einrichtung geschickt."),$mails_sent,$wem) . "</b>");
                }

                log_event('INST_USER_ADD', $ins_id ,$u_id, 'admin');

                // als admin aufnehmen
                $query = "INSERT INTO user_inst (user_id, Institut_id, inst_perms)
                          VALUES (?, ?, 'admin')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($u_id, $ins_id));

                my_msg("<b>" . sprintf(_("%s wurde als \"admin\" in die Einrichtung aufgenommen."), $Fullname) . "</b>");
            } else {
                my_error("<b>" . _("Sie haben keine Berechtigung einen Admin zu berufen!") . "</b>");
            }
        } else {
            //ok, aber nur hochstufen auf Maximal-Status (hat sich selbst schonmal gemeldet als Student an dem Inst)
            if ($inst_perms == 'user') {
                // ok, neu aufnehmen als das was er global ist
                $query = "UPDATE user_inst
                          SET inst_perms = ?
                          WHERE user_id = ? AND Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($perms, $u_id, $ins_id));

                log_event('INST_USER_STATUS', $ins_id ,$u_id, $perms);
            } else {
                $query = "INSERT INTO user_inst (user_id, Institut_id, inst_perms)
                          VALUES (?, ?, ?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($u_id, $ins_id, $perms));

                log_event('INST_USER_ADD', $ins_id ,$u_id, $perms);
            }
            if ($statement->rowCount()) {
                my_msg("<b>" . sprintf(_("%s wurde als \"%s\" in die Einrichtung aufgenommen. Um Rechte etc. zu &auml;ndern folgen Sie dem Link zu den Nutzerdaten der Person!"), $Fullname, $perms) . "</b>");
            } else {
                parse_msg ("error�<b>" . sprintf(_("%s konnte nicht in die Einrichtung aufgenommen werden!"), $Fullname) . "�");
            }
        }
    }
    checkExternDefaultForUser($u_id);

    $inst_id=$ins_id;
}

$lockrule = LockRules::getObjectRule($inst_id);
if ($admin_view && $lockrule->description && LockRules::Check($inst_id, 'participants')) {
    my_info(formatLinks($lockrule->description),'',3);
}

?>
    <tr>
        <td class="blank" colspan="2">
<?


//Abschnitt zur Auswahl und Suche von neuen Personen
if ($inst_id != '' && $inst_id != '0') {

    $inst_name = $SessSemName[0];
    $auswahl = $inst_id;

    // Mitglieder z�hlen und E-Mail-Adressen zusammentstellen
    if ($perm->have_studip_perm('admin', $inst_id)) {
        $query = "SELECT Email
                  FROM user_inst
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE Institut_id = ? AND inst_perms != 'user' AND Email != ''";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($auswahl));
        $mail_list = $statement->fetchAll(PDO::FETCH_COLUMN);

        $count = count($mail_list);
    } else {
        $count = CountMembersStatusgruppen($auswahl);
    }

    echo '</td></tr>';

    if ($admin_view) {
        if (!LockRules::Check($inst_id, 'participants')) {
            // Der Admin will neue Sklaven ins Institut berufen...
            $query = "SELECT DISTINCT auth_user_md5.user_id, {$_fullname_sql['full_rev_username']} AS fullname
                      FROM auth_user_md5
                      LEFT JOIN user_info USING (user_id)
                      LEFT JOIN user_inst ON user_inst.user_id = auth_user_md5.user_id AND Institut_id = :ins_id
                      WHERE perms !='root'
                        AND (user_inst.inst_perms = 'user' OR user_inst.inst_perms IS NULL)
                        AND (Vorname LIKE :input OR Nachname LIKE :input OR username LIKE :input)
                      ORDER BY Nachname, Vorname";
            $InstituteUser = new SQLSearch($query, _('Nutzer eintragen'), 'user_id');
            ?>
            <!-- Suche mit Ergebnissen -->
            <td class="blank" width="50%" valign="top" align="center">
                <form action="<?= URLHelper::getLink("?inst_id=".$inst_id) ?>" method="POST">
                    <?= CSRFProtection::tokenTag() ?>
                    <table width="90%" border="0" cellpadding="2" cellspacing="0">
                        <tr>
                            <td class="content_seperator">
                                <font size=-1>
                                    <b>&nbsp;<?=_("Neue Person der Einrichtung zuordnen")?></b>
                                </font>
                        </tr>
                        <tr>
                            <td class="table_row_even">
                                <?= _("Suchen Sie im folgenden Feld nach Nutzern und klicken Sie anschlie�end 'hinzuf�gen', um den Nutzer als Personal einzutragen.") ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="table_row_even">
                            <?php
                            print QuickSearch::get("u_id", $InstituteUser)
                                    ->withButton()
                                    ->render();
                            ?>
                            &nbsp;
                            <input type="hidden" name="ins_id" value="<?= $inst_id ?>"><br>
                            <b><?=_("Folgende nur bei Zuordnung eines Admins:")?></b><br>
                            <input type="checkbox" id="enable_mail_admin" name="enable_mail_admin" value="admin"><label for="enable_mail_admin" ><?=_("Admins der Einrichtung benachrichtigen")?></label><br>
                            <input type="checkbox" id="enable_mail_dozent" name="enable_mail_dozent" value="dozent"><label for="enable_mail_dozent" ><?=_("Dozenten der Einrichtung benachrichtigen")?></label><br>
                            <?= Button::create(_('Hinzuf�gen'), 'berufen') ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
            <td class="blank" valign="top" width="50%" align="center">

            <? } else echo '<td colspan="2" class="blank" valign="top" style="padding-left:5px;">';
            ?><!-- Mail an alle MitarbeiterInnen -->
                <table width="90%" border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td class="content_seperator">
                            <font size="-1">
                                <b>&nbsp;<?=_("Nachricht an alle MitarbeiterInnen verschicken")?></b>
                        </td>
                    </tr>
                    <tr>
                        <td class="table_row_even">
                            <font size="-1">
                                <br>
                                <?=sprintf(_("Klicken Sie auf %s%s Rundmail an alle MitarbeiterInnen%s, um eine E-Mail an alle MitarbeiterInnen zu verschicken."), "<a href=\"mailto:" . join(",",$mail_list) . "?subject=" . urlencode(_("MitarbeiterInnen-Rundmail")) .  "\">",  '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/blue/mail.png" border="0">', "</a>");?>
                            </font>
                        </td>
                    </tr>

                    <tr>
                        <td class="table_row_even">
                            <font size="-1">
                                <br>
                                <?=sprintf(_("Klicken Sie auf %s%s Stud.IP Nachricht an alle MitarbeiterInnen%s, um eine interne Nachricht an alle MitarbeiterInnen zu verschicken."),
                                    "<a href=\"".URLHelper::getLink("sms_send.php?inst_id=$inst_id&subject=" . urlencode(_("MitarbeiterInnen-Rundmail - ". $SessSemName[0])))."\">",
                                    '<img src="'.Assets::image_path('icons/16/blue/mail.png').'" border="0">',
                                    "</a>"
                                );?>
                            </font>
                        </td>
                    </tr>

                </table>
            </td>
            <td>
                <!-- Infobox -->
                <?
                    $template = $GLOBALS['template_factory']->open('infobox/infobox_inst_admin');

                    $template->set_attribute('inst_name', $inst_name);
                    echo $template->render();
                ?>
            </td>
        </tr>
    <?
    }

$datafields_list = DataFieldStructure::getDataFieldStructures("userinstrole");

if ($extend == 'yes') {
    if (is_array($GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['extended'])) {
        $dview = $GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['extended'];
    }
    else $dview = array();
} else {
    if(is_array($GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['default'])) {
        $dview = $GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['default'];
    }
    else $dview = array();
}

if (!is_array($dview) || sizeof($dview) == 0) {
    $struct = array (
        "raum" => array("name" => _("Raum"), "width" => "10%"),
        "sprechzeiten" => array("name" => _("Sprechzeiten"), "width" => "10%"),
        "telefon" => array("name" => _("Telefon"), "width" => "10%"),
        "email" => array("name" => _("E-Mail"), "width" => "10%")
    );

    if ($extend == 'yes') {
        $struct["homepage"] = array("name" => _("Homepage"), "width" => "10%");
    }
} else {
    foreach ($datafields_list as $entry) {
        if (in_array($entry->getId(), $dview) === TRUE) {
            $struct[$entry->getId()] = array (
                'name' => $entry->getName(),
                'width' => '10%'
            );
        }
    }
}

// this array contains the structure of the table for the different views
if ($extend == "yes") {
    switch ($show) {
        case 'liste' :
            if ($perm->have_perm("admin")) {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "30%"),
                    "status" => array(
                        "name" => _("Status"),
                        "link" => "?sortby=inst_perms&direction=" . $new_direction,
                        "width" => "10"),
                    "statusgruppe" => array(
                        "name" => _("Funktion"),
                        "width" => "15%")
                );
            }
            else {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "30%"),
                    "statusgruppe" => array(
                        "name" => _("Funktion"),
                        "width" => "10%")
                );
            }
            break;
        case 'status' :
            $table_structure = array(
                "name" => array(
                    "name" => _("Name"),
                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                    "width" => "30%"),
                "statusgruppe" => array(
                    "name" => _("Funktion"),
                    "width" => "15%")
            );
            break;
        default :
            if ($perm->have_perm("admin")) {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "30%"),
                    "status" => array(
                        "name" => _("Status"),
                        "link" => "?sortby=inst_perms&direction=" . $new_direction,
                        "width" => "10")
                );
            }
            else {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "30%")
                );
            }
    } // switch
}
else {
    switch ($show) {
        case 'liste' :
            if ($perm->have_perm("admin")) {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "35%"),
                    "status" => array(
                        "name" => _("Status"),
                        "link" => "?sortby=inst_perms&direction=" . $new_direction,
                        "width" => "10"),
                    "statusgruppe" => array(
                        "name" => _("Funktion"),
                        "width" => "15%")
                );
            }
            else {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "30%"),
                    "statusgruppe" => array(
                        "name" => _("Funktion"),
                        "width" => "15%")
                );
            }
            break;
        case 'status' :
            $table_structure = array(
                "name" => array(
                    "name" => _("Name"),
                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                    "width" => "40%"),
                "statusgruppe" => array(
                    "name" => _("Funktion"),
                    "width" => "20%")
            );
            break;
        default :
            if ($perm->have_perm("admin")) {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "40%"),
                    "status" => array(
                        "name" => _("Status"),
                        "link" => "?sortby=inst_perms&direction=" . $new_direction,
                        "width" => "15")
                );
            }
            else {
                $table_structure = array(
                    "name" => array(
                        "name" => _("Name"),
                        "link" => "?sortby=Nachname&direction=" . $new_direction,
                        "width" => "40%")
                );
            }
    } // switch
}

// StEP 154: Nachricht an alle Mitglieder der Gruppe; auch auf der inst_members.php
if ($admin_view OR $perm->have_studip_perm('autor', $SessSemName[1])) {
    $nachricht['nachricht'] = array(
        "name" => _("Aktionen"),
        "width" => "5%"
    );
}

$table_structure = array_merge((array)$table_structure, (array)$struct);
$table_structure = array_merge((array)$table_structure, (array)$nachricht);

$colspan = sizeof($table_structure)+1;

echo '<table border="0" width="100%" cellpadding="4" cellspacing="0" align="center">', "\n";
if ($sms_msg) {
    echo "<tr><td class=\"blank\">";
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"1\" height=\"5\"></td></tr>\n";
    parse_msg($sms_msg, "�", "blank", 1, FALSE);
}

echo "<tr><td class=\"blank\">";
echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"1\" height=\"5\"></td></tr>\n";
echo "<tr><td class=\"blank\" id=\"list_institute_members\">\n";

if ($perm->have_perm("admin")) {
    echo '<form action="'.URLHelper::getLink().'" method="post">', "\n";
    echo CSRFProtection::tokenTag();
}

// add skip links
SkipLinks::addIndex(_("Mitarbeiterliste"), 'list_institute_members');

echo "<table border=\"0\" width=\"99%\" cellpadding=\"4\" cellspacing=\"0\" align=\"center\">\n";
echo "<tr>\n";
echo "<td class=\"table_row_even\" width=\"60%\">\n";

// Admins can choose between different grouping functions
if ($perm->have_perm("admin")) {
    printf("<font size=\"-1\"><b>%s&nbsp;</b></font>\n", _("Gruppierung:"));
    printf("<select name=\"show\" style=vertical-align:middle><option %svalue=\"funktion\">%s</option>\n",
        ($show == "funktion" ? "selected " : ""), _("Funktion"));
    printf("<option %svalue=\"status\">%s</option>\n",
        ($show == "status" ? "selected " : ""), _("Status"));
    printf("<option %svalue=\"liste\">%s</option>\n",
        ($show == "liste" ? "selected " : ""), _("keine"));
    echo "</select>\n";
    echo Button::create(_('�bernehmen'));
}
else {
    if ($show == "funktion") {
        echo '&nbsp; &nbsp; &nbsp; <a href="'.URLHelper::getLink('?show=liste').'">';
        printf("<font size=\"-1\"><b>%s</b></font></a>\n", _("Alphabetische Liste anzeigen"));
    }
    else {
        echo '&nbsp; &nbsp; &nbsp; <a href="'.URLHelper::getLink('?show=funktion').'">';
        printf("<font size=\"-1\"><b>%s</b></font></a>\n", _("Nach Funktion gruppiert anzeigen"));
    }
}

echo "</td><td class=\"table_row_even\" width=\"30%\">\n";
printf("<font size=\"-1\">" . _("<b>%s</b> MitarbeiterInnen gefunden") . "</font>", $count);
echo "</td><td class=\"table_row_even\" width=\"10%\">\n";

if ($extend == "yes") {
    echo LinkButton::create(_('Normale Ansicht'), URLHelper::getURL('?extend=no'));
}
else {
    echo LinkButton::create(_('Erweiterte Ansicht'), URLHelper::getURL('?extend=yes'));
}

echo "</td></tr></table>\n";

if ($perm->have_perm("admin")) {
    echo "\n</form>\n";
}
echo "<table class=\"zebra\" border=\"0\" width=\"99%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";

table_head($table_structure);

// if you have the right question you will get the right answer ;-)
if ($show == "funktion") {
    $all_statusgruppen = $groups;
    if ($all_statusgruppen) {
        function display_recursive($roles, $level = 0, $title = '') {
            global $statusgruppe_user_sortby, $direction, $extend, $auswahl;
            global $_fullname_sql, $table_structure, $colspan;
            global $admin_view, $rechte, $perm, $SessSemName;
            foreach ($roles as $role_id => $role) {
                if ($title == '') {
                    $zw_title = $role['role']->getName();
                } else {
                    $zw_title = $title .' > '. $role['role']->getName();
                }
                if ($extend == 'yes') {
                    $query = "SELECT {$_fullname_sql['full_rev']} AS fullname, ui.inst_perms,
                                     ui.raum, ui.sprechzeiten, ui.Telefon, aum.Email, aum.user_id,
                                     aum.username, info.Home, statusgruppe_id
                              FROM statusgruppe_user
                              LEFT JOIN auth_user_md5 AS aum USING (user_id)
                              LEFT JOIN user_info AS info USING (user_id)
                              LEFT JOIN user_inst AS ui USING (user_id)
                              WHERE ui.Institut_id = :inst_id AND statusgruppe_id = :role_id
                                AND ui.inst_perms != 'user'
                              ORDER BY :sort_column :sort_order";
                } else {
                    $query = "SELECT {$_fullname_sql['full_rev']} AS fullname, user_inst.raum,
                                     user_inst.sprechzeiten, user_inst.Telefon, inst_perms,
                                     Email, user_id, username, statusgruppe_id
                              FROM statusgruppe_user
                              LEFT JOIN auth_user_md5 USING (user_id)
                              LEFT JOIN user_info USING (user_id)
                              LEFT JOIN user_inst USING (user_id)
                              WHERE Institut_id = :inst_id AND statusgruppe_id = :role_id
                                AND inst_perms != 'user'
                              ORDER BY :sort_column :sort_order";
                }
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':inst_id', $auswahl);
                $statement->bindValue(':role_id', $role_id);
                $statement->bindValue(':sort_column', $statusgruppe_user_sortby, StudipPDO::PARAM_COLUMN);
                $statement->bindValue(':sort_order', $direction, StudipPDO::PARAM_COLUMN);
                $statement->execute();

                $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);

                if (count($institut_members) > 0) {
                    // StEP 154: Nachricht an alle Mitglieder der Gruppe
                    if ($perm->have_studip_perm('autor', $SessSemName[1]) AND $GLOBALS["ENABLE_EMAIL_TO_STATUSGROUP"] == true) {
                        $group_colspan = $colspan - 2;
                        echo "<tr><td class=\"content_seperator\" colspan=\"$group_colspan\" height=\"20\">";
                        echo "<font size=\"-1\"><b>&nbsp;";
                        echo htmlReady($zw_title);
                        echo "<b></font>"."</td><td class=\"content_seperator\" colspan=\"2\" height=\"20\">";
                        echo "<a href=\"".URLHelper::getLink("sms_send.php?sms_source_page=" . ($admin_view == true ? "inst_admin.php" : "institut_members.php") . "&group_id=".$role_id."&subject=".rawurlencode($SessSemName[0]))."\"><img src=\"" . Assets::image_path('icons/16/blue/mail.png') . "\" " . tooltip(sprintf(_("Nachricht an alle Mitglieder der Gruppe %s verschicken"), $zw_title)) . " border=\"0\"></a>&nbsp;";
                        echo "</td></tr>\n";
                    }
                    else {
                        echo "<tr><td class=\"content_seperator\" colspan=\"$colspan\" height=\"20\">";
                        echo "<font size=\"-1\"><b>&nbsp;";
                        echo htmlReady($zw_title);
                        echo "<b></font></td></tr>\n";
                    }
                    table_body($institut_members, $auswahl, $table_structure);
                }
                if ($role['child']) {
                    display_recursive($role['child'], $level + 1, $zw_title);
                }
            }
        }
        display_recursive($all_statusgruppen);
    }
    if ($perm->have_perm('admin')) {
        $assigned = GetAllSelected($auswahl) ?: array('');
        if ($extend == 'yes') {
            $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                             ui.inst_perms, ui.raum, ui.sprechzeiten, ui.Telefon,
                             aum.Email, aum.user_id, aum.username
                      FROM user_inst AS ui
                      LEFT JOIN auth_user_md5 AS aum USING (user_id)
                      LEFT JOIN user_info USING (user_id)
                      WHERE ui.Institut_id = :inst_id AND ui.inst_perms != 'user'
                        AND ui.user_id NOT IN (:user_ids)
                      ORDER BY :sort_column :sort_order";
        } else {
            $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                             ui.inst_perms, ui.raum, ui.Telefon,
                             aum.user_id, aum.username
                      FROM user_inst AS ui
                      LEFT JOIN auth_user_md5 AS aum USING (user_id)
                      LEFT JOIN user_info USING (user_id)
                      WHERE ui.Institut_id = :inst_id AND ui.inst_perms != 'user'
                        AND ui.user_id NOT IN (:user_ids)
                      ORDER BY :sort_column :sort_order";
        }
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':inst_id', $auswahl);
        $statement->bindValue(':user_ids', $assigned, StudipPDO::PARAM_ARRAY);
        $statement->bindValue(':sort_column', $sortby, StudipPDO::PARAM_COLUMN);
        $statement->bindValue(':sort_order', $direction, StudipPDO::PARAM_COLUMN);
        $statement->execute();

        $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (count($institut_members) > 0) {
            echo "<tr><td class=\"content_seperator\" colspan=\"$colspan\" height=\"20\">";
            echo "<font size=\"-1\"><b>&nbsp;";
            echo _("keiner Funktion zugeordnet") . "<b></font></td></tr>\n";
            table_body($institut_members, $auswahl, $table_structure);
        }
    }
} elseif ($show == 'status') {
    $inst_permissions = array(
        'admin'  => _('Admin'),
        'dozent' => _('DozentIn'),
        'tutor'  => _('TutorIn'),
        'autor'  => _('AutorIn')
    );

    $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                     ui.raum, ui.sprechzeiten, ui.Telefon,
                     inst_perms, Email, user_id, username
              FROM user_inst AS ui
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE ui.Institut_id = :inst_id AND inst_perms = :perms
              ORDER BY :sort_column :sort_order";
    $statement = DBManager::get()->prepare($query);

    foreach ($inst_permissions as $key => $permission) {
        $statement->bindValue(':inst_id', $auswahl);
        $statement->bindValue(':perms', $key);
        $statement->bindValue(':sort_column', $sortby, StudipPDO::PARAM_COLUMN);
        $statement->bindValue(':sort_order', $direction, StudipPDO::PARAM_COLUMN);
        $statement->execute();

        $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);

        $statement->closeCursor();

        if (count($institut_members) > 0) {
            $group_colspan = $colspan - 2;
            echo "<tr><td class=\"content_seperator\" colspan=\"$group_colspan\" height=\"20\">";
            echo "<font size=\"-1\"><b>&nbsp;";
            echo $permission;
            echo "<b></font>"."</td><td class=\"content_seperator\" colspan=\"2\" height=\"20\">";
            echo "<a href=\"".URLHelper::getLink("sms_send.php?sms_source_page=inst_admin.php&filter=inst_status&who=".$key . "&group_id=" .$role_id."&subject=".rawurlencode($SessSemName[0]))."\"><img src=\"" . Assets::image_path('icons/16/blue/mail.png')
                ."\" " . tooltip(sprintf(_("Nachricht an alle Mitglieder mit dem Status %s verschicken"), $permission)) .
                 " border=\"0\"></a>&nbsp;";
            echo "</td></tr>\n";

            table_body($institut_members, $auswahl, $table_structure);
        }
    }
} else {
    $parameters = array();
    if ($extend == 'yes') {
        if ($perm->have_perm('admin')) {
            $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                             ui.raum, ui.sprechzeiten, ui.Telefon, ui.inst_perms,
                             user_id, info.Home, aum.Email, aum.username
                      FROM user_inst AS ui
                      LEFT JOIN auth_user_md5 AS aum USING (user_id)
                      LEFT JOIN user_info AS info USING (user_id)
                      WHERE ui.Institut_id = :inst_id AND ui.inst_perms != 'user'
                      ORDER BY :sort_column :sort_order";
        } else {
            $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                             ui.raum, ui.sprechzeiten, ui.Telefon,
                             user_id, info.Home, aum.Email, aum.username, Institut_id
                      FROM statusgruppen
                      LEFT JOIN statusgruppe_user USING (statusgruppe_id)
                      LEFT JOIN user_inst AS ui USING (user_id)
                      LEFT JOIN auth_user_md5 AS aum USING (user_id)
                      LEFT JOIN user_info AS info USING (user_id)
                      WHERE statusgruppen.statusgruppe_id IN (:statusgruppen_ids)
                        AND Institut_id = :inst_id
                      ORDER BY :sort_column :sort_order";
            $parameters[':statusgruppen_ids'] = getAllStatusgruppenIDS($auswahl);
        }
    } else {
        if ($perm->have_perm('admin')) {
            $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                             ui.raum, ui.sprechzeiten, ui.Telefon,
                             user_id, username, inst_perms
                      FROM user_inst AS ui
                      LEFT JOIN auth_user_md5 USING (user_id)
                      LEFT JOIN user_info USING (user_id)
                      WHERE ui.Institut_id = :inst_id AND inst_perms != 'user'
                      ORDER BY :sort_column :sort_order";
        } else {
            $query = "SELECT {$_fullname_sql['full_rev']} AS fullname,
                             ui.raum, ui.sprechzeiten, ui.Telefon,
                             user_id, username, Institut_id
                      FROM statusgruppen
                      LEFT JOIN statusgruppe_user AS su USING (statusgruppe_id)
                      LEFT JOIN user_inst AS ui USING (user_id)
                      LEFT JOIN auth_user_md5 AS aum USING (user_id)
                      LEFT JOIN user_info USING (user_id)
                      WHERE statusgruppen.statusgruppe_id IN (:statusgruppen_ids)
                        AND Institut_id = :inst_id
                      GROUP BY user_id
                      ORDER BY :sort_column :sort_order";
            $parameters[':statusgruppen_ids'] = getAllStatusgruppenIDS($auswahl);
        }
    }
    $statement = DBManager::get()->prepare($query);
    $statement->bindValue(':inst_id', $auswahl);
    $statement->bindValue(':sort_column', $sortby, StudipPDO::PARAM_COLUMN);
    $statement->bindValue(':sort_order', $direction, StudipPDO::PARAM_COLUMN);

    $aborted = false;
    foreach ($parameters as $parameter => $value) {
        if (is_array($value) && count($value) === 0) {
            $aborted = true;
            break;
        }
        $statement->bindValue($parameter, $value);
    }
    
    if (!$aborted) {
        $statement->execute();

        $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (count($institut_members) > 0) {
            table_body($institut_members, $auswahl, $table_structure);
        }
    }
}

if (get_config('EXPORT_ENABLE') && (count($institut_members) > 0) && $perm->have_perm('tutor')) {
    include_once($GLOBALS['PATH_EXPORT'] . "/export_linking_func.inc.php");
    echo "<tr><td colspan=$colspan><br>" . export_form($auswahl, "person", $SessSemName[0]) . "</td></tr>";
}
echo "<tr><td class=\"blank\" colspan=\"$colspan\">&nbsp;</td></tr>\n";
echo "</table></td></tr></table>\n";
echo "</body></html>";

} // Ende Abfrageschleife, ob �berhaupt eine Instituts_id gesetzt ist

include('lib/include/html_end.inc.php');
page_close();