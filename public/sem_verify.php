<?
# Lifter001: DONE - nothing to do
# Lifter002: TODO
# Lifter005: TODO - passwortabsicherung
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* sem_verify.php
*
* checks the entry to a Veranstaltung an insert user to the seminar_user table
*
*
* @author       André Noack <noack@data-quest.de>
* @author       Cornelis Kater <ckater@gwdg.de>
* @author       Stefan Suchi <suchi@data-quest.de>
* @author       Suchi & Berg GmbH <info@data-quest.de>
* @author       Michael Riehemann <michael.riehemann@uni-oldenburg.de>
* @access       public
* @module       sem_verify.php
* @modulegroup  misc
* @package      studip_core
*/

/**
 * @addtogroup notifications
 *
 * Enrolling in a course triggers a CourseDidEnroll
 * notification. The course's ID is transmitted as
 * subject of the notification.
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// sem_verify.php
// Ueberprueft Zutrittsvorausetzungen fuer Veranstaltungen und traegt Nutzer in die Tabelle seminar_user ein
// Copyright (C) 2002 André Noack <noack@data-quest.de>, Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

unregister_globals();
require_once 'app/models/studygroup.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$send_from_search_page = Request::get('send_from_search_page');
$send_from_search = Request::get('send_from_search') !== null;
if (!preg_match('/^('.preg_quote($CANONICAL_RELATIVE_PATH_STUDIP,'/').')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', $send_from_search_page)) $send_from_search_page = '';

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

/*
 * This functions is used for printing a message, that the user can decide whether really to sign in to the seminar or not
 * @param   $sem_id     Seminar_id
 * @param   $sem_name   Seminar-name
 * @param   $user_id        User-Id
 */
function temporaly_accepted($sem_name, $user_id, $sem_id, $ask = "TRUE", $studiengang_id, $url) {
    global $pass;

    if ($ask == "TRUE") {
        $query = "SELECT admission_prelim_txt FROM seminare WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($sem_id));
        $admission_prelim_txt = $statement->fetchColumn();
        echo "<tr><td class=\"blank\">&nbsp;&nbsp;</td><td class=\"blank\">";
        printf (_("Um endg&uuml;ltig in die Veranstaltung %s aufgenommen zu werden, m&uuml;ssen Sie noch weitere Voraussetzungen erf&uuml;llen."),'<b>'.$sem_name.'</b>');
        if ($admission_prelim_txt) {
            print " "._("Lesen Sie bitte folgenden Hinweistext:")."<br>";
            echo "<br><table width=90%><tr><td>\n";
            echo formatReady($admission_prelim_txt);
            echo "</td></tr></table><br>\n";
        } else {
            printf(" "._("Bitte erkundigen Sie sich bei dem/der %s der Veranstaltung nach weiteren Teilnahmevoraussetzungen."), get_title_for_status('dozent', 1));
        }
        printf (_("Wenn Sie auf \"eintragen\" klicken, werden Sie vorl&auml;ufig f&uuml;r diese Veranstaltung eingetragen. Erf&uuml;llen Sie die Anforderungen, um von dem/der %s fest in die Veranstaltung %s eingetragen zu werden."), get_title_for_status('dozent', 1), '<b>'.$sem_name.'</b>');
        echo "<br><br>\n";

        printf("<form action=\"%s\" method=\"post\">\n",$url);
        echo CSRFProtection::tokenTag();
        printf("<input type=\"hidden\" name=\"pass\" value=\"$pass\">");
        if (get_config('ADMISSION_PRELIM_COMMENT_ENABLE')){
            echo _("Bemerkungen zu Teilnahmevoraussetzungen:");
            echo '<br><textarea name="comment" cols="50" rows="5"></textarea><br><br>';
        }
        print(Button::create(_('Eintragen'), array('title' => _("In diese Veranstaltung eintragen"))));
        print("<input type=\"hidden\" name=\"ask\" value=\"FALSE\">\n");
        printf ("<input type=\"HIDDEN\" name=\"sem_verify_suggest_studg\" value=\"%s\">\n", $studiengang_id);
        print(LinkButton::createCancel(_('Abbrechen'), 'details.php?sem_id='.$sem_id, array('title' => _("Nicht in diese Veranstaltung eintragen"))));
        print("</form>");
        print("</td></tr><tr><td class=\"blank\" colspan=2>&nbsp;</td></tr></table>");
        page_close();
        die;

    } else {
        if (get_config('ADMISSION_PRELIM_COMMENT_ENABLE')){
            $comment = get_fullname() . ': ' . (Request::quoted('comment'));
        } else {
            $comment = '';
        }
        admission_seminar_user_insert($user_id, $sem_id, 'accepted', $studiengang_id, $comment);

        $current_seminar = Seminar::getInstance($sem_id);

        if (!SeminarCategories::GetByTypeId($current_seminar->status)->studygroup_mode) {
            parse_msg (sprintf("msg§"._("Sie wurden mit dem Status <b>vorl&auml;ufig akzeptiert</b> in die Veranstaltung %s eingetragen. F&uuml;r weitere Informationen lesen Sie den Abschnitt 'Anmeldeverfahren' in der &Uuml;bersichtsseite zu dieser Veranstaltung."), '<b>'.$sem_name.'</b>'));
        } else {
            StudygroupModel::applicationNotice($sem_id, $user_id);
            parse_msg(sprintf("msg§"._("Sie wurden auf die Anmeldeliste der Studiengruppe %s eingetragen. Die Moderatoren der Studiengruppe können Sie jetzt freischalten."), $sem_name));
        }
        echo "<tr><td class=\"blank\" colspan=2>";

        // LOGGING
        log_event('SEM_USER_ADD', $sem_id, $user_id, 'accepted', 'Vorläufig akzeptiert'.($log_user_number?', Teilnehmernummer: '.$log_user_number:''));

    }
}

/**
* This function checks, if a given seminar has the admission: temporarily accepted
*
* @param        string  seminar_id
* @param        string  user_id
* @return       boolean
*
*/
function seminar_preliminary($seminar_id,$user_id=NULL) {
    $query = "SELECT Name, admission_prelim FROM seminare WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if ($temp['admission_prelim'] != 1) {
        return false;
    }

    if ($user_id) {
        if (admission_seminar_user_get_position($user_id, $seminar_id)) {
            echo "<tr><td class=\"blank\" colspan=2>";
            parse_msg (sprintf("msg§"._("Sie sind für die Veranstaltung \"%s\" bereits vorläufig eingetragen!"),htmlReady($temp['Name'])));
            echo "</td></tr>";
            page_close();
            die;
        }
    }
    return true;
}
$pass = Request::get('pass');
$id = Request::option('id');
if ($id) {
    $current_seminar = Seminar::getInstance($id);
} else {
    throw new Exception("no valid id in request");
}
// Start of Output
PageLayout::setTitle(_("Veranstaltungsfreischaltung"));
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
require_once ('lib/dates.inc.php');
require_once 'lib/msg.inc.php';
require_once 'lib/functions.php';
require_once 'lib/admission.inc.php';
require_once 'lib/classes/StudipAdmissionGroup.class.php';
require_once 'lib/classes/UserDomain.php';
require_once 'lib/classes/LockRules.class.php';


?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
    <tr><td class="table_header_bold" colspan=2>&nbsp;<b><?=_("Veranstaltungsfreischaltung")?> - <?=htmlReady(getHeaderLine($id))?></b></td></tr>
    <tr><td class="blank" colspan=2>&nbsp;<br></td></tr>
<?

    // temporaly accepted, if $ask is not set, then we assume, that it must be true
    $ask = Request::option('ask');
    if (empty($ask)) {
        $ask = "TRUE";
    }
    $temp_url = UrlHelper::getLink('?' . $_SERVER['QUERY_STRING']);

    // admins und roots haben hier nix verloren
    if ($perm->have_perm("admin")) {
        parse_msg ("info§"._("Sie sind einE <b>AdministratorIn</b> und k&ouml;nnen sich daher nicht f&uuml;r einzelne Veranstaltungen anmelden."));
        echo"<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
        if ($send_from_search)
            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
        echo "<br><br></td></tr></table>";
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }

    $same_domain = true;
    $user_domains = UserDomain::getUserDomainsForUser($user->id);

    if (count($user_domains) > 0) {
        $seminar_domains = UserDomain::getUserDomainsForSeminar($id);
        $same_domain = count(array_intersect($seminar_domains, $user_domains)) > 0;
    }

    if (!$same_domain && !SeminarCategories::GetByTypeId($current_seminar->status)->studygroup_mode)
    {
        parse_msg ("info§"._("Sie sind nicht in einer zugelassenenen Nutzerdomäne, Sie k&ouml;nnen sich nicht eintragen!"));
        echo"<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
        if ($send_from_search)
            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
        echo "<br><br></td></tr></table>";
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }

    if ($current_seminar->admission_type == 3 || !$current_seminar->isVisible() && !$perm->have_perm(get_config('SEM_VISIBILITY_PERM')))
    {
        parse_msg ("info§"._("Die Veranstaltung ist gesperrt, Sie k&ouml;nnen sich nicht eintragen!"));
        echo"<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
        if ($send_from_search)
            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
        echo "<br><br></td></tr></table>";
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }

    if (LockRules::Check($id, 'participants'))
    {
        $lockdata = LockRules::getObjectRule($id);
        parse_msg ("error§"._("In diese Veranstaltung k&ouml;nnen Sie sich nicht eintragen!") . ($lockdata['description'] ? '§info§' . formatLinks($lockdata['description']) : ''));
        echo"<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
        if ($send_from_search)
            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
        echo "<br><br></td></tr></table>";
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }

    // get colour group (used for grouping in meine_seminar.php, can be changed by user)
    $group = $current_seminar->getDefaultGroup();

    //check stuff for admission
    check_admission();
    $sem_verify_suggest_studg = Request::option('sem_verify_suggest_studg');
    if (Request::option('sem_verify_selection_send') && !$sem_verify_suggest_studg)
        parse_msg ("error§"._("Bitte w&auml;hlen Sie einen Studiengang zur Anmeldung f&uuml;r diese Veranstaltung aus!"));

    //check if entry is allowed

    // Check the start and end-times of the current seminar and print an adequate message
    if ($current_seminar->admission_starttime > time()) {
        echo"<tr><td class=\"blank\">&nbsp;&nbsp;&nbsp;&nbsp;</td><td class=\"blank\">";
        echo "<font color=\"#FF0000\">";
        printf(_("Der Anmeldezeitraum dieser Veranstaltung startet erst am %s um %s Uhr."),date("d.m.Y",$current_seminar->admission_starttime), date("G:i",$current_seminar->admission_starttime));;
        echo "</font>";
        echo "<br><br></td></tr></table>";
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }
    if (($current_seminar->admission_endtime_sem < time()) && ($current_seminar->admission_endtime_sem != -1)) {
        echo"<tr><td class=\"blank\">&nbsp;&nbsp;&nbsp;&nbsp;</td><td class=\"blank\">";
        echo "<font color=\"#FF0000\">";
        printf(_("Der Anmeldezeitraum dieser Veranstaltung endete am %s um %s Uhr."),date("d.m.Y",$current_seminar->admission_endtime_sem), date("G:i",$current_seminar->admission_endtime_sem));;
        echo "</font>";
        echo "<br><br></td></tr></table>";
        include ('lib/include/html_end.inc.php');
        page_close();
        die;
    }

    //check if seminar is grouped
    $query = "SELECT 1 FROM user_studiengang WHERE user_id = ?"; //Hat der Student ueberhaupt Studiengaenge angegeben?
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $user_has_studiengang = (bool)$statement->fetchColumn();

    if($user_has_studiengang && ($group_obj = StudipAdmissionGroup::GetAdmissionGroupBySeminarId($id)) ){
        $admission_group = is_object($group_obj) ? $group_obj->getId() : false;
        $admission_disable_waitlist = $current_seminar->admission_disable_waitlist;
        $admission_type = $current_seminar->admission_type;
        if($admission_type == 2) $admission_type_text = _("Chronologische Anmeldung");
        if($admission_type == 1) $admission_type_text = _("Losverfahren");
        if($admission_type == 1 && $current_seminar->admission_selection_take_place) $admission_type_text = _("Losverfahren beendet");

        if ($admission_group) {
            //get some infos about the current status of the seminar (admission-list, user-list, seminar-name)
            $current_name = $current_seminar->getName();
            if ($seminar_id = $group_obj->checkUserSubscribedToGroup($user->id)) {
                $seminar_name =  $group_obj->members[$seminar_id]->getName();
                $seminar = true;
            }
            if ($warte_id  = $group_obj->checkUserSubscribedToGroupWaitinglist($user->id)) {
                $warteliste_name = $group_obj->members[$warte_id]->getName();
                $warteliste = true;
            }
            if (($admission_type == 2 || ($admission_type == 1 && $current_seminar->admission_selection_take_place)) && get_free_admission($id)) $platz = 1;
            else $platz = 0;

            /* now we know the following:
             *  - Is the user already subscribed to another seminar? ($seminar)
             *  - Is the user already awaiting in another seminar?   ($warteliste)
             *  - Is there a place left in the seminar, the user wants to subsribe to? ($platz)
             *
             * The only thing that we have to do now, is to check for all possible constellations.
             */

            echo "<tr><td class=\"blank\">&nbsp;&nbsp;&nbsp;&nbsp;</td><td class=\"blank\">";

            $meldung = '<div style="margin-top:5px;">
                    '._("Veranstaltungsgruppe:").'&nbsp;'.htmlReady($group_obj->getValue('name')).'
                    &nbsp;('. $admission_type_text .')
                    <ol>';
            foreach($group_obj->getMemberIds() as $m_id)
            {
                if (!$group_obj->members[$m_id]->isVisible()) continue; // hide invisible courses
                    $target = "details.php?sem_id=$m_id&send_from_search=1&send_from_search_page=sem_verify.php?id=$id";
                    $meldung .= '<li><a href="'.$target.'">'
                            . htmlReady($group_obj->members[$m_id]->getName())
                            .'</a>&nbsp;('.htmlReady($group_obj->members[$m_id]->getFormattedTurnus(true)).')
                        </li>';
            }
            $meldung .='</ol>
                    '.($group_obj->getValue('status') ? _("Eintrag nur in einer Veranstaltung der Gruppe") : _("Eintrag in einer Veranstaltung und in einer Warteliste der präferierten Veranstaltung der Gruppe")).'
                </div>';

            parse_msg("info§".$meldung, "§", "blank",3);

            $exit = true;

            if (!$seminar && !$warteliste && $platz) {
                $meldung  = sprintf(_("Sie bekommen einen Platz in der Veranstaltung %s."), "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b>");
                if($group_obj->getValue('status') == 0 && $admission_type == 2){
                    $meldung .= '<p>' . _("Falls dies nicht Ihre präferierte Veranstaltung dieser Gruppe ist, tragen Sie sich bitte ebenfalls für Ihre präferierte Veranstaltung ein.") . '</p>';
                    $meldung .= '<p>' . _("Wenn Sie dort über die Warteliste nachrücken, wird Ihre Eintragung in dieser Veranstaltung automatisch gelöscht.") . '</p>';
                }
                $exit = false;
            }

            if (!$seminar && !$warteliste && !$platz) {
                if($admission_type == 1 && !$current_seminar->admission_selection_take_place){
                    $meldung  = sprintf(_("Die Teilnehmer der Veranstaltung %s werden ausgelost."), "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b><br>");
                    $meldung .= _("Sie wurden auf die Anmeldeliste gesetzt.")."</font>";
                    $exit = false;
                } else {
                    $meldung  = "<font color=\"#FF0000\">".  sprintf(_("Sie bekommen im Moment keinen Platz in der Veranstaltung %s."), "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b><br>");
                    if (!$admission_disable_waitlist) {
                        $meldung .= _("Sie wurden jedoch auf die Warteliste gesetzt.")."</font>";
                        if($group_obj->getValue('status') == 0){
                            $meldung .= '<p>' . _("Um sicher zu gehen, dass Sie einen Platz in einer Veranstaltung dieser Gruppe bekommen, sollten Sie sich zusätzlich in einer weiteren Veranstaltung fest eintragen.") . '</p>';
                            $meldung .= '<p>' . _("Sobald Sie in dieser Veranstaltung von der Warteliste aufrücken, wird Ihre dortige Eintragung automatisch gelöscht.");
                        }
                        $exit = false;
                    }
                }
            }

            if ($seminar && !$warteliste && $platz) {
                $meldung  = sprintf(_("In dieser Veranstaltung sind noch Plätze frei. Sie haben jedoch bereits einen Platz in der Veranstaltung %s."), "<br>&nbsp;<b>".htmlReady($seminar_name)." (".htmlReady(view_turnus($seminar_id)).")</b>") . '<br>';
                $meldung .= sprintf(_("Um sich für die Veranstaltung %s fest anzumelden, löschen Sie bitte erst Ihre dortige Eintragung."), "<br>&nbsp;<b>".htmlReady($current_name)." (".htmlReady($current_seminar->getFormattedTurnus(false)). ")</b><br>");
            }

            if ($seminar && !$warteliste && !$platz) {
                $meldung  = "<font color=\"#FF0000\">";
                $meldung .= sprintf(_("Sie sind bereits in der Veranstaltung %s in dieser Gruppe eingetragen."), "<br>&nbsp;<b>".htmlReady($seminar_name)." (". htmlReady(view_turnus($seminar_id)) .")</b><br>");
                if (!$admission_disable_waitlist && $group_obj->getValue('status') == 0){
                    $meldung .= "<p>" . sprintf(_("Sie wurden für die Veranstaltung %s auf die Warteliste gesetzt."), "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b><br>") . "</p></font>";
                    $meldung .= _("Sobald Sie hier nachrücken, wird Ihre andere Anmeldung automatisch gelöscht.");
                    $exit = false;
                }
            }

            if (!$seminar && $warteliste && $platz) {
                $meldung  = sprintf(_("Sie stehen bereits für die Veranstaltung %s auf der Warteliste."),
                "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>");
                if($group_obj->getValue('status') == 0){
                    $meldung .= sprintf(_("Ihre Anmeldung für die Veranstaltung %s wird automatisch gelöscht, wenn Sie dort über die Warteliste aufrücken."),
                                "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b><br>");
                    $exit = false;
                }
                $meldung .= "<p>". sprintf(_("Wenn Sie sich hier fest eintragen möchten, löschen Sie bitte erst Ihren Eintrag in der Warteliste für die Veranstaltung %s."), "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>") . "</p>";
            }

            if (!$seminar && $warteliste && !$platz) {
                if($admission_type == 1 && !$current_seminar->admission_selection_take_place){
                    $meldung = sprintf(_("Sie stehen bereits für die Veranstaltung %s auf der Anmeldeliste. Wenn Sie sich in die Anmeldeliste der Veranstaltung %s eintragen möchten, löschen Sie bitte erst Ihre andere Eintragung."), "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>", "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b><br>");
                } else {
                    if (!$admission_disable_waitlist){
                        $meldung = sprintf(_("Sie stehen bereits für die Veranstaltung %s auf der Warteliste. Wenn Sie sich in die Warteliste der Veranstaltung %s eintragen möchten, löschen Sie bitte erst Ihre andere Eintragung."), "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>", "<br>&nbsp;<b>".htmlReady($current_name)." (". htmlReady($current_seminar->getFormattedTurnus(false)) .")</b><br>");
                    } else {
                        $meldung = sprintf(_("Sie stehen bereits für die Veranstaltung %s auf der Warteliste."), "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>");
                    }
                }
            }

            if ($seminar && $warteliste && $platz) {
                $meldung  = sprintf(_("Sie stehen bereits für die Veranstaltung %s auf der Warteliste und sind in die Veranstaltung %s eingetragen."),
                                "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>",
                                "<br>&nbsp;<b>".htmlReady($seminar_name)." (". htmlReady(view_turnus($seminar_id)) .")</b><br>");
                $meldung .= "<p>" . sprintf(_("Sie können sich hier erst eintragen, wenn Sie Ihr Abonnement der Veranstaltung %s löschen."), "<br>&nbsp;<b>".htmlReady($seminar_name)." (". htmlReady(view_turnus($seminar_id)) .")</b><br>");
            }

            if ($seminar && $warteliste && !$platz) {
                $meldung  = sprintf(_("Sie stehen bereits für die Veranstaltung %s auf der Warteliste und sind in die Veranstaltung %s eingetragen."),
                "<br>&nbsp;<b>".htmlReady($warteliste_name)." (". htmlReady(view_turnus($warte_id)) .")</b><br>",
                "<br>&nbsp;<b>".htmlReady($seminar_name)." (". htmlReady(view_turnus($seminar_id)) .")</b><br>");
                if (!$admission_disable_waitlist){
                    $meldung .= "<p>". sprintf(_("Sie können sich hier erst eintragen, wenn Sie sich von der Warteliste der Veranstaltung %s löschen."),
                    "<br>&nbsp;<b>".htmlReady($seminar_name)." (". htmlReady(view_turnus($seminar_id)) .")</b><br>");
                }
            }

            parse_msg("info§" . $meldung, "§", "blank",3);

            if($exit)
            {
                echo "</td></tr></table>";
                include 'lib/include/html_end.inc.php';
                page_close();
                die();
            }
            echo "</td></tr>";
        }
    }

    //nobody darf sogar durch (wird spaeter schon abgefangen)
    if ($perm->have_perm("user"))
    {

        //Sonderfall, Passwort fuer Schreiben nicht eingegeben, Lesen aber erlaubt
        if (Request::option('EntryMode') == "read_only"){
            $query = "SELECT Lesezugriff, Name FROM seminare WHERE Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($id));
            $temp = $statement->fetch(PDO::FETCH_ASSOC);

            if ($temp['Lesezugriff'] <= 1 && $perm->have_perm("autor")) {
                if (!seminar_preliminary($id,$user->id)) {  // we have to change behaviour, depending on preliminary

                    insert_seminar_user($id, $GLOBALS['user']->id, 'user', false, false, 'Mit Leserechten - ohne Schreibrechte - eingetragen');

                    parse_msg (sprintf("msg§"._("Sie wurden mit dem Status <b>Leser</b> in die Veranstaltung %s eingetragen."), '<b>'.htmlReady($temp['Name']).'</b>'));
                    echo"<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                    if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                    echo "<br><br></td></tr>";
                } else {
                    parse_msg (sprintf("error§"._("Die Veranstaltung \"%s\" ist teilnahmebeschränkt. Sie können sich nicht als Leser eintragen lassen."),htmlReady($temp['Name'])));
                }
                echo "</table>";
            }
            include ('lib/include/html_end.inc.php');
            page_close();
            die;
        }

        //wenn eine Sessionvariable gesetzt ist, nehmen wir besser die
        if (empty($id)) if (isset($SessSemName[1]))
            $id=$SessSemName[1];

        //laden von benoetigten Informationen
        $query = "SELECT Lesezugriff, Schreibzugriff, Passwort, Name FROM seminare WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if ($temp) {
            $SemSecLevelRead  = $temp['Lesezugriff'];
            $SemSecLevelWrite = $temp['Schreibzugriff'];
            $SemSecPass       = $temp['Passwort'];
            $SeminarName      = htmlReady($temp['Name']);
        }
        $query ="SELECT status FROM seminar_user WHERE Seminar_id = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id, $user->id));
        $SemUserStatus = $statement->fetchColumn();

        //Ueberpruefung auf korrektes Passwort
        if (!empty($pass) && $pass!="" && md5($pass)==$SemSecPass) {
            if (($SemUserStatus=="user") && ($perm->have_perm("autor")))
            {
                $query = "UPDATE seminar_user SET status = 'autor' WHERE Seminar_id = ? AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($id, $user->id));

                // LOGGING
                log_event('SEM_USER_ADD', $id, $user->id, 'autor', 'Schreibrechte erworben');
                parse_msg (sprintf("msg§"._("Sie wurden in der Veranstaltung %s auf den Status <b> Autor </b> hochgestuft."), '<b>'.$SeminarName.'</b>'));
                echo "<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                    if ($send_from_search)
                        echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                echo "<br><br></td></tr></table>";
                include ('lib/include/html_end.inc.php');
                page_close();
                die;
            }
            elseif ($perm->have_perm("autor"))
            {
                if (!seminar_preliminary($id,$user->id)) {

                    insert_seminar_user($id, $GLOBALS['user']->id, 'autor', false, false, 'Mit Schreibrechten eingetragen');

                    parse_msg (sprintf("msg§"._("Sie wurden mit dem Status <b>Autor</b> in die Veranstaltung %s eingetragen."), '<b>'.$SeminarName.'</b>'));
                    echo"<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                    if ($send_from_search) echo "&nbsp; |";
                } else {
                    temporaly_accepted($SeminarName, $user->id, $id, $ask, $sem_verify_suggest_studg, $temp_url);
                }
                if ($send_from_search)
                        echo "&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                echo "<br><br></td></tr></table>";
                include ('lib/include/html_end.inc.php');
                page_close();
                die;
            }
        }
        elseif (!empty($pass) && $pass!="") {
            parse_msg ("error§Ung&uuml;ltiges Passwort eingegeben, bitte nocheinmal versuchen !");
        }

    //Die eigentliche Ueberpruefung verschiedener Rechtesachen
    //User schon in der Seminar_user vorhanden? Und was macht er da eigentlich?
        if ($SemUserStatus) {
            if ($SemUserStatus=="user") { //Nur user? Dann muessen wir noch mal puefen
                if ($SemSecLevelWrite==2) { //Schreiben nur per Passwort, der Benutzer darf es eingeben
                    if ($perm->have_perm("autor")) { //nur globale Autoren duerfen sich hochstufen!
                        printf ("<tr><td class=\"blank\" colspan=2>&nbsp; &nbsp; " . _("Bitte geben Sie das Passwort f&uuml;r die Veranstaltung %s ein.") . "<br><br></td></tr>", '<b>'.$SeminarName.'</b>');
                        ?>
                        </td></tr>
                        <tr><td class="blank" colspan=2>
                        <form name="details" action="<? echo $temp_url; ?>" method="POST">
                        <?= CSRFProtection::tokenTag() ?>
                        &nbsp; &nbsp; <input type="PASSWORD" name="pass" size="12">
                        <input type="hidden" name="id" value="<? echo $id;?>">
                        <?= Button::createAccept(_('Abschicken')) ?>
                        </form>
                        </td></tr>
                        <?
                        echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp; &nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                            if ($send_from_search)
                                echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br><br>";
                        ?>
                        </td></tr></table>
                        <?
                    } else {
                        parse_msg ("info§". sprintf(_("Um in der Veranstaltung %s schreiben zu d&uuml;rfen, m&uuml;ssen Sie zumindest auf die Registrierungsmail reagiert haben!"), '<b>'.$SeminarName . '</b>'));
                        echo"<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                        if ($send_from_search)
                                echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br><br></td></tr></table>";
                    }
                    include ('lib/include/html_end.inc.php');
                    page_close();
                    die;
                }
                elseif ($SemSecLevelWrite==1){//Hat sich der globale Status in der Zwischenzeit geaendert? Dann hochstufen
                    if ($perm->have_perm("autor")) {
                        $query = "UPDATE seminar_user SET status = 'autor' WHERE Seminar_id = ? AND user_id = ?";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($id, $user->id));

                        // LOGGING
                        log_event('SEM_USER_ADD', $id, $user->id, 'autor', 'Hochgestuft auf autor');

                        parse_msg(sprintf("info§"._("Sie wurden in der Veranstaltung %s hochgestuft auf den Status <b>Autor</b>."), '<b>'.$SeminarName.'</b>'));
                        echo"<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                        if ($send_from_search)
                                echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br><br></td></tr></table>";
                        include ('lib/include/html_end.inc.php');
                        page_close();
                        die;
                    } else {//wenn nicht, informieren
                        parse_msg(sprintf("info§"._("Sie sind nur mit der Berechtigung <b>Leser</b> f&uuml;r die Veranstaltung %s freigeschaltet. Wenn Sie auf die Registrierungsmail antworten, k&ouml;nnen Sie in dieser Veranstaltung auch schreiben."), '<b>'.$SeminarName.'</b>'));
                        echo"<tr><td class=\"blank\" colspan=2>&nbsp; &nbsp; <a href=\"seminar_main.php?auswahl=$id\">"._("Hier kommen Sie zu der Veranstaltung")."</a>";
                        if ($send_from_search)
                                echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br><br></td></tr></table>";
                        include ('lib/include/html_end.inc.php');
                        page_close();
                        die;
                    }
                }
            } else { //User ist schon Autor oder hoeher, soll den Quatsch mal lassen und weiter ins Seminar
                parse_msg('info§' . sprintf(_("Sie sind schon mit der Berechtigung %s f&uuml;r die Veranstaltung %s freigeschaltet."), '<b>'. $SemUserStatus . '</b>', '<b>'. $SeminarName . '</b>'));
                echo "<tr><td class=\"blank\" colspan=2>&nbsp; &nbsp; <a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                if ($send_from_search)
                    echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                echo "<br><br></td></tr></table>";
                include ('lib/include/html_end.inc.php');
                page_close();
                die;
            }
        } else {//User ist noch nicht eingetragen in seminar_user
            if ($perm->have_perm("autor")) { //User ist global 'Autor'also normaler User
                if (($SemSecLevelWrite==3) && ($SemSecLevelRead==3)) {//Teilnehmerbeschraenkte Veranstaltung, naehere Uberpruefungen erforderlich
                    /*if ($auth->auth["perm"]=="dozent") { //Dozenten duerfen sich nicht fuer Anmeldebeschraenkte Veranstaltungen anmelden
                        parse_msg ('info§'. sprintf(_("Sie d&uuml;rfen sich mit dem Status Dozent nicht f&uuml;r die teilnahmebeschr&auml;nkte Veranstaltung %s anmelden.<br>Wenn Sie dennoch eingetragen werden m&ouml;chten, wenden Sie sich bitte direkt an die Dozentin oder den Dozenten der Veranstaltung."), '<b>'.$SeminarName.'</b>'));
                        echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                        if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br   ><br></td></tr></table>";
                        include ('lib/include/html_end.inc.php');
                        page_close();
                        die;
                        }*/
                    $query = "SELECT 1 FROM user_studiengang WHERE user_id = ?"; //Hat der Studie ueberhaupt Studiengaenge angegeben?
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($user->id));
                    if (!$statement->fetchColumn()) { //Es sind gar keine vorhanden! Hinweis wie man das eintragen kann
                        parse_msg ('info§' . sprintf(_("Die Veranstaltung %s ist teilnahmebeschr&auml;nkt. Um sich f&uuml;r teilnahmebeschr&auml;nkte Veranstaltungen eintragen zu k&ouml;nnen, m&uuml;ssen Sie einmalig Ihre Studieng&auml;nge angeben! <br> Bitte tragen Sie Ihre Studieng&auml;nge auf Ihrer %sProfilseite%s ein!"), '<b>'.$SeminarName .'</b>', '<a href="dispatch.php/settings/studies#studiengaenge">','</a>'));
                        echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                        if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br   ><br></td></tr></table>";
                        include ('lib/include/html_end.inc.php');
                        page_close();
                        die;
                        }
                    //Wurden wir evtl. schon in die Veranstaltung als Wartender eingetragen?
                    if (admission_seminar_user_get_position($user->id, $id)) { //Es gibt einen Eintrag, da darf ich also nicht mehr rein
                        parse_msg ('info§' . sprintf(_("Sie stehen schon auf der Anmelde- bzw. Warteliste der Veranstaltung %s. <br>Wenn Sie sich neu oder f&uuml;r ein anderes Kontingent eintragen wollen, dann l&ouml;schen Sie bitte vorher die Zuordnug auf der &Uuml;bersicht Ihrer Veranstaltungen."), '<b>'.$SeminarName.'</b>'));
                        echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                        if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                        echo "<br   ><br></td></tr></table>";
                        include ('lib/include/html_end.inc.php');
                        page_close();
                        die;
                        }
                    //Ok, es gibt also Studiengaenge und wir stehen noch nicht in der admission_seminar_user
                    $current_seminar = Seminar::getInstance($id);

                    //Sind noch Plätze frei?
                    if (!$current_seminar->getFreeAdmissionSeats()) {
                        if($current_seminar->admission_disable_waitlist){
                            $meldung = sprintf(_("Es gibt zur Zeit keinen freien Platz in der teilnahmebeschr&auml;nkten Veranstaltung %s."), '<b>'.$SeminarName.'</b>') .' <br> ';
                            parse_msg('info§'. $meldung);
                            echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                            if ($send_from_search)
                                echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                            echo "<br   ><br></td></tr></table>";
                            include ('lib/include/html_end.inc.php');
                            page_close();
                            die;
                        }
                    }
                    $query = "SELECT studiengang_id, 1
                              FROM admission_seminar_studiengang AS ass
                              LEFT JOIN studiengaenge USING (studiengang_id)
                              LEFT JOIN user_studiengang USING (studiengang_id)
                              WHERE seminar_id = ? AND (user_id = ? OR ass.studiengang_id = 'all')";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($id, $user->id));
                    $user_studiengang = $statement->fetchGrouped(PDO::FETCH_COLUMN);

                    if (!$sem_verify_suggest_studg) {//Wir wissen noch nicht mit welchem Studiengang der Benutzer rein will
                        if (count($user_studiengang) == 1 || (count($user_studiengang) > 1 && !$current_seminar->isAdmissionQuotaEnabled())) {//Nur einen passenden gefunden? Dann nehmen wir einfach mal diesen...
                            $sem_verify_suggest_studg = key($user_studiengang);
                        } elseif (count($user_studiengang) > 1) { //Mehrere gefunden, fragen welcher es denn sein soll
                            printf ('<tr><td class="blank" colspan=2>&nbsp; &nbsp; ' . _("Die Veranstaltung %s ist teilnahmebeschr&auml;nkt."). '<br><br></td></tr>', '<b>' . $SeminarName . '</b>');
                            print "<tr><td class=\"blank\" colspan=2>&nbsp; &nbsp; "._("Sie k&ouml;nnen sich f&uuml;r <b>eines</b> der m&ouml;glichen Kontingente anmelden.")."<br><br>&nbsp; &nbsp; "._("Bitte w&auml;hlen Sie das f&uuml;r Sie am besten geeignete Kontingent aus:")." <br><br></td></tr>";
                            ?>
                            <tr><td class="blank" colspan=2>
                            <form action="<? echo $temp_url; ?>" method="POST" >
                                <?= CSRFProtection::tokenTag() ?>
                                <input type="hidden" name="sem_verify_selection_send" value="TRUE">
                                   <?
                                foreach($current_seminar->admission_studiengang as $studiengang_id => $studiengang) {
                                    if (isset($user_studiengang[$studiengang_id]))
                                        printf ("&nbsp; &nbsp; <input type=\"RADIO\" name=\"sem_verify_suggest_studg\" value=\"%s\">&nbsp; <font size=-1><b>"._("Kontingent f&uuml;r %s (%s Pl&auml;tze insgesamt / %s belegt)")."</b></font><br>", $studiengang_id, htmlReady($studiengang['name']), $studiengang['num_total'], $studiengang['num_occupied']);
                                    else
                                        printf ("&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<font size=-1 color=\"#888888\">"._("Kontingent f&uuml;r %s (%s Pl&auml;tze insgesamt / %s belegt)")."</font><br>", htmlReady($studiengang['name']), $studiengang['num_total'], $studiengang['num_occupied']);
                                    }
                                   ?>
                            <br>&nbsp; &nbsp; <?= Button::createAccept(_('OK')) ?>
                            </form>
                            </td></tr>
                            <?
                            echo "<tr><td class=\"blank\" colspan=\"2\">";
                            if ($current_seminar->admission_type == 1) {
                                if ($current_seminar->admission_selection_take_place)
                                    printf ("<font size=-1>&nbsp; &nbsp; "._("Die Teilnehmerauswahl erfolgte nach dem Losverfahren am %s Uhr.")." "._("Weitere Pl&auml;tze k&ouml;nnen evtl. &uuml;ber die Warteliste vergeben werden.")." <br>&nbsp; &nbsp; "._("In Klammern ist die Anzahl der <b>insgesamt</b> verf&uuml;gbaren Pl&auml;tze pro Kontingent angegeben.")."</font><br>&nbsp; ", date("d.m.Y, G:i", $current_seminar->admission_endtime));
                                else
                                    printf ("<font size=-1>&nbsp; &nbsp; "._("Die Teilnehmerauswahl erfolgt nach dem Losverfahren am %s Uhr.")." <br>&nbsp; &nbsp; "._("In Klammern ist die Anzahl der <b>insgesamt</b> verf&uuml;gbaren Pl&auml;tze pro Kontingent angegeben.")."</font><br>&nbsp; ", date("d.m.Y, G:i", $current_seminar->admission_endtime));
                            } else {
                                if ($current_seminar->admission_selection_take_place)
                                    printf ("<font size=-1>&nbsp; &nbsp; "._("Die Teilnehmerauswahl erfolgte in der Reihenfolge der Anmeldung.")." "._(" Weitere Pl&auml;tze k&ouml;nnen evtl. &uuml;ber die Warteliste vergeben werden.")."<br>&nbsp; &nbsp;"._("In Klammern ist die Anzahl der <b>insgesamt</b> verf&uuml;gbaren Pl&auml;tze pro Kontingent angegeben.")."</font><br>&nbsp; ");
                                else
                                    printf ("<font size=-1>&nbsp; &nbsp; "._("Die Teilnehmerauswahl erfolgt in der Reihenfolge der Anmeldung.")."<br>&nbsp; &nbsp; "._("In Klammern ist die Anzahl der <b>insgesamt</b> verf&uuml;gbaren Pl&auml;tze pro Kontingent angegeben.")."</font><br>&nbsp; ");
                            }
                            echo "</td></tr>";
                            echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp; &nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                                if ($send_from_search)
                                    echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                            echo "<br><br>";
                            ?>
                            </td></tr></table>
                            <?
                            include ('lib/include/html_end.inc.php');
                            page_close();
                            die;
                        } else { //Keinen passenden Studiengaenge gefunden, abbruch
                            $query = "SELECT 1 FROM user_studiengang WHERE user_id = ?"; //Hat der Studie ueberhaupt Studiengaenge angegeben?
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array($user->id));
                            if ($statement->fetchColumn()) { //Es waren nur die falschen
                                parse_msg ('info§' . sprintf(_("Sie belegen leider keinen passenden Studiengang, um an der teilnahmebeschr&auml;nkten Veranstaltung %s teilnehmen zu k&ouml;nnen."), '<b>'.$SeminarName.'</b>'));
                                echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                                if ($send_from_search)
                                        echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                                echo "<br   ><br></td></tr></table>";
                                page_close();
                                include ('lib/include/html_end.inc.php');
                                die;
                            }
                        }
                    }
                    if ($sem_verify_suggest_studg && isset($user_studiengang[$sem_verify_suggest_studg])) { //User hat einen Studiengang angegeben oeder wir haben genau einen passenden gefunden, mit dem er jetzt rein will/kann

                        if (!$current_seminar->isAdmissionQuotaChecked()) { //Variante Eintragen nach Lostermin oder Enddatum der Kontigentierrung. Wenn noch Platz ist fuellen wir einfach auf, ansonsten Warteliste
                            if ($current_seminar->getFreeAdmissionSeats()) { //Wir koennen einfach eintragen, Platz ist noch
                                if (!seminar_preliminary($id,$user->id)) {

                                    insert_seminar_user($id, $GLOBALS['user']->id, 'autor', false, $sem_verify_suggest_studg, 'Mit Kontingent und Schreibrechten eingetragen, Studiengänge: ' . $sem_verify_suggest_studg);

                                    parse_msg ('msg§' . sprintf(_("Sie wurden mit dem Status <b>Autor</b> in die Veranstaltung %s eingetragen. Damit sind Sie zugelassen."), '<b>' . $SeminarName .'</b>'));
                                    echo"<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                                    if ($send_from_search) echo "&nbsp; |";
                                } else {
                                    temporaly_accepted($SeminarName, $user->id, $id, $ask, $sem_verify_suggest_studg, $temp_url);
                                }
                                if ($send_from_search)
                                        echo "&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                                echo "<br   ><br></td></tr></table>";
                                include ('lib/include/html_end.inc.php');
                                page_close();
                                die;
                            } else { //Auf die Warteliste
                                if(!$current_seminar->admission_disable_waitlist){
                                    $position = admission_seminar_user_insert($user->id, $id, 'awaiting', $sem_verify_suggest_studg);
                                    // logging
                                    log_event('SEM_USER_ADD', $id, $user->id, 'awaiting', 'Auf die Warteliste gesetzt, mit Kontingent, Position: '.$position.', Kontingent: '.$sem_verify_suggest_studg);
                                    $meldung = sprintf(_("Es gibt zur Zeit keinen freien Platz in der teilnahmebeschr&auml;nkten Veranstaltung %s. Sie wurden jedoch auf Platz %s der Warteliste gesetzt."), '<b>'.$SeminarName.'</b>', $position).' <br> '._("Sie werden automatisch eingetragen, sobald ein Platz f&uuml;r Sie frei wird.");
                                } else {
                                    $meldung = sprintf(_("Es gibt zur Zeit keinen freien Platz in der teilnahmebeschr&auml;nkten Veranstaltung %s."), '<b>'.$SeminarName.'</b>') .' <br> ';
                                }
                                parse_msg('info§'. $meldung);
                                echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                                if ($send_from_search)
                                        echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                                echo "<br   ><br></td></tr></table>";
                                include ('lib/include/html_end.inc.php');
                                page_close();
                                die;
                            }
                        } else { //noch nicht gelost oder Enddatum, also Kontingentierung noch aktiv
                            if ($current_seminar->admission_type == 1) { //Variante Losverfahren
                                admission_seminar_user_insert($user->id, $id, 'claiming', $sem_verify_suggest_studg);
                                // LOGGING
                                log_event('SEM_USER_ADD', $id, $user->id, 'claiming', 'Auf Warteliste für Losverfahren gesetzt');
                                parse_msg (sprintf("info§"._("Sie wurden auf die Anmeldeliste der Veranstaltung <b>%s</b> gesetzt.")." <br>"._("Teilnehmer der Veranstaltung <b>%s</b> werden Sie, falls Sie im Losverfahren am %s Uhr ausgelost werden.") . (!$current_seminar->admission_disable_waitlist ? _(" Sollten Sie nicht unter den Ausgelosten sein, werden Sie auf die Warteliste gesetzt. Sobald ein Platz f&uuml;r Sie frei wird, werden Sie vom System automatisch als Teilnehmer eingetragen.") : ""), $SeminarName, $SeminarName, date("d.m.Y, G:i", $current_seminar->admission_endtime)));
                                echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                                if ($send_from_search)
                                        echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                                echo "<br   ><br></td></tr></table>";
                                include ('lib/include/html_end.inc.php');
                                page_close();
                                die;
                            } else { //Variante chronologisches Anmelden
                                if ($current_seminar->getFreeAdmissionSeats($sem_verify_suggest_studg)) {//noch Platz in dem Kontingent --> direkt in seminar_user
                                    if (!seminar_preliminary($id,$user->id)) {

                                        insert_seminar_user($id, $GLOBALS['user']->id, 'autor', false, $sem_verify_suggest_studg, 'Mit Kontingent und Schreibrechten eingetragen, Studiengänge: ' . $sem_verify_suggest_studg);

                                        parse_msg (sprintf("msg§"._("Sie wurden mit dem Status <b>Autor</b> in die Veranstaltung <b>%s</b> eingetragen. Damit sind Sie zugelassen."), $SeminarName));
                                        echo"<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                                        if ($send_from_search) echo "&nbsp; |";
                                    } else {
                                        temporaly_accepted($SeminarName, $user->id, $id, $ask, $sem_verify_suggest_studg, $temp_url);
                                    }
                                    if ($send_from_search)
                                            echo "&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                                    echo "<br><br></td></tr></table>";
                                    page_close();
                                    die;
                                } else { //kein Platz mehr im Kontingent --> auf Warteposition in admission_seminar_user
                                    if(!$current_seminar->admission_disable_waitlist){
                                        $position = admission_seminar_user_insert($user->id, $id, 'awaiting', $sem_verify_suggest_studg);
                                        // LOGGING
                                        log_event('SEM_USER_ADD', $id, $user->id, 'awaiting', 'Auf Warteliste, mit Kontingent: '.$sem_verify_suggest_studg.', Position: '.$position);

                                        $meldung = sprintf(_("Es gibt zur Zeit keinen freien Platz in der teilnahmebeschr&auml;nkten Veranstaltung %s. Sie wurden jedoch auf Platz %s der Warteliste gesetzt."), '<b>'.$SeminarName.'</b>', $position).' <br> '._("Sie werden automatisch eingetragen, sobald ein Platz f&uuml;r Sie frei wird.");
                                    } else {
                                        $meldung = sprintf(_("Es gibt zur Zeit keinen freien Platz in der teilnahmebeschr&auml;nkten Veranstaltung %s."), '<b>'.$SeminarName.'</b>') .' <br> ';
                                    }
                                    parse_msg('info§'. $meldung);
                                    echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                                    if ($send_from_search)
                                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                                    echo "<br   ><br></td></tr></table>";
                                    include ('lib/include/html_end.inc.php');
                                    page_close();
                                    die;
                                }
                            }
                        }
                    }
                }
                elseif (($SemSecLevelWrite==2) && ($SemSecLevelRead==2)) {//Paswort auf jeden Fall erforderlich, also her damit
                    printf ("<tr><td class=\"blank\" colspan=2>&nbsp; &nbsp;"._("Bitte geben Sie das Passwort f&uuml;r die Veranstaltung <b>%s</b> ein.")."<br><br></td></tr>", $SeminarName);
                    ?>
                    </td></tr>
                    <tr><td class="blank" colspan=2>
                    <form name="details" action="<? echo $temp_url; ?>" method="POST">
                    <?= CSRFProtection::tokenTag() ?>
                    &nbsp; &nbsp; <input type="PASSWORD" name="pass" size="12">
                    <input type="hidden" name="id" value="<? echo $id;?>">
                    <?= Button::createAccept(_('Abschicken')) ?>
                    </form>
                    </td></tr>
                    <?
                    echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp; &nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                        if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                    echo "<br><br>";
                    ?>
                    </td></tr></table>
                    <?
                    include ('lib/include/html_end.inc.php');
                    page_close();
                    die;
                }
                elseif ($SemSecLevelWrite==2) {//nur passwort fuer Schreiben, User koennte ohne Passwort als 'User' in das Seminar
                    print "<form name=\"details\" action=\"".$temp_url."\" method=\"POST\">";
                    echo CSRFProtection::tokenTag();
                    print "<tr><td class=\"blank\" colspan=\"2\">";
                    print "<table width=\"97%\" align=\"center\" border=\"0\" cellapdding=\"2\" cellspacing=\"0\">";
                    print "<tr><td width=\"48%\" class=\"blank\">";
                    print _("Wenn Sie mit Lese- und Schreibberechtigung an der Veranstaltung teilnehmen wollen, geben Sie hier bitte das Passwort f&uuml;r diese Veranstaltung ein:");
                    print "</td><td width=\"4%\" class=\"blank\">&nbsp;";
                    print "</td><td width=\"48%\" class=\"blank\" >";
                    print _("Sie k&ouml;nnen auch ohne Eingabe eines Passwortes an der Veranstaltung teilnehmen. Sie haben in diesem Fall jedoch nur Leseberechtigung.");
                    print "</td></tr>";
                    print "<tr><td width=\"48%\" class=\"blank\" valign=\"top\">";
                    print "<br><input type=\"RADIO\" name=\"EntryMode\" checked value=\"pass\">&nbsp;"._("Ich kenne das Passwort dieser Veranstaltung");
                    print "</td><td width=\"4%\" class=\"blank\">&nbsp;";
                    print "</td><td width=\"48%\" class=\"blank\" valign=\"top\">";
                    print "<br><input type=\"RADIO\" name=\"EntryMode\" value=\"read_only\">&nbsp;"._("Ich m&ouml;chte an der Veranstaltung nur mit Leseberechtigung teilnehmen.");
                    print "<br>&nbsp;</td></tr>";
                    ?>
                    <tr><td class="blank">
                    <font size="-1"><?=_("Bitte geben Sie hier das Passwort ein:")?></font><br>
                    <input type="PASSWORD" name="pass" size="20">
                    <input type="hidden" name="id" value="<? echo $id;?>">
                    </td>
                    <td class="blank">&nbsp;</td>
                    <td class="blank" valign="top">
                        <font size="-1">
                            <?=_("(Sie k&ouml;nnen das Passwort sp&auml;ter unter &raquo;Details&laquo; innerhalb der Veranstaltung eingeben.)") ?>
                            </font>
                        </td>
                    </tr>
                    <tr><td class="blank" colspan="3" align="center">
                    <?= Button::createAccept(_('OK')) ?><br>&nbsp;
                    </td></tr></table>
                    </form>
                    <?
                    echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp; &nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                        if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                    echo "<br><br>";
                    ?>
                    </td></tr></table>
                    <?
                    include ('lib/include/html_end.inc.php');
                    page_close();
                    die;
                } else {//kein Passwortschutz, also wird der Kerl auf jeden Fall autor im Seminar
                    $InsertStatus="autor";
                }
            } else {//der Benutzer ist auch global 'User'
                if ($SemSecLevelRead>0) {//Lesen duerfen nur Autoren, also wech hier
                    parse_msg (sprintf("info§"._("Um an der Veranstaltung <b>%s</b> teilnehmen zu k&ouml;nnen, m&uuml;ssen Sie zumindest auf die Registrierungsmail geantwortet haben!"), $SeminarName));
                    echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
                    if ($send_from_search)
                            echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
                    echo "<br><br></td></tr></table>";
                    include ('lib/include/html_end.inc.php');
                    page_close();
                    die;
                } else {//Lesen mit Berechtigung 'User' geht
                    if ($SemSecLevelWrite==0) {//Wenn Schreiben auch mit Berechtigung 'user' geht, darf es sogar als 'autor' rein (auch wenn es gegen das Grundprizip verstoesst (keine hoeheren Rechte als globale Rechte). Das geht nur, wenn in der config.inc Nobody write=TRUE fuer Veranstaltungsklasse ist
                        $InsertStatus="autor";
                    } else { //sonst bleibt es bei 'user'
                        $InsertStatus="user";
                    }
                }
            }
        }

        if (isset($InsertStatus)) {//Status reinschreiben
            if (!seminar_preliminary($id,$user->id)) {

                insert_seminar_user($id, $GLOBALS['user']->id, $InsertStatus);

                NotificationCenter::postNotification('CourseDidEnroll', $id);

                parse_msg (sprintf("msg§"._("Sie wurden mit dem Status <b>%s</b> in die Veranstaltung <b>%s</b> eingetragen."), $InsertStatus, $SeminarName));
                echo"<tr><td class=\"blank\" colspan=2><a href=\"seminar_main.php?auswahl=$id\">&nbsp; &nbsp; "._("Hier kommen Sie zu der Veranstaltung")."</a>";
                if ($send_from_search) echo "&nbsp; |";
            } else {
                temporaly_accepted($SeminarName, $user->id, $id, $ask, $sem_verify_suggest_studg, $temp_url);
            }
            if ($send_from_search)
                    echo "&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
            echo "<br><br></td></tr></table>";
            include ('lib/include/html_end.inc.php');
            page_close();
            die;
        }
    }

  if ($current_seminar->read_level == 0) {//nur wenn das Seminar wirklich frei ist geht's hier weiter
    printf('<tr><td class="blank" colspan=2>&nbsp; &nbsp; '._("Die Veranstaltung %s erfordert keine Anmeldung. %sHier kommen Sie zu der Veranstaltung%s!").'<br><br></td></tr></table>', '<b>'.htmlready($current_seminar->getName()).'</b>', "<a href=\"seminar_main.php?auswahl=$id\">", '</a>');
  } else {//keine Rechte f&uuml;r das Seminar
        parse_msg ('error§' . sprintf(_("Sie haben nicht die erforderlichen Rechte, um an der Veranstaltung %s teilnehmen zu d&uuml;rfen!"), '<b>'.htmlready($current_seminar->getName()).'</b>'));
        echo "<tr><td class=\"blank\" colspan=2><a href=\"index.php\">&nbsp;&nbsp; "._("Zur&uuml;ck zur Startseite")."</a>";
        if ($send_from_search)
                echo "&nbsp; |&nbsp;<a href=\"$send_from_search_page\">"._("Zur&uuml;ck zur letzten Auswahl")."</a>";
        echo "<br><br></td></tr></table>";
    }
    include ('lib/include/html_end.inc.php');
    page_close();
?>
