<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
teilnehmer.php - Anzeige der Teilnehmer eines Seminares
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check('user');

include 'lib/seminar_open.php'; //hier werden die sessions initialisiert

require_once ('lib/msg.inc.php');
require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/admission.inc.php'); //Funktionen der Teilnehmerbegrenzung
require_once ('lib/statusgruppe.inc.php');  //Funktionen der Statusgruppen
require_once ('lib/messaging.inc.php'); //Funktionen des Nachrichtensystems
require_once ('config.inc.php');    //We need the config for some parameters of the class of the Veranstaltung
require_once ('lib/user_visible.inc.php');
require_once ('lib/export/export_studipdata_func.inc.php');
require_once ('lib/classes/Avatar.class.php');
require_once ('lib/classes/LockRules.class.php');

if (get_config('CHAT_ENABLE')){
    include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
}
$db = new DB_Seminar;
$db2 = new DB_Seminar;

$show_user_picture = false;
/*
* set the user_visibility of all unkowns to their global visibility
* set tutor and dozent to visible=yes
*/
$st = DBManager::get()->prepare("UPDATE seminar_user SET visible = 'yes' WHERE status IN ('tutor', 'dozent') AND Seminar_id = ?");
$st->execute(array($_SESSION['SessionSeminar']));

$st = DBManager::get()->prepare("UPDATE seminar_user su INNER JOIN auth_user_md5 aum USING(user_id)
                                 SET su.visible=IF(aum.visible IN('no','never') OR (aum.visible='unknown' AND " . (int)!Config::get()->USER_VISIBILITY_UNKNOWN . "), 'no','yes')
                                 WHERE Seminar_id = ? AND su.visible='unknown'");
$st->execute(array($_SESSION['SessionSeminar']));

/* ---------------------------------- */
$username = Request::quoted('username');
$cmd = Request::quoted('cmd');

if ($cmd == "make_me_visible" && !$perm->have_studip_perm('tutor',$SessSemName[1])) {
    if (Request::option('mode') == "participant") {
        $db->query("UPDATE seminar_user SET visible = 'yes' WHERE user_id = '".$auth->auth['uid']."' AND Seminar_id = '".$SessSemName[1]."'");
    } elseif (Request::option('mode')  == "awaiting") {
        $db->query("UPDATE admission_seminar_user SET visible = 'yes' WHERE user_id = '".$auth->auth['uid']."' AND seminar_id = '".$SessSemName[1]."'");
    }
}

if ($cmd == "make_me_invisible" && !$perm->have_studip_perm('tutor',$SessSemName[1])) {
    if (Request::option('mode') == "participant" ) {
        $db->query("UPDATE seminar_user SET visible = 'no' WHERE user_id = '".$auth->auth['uid']."' AND Seminar_id = '".$SessSemName[1]."'");
    } else {
        $db->query("UPDATE admission_seminar_user SET visible = 'no' WHERE user_id = '".$auth->auth['uid']."' AND seminar_id = '".$SessSemName[1]."'");
    }
}

checkObject();
checkObjectModule("participants");
object_set_visit_module('participants');
$last_visitdate = object_get_visit($SessSemName[1], 'participants');

if ($rechte) {
    PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenTeilnehmer");
} else {
    PageLayout::setHelpKeyword("Basis.InVeranstaltungTeilnehmer");
}
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("TeilnehmerInnen"));
Navigation::activateItem('/course/members/view');
// add skip link
SkipLinks::addIndex(Navigation::getItem('/course/members/view')->getTitle(), 'main_content', 100);

//Subject for sms
$stmt = DBManager::get()->query("SELECT VeranstaltungsNummer as sn FROM seminare WHERE Seminar_id = '".$SessSemName[1]."'");
$result = $stmt->fetch();
$subject = ( $result["sn"] == "" ) ? "[".$SessSemName['0']."]" : "[".$result['sn'].": ".$SessSemName['0']."]";

// Send message to multiple user
if (Request::submitted('do_send_msg') && Request::intArray('send_msg') && Seminar_Session::check_ticket(Request::option('studipticket')) && !LockRules::Check($id, 'participants')){
        $post = NULL;
        $sms_data = array();
        $send_msg = array_keys($_REQUEST['send_msg']);
        page_close(NULL);

        header('Location: '.URLHelper::getURL('sms_send.php', array('sms_source_page' => 'teilnehmer.php?cid=' .$_SESSION['SessionSeminar'], 'subject' => $subject, 'tmpsavesnd' => 1, 'rec_uname' => $send_msg)));
        die;
}

    // Start  of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

$messaging=new messaging;
$cssSw=new cssClassSwitcher;

if ($_SESSION['sms_msg']) {
    $msg = $_SESSION['sms_msg'];
    unset($_SESSION['sms_msg']) ;
}
// Aenderungen nur in dem Seminar, in dem ich gerade bin...
    $id=$SessSemName[1];

$db=new DB_Seminar;
$db2=new DB_Seminar;
$db3=new DB_Seminar;
$db4=new DB_Seminar;

$csv_not_found = array();

// check, if a the add-user button has been pressed and set cmd accordingly
if (Request::submitted('add_user')) $cmd = 'add_user';

/*
 * This function checks if a the given user has to be shown (is in the array
 * of downpulled users)
 *
 * @param  user_id integer
 *
 * returns boolean
 *
 */
function is_opened($user_id) {
    global $open_users;

    if (!isset($open_users)) return FALSE;
    if (array_search($user_id, $open_users) === FALSE) {
        return FALSE;
    } else {
        return TRUE;
    }
}

/**
 * Insert value into array if not present, remove otherwise.
 */
function insert_or_remove (array &$array, $value) {
    $index = array_search($value, $array);

    if ($index === false) {
        $array[] = $value;
    } else {
        unset($array[$index]);
        $array = array_values($array);
    }
}

URLHelper::addLinkParam('view_order', Request::option('view_order'));

if (!isset($open_areas)) {
        $open_areas = array();
}

if ($cmd == "allinfos" && $rechte) {
        insert_or_remove($open_areas, $area);
}

URLHelper::addLinkParam('open_areas', $open_areas);

if (!isset($open_users)) {
        $open_users = array();
}



if (($cmd == "moreinfos" || $cmd == "lessinfos") && $rechte) {
    // get user_id if somebody wants more infos about a user
    $db->query("SELECT user_id FROM auth_user_md5 WHERE username = '$username'");
    $db->next_record();
    $user_id = $db->f("user_id");
    insert_or_remove($open_users, $user_id);
}

URLHelper::addLinkParam('open_users', $open_users);

// Aktivitaetsanzeige an_aus

if ($cmd =="showscore") {
    //erst mal sehen, ob er hier wirklich Dozent ist...
    if ($rechte) {
        $db->query("UPDATE seminare SET showscore = '1' WHERE Seminar_id = '$id'");
        $msg = "msg§" . _("Die Aktivit&auml;tsanzeige wurde aktiviert.") . "§";
    }
}

if ($cmd =="hidescore") {
    //erst mal sehen, ob er hier wirklich Dozent ist...
    if ($rechte) {
        $db->query("UPDATE seminare SET showscore = '0' WHERE Seminar_id = '$id'");
        $msg = "msg§" . _("Die Aktivit&auml;tsanzeige wurde deaktiviert.") . "§";
    }
}

if (Seminar_Session::check_ticket(Request::option('studipticket')) && !LockRules::Check($id, 'participants')){
    // edit special seminar_info of an user
    if ($cmd == "change_userinfo") {
        //first we have to check if he is really "Dozent" of this seminar
        if ($rechte) {
            $db->query("UPDATE admission_seminar_user SET comment = '".Request::quoted('userinfo')."' WHERE seminar_id = '$id' AND user_id = '".Request::quoted('user_id')."'");
            $db->query("UPDATE seminar_user SET comment = '".Request::quoted('userinfo')."' WHERE Seminar_id = '$id' AND user_id = '".Request::quoted('user_id')."'");
            $msg = "msg§" . _("Die Zusatzinformationen wurden ge&auml;ndert.") . "§";
        }
        $cmd = "moreinfos";
    }

    // Hier will jemand die Karriereleiter rauf...

    if ( ($cmd == "pleasure" && $username) || (Request::submitted('do_autor_to_tutor') && is_array($_REQUEST['autor_to_tutor'])) ){
        //erst mal sehen, ob er hier wirklich Dozent ist... Tutoren d&uuml;rfen andere nicht zu Tutoren befoerdern!
        if ($rechte AND $SemUserStatus != "tutor")  {
            $msgs = array();
            if ($cmd == "pleasure"){
                $pleasure = array($username);
            } else {
                $pleasure = (is_array($_REQUEST['autor_to_tutor']) ? array_keys($_REQUEST['autor_to_tutor']) : array());
            }
            foreach($pleasure as $username){
                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username' AND perms!='user' AND perms!='autor'");
                if ($db->next_record()) {
                    $userchange = $db->f("user_id");
                    $fullname = $db->f("fullname");
                    $next_pos = get_next_position("tutor",$id);
                    // LOGGING
                    log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'tutor', 'Hochgestuft zum Tutor');
                    $db->query("UPDATE seminar_user SET status='tutor', position='$next_pos', visible='yes' WHERE Seminar_id = '$id' AND user_id = '$userchange' AND status='autor'");
                    if($db->affected_rows()) $msgs[] = $fullname;
                }
            }
            $msg = "msg§" . sprintf(_("Bef&ouml;rderung von %s durchgef&uuml;hrt"), htmlReady(join(', ',$msgs))) . "§";
        }
        else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
    }

    // jemand ist der anspruchsvollen Aufgabe eines Tutors nicht gerecht geworden...

    if ( ($cmd == "pain" && $username) || (Request::submitted('do_tutor_to_autor') && is_array($_REQUEST['tutor_to_autor'])) ){
        //erst mal sehen, ob er hier wirklich Dozent ist... Tutoren d&uuml;rfen andere Tutoren nicht rauskicken!
        if ($rechte AND $SemUserStatus != "tutor") {
            $msgs = array();
            if ($cmd == "pain"){
                $pain = array($username);
            } else {
                $pain = (is_array($_REQUEST['tutor_to_autor']) ? array_keys($_REQUEST['tutor_to_autor']) : array());
            }
            foreach($pain as $username){
                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username'");
                $db->next_record();
                $userchange = $db->f("user_id");
                $fullname = $db->f("fullname");

                $db->query("SELECT position FROM seminar_user WHERE user_id = '$userchange'");
                $db->next_record();
                $pos = $db->f("position");
                // LOGGING
                log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'autor', 'Runtergestuft zum Autor');
                $db->query("UPDATE seminar_user SET status='autor', position=0 WHERE Seminar_id = '$id' AND user_id = '$userchange' AND status='tutor'");

                re_sort_tutoren($id, $pos);

                if($db->affected_rows()) $msgs[] = $fullname;
            }
            $msg = "msg§" . sprintf (_("%s %s wurde entlassen und auf den Status '%s' zur&uuml;ckgestuft."), get_title_for_status('tutor', count($msgs)), htmlReady(join(', ',$msgs)), get_title_for_status('autor', 1)) . "§";
        }
        else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
    }

    // jemand ist zu bloede, sein Seminar selbst zu abbonieren...

    if ( ($cmd == "schreiben" && $username) || (Request::submitted('do_user_to_autor') && is_array($_REQUEST['user_to_autor'])) ){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "schreiben"){
                $schreiben = array($username);
            } else {
                $schreiben = (is_array($_REQUEST['user_to_autor']) ? array_keys($_REQUEST['user_to_autor']) : array());
            }
            foreach($schreiben as $username){
                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username' AND perms != 'user'");
                if ($db->next_record()) {
                    $userchange = $db->f("user_id");
                    $fullname = $db->f("fullname");
                    // LOGGING
                    log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'autor', 'Hochgestuft zum Autor');
                    $db->query("UPDATE seminar_user SET status='autor' WHERE Seminar_id = '$id' AND user_id = '$userchange' AND status='user'");
                    if($db->affected_rows()) $msgs[] = $fullname;
                }
            }
            $msg = "msg§" . sprintf(_("User %s wurde als Autor in die Veranstaltung aufgenommen."), htmlReady(join(', ',$msgs))) . "§";
        }
        else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
    }

    // jemand sollte erst mal das Maul halten...

    if ( ($cmd == "lesen" && $username) || (Request::submitted('do_autor_to_user') && is_array($_REQUEST['autor_to_user'])) ){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "lesen"){
                $lesen = array($username);
            } else {
                $lesen = (is_array($_REQUEST['autor_to_user']) ? array_keys($_REQUEST['autor_to_user']) : array());
            }
            foreach($lesen as $username){
                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username'");
                $db->next_record();
                $userchange = $db->f("user_id");
                $fullname = $db->f("fullname");
                // LOGGING
                log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'user', 'Runtergestuft zum User, keine Schreibberechtigung mehr');
                $db->query("UPDATE seminar_user SET status='user' WHERE Seminar_id = '$id' AND user_id = '$userchange' AND status='autor'");
                if($db->affected_rows()) $msgs[] = $fullname;
            }
            $msg = "msg§" . sprintf(_("Der/die AutorIn %s wurde auf den Status 'Leser' zur&uuml;ckgestuft."), htmlReady(join(', ',$msgs))) . "§";
            $msg.= "info§" . _("Um jemanden permanent am Schreiben zu hindern, m&uuml;ssen Sie die Veranstaltung auf \"Schreiben nur mit Passwort\" setzen und ein Veranstaltungs-Passwort vergeben.") . "<br>\n"
                    . _("Dann k&ouml;nnen sich weitere BenutzerInnen nur noch mit Kenntnis des Veranstaltungs-Passworts als 'Autor' anmelden.") . "§";
        }
        else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
    }

    // und tschuess...

    if ( ($cmd == "raus" && $username) || (Request::submitted('do_user_to_null') && is_array($_REQUEST['user_to_null'])) ){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "raus"){
                $raus = array($username);
            } else {
                $raus = (is_array($_REQUEST['user_to_null']) ? array_keys($_REQUEST['user_to_null']) : array());
            }
            foreach($raus as $username){
                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username'");
                $db->next_record();
                $userchange = $db->f("user_id");
                $fullname = $db->f("fullname");
                // LOGGING
                log_event('SEM_USER_DEL', $id, $userchange, 'Wurde aus der Veranstaltung rausgeworfen');
                $db->query("DELETE FROM seminar_user WHERE Seminar_id = '$id' AND user_id = '$userchange' AND status='user'");

                if($db->affected_rows()){
                    setTempLanguage($userchange);
                    $message = sprintf(_("Ihr Abonnement der Veranstaltung **%s** wurde von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn aufgehoben."), $SessSemName[0], get_title_for_status('dozent', 1));
                    restoreLanguage();
                    $messaging->insert_message(mysql_escape_string($message), $username, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Abonnement aufgehoben"), TRUE);
                    // raus aus allen Statusgruppen
                    RemovePersonStatusgruppeComplete ($username, $id);
                    $msgs[] = $fullname;
                }
            }
            //Pruefen, ob es Nachruecker gibt
            update_admission($id);

            $msg = "msg§" . sprintf(_("LeserIn %s wurde aus der Veranstaltung entfernt."), htmlReady(join(', ',$msgs))) . "§";
            $msg.= "info§" . _("Um jemanden permanent am Lesen zu hindern, m&uuml;ssen Sie die Veranstaltung auf \"Lesen nur mit Passwort\" setzen und ein Veranstaltungs-Passwort vergeben.") . "<br>\n"
                    . _("Dann k&ouml;nnen sich weitere BenutzerInnen nur noch mit Kenntnis des Veranstaltungs-Passworts anmelden.") . "§";
        }
        else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
    }

    //aus der Anmelde- oder Warteliste entfernen
    if ( ($cmd == "admission_raus" && $username)  || (Request::submitted('do_admission_delete') && is_array($_REQUEST['admission_delete']) ) ) {
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "admission_raus"){
                $adm_delete[] = $username;
            } else {
                $adm_delete = (is_array($_REQUEST['admission_delete']) ? array_keys($_REQUEST['admission_delete']) : array());
            }
            foreach($adm_delete as $username){
                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username'");
                $db->next_record();
                $userchange=$db->f("user_id");
                $fullname = $db->f("fullname");

                // LOGGING
                log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'Wurde aus der Warteliste der Veranstaltung rausgeworfen');

                $db->query("DELETE FROM admission_seminar_user WHERE seminar_id = '$id' AND user_id = '$userchange'");
                if($db->affected_rows()){
                    setTempLanguage($userchange);
                    if (!Request::int('accepted')) {
                        $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn von der Warteliste der Veranstaltung **%s** gestrichen und sind damit __nicht__ zugelassen worden."), get_title_for_status('dozent', 1), $SessSemName[0]);
                    } else {
                        $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn aus der Veranstaltung **%s** gestrichen und sind damit __nicht__ zugelassen worden."), get_title_for_status('dozent', 1), $SessSemName[0]);
                    }
                    restoreLanguage();

                    $messaging->insert_message(mysql_escape_string($message), $username, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("nicht zugelassen in Veranstaltung"), TRUE);

                    $msgs[] = $fullname;
                }
            }
            //Warteliste neu sortieren
            renumber_admission($id);
            if (Request::int('accepted')) update_admission($id);
            $msg = "msg§". sprintf(_("LeserIn %s wurde aus der Anmelde bzw. Warteliste entfernt."), htmlReady(join(', ', $msgs))) . '§';
        } else {
            $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
        }
    }
    if(is_array($_REQUEST['admission_rein'])){
        $cmd = 'admission_rein';
        $username = key($_REQUEST['admission_rein']);
    }
    //aus der Anmelde- oder Warteliste in die Veranstaltung hochstufen / aus der freien Suche als Tutoren oder Autoren eintragen
    if ((Request::submitted('do_admission_insert') && is_array($_REQUEST['admission_insert'])) || (($cmd ==  "admission_rein" || $cmd == "add_user") && $username)){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "admission_rein" || $cmd == "add_user"){
                $user_add[] = $username;
            } else {
                $user_add = (is_array($_REQUEST['admission_insert']) ? array_keys($_REQUEST['admission_insert']) : array());
            }
            foreach($user_add as $username){

                $db->query("SELECT " . $_fullname_sql['full'] . " AS fullname, a.* FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username = '$username'");
                $db->next_record();
                $userchange = $db->f("user_id");
                $fullname = $db->f("fullname");

                if ($cmd == "add_user" && $SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]
                    && $perm->have_studip_perm('dozent', $id)
                    && ($db->f('perms') == 'tutor' || $db->f('perms') == 'dozent')) {

                    if (!$SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["only_inst_user"]) {
                        $status = 'tutor';
                    } else {
                        // query - VA und Person teilen sich die selbe Einrichtung und Person ist weder autor noch tutor in der Einrichtung
                        $stmt = DBManager::get()->query("SELECT DISTINCT user_id FROM seminar_inst
                            LEFT JOIN user_inst USING(Institut_id)
                            WHERE user_id = '$userchange' AND seminar_id ='$SessSemName[1]'
                                AND inst_perms!='user' AND inst_perms!='autor'");

                        if ($stmt->rowCount()) {
                            $status = 'tutor';
                        } else {
                            $status = 'autor';
                        }
                    }
                } else {
                    $status = 'autor';
                }

                $admission_user = insert_seminar_user($id, $userchange, $status, (Request::int('accepted') || $_REQUEST['consider_contingent'] ? TRUE : FALSE), $_REQUEST['consider_contingent']);
                //Only if user was on the waiting list
                if($admission_user){
                    setTempLanguage($userchange);
                    if ($cmd == "add_user") {
                        $message = sprintf(_("Sie wurden vom einem/einer %s oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                    } else {
                        if (!Request::int('accepted')) {
                            $message = sprintf(_("Sie wurden vom einem/einer %s oder AdministratorIn aus der Warteliste in die Veranstaltung **%s** aufgenommen und sind damit zugelassen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                        } else {
                            $message = sprintf(_("Sie wurden von einem/einer %s oder AdministratorIn vom Status **vorläufig akzeptiert** zum/r TeilnehmerIn der Veranstaltung **%s** hochgestuft und sind damit zugelassen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                        }
                    }
                    restoreLanguage();
                    $messaging->insert_message(mysql_escape_string($message), $username, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                }
                $msgs[] = $fullname;
            }

            //Warteliste neu sortieren
            renumber_admission($id);

            if($admission_user){
                if ($cmd=="add_user") {
                    $msg = "msg§" . sprintf(_("NutzerIn %s wurde in die Veranstaltung mit dem Status <b>%s</b> eingetragen."), htmlReady($fullname), $status) . "§";
                } else {
                    if (!Request::int('accepted')) {
                        $msg = "msg§" . sprintf(_("NutzerIn %s wurde aus der Anmelde bzw. Warteliste mit dem Status <b>%s</b> in die Veranstaltung eingetragen."), htmlReady(join(', ', $msgs)), $status) . "§";
                    } else {
                        $msg = "msg§" . sprintf(_("NutzerIn %s wurde mit dem Status <b>%s</b> endgültig akzeptiert und damit in die Veranstaltung aufgenommen."), htmlReady(join(', ', $msgs)), $status) . "§";
                    }
                }
            } else if($_REQUEST['consider_contingent']){
                $msg = "error§" . _("Es stehen keine weiteren Plätze mehr im Teilnehmerkontingent zur Verfügung.") . "§";
            }
        } else {
            $msg = "error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
        }
    }

    // import users from a csv-list
    if ($cmd == 'csv' && $rechte) {
        $csv_mult_founds = array();
        $csv_count_insert = 0;
        $csv_count_multiple = 0;
        $df_id = null;
        if ($_REQUEST['csv_import_format'] && !in_array($_REQUEST['csv_import_format'], words('realname username'))) {
            //check accessible datafields for ("user" => 1, "autor" => 2, "tutor" => 4, "dozent" => 8)
            foreach(DataFieldStructure::getDataFieldStructures('user', (1|2|4|8), true) as $df) {
                if ($df->accessAllowed($perm) && in_array($df->getId(), $TEILNEHMER_IMPORT_DATAFIELDS)
                    && $df->getId() == $_REQUEST['csv_import_format']) {
                    $df_id = $df->getId();
                    break;
                }
            }
        }
        if ($_REQUEST['csv_import']) {
            $csv_lines = preg_split('/(\n\r|\r\n|\n|\r)/', trim($_REQUEST['csv_import']));
            foreach ($csv_lines as $csv_line) {
                $csv_name = preg_split('/[,\t]/', substr($csv_line, 0, 100),-1,PREG_SPLIT_NO_EMPTY);
                $csv_nachname = trim($csv_name[0]);
                $csv_vorname = trim($csv_name[1]);
                if ($csv_nachname){
                    if($_REQUEST['csv_import_format'] == 'realname'){
                        $db->query("SELECT a.user_id, username, " . $_fullname_sql['full_rev'] ." AS fullname, perms, b.Seminar_id as is_present FROM auth_user_md5 a ".
                        "LEFT JOIN user_info USING(user_id) LEFT JOIN seminar_user b ON (b.user_id=a.user_id AND b.Seminar_id='$SessSemName[1]')  ".
                        "WHERE perms IN ('autor','tutor','dozent') AND ".
                        "(Nachname LIKE '" . $csv_nachname . "'"
                        . ($csv_vorname ? " AND Vorname LIKE '" . $csv_vorname . "'" : '')
                        . ") ORDER BY Nachname");
                    } elseif ($_REQUEST['csv_import_format'] == 'username') {
                        $db->query("SELECT a.user_id, username, " . $_fullname_sql['full_rev'] ." AS fullname, perms, b.Seminar_id as is_present FROM auth_user_md5 a ".
                        "LEFT JOIN user_info USING(user_id) LEFT JOIN seminar_user b ON (b.user_id=a.user_id AND b.Seminar_id='$SessSemName[1]')  ".
                        "WHERE perms IN ('autor','tutor','dozent') AND ".
                        "username LIKE '" . $csv_nachname . "' ORDER BY Nachname");
                    } else {
                        $db->query("SELECT a.user_id, username, " . $_fullname_sql['full_rev'] ." AS fullname, perms, b.Seminar_id as is_present FROM
                        datafields_entries de LEFT JOIN auth_user_md5 a on a.user_id=de.range_id ".
                        "LEFT JOIN user_info USING(user_id) LEFT JOIN seminar_user b ON (b.user_id=a.user_id AND b.Seminar_id='$SessSemName[1]')  ".
                        "WHERE perms IN ('autor','tutor','dozent') AND ".
                        "de.datafield_id='".$df_id."' AND de.content = '" . $csv_nachname . "' ORDER BY Nachname");
                    }
                    if ($db->num_rows() > 1) {
                        while ($db->next_record()) {
                            if($db->f('is_present')) {
                                $csv_count_present++;
                            } else {
                                $csv_mult_founds[$csv_line][] = $db->Record;
                            }
                        }

                        if (is_array($csv_mult_founds[$csv_line])) {
                            $csv_count_multiple++;
                        }
                    } elseif ($db->num_rows() > 0) {
                        $db->next_record();
                        if(!$db->f('is_present')){
                            if(insert_seminar_user($id, $db->f('user_id'), 'autor', isset($_REQUEST['consider_contingent']), $_REQUEST['consider_contingent'])){
                                $csv_count_insert++;
                                setTempLanguage($userchange);
                                if ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]) {
                                    $message = sprintf(_("Sie wurden von einem/r LeiterIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                                } else {
                                    $message = sprintf(_("Sie wurden vom einem/r DozentIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                                }
                                restoreLanguage();
                                $messaging->insert_message(mysql_escape_string($message), $db->f('username'), "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                            } elseif (isset($_REQUEST['consider_contingent'])){
                                $csv_count_contingent_full++;
                            }
                        } else {
                            $csv_count_present++;
                        }
                    } else {
                        // not found
                        $csv_not_found[] = stripslashes($csv_nachname) . ($csv_vorname ? ', ' . stripslashes($csv_vorname) : '');
                    }
                }
            }
        }
        if (sizeof($_REQUEST['selected_users'])) {
            foreach ($_REQUEST['selected_users'] as $selected_user) {
                if ($selected_user) {
                    if(insert_seminar_user($id, get_userid($selected_user), 'autor', isset($_REQUEST['consider_contingent']), $_REQUEST['consider_contingent'])){
                        $csv_count_insert++;
                        setTempLanguage($userchange);
                        if ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]) {
                            $message = sprintf(_("Sie wurden von einem/r LeiterIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                        } else {
                            $message = sprintf(_("Sie wurden vom einem/r DozentIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                        }
                        restoreLanguage();
                        $messaging->insert_message(mysql_escape_string($message), $selected_user, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                    } elseif (isset($_REQUEST['consider_contingent'])){
                        $csv_count_contingent_full++;
                    }
                }
            }
        }
        $msg = '';
        if (!$csv_count_multiple) {
            $cmd = '';
        }
        if (!sizeof($csv_lines) && !sizeof($_REQUEST['selected_users'])) {
            $msg = 'error§' . _("Keine NutzerIn gefunden!") . '§';
            $cmd = '';
        } else {
            if ($csv_count_insert) {
                $msg .=  'msg§' . sprintf(_("%s NutzerInnen als AutorIn in die Veranstaltung eingetragen!"),
                        $csv_count_insert) . '§';
            }
            if ($csv_count_present) {
                $msg .=  'info§' . sprintf(_("%s NutzerInnen waren bereits in der Veranstaltung eingetragen!"),
                        $csv_count_present) . '§';
            }
            if ($csv_count_multiple) {
                $msg .= 'info§' . sprintf(_("%s NutzerInnen konnten <b>nicht eindeutig</b> zugeordnet werden! Nehmen Sie die Zuordnung am Ende dieser Seite manuell vor."),
                        $csv_count_multiple) . '§';
            }
            if (sizeof($csv_not_found)) {
                $msg .= 'error§' . sprintf(_("%s NutzerInnen konnten <b>nicht</b> zugeordnet werden! Am Ende dieser Seite finden Sie die Namen, die nicht zugeordnet werden konnten."),
                        sizeof($csv_not_found)) . '§';
            }
            if($csv_count_contingent_full){
                $msg .= 'error§' . sprintf(_("%s NutzerInnen konnten <b>nicht</b> zugeordnet werden, da das ausgewählte Kontingent keine freien Plätze hat."),
                        $csv_count_contingent_full) . '§';
            }
        }
    }

    // so bin auch ich berufen?

    if ( Request::submitted('add_tutor') ) {
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte AND $SemUserStatus!="tutor") {
                    // nur wenn wer ausgewaehlt wurde
            $u_id = Request::option('u_id');
            if ($u_id != "0") {
                $query = "SELECT DISTINCT b.user_id, username, Vorname, Nachname, inst_perms, perms FROM seminar_inst d LEFT JOIN user_inst a USING(Institut_id) ".
                "LEFT JOIN auth_user_md5  b USING(user_id) ".
                "LEFT JOIN seminar_user c ON (c.user_id=a.user_id AND c.seminar_id='$SessSemName[1]')  ".
                "WHERE d.seminar_id = '$SessSemName[1]' AND a.inst_perms IN ('tutor','dozent') AND ISNULL(c.seminar_id) ORDER BY Nachname";
                $db->query($query);
                    // wer versucht denn da wen nicht zugelassenen zu berufen?
                if ($db->next_record()) {
                    // so, Berufung ist zulaessig
                    $db2->query("SELECT status FROM seminar_user WHERE Seminar_id = '$id' AND user_id = '$u_id'");
                    if ($db2->next_record()) {
                        // der Dozent hat Tomaten auf den Augen, der Mitarbeiter sitzt schon im Seminar. Na, auch egal...
                        if ($db2->f("status") == "autor" || $db2->f("status") == "user") {
                            // gehen wir ihn halt hier hochstufen
                            $next_pos = get_next_position("tutor",$id);
                            // LOGGING
                            log_event('SEM_USER_ADD', $id, $userchange, 'tutor', 'Wurde zum Tutor ernannt (add_tutor_x)');
                            $db2->query("UPDATE seminar_user SET status='tutor', position='$next_pos' WHERE Seminar_id = '$id' AND user_id = '$u_id'");
                            $msg = "msg§" . sprintf(_("%s wurde auf den Status '%s' bef&ouml;rdert."), get_fullname($u_id,'full',1), get_title_for_status('tutor', 1)) . "§";
                            //kill from waiting user
                            $db2->query("DELETE FROM admission_seminar_user WHERE seminar_id = '$id' AND user_id = '$u_id'");
                            //reordner waiting list
                            renumber_admission($id);
                        } else {
                            ;   // na, das ist ja voellig witzlos, da tun wir einfach nix.
                                // Nicht das sich noch ein Dozent auf die Art und Weise selber degradiert!
                        }
                    } else {  // ok, einfach aufnehmen.
                        insert_seminar_user($id, $u_id, "tutor");

                        $msg = "msg§" . sprintf(_("%s wurde als %s in die Veranstaltung aufgenommen."), get_fullname($u_id,'full',1), get_title_for_status('tutor', 1));

                        setTempLanguage($userchange);
                        $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiteriIn (%s) oder AdministratorIn in die Veranstaltung **%s** aufgenommen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                        restoreLanguage();
                        $messaging->insert_message(mysql_escape_string($message), get_username($u_id), "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                    }
                }
                else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
            }
            else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
        }
        else $msg ="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.") . "§";
    }
}
//Alle fuer das Losen anstehenden Veranstaltungen bearbeiten (wenn keine anstehen wird hier nahezu keine Performance verbraten!)
check_admission();

if (LockRules::Check($SessSemName[1], 'participants')) {
    $lockdata = LockRules::getObjectRule($SessSemName[1]);
    if ($lockdata['description']) {
        $msg .= "info§" . formatLinks($lockdata['description']);
    }
}

$gruppe = array(
    'dozent' => get_title_for_status('dozent', 2),
    'tutor'  => get_title_for_status('tutor', 2),
    'autor'  => get_title_for_status('autor', 2),
    'user'   => get_title_for_status('user', 2)
);

if ($perm->have_perm("tutor")) {
    $gruppe['accepted'] = get_title_for_status('accepted', 2);
}

$multiaction['tutor'] = array('insert' => null, 'delete' => array('tutor_to_autor', sprintf(_("Ausgewählte %s entlassen"), get_title_for_status('tutor', 2))), 'send' => array('send_msg', 'Nachricht an ausgewählte Benutzer verfassen'));
$multiaction['autor'] = array('insert' => array('autor_to_tutor', sprintf(_("Ausgewählte Benutzer als %s eintragen"), get_title_for_status('tutor', 2))), 'delete' => array('autor_to_user', _("Ausgewählten Benutzern das Schreibrecht entziehen")), 'send' => array('send_msg', 'Nachricht an ausgewählte Benutzer verfassem'));
$multiaction['user'] = array('insert' => array('user_to_autor',_("Ausgewählten Benutzern das Schreibrecht erteilen")), 'delete' => array('user_to_null', _("Ausgewählte Benutzer aus der Veranstaltung entfernen")),'send' => array('send_msg', 'Nachricht an ausgewählte Benutzer verfassen'));
$multiaction['accepted'] = array('insert' => array('admission_insert',_("Ausgewählte Benutzer akzeptieren")), 'delete' => array('admission_delete', _("Ausgewählte Benutzer aus der Veranstaltung entfernen")), 'send' => array('send_msg', 'Nachricht an ausgewählte Benutzer verfassen'));

$db->query("SELECT COUNT(user_id) as teilnehmer, COUNT(IF(admission_studiengang_id <> '',1,NULL)) as teilnehmer_kontingent FROM seminar_user WHERE seminar_id='".$SessSemName[1]."' AND status IN('autor','user')");
$db->next_record();
$anzahl_teilnehmer = $db->f('teilnehmer');
$anzahl_teilnehmer_kontingent = $db->f('teilnehmer_kontingent');
$db->query("SELECT COUNT(user_id) as teilnehmer, COUNT(IF(studiengang_id <> '',1,NULL)) as teilnehmer_kontingent FROM admission_seminar_user WHERE seminar_id='".$SessSemName[1]."' AND status = 'accepted'");
$db->next_record();
$anzahl_teilnehmer += $db->f('teilnehmer');
$anzahl_teilnehmer_kontingent += $db->f('teilnehmer_kontingent');
?>

        <script type="text/javascript">
            function invert_selection(prefix, theform){
                my_elements = document.forms[theform].elements;
                for(i = 0; i < my_elements.length; ++i){
                    if(my_elements[i].type == 'checkbox' && my_elements[i].name.substr(0, prefix.length) == prefix){
                    if(my_elements[i].checked)
                        my_elements[i].checked = false;
                    else
                        my_elements[i].checked = true;
                    }
                }
            return false;
            }

        </script>
        <table cellspacing="0" border="0" width="100%">
        <tr>
        <td colspan="2" class="blank" id="change_visibility">
            <?
            $db3->query("SELECT status, visible FROM seminar_user WHERE user_id = '".$auth->auth['uid']."' AND Seminar_id = '".$_SESSION['SessionSeminar']."'");
            $visible_mode = "false";

            if ($db3->num_rows() > 0) {
                $db3->next_record();
                if ($db3->f("visible") == "yes") {
                    $iam_visible = true;
                } else {
                    $iam_visible = false;
                }
                if ($db3->f("status") == "user" || $db3->f("status")=="autor") {
                    $visible_mode = "participant";
                } else {
                    $iam_visible = true;
                    $visible_mode = false;
                }
            }

            $db3->query("SELECT status, visible FROM admission_seminar_user WHERE user_id = '".$auth->auth['uid']."' AND seminar_id = '".$_SESSION['SessionSeminar']."'");
            if ($db3->num_rows() > 0) {
                if ($db3->f("visible") == "yes") {
                    $iam_visible = true;
                } else {
                    $iam_visible = false;
                }
                $visible_mode = "awaiting";
            }
        if (!$perm->have_studip_perm('tutor',$SessSemName[1])) {
            // add skip link
            SkipLinks::addIndex(_("Sichtbarkeit ändern"), 'change_visibility');
            if ($iam_visible) {
        ?>
        <br>
            <b><?=  _("Sie erscheinen für andere TeilnehmerInnen sichtbar auf der Teilnehmerliste."); ?></b><br>
            <a href="<?= URLHelper::getLink('?cmd=make_me_invisible&mode='.$visible_mode) ?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/visibility-invisible.png" border="0">
            <?= _("Klicken Sie hier, um unsichtbar zu werden.") ?>
            </a>
        <br>
        <?
            } else {
        ?>
        <br>
            <b><?=  _("Sie erscheinen nicht auf der Teilnehmerliste."); ?></b><br>
            <a href="<?= URLHelper::getLink('?cmd=make_me_visible&mode='.$visible_mode) ?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/visibility-invisible.png" border="0">
            <?= _("Klicken Sie hier, um sichtbar zu werden.") ?>
            </a>
        <br>
        <?
            }
        }
            ?>
        </td>
    </tr>

    <? if ($rechte) { ?>

    <tr>
        <td class="blank" colspan="2" align="left">
            <table class="blank" border=0 cellpadding=0 cellspacing=0>
                    <tr>
                        <td class="blank">&nbsp;</td>
                    </tr>
                    <tr>
                <td class="steelkante" valign="middle">
                            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="22" width="5">
                        </td>
                <td class="steelkante" valign="middle">
                            <font size="-1"><?=_("Sortierung:")?>&nbsp;</font>
                        </td>
                        <? if (!(Request::option('view_order')) || (Request::option('view_order') == "abc")) { ?>
                        <td nowrap class="steelgraulight_shadow" valign="middle">
                            <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
                            <font size="-1"><?=_("Alphabetisch")?></font>&nbsp;
                        <? } else { ?>
                        <td nowrap class="steelkante" valign="middle">
                            &nbsp;
                            <a href="<?= URLHelper::getLink('?view_order=abc') ?>">
                                <?= Assets::img('icons/16/grey/arr_1right.png', array('class' => 'text-top')) ?>
                                <font size="-1" color="#555555"><?=_("Alphabetisch")?></font>
                            </a>
                            &nbsp;
                        <? } ?>
                        </td>
                        <? if ((Request::option('view_order')) && (Request::option('view_order') == "date")) { ?>
                        <td nowrap class="steelgraulight_shadow" valign="middle">
                            <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
                            <font size="-1"><?=_("Anmeldedatum")?></font>&nbsp;
                        <? } else { ?>
                        <td nowrap class="steelkante" valign="middle">
                            &nbsp;
                            <a href="<?= URLHelper::getLink('?view_order=date') ?>">
                                <?= Assets::img('icons/16/grey/arr_1right.png', array('class' => 'text-top')) ?>
                                <font size="-1" color="#555555"><?=_("Anmeldedatum")?></font>
                            </a>
                            &nbsp;
                        <? } ?>
                        </td>
                        <? if ((Request::option('view_order')) && (Request::option('view_order') == "active")) { ?>
                        <td nowrap class="steelgraulight_shadow" valign="middle">
                            <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
                            <font size="-1"><?=_("Aktivität")?></font>&nbsp;
                        <? } else { ?>
                        <td nowrap class="steelkante" valign="middle">
                            &nbsp;
                            <a href="<?= URLHelper::getLink('?view_order=active') ?>">
                                <?= Assets::img('icons/16/grey/arr_1right.png', array('class' => 'text-top')) ?>
                                <font size="-1" color="#555555"><?=_("Aktivität")?></font>
                            </a>
                            &nbsp;
                        <? } ?>
                        </td>

                            <td nowrap align="right" class="steelkante" valign="middle"> <?

            $db3->query ("SELECT showscore  FROM seminare WHERE Seminar_id = '".$_SESSION['SessionSeminar']."'");
            while ($db3->next_record()) {
                if ($db3->f("showscore") == 1) {
                    if ($rechte) {
                        printf ("<a href=\"%s\"><img src=\"" . Assets::image_path('showscore1.png') . "\" %s> </a>", URLHelper::getLink('?cmd=hidescore'), tooltip(_("Aktivitätsanzeige eingeschaltet. Klicken zum Ausschalten.")));
                    } else {
                        echo "&nbsp; ";
                    }
                    $showscore = TRUE;
                } else {
                    if ($rechte) {
                        printf ("<a href=\"%s\"><img src=\"" . Assets::image_path('showscore0.png') . "\" %s> </a>", URLHelper::getLink('?cmd=showscore'), tooltip(_("Aktivitätsanzeige ausgeschaltet. Klicken zum Einschalten.")));
                    } else {
                        echo "&nbsp; ";
                    }
                    $showscore = FALSE;
                }
            }
        ?>
        </td>

<td><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/balken.jpg"></td>
                    <tr>
                </table>
        </td>
    </tr>
    <tr>
        <td class="blank" width="100%" colspan="2">
        <a href="<?= URLHelper::getLink('sms_send.php', array('sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'course_id' => $SessSemName[1], 'emailrequest' => 1, 'subject' => $subject, 'filter' => 'all')) ?>">
        <?= Assets::img('icons/16/blue/move_right/mail.png', array('class' => 'text-top')) ?>
        <?=_("Systemnachricht mit Emailweiterleitung an alle Teilnehmer verschicken")?>
        </a>
        </td>
    </tr>
    <? } ?>

    <tr>
        <td class="blank" width="100%" colspan="2">&nbsp;
            <?
            if ($msg) parse_msg($msg);
            ?>
        </td>
    </tr>
<tr>
    <td class="blank" colspan="2">

    <table width="99%" border="0"  cellpadding="2" cellspacing="0" align="center">

<?
$studipticket = Seminar_Session::get_ticket();

//Index berechnen
$db3->query ("SELECT count(dokument_id) AS count_doc FROM dokumente WHERE seminar_id = '".$_SESSION['SessionSeminar']."'");
if ($db3->next_record()) {
    $aktivity_index_seminar = $db3->f("count_doc") * 5;
}
$db3->query ("SELECT count(topic_id) AS count_post FROM px_topics WHERE Seminar_id = '".$_SESSION['SessionSeminar']."'");
if ($db3->next_record()) {
    $aktivity_index_seminar += $db3->f("count_post");
}
$db3->query ("SELECT count(user_id) AS count_pers FROM seminar_user WHERE Seminar_id = '".$_SESSION['SessionSeminar']."'");
if ($db3->next_record() && $db3->f("count_pers")) {
    $aktivity_index_seminar /= $db3->f("count_pers");
}

//Veranstaltungsdaten holen
$sem = Seminar::GetInstance($_SESSION['SessionSeminar']);
$sem->restoreAdmissionStudiengang();
if ($rechte) {
    if ($sem->isAdmissionEnabled())
        $colspan=10;
    else
        $colspan=9;
} else
    $colspan=7;
if ($showscore==TRUE) $colspan++;

switch (Request::option('view_order')) {
    case "date":
        $sortby = "mkdate";
        break;

    case "active":
        $sortby = "doll DESC";
        break;

    default:
        $sortby = "Nachname, Vorname";
        break;
}

while (list ($key, $val) = each ($gruppe)) {

    $counter=1;

    if ($key == "accepted") {  // modify query if user is in admission_seminar_user and not in seminar_user
        $tbl = "admission_seminar_user";
        $tbl2 = "";
    } else {
        $tbl = "seminar_user";
        $tbl2 = "admission_";
    }

    $db->query ("SELECT $tbl.visible, $tbl.mkdate, comment, $tbl.user_id, ". $_fullname_sql['full'] ." AS fullname,
                username, status, count(topic_id) AS doll,  studiengaenge.name, ".$tbl.".".$tbl2."studiengang_id
                AS studiengang_id
                FROM $tbl LEFT JOIN px_topics ON (px_topics.user_id = ".$tbl.".user_id
                                                                AND px_topics.Seminar_id = ".$tbl.".Seminar_id AND px_topics.anonymous = 0)
                LEFT JOIN auth_user_md5 ON (".$tbl.".user_id=auth_user_md5.user_id)
                LEFT JOIN user_info ON (auth_user_md5.user_id=user_info.user_id)
                LEFT JOIN studiengaenge ON (".$tbl.".".$tbl2."studiengang_id = studiengaenge.studiengang_id)
                WHERE ".$tbl.".Seminar_id = '".$_SESSION['SessionSeminar']."'
                AND status = '$key' GROUP by ".$tbl.".user_id ORDER BY $sortby");

    if($rechte && $key == 'autor'  && $sem->isAdmissionEnabled()){
        echo '<tr><td class="blank" colspan="'.$colspan.'" align="right"><font size="-1">';
        printf(_("<b>Teilnahmebeschränkte Veranstaltung</b> -  Teilnehmerkontingent: %s, davon belegt: %s, zusätzlich belegt: %s"),
            $sem->admission_turnout, $anzahl_teilnehmer_kontingent, $anzahl_teilnehmer - $anzahl_teilnehmer_kontingent);
        echo '</font></td></tr>';
    }
    if ($db->num_rows()) { //Only if Users were found...
        $info_is_open = false;
        $tutor_count = 0;
    // die eigentliche Teil-Tabelle
    if ($key != 'dozent') {
        echo "<form name=\"$key\" action=\"".URLHelper::getLink("?studipticket=$studipticket")."\" method=\"post\">";
        echo CSRFProtection::tokenTag();
    }
    if ($key == 'accepted') echo '<input type="hidden" name="accepted" value="1">';

    echo "<tr height=28>";
    if ($showscore==TRUE)
        echo "<td class=\"steel\" width=\"1%\">&nbsp; </td>";
    print "<td class=\"steel\" width=\"1%\" align=\"center\" valign=\"middle\">";
    if ($rechte) {
        if (in_array($key, $open_areas)) {
            $image = "icons/16/blue/arr_1down.png";
            $tooltiptxt = _("Informationsfelder wieder hochklappen");
        } else {
            $image = "icons/16/blue/arr_1right.png";
            $tooltiptxt = _("Alle Informationsfelder aufklappen");
        }
        print "<a href=\"".URLHelper::getLink("?cmd=allinfos&area=$key")."\">";
        print "<img src=\"". Assets::image_path($image) ."\" ".tooltip($tooltiptxt)." class=\"text-top\"></a>";
    } else {
        print "&nbsp; ";
    }

    print "</td>";

    // add skip link
    SkipLinks::addIndex($val, 'member_group_' . $key);

    echo '<td class="steel" width="19%" align="left" id="member_group_' . $key . '">'.
           '<img src="'.$GLOBALS['ASSETS_URL'].'images/blank.gif" width="1" height="20">'.
           '<font size="-1"><b>' . $val . '</b></font>'.
         '</td>';

    // mail button einfügen
    if ($rechte) {
        echo '<td class="steel" width="10%">';
        // hier kann ne flag setzen um mail extern zu nutzen
        if ($ENABLE_EMAIL_TO_STATUSGROUP) {
            $db_mail = new DB_Seminar();
            $seminar_user_table =
                $key == 'accepted' ? 'admission_seminar_user' : 'seminar_user';
            $db_mail->query("SELECT Email FROM $seminar_user_table su ".
                            "LEFT JOIN auth_user_md5 au ON (su.user_id = au.user_id) ".
                            "WHERE su.seminar_id = '".$SessSemName[1]."' ".
                            "AND status = '$key' ORDER BY Email");
            $users = array();
            while ($db_mail->next_record()) {
                $users[] = $db_mail->f("Email");
            }
            $all_user = implode(',', $users);

            echo '<a href="mailto:'.$all_user.'">';
            echo Assets::img('icons/16/blue/move_right/mail.png', array('title' => sprintf(_('E-Mail an alle %s schicken'), $val), 'align' => 'absmiddle'));
            echo '</a>&nbsp;';
        }

        if ($key == 'accepted') {
            $msg_params = array('filter' => 'prelim', 'sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'course_id' => $SessSemName[1], 'subject' => $subject);
        } else {
            $msg_params = array('filter' => 'send_sms_to_all', 'who' => $key, 'sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'course_id' => $SessSemName[1], 'subject' => $subject);
        }
        echo '<a href="'.URLHelper::getLink('sms_send.php', $msg_params).'">';
        echo Assets::img('icons/16/blue/mail.png', array('title' => sprintf(_('Nachricht an alle %s schicken'), $val), 'align' => 'absmiddle'));
        echo '</a>';
        echo '</td>';
    } else {
        echo '<td class="steel">&nbsp;</td>';
    }

    echo "</b></font></td>";

    if ($key != "dozent" && $rechte) {
        printf("<td class=\"steel\" width=\"1%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Anmeldedatum"));
    } else if ($key == "dozent" && $rechte) {
        printf("<td class=\"steel\" width=\"9%%\" align=\"center\" valign=\"bottom\">&nbsp;</td>");
    }
    printf("<td class=\"steel\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Forenbeiträge"));
    printf("<td class=\"steel\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Dokumente"));
    printf("<td class=\"steel\" width=\"9%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Nachricht"));


    if ($rechte && !LockRules::Check($id, 'participants')) {
        $tooltip = tooltip(_("Klicken, um Auswahl umzukehren"),false);
        if ($sem->isAdmissionEnabled())
            $width=15;
        else
            $width=20;

        if ($key == "dozent") {
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\"><b>&nbsp;</b></td>", $width);
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\"><b>&nbsp;</b></td>", $width);
            if ($sem->isAdmissionEnabled())
                echo"<td class=\"steel\" width=\"10%\" align=\"center\" colspan=\"2\"><b>&nbsp;</b></td>";
        }

        if ($key == "tutor") {
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\"><font size=\"-1\"><b>&nbsp;</b></font></td>", $width);
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"tutor_to_autor\" onClick=\"return invert_selection('tutor_to_autor','%s');\" %s><b>%s</b></a></font></td>", $width, $key, $tooltip, sprintf(_("%s entlassen"), get_title_for_status('tutor', 1)));
            if ($sem->isAdmissionEnabled())
                echo"<td class=\"steel\" width=\"10%\" align=\"center\"><b>&nbsp;</b></td>";
        }

        if ($key == "autor") {
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"autor_to_tutor\" onClick=\"return invert_selection('autor_to_tutor','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, sprintf(_("als %s eintragen"), get_title_for_status('tutor', 1)));
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"autor_to_user\" onClick=\"return invert_selection('autor_to_user','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("Schreibrecht entziehen"));
            if ($sem->isAdmissionEnabled())
                printf("<td class=\"steel\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Kontingent"));
        }

        if ($key == "user") {
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"user_to_autor\" onClick=\"return invert_selection('user_to_autor','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("Schreibrecht erteilen"));
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"user_to_null\" onClick=\"return invert_selection('user_to_null','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("BenutzerIn entfernen"));
            if ($sem->isAdmissionEnabled())
                print"<td class=\"steel\" width=\"10%\" align=\"center\"><b>&nbsp;</b></td>";
        }

        if ($key == "accepted") {
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"admission_insert\" onClick=\"return invert_selection('admission_insert','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip,  _("Akzeptieren"));
            printf ("<td class=\"steel\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"admission_delete\" onClick=\"return invert_selection('admission_delete','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("BenutzerIn entfernen"));
            if ($sem->isAdmissionEnabled())
                printf("<td class=\"steel\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Kontingent"));
        }
    }

    echo "</tr>";
    $c=1;
    $invisible=0;
    $i_see_everybody = $perm->have_studip_perm('tutor', $SessSemName[1]);

    while ($db->next_record()) {
        if (($db->Record['user_id'] == $user->id) && ($db->f('visible') != 'yes')) {
            $db->Record['fullname'] .= ' ('._("unsichtbar").')';
        }

    if ($c % 2) {   // switcher fuer die Klassen
        $class="steel1";
    } else {
        $class="steelgraulight";
    }

//  Elemente holen

    $Dokumente = 0;
    $UID = $db->f("user_id");
    $db2->query ("SELECT count(dokument_id) AS doll FROM dokumente WHERE seminar_id = '".$_SESSION['SessionSeminar']."' AND user_id = '$UID' GROUP by seminar_id");
    while ($db2->next_record()) {
        $Dokumente = $db2->f("doll");
    }
    $postings_user = $db->f("doll");

// Aktivitaet berechnen

    if ($showscore == TRUE) {
        if ($aktivity_index_seminar == 0){
                $aktivity_index_user = 0; // to avoid div by zero
                } else {
            $aktivity_index_user =  (($postings_user + (5 * $Dokumente)) / $aktivity_index_seminar) * 100;
        }
        if ($aktivity_index_user > 100) {
            $offset = $aktivity_index_user / 4;
            if ($offset < 0) {
                $offset = 0;
            } elseif ($offset > 200) {
                $offset = 200;
            }
            $red = dechex(200-$offset) ;
            $green = dechex(200);
            $blue = dechex(200-$offset) ;
            if ($offset > 184)  {
                $red = "0".$red;
                $blue = "0".$blue;
            }
        } else {
            $red = dechex(200);
            $green = dechex($aktivity_index_user * 2) ;
            $blue = dechex($aktivity_index_user * 2) ;
            if ($aktivity_index_user < 8)  {
                $green = "0".$green;
                $blue = "0".$blue;
            }
        }
    }

// Anzeige der eigentlichen Namenzeilen
if ($db->f('visible') == 'yes' || $i_see_everybody || $db->f('user_id') == $user->id) {
    echo "<tr>";
    if ($showscore == TRUE) {
        printf("<td bgcolor=\"#%s%s%s\">", $red, $green,$blue, $class2);
        printf("<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" %s width=\"10\"></td>", tooltip(_("Aktivität: ").round($aktivity_index_user)."%"));
    }

    if ($rechte) {
        if (is_opened($db->f("user_id"))) {
            $link = URLHelper::getLink("?cmd=lessinfos&username=".$db->f("username")."#".$db->f("username"));
            $img = "icons/16/blue/arr_1down.png";
        } else {
            $link = URLHelper::getLink("?cmd=moreinfos&username=".$db->f("username")."#".$db->f("username"));
            $img = "icons/16/blue/arr_1right.png";
        }
    }
    if ($i_see_everybody) {
        $anker = "<a name=\"".$db->f("username")."\">";
    } else {
        $anker = '';
    }
    printf ("<td class=\"%s\" nowrap>%s<font size=\"-1\">&nbsp;%s.</td>", $class, $anker, $c);
    printf ("<td colspan=\"2\" class=\"%s\">", $class);
    if ($rechte) {
        printf ("<a href=\"%s\"><img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/%s\"", $link, $img);
        echo tooltip(sprintf(_("Weitere Informationen über %s"), $db->f("username")));
        echo ">&nbsp;</a>";
    }
    ?>
        <span style="position: relative">
            <a href="<?= URLHelper::getLink('about.php?username='.$db->f("username")) ?>">
                <? if (!$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) :
                    $last_visitdate = time()+10;
                endif ?>
                <? $db->f('mkdate') >= $last_visitdate
                    ? $options = array('title' => _('DieseR NutzerIn ist nach Ihrem '.
                        'letzten Besuch dieser Veranstaltung beigetreten'))
                    :  $options = array() ?>
                <? $options['style'] = 'margin-right: 5px' ?>
                <?= Avatar::getAvatar($db->f("user_id"))->getImageTag(Avatar::SMALL, $options) ?>
                <?= $db->f('mkdate') >= $last_visitdate ? Assets::img('red_star.png', array(
                    'style' => 'position: absolute; top: -4px; left: 13px'
                )) : '' ?>
                <?= htmlReady($db->f("fullname")) ?>
            </a>
        </span>
        </td>
    <?
    if ($key != "dozent" && $rechte) {
        if ($db->f("mkdate")) {
            echo "<td class=\"$class\" align=\"center\"><font size=\"-1\">".date("d.m.y,",$db->f("mkdate"))."&nbsp;".date("H:i:s",$db->f("mkdate"))."</font></td>";
        } else {
            echo "<td class=\"$class\" align=\"center\"><font size=\"-1\">"._("unbekannt")."</font></td>";
        }
    } else if ($key == "dozent" && $rechte) {
        echo "<td class=\"$class\" align=\"center\">&nbsp;</td>";
    }
    echo "<td class=\"$class\" align=\"center\"><font size=\"-1\">".$db->f("doll")."</font></td>";
    echo "<td class=\"$class\" align=\"center\"><font size=\"-1\">".$Dokumente."</font></td>";

    echo "<td class=\"$class\" align=\"center\">";

    $username=$db->f("username");
    if ($db->f('visible') == 'yes' || $i_see_everybody) {
        if (get_config('CHAT_ENABLE')){
            echo chat_get_online_icon($db->f("user_id"),$db->f("username"),$SessSemName[1]) . " ";
        }

        printf ("<a href=\"%s\"><img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\" %s ></a>", URLHelper::getLink("sms_send.php", array("sms_source_page" => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], "subject" => $subject, "rec_uname" => $db->f("username"))), tooltip(_("Nachricht an Benutzer verschicken")));

    if (isset($multiaction[$key]['send'][0]) && $rechte)
    printf("<input class=\"text-top\" type=\"checkbox\" name=\"send_msg[%s]\" value=\"1\"></td>", $username);
    }

    echo "</td>";

    // Befoerderungen und Degradierungen
    if ($rechte && !LockRules::Check($id, 'participants')) {

        // Tutor entlassen
        if ($key == "tutor" AND $SemUserStatus!="tutor") {
            echo "<td class=\"$class\">&nbsp</td>";
            echo "<td class=\"$class\" align=\"center\">";
            echo "<a href=\"".URLHelper::getLink("?cmd=pain&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a>";
            echo "<input type=\"checkbox\" name=\"tutor_to_autor[$username]\" value=\"1\">";
            echo "</td>";
        }

        elseif ($key == "autor") {
            // zum Tutor befördern
            if ($SemUserStatus!="tutor") {
                if ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["only_inst_user"])
                    $db2->query ("SELECT DISTINCT user_id FROM seminar_inst LEFT JOIN user_inst USING(Institut_id) WHERE user_id = '$UID' AND seminar_id ='$SessSemName[1]' AND inst_perms!='user' AND inst_perms!='autor'");
                else
                    $db2->query ("SELECT user_id FROM auth_user_md5  WHERE perms IN ('tutor', 'dozent') AND user_id = '$UID' ");
                if ($db2->next_record()) {
                    ++$tutor_count;
                    echo "<td class=\"$class\" align=\"center\">";
                    echo "<a href=\"".URLHelper::getLink("?cmd=pleasure&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\"></a>";
                    echo "<input type=\"checkbox\" name=\"autor_to_tutor[$username]\" value=\"1\">";
                    echo "</td>";
                } else echo "<td class=\"$class\" >&nbsp;</td>";
            } else echo "<td class=\"$class\">&nbsp;</td>";
            // Schreibrecht entziehen
            echo "<td class=\"$class\" align=\"center\">";
            echo "<a href=\"".URLHelper::getLink("?cmd=lesen&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a>";
            echo "<input type=\"checkbox\" name=\"autor_to_user[$username]\" value=\"1\">";
            echo "</td>";
        }

        // Schreibrecht erteilen
        elseif ($key == "user") {
            $db2->query ("SELECT perms, user_id FROM auth_user_md5 WHERE user_id = '$UID' AND perms != 'user'");
            if ($db2->next_record()) { // Leute, die sich nicht zurueckgemeldet haben duerfen auch nicht schreiben!
                echo "<td class=\"$class\" align=\"center\">";
                echo "<a href=\"".URLHelper::getLink("?cmd=schreiben&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\"></a>";
                echo "<input type=\"checkbox\" name=\"user_to_autor[$username]\" value=\"1\">";
                echo "</td>";
            } else echo "<td class=\"$class\">&nbsp;</td>";
            // aus dem Seminar werfen
            echo "<td class=\"$class\" align=\"center\">";
            echo "<a href=\"".URLHelper::getLink("?cmd=raus&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a>";
            echo "<input type=\"checkbox\" name=\"user_to_null[$username]\" value=\"1\">";
            echo "</td>";
        }

        elseif ($key == "accepted") { // temporarily accepted students
            // forward to autor
            echo "<td width=\"15%\" align=\"center\" class=\"$class\">";
            echo "<a href=\"".URLHelper::getLink("?cmd=admission_rein&username=$username&accepted=1&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\"></a><input type=\"checkbox\" name=\"admission_insert[$username]\" value=\"1\">";
            echo "</td>";
            // kick
            echo "<td class=\"$class\" align=\"center\">";
            echo "<a href=\"".URLHelper::getLink("?cmd=admission_raus&username=$username&accepted=1&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a><input type=\"checkbox\" name=\"admission_delete[$username]\" value=\"1\">";
            echo "</td>";
        }

        else { // hier sind wir bei den Dozenten
            echo "<td colspan=\"2\" class=\"$class\" >&nbsp;</td>";
        }

        if ($sem->isAdmissionEnabled()) {
            if ($key == "autor" || $key == "user" || $key == "accepted")
                printf ("<td width=\"80%%\" align=\"center\" class=\"%s\"><font size=-1>%s%s</font></td>", $class, ($db->f("studiengang_id") == "all") ? _("alle Studieng&auml;nge") : $db->f("name"), (!$db->f("name") && !$db->f("studiengang_id") == "all") ?  "&nbsp; ": "");
            else
                printf ("<td width=\"10%%\" align=\"center\" class=\"%s\">&nbsp;</td>", $class);
        }
    } // Ende der Dozenten/Tutorenspalten
    print("</tr>\n");
            // info-field for users
        if ((is_opened($db->f("user_id")) || in_array($key, $open_areas)) && $rechte) { // show further userinfosi
            $info_is_open = true;
            $user_data = array();

            //get data for user, if dozent or higher
            if ($perm->have_perm("dozent")) {
                /* remark: if you change something in the data-acquisition engine
                * please do not forget to change it also in "export/export_studipdata_func.inc.php"
                * in the function export_teilis(...)
                */

                $additional_data = get_additional_data($db->f('user_id'), $id);

                foreach ($additional_data as $val)
                {
                    if ($val['content'] && $val['display'])
                    {
                        if (is_array($val['content']))
                        {
                            $zw = implode(', ', $val['content']);

                            $user_data [] = array('name' => $val['name'], 'content' => $zw);
                        } else
                        {
                            if ($val['name'] == 'user_picture')
                            {
                                $show_user_picture = true;
                            } else
                            {
                                $user_data [] = $val;
                            }
                        }
                    }
                }
            }

        ?>
            <tr class="<?= $class ?>">

                <? if ($showscore) : ?>
                    <td colspan="2">&nbsp;</td>
                <? else : ?>
                    <td>&nbsp;</td>
                <? endif ?>

                <td valign="top">
                    <font size="-1">
                        <dl style="margin-left:2em;">
                            <? foreach ($user_data as $val) : ?>
                                <? if ($val["content"] == "") continue; ?>
                                <dt><?= $val["name"] ?> :</dt>
                                <dd><?= $val["content"] ?></dd>
                                <!--
                                <font size="-1">
                                    <?= $val["name"] ?>: <?= $val["content"] ?>
                                </font>
                                <br>
                                -->
                            <? endforeach ?>
                        </dl>
                    </font>
                </td>

                <? if ($show_user_picture) : ?>
                    <td>
                        <?= Avatar::getAvatar($db->f('user_id'))->getImageTag(Avatar::MEDIUM) ?>
                    </td>
                <? endif ?>

                <td colspan="<?= $colspan - 2 - ($show_user_picture ? 1 : 0) - ($showscore ? 1 : 0)?>">
                    <form action="<?= URLHelper::getLink('#'.$db->f("username")) ?>" method="POST">
                        <?= CSRFProtection::tokenTag() ?>
                        <font size="-1"><?=_("Bemerkungen:")?></font><br>
                        <textarea name="userinfo" rows="3" cols="50"><?= $db->f("comment") ?></textarea>
                        <br>
                        <font size="-1"><?= _("&Auml;nderungen") ?></font>
                        <?= Button::create(_('Übernehmen')) ?>
                        <input type="hidden" name="user_id" value="<?=$db->f("user_id")?>">
                        <input type="hidden" name="cmd" value="change_userinfo">
                        <input type="hidden" name="username" value="<?= $db->f("username") ?>">
                        <input type="hidden" name="studipticket" value="<?= $studipticket ?>">
                    </form>
                </td>
            </tr>
        <?
        }
    $c++;
} // eine Zeile zuende

else {
    $invisible++;
}
}

if($key != 'dozent' && $rechte && !$info_is_open) {
    echo '<tr><td class="blank" colspan="'.($showscore ? 7 : 6).'">&nbsp;</td>';

    if (isset($multiaction[$key]['send'][0]))
        echo '<td class="blank" align="center">' . Button::create(_('Neue Nachricht'),'do_' . $multiaction[$key]['send'][0],array('title'=> $multiaction[$key]['send'][1])) . '</td>';
   else
        echo '<td class="blank">&nbsp;</td>';


    if (!LockRules::Check($id, 'participants')) {
        if (isset($multiaction[$key]['insert'][0]) && !($key == 'autor' && !$tutor_count)) {
           echo '<td class="blank" align="center">' . Button::create(_('Eintragen'),  'do_' . $multiaction[$key]['insert'][0],array('title'=> $multiaction[$key]['insert'][1])) . '</td>';
        } else {
            echo '<td class="blank">&nbsp;</td>';
        }

        echo '<td class="blank" align="center">' . Button::create(_('Entfernen'),'do_' . $multiaction[$key]['delete'][0],array('title'=>$multiaction[$key]['delete'][1])) . '</td>';

        if ($sem->isAdmissionEnabled()) {
            echo '<td class="blank">&nbsp;</td>';
        }
    }

    echo "</tr></form>";
}
echo "<tr><td class=\"blank\" colspan=\"$colspan\">&nbsp;</td></tr>";
} // eine Gruppe zuende
if ($invisible >= 1) {
    echo "<tr><td colspan=\"$colspan\">".sprintf(_("+%d unsichtbare %s"), $invisible,$val)."</td></tr>";
    $invisible = 0;
}
}

echo "</table>\n";

echo "</td></tr>\n";  // Auflistung zuende

// Warteliste
$awaiting = false;
if ($rechte) {
    $db->query ("SELECT admission_seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname , username, studiengaenge.name, position, admission_seminar_user.studiengang_id, status FROM admission_seminar_user LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) LEFT JOIN studiengaenge ON (admission_seminar_user.studiengang_id=studiengaenge.studiengang_id)  WHERE admission_seminar_user.seminar_id = '".$_SESSION['SessionSeminar']."' AND admission_seminar_user.status != 'accepted' ORDER BY position, name");
    if ($db->num_rows()) { //Only if Users were found...
        $awaiting = true;
        ?>
        <tr>
        <td class="blank" width="100%" colspan="2">
        <a href="<?= URLHelper::getLink('sms_send.php', array( 'sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'course_id' => $SessSemName[1],  'emailrequest' => 1, 'subject' => $subject, 'filter' => 'waiting')) ?>">
        <?= Assets::img('icons/16/blue/move_right/mail.png', array('class' => 'text-top'))?>
        <?=_("Systemnachricht mit Emailweiterleitung an alle Wartenden verschicken")?>
        </a>
        </td>
        </tr>
        <?
        // die eigentliche Teil-Tabelle
        echo '<form name="waitlist" action="'.URLHelper::getLink('?studipticket='.$studipticket).'" method="post">';
        echo CSRFProtection::tokenTag();
        echo "<tr><td class=\"blank\" colspan=\"2\">";
        echo "<table width=\"99%\" border=\"0\"  cellpadding=\"2\" cellspacing=\"0\" align=\"center\">";
        echo "<tr height=\"28\">";
        printf ("<td class=\"steel\" width=\"%s%%\" align=\"left\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"1\" height=\"20\"><font size=\"-1\"><b>%s</b></font></td>", ($sem->admission_type == 1 && $sem->admission_selection_take_place !=1) ? "40" : "30",  ($sem->admission_type == 2 || $sem->admission_selection_take_place==1) ? _("Warteliste") : _("Anmeldeliste"));
        if ($sem->admission_type == 2 || $sem->admission_selection_take_place==1)
            printf("<td class=\"steel\" width=\"10%%\" align=\"center\"><font size=\"-1\"><b>%s</b></font></td>", _("Position"));
        printf("<td class=\"steel\" width=\"10%%\" align=\"center\">&nbsp; </td>");
        printf("<td class=\"steel\" width=\"10%%\" align=\"center\"><font size=\"-1\"><b>%s</b></font></td>", _("Nachricht"));
        if(!LockRules::Check($id, 'participants')){
            printf("<td class=\"steel\" width=\"15%%\" align=\"center\"><font size=\"-1\"><a name=\"blubb\" onClick=\"return invert_selection('admission_insert','waitlist');\" %s><b>%s</b></a></font></td>", tooltip(_("Klicken, um Auswahl umzukehren"),false), _("eintragen"));
            printf("<td class=\"steel\" width=\"15%%\" align=\"center\"><font size=\"-1\"><a name=\"bla\" onClick=\"return invert_selection('admission_delete','waitlist');\" %s><b>%s</b></a></font></td>", tooltip(_("Klicken, um Auswahl umzukehren"),false), _("entfernen"));
        }
        printf("<td class=\"steel\" width=\"10%%\" align=\"center\"><font size=\"-1\"><b>%s</b></font></td></tr>\n", _("Kontingent"));


        while ($db->next_record()) {
            if ($db->f("status") == "claiming") { // wir sind in einer Anmeldeliste und brauchen Prozentangaben
                $admission_chance = $sem->getAdmissionChance($db->f("studiengang_id"));
            }

            $cssSw->switchClass();
            printf ("<tr><td width=\"%s\" class=\"%s\" align=\"left\"><font size=\"-1\"><a name=\"%s\" href=\"%s\">%s</a></font></td>",  ($sem->admission_type == 1 && $sem->admission_selection_take_place !=1) ? "40%" : "30%", $cssSw->getClass(), $db->f("username"), URLHelper::getLink('about.php?username='.$db->f("username")), htmlReady($db->f("fullname")));
            if ($sem->admission_type == 2 || $sem->admission_selection_take_place==1)
                printf ("<td width=\"10%%\" align=\"center\" class=\"%s\"><font size=\"-1\">%s</font></td>", $cssSw->getClass(), $db->f("position"));
            printf ("<td width=\"10%%\" align=\"center\" class=\"%s\">&nbsp; </td>", $cssSw->getClass());

            printf ("<td width=\"10%%\" align=\"center\" class=\"%s\"><a href=\"%s\"><img class=\"text-bottom\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\" %s></a></td>", $cssSw->getClass(), URLHelper::getLink('sms_send.php', array('sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'rec_uname' => $db->f("username"))), tooltip(_("Nachricht an Benutzer verschicken")));
            if(!LockRules::Check($id, 'participants')){
                printf ("<td width=\"15%%\" align=\"center\" class=\"%s\"><input type=\"image\" name=\"admission_rein[%s]\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\">
                        <input type=\"checkbox\" name=\"admission_insert[%s]\" value=\"1\"></td>", $cssSw->getClass(), $db->f("username"), $db->f("username"));
                printf ("<td width=\"15%%\" align=\"center\" class=\"%s\"><a href=\"%s\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a>
                        <input type=\"checkbox\" name=\"admission_delete[%s]\" value=\"1\"></td>", $cssSw->getClass(), URLHelper::getLink("?cmd=admission_raus&username=".$db->f("username")."&studipticket=$studipticket"), $db->f("username"));
            }
            printf ("<td width=\"10%%\" align=\"center\" class=\"%s\"><font size=\"-1\">%s</font></td></tr>\n", $cssSw->getClass(), ($db->f("studiengang_id") == "all") ? _("alle Studieng&auml;nge") : $db->f("name"));
        }
        if(!LockRules::Check($id, 'participants')){
            echo '<tr><td class="blank" colspan="3" align="right"><font size="-1">';
            echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/black/info.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzufügen auf die Kontingentplätze angerechnet werden."),1,1).' >';
            echo '<label for="kontingent">'._("Kontingent berücksichtigen:");
            echo '<input id="kontingent" type="checkbox" checked name="consider_contingent" value="1" style="vertical-align:middle"></label>';
            echo '&nbsp;</font></td>';
            echo '<td class="blank" align="center">' . Button::create(_('Eintragen'),'do_admission_insert',array('title'=>_("Ausgewählte Nutzer aus der Warteliste in die Veranstaltung eintragen"))) . '</td>';
            echo '<td class="blank" align="center">' . Button::create(_('Entfernen'),'do_admission_delete',array('title'=>_("Ausgewählte Nutzer aus der Warteliste entfernen"))) . '</td>';
            echo '<td class="blank">&nbsp;</td></tr>';
        }
        echo '</table>';
        echo '</td></tr></form>';
    }
}

// Der Dozent braucht mehr Unterstuetzung, also Tutor aus der(n) Einrichtung(en) berufen...
//Note the option "only_inst_user" from the config.inc. If it is NOT setted, this Option is disabled (the functionality will do in this case do seachform below)
if (!LockRules::Check($id, 'participants') && $rechte
        && $SemUserStatus!="tutor"
        && $SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["only_inst_user"]
        && $cmd != 'csv') {
    $query = "SELECT a.user_id, username, " . $_fullname_sql['full_rev'] ." AS fullname, inst_perms, perms FROM seminar_inst d LEFT JOIN user_inst a USING(Institut_id) ".
    "LEFT JOIN auth_user_md5  b USING(user_id) LEFT JOIN user_info USING(user_id) ".
    "LEFT JOIN seminar_user c ON (c.user_id=a.user_id AND c.seminar_id='$SessSemName[1]')  ".
    "WHERE d.seminar_id = '$SessSemName[1]' AND a.inst_perms IN ('tutor','dozent') AND ISNULL(c.seminar_id) GROUP BY a.user_id ORDER BY Nachname";

    $db->query($query); // ergibt alle berufbaren Personen
    ?>

    <tr>
        <td class=blank colspan=2>&nbsp;
        </td>
    </tr>
    <tr><td class=blank colspan=2>

    <table width="99%" border="0" cellpadding="2" cellspacing="0" border="0" align="center">
    <form action="<?= URLHelper::getLink() ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?=$studipticket?>">
    <tr>
        <td class="steel1" width="40%" align="left">&nbsp; <font size="-1"><b><?=_("MitarbeiterInnen der Einrichtung(en)")?></b></font></td>
        <td class="steel1" width="40%" align="left"><select name="u_id" size="1">
        <?
        printf("<option value=\"0\">- -  %s - -\n", _("bitte ausw&auml;hlen"));
        while ($db->next_record())
            printf("<option value=\"%s\">%s - %s\n", $db->f("user_id"), htmlReady(my_substr($db->f("fullname")." (".$db->f("username"),0,35)).")", $db->f("inst_perms"));
        ?>
        </select></td>
        <td class="steel1" width="20%" align="center"><font size=-1><?= sprintf(_("als %s"), get_title_for_status('tutor', 1)) ?></font><br>
                 <?= Button::create(_('Eintragen'),'add_tutor' ,array('value'=> sprintf(_("als %s berufen"), get_title_for_status('tutor', 1)) )) ?></td>

          </tr></form></table>
<?

} // Ende der Berufung

//insert autors via free search form
if (!LockRules::Check($id, 'participants') && $rechte) {
    if ($cmd != 'csv') {
    if ($search_exp) {
        $search_exp = trim($search_exp);
        $query = "SELECT a.user_id, username, " . $_fullname_sql['full_rev'] ." AS fullname, perms FROM auth_user_md5 a ".
            "LEFT JOIN user_info USING(user_id) LEFT JOIN seminar_user b ON (b.user_id=a.user_id AND b.seminar_id='$SessSemName[1]')  ".
            "WHERE perms IN ('autor','tutor','dozent') AND ISNULL(b.seminar_id) AND ".
            "(username LIKE '%$search_exp%' OR Vorname LIKE '%$search_exp%' OR Nachname LIKE '%$search_exp%') ".
            "ORDER BY Nachname";
        $db->query($query); // results all users which are not in the seminar
        ?>

    <tr>
        <td class="blank" colspan="2">&nbsp;
        </td>
    </tr>
    <tr><td class="blank" colspan="2">
    <a name="freesearch"></a>
    <form action="<?= URLHelper::getLink('?cmd=add_user') ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?=$studipticket?>">
    <table width="99%" border="0" cellpadding="2" cellspacing="0" border=0 align="center">
        <tr>
            <td class="steel1" width="40%" align="left">&nbsp; <font size="-1"><b><?=_("Gefundene Nutzer")?></b></font></td>
            <td class="steel1" width="40%" align="left"><select name="username" size="1">
            <?
            printf("<option value=\"0\">- -  %s - -\n", _("bitte ausw&auml;hlen"));
            while ($db->next_record())
                printf("<option value=\"%s\">%s - %s\n", $db->f("username"), htmlReady(my_substr($db->f("fullname")." (".$db->f("username"),0,35)).")", $db->f("perms"));
            ?>
            </select>
            <?if($sem->isAdmissionEnabled()){
                echo '<br><br><img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/black/info.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzufügen auf die Kontingentplätze angerechnet werden."),1,1).' >';
                echo '<font size="-1"><label for="kontingent2">'._("Kontingent berücksichtigen:");
                echo '&nbsp;<select name="consider_contingent" id="kontingent2">';
                echo '<option value="">'._("Kein Kontingent").'</option>';
                if(is_array($sem->admission_studiengang)){
                    foreach($sem->admission_studiengang as $studiengang => $data){
                        echo '<option value="'.$studiengang.'" '.($_REQUEST['consider_contingent'] == $studiengang ? 'selected' : '').'>'.htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')').'</option>';
                    }
                }
                echo '</select></label></font>';
            }
            ?>
            </td>
            <td class="steel1" width="20%" align="center"><font size=-1>
            <?
            if (!$SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["only_inst_user"] && $perm->have_studip_perm("dozent",$SessSemName[1])){
                printf(_("als %s / %s"), get_title_for_status('tutor', 1), get_title_for_status('autor', 1));
            } else {
                printf(_("als %s"), get_title_for_status('autor', 1));
            }
            ?></font><br>
               <?= Button::create(_('Eintragen'),'add_user',array('value'=> sprintf(_("als %s berufen"), get_title_for_status('autor', 1)))) ?>&nbsp;<?= LinkButton::create(_('Neuesuche'),URLHelper::getURL()) ?></td>

        </tr>
    </table>
    </form>
        <?
    } else { //create a searchform
        ?>
    <tr>
        <td class=blank colspan=2>&nbsp;
        </td>
    </tr>
    <tr><td class=blank colspan=2>
    <form action="<?= URLHelper::getLink()."#suchergebnisse" ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <table width="99%" border="0" cellpadding="2" cellspacing="0" border=0 align="center">
    <tr>
        <td class="steel1" width="40%" align="left">&nbsp; <font size=-1><b><?=_("Nutzer in die Veranstaltung eintragen")?></b></font>
            <a name="suchergebnisse"><br><font size=-1>&nbsp; <? printf(_("Bitte geben Sie den Vornamen, Nachnamen %s oder Benutzernamen zur Suche ein"), "<br>&nbsp;")?> </font></a></td>
        <td class="steel1" width="20%" align="left">
        <input type="hidden" name="studipticket" value="<?=$studipticket?>">
        <?php
        $NutzerSuchen = new SQLSearch("SELECT auth_user_md5.username, CONCAT(auth_user_md5.Nachname, \", \", auth_user_md5.Vorname, \" (\", auth_user_md5.username, \") - \" , auth_user_md5.perms) " .
            "FROM auth_user_md5 " .
                "LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
            "WHERE (CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                "OR auth_user_md5.username LIKE :input) " .
                "AND auth_user_md5.perms IN ('autor', 'tutor', 'dozent') " .
                "AND auth_user_md5.user_id NOT IN (SELECT user_id FROM seminar_user WHERE Seminar_id = :seminar_id ) " .
            "ORDER BY Vorname, Nachname", _("Teilnehmer suchen"), "username");
        print QuickSearch::get("username", $NutzerSuchen)
                ->withButton()
                ->setInputStyle("width: 240px")
                ->render();
        ?>
        <input type="hidden" name="seminar_id" value="<?= $SessSemName[1] ?>">
        </td>
        <td class="steel1" width="20%" align="center">
            <? if($sem->isAdmissionEnabled()) : ?>
            <select name="consider_contingent">
                <option value=""><?= _("Kein Kontingent") ?></option>
                <? if(is_array($sem->admission_studiengang))
                    foreach($sem->admission_studiengang as $studiengang => $data) : ?>
                    <option value="<?= $studiengang ?>" <?= $_REQUEST['consider_contingent'] == $studiengang ? 'selected' : '' ?>>
                        <?= htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')') ?>
                    </option>
                <? endforeach ?>
            </select>
            <? endif ?>
        </td>
        <td class="steel1" width="20%" align="center">
             <?= Button::create(_('Eintragen'),'add_user',array('value'=>_("eintragen"))) ?>  </td>

       
    </tr></table></form></tr>
    <?
}
}
    // import new members (as "autor") from a CSV-list
    echo "<tr>\n<td class=\"blank\" colspan=\"2\">&nbsp;</td></tr>\n";
    echo "<tr><td class=\"blank\" colspan=\"2\">\n";
    echo "<form action=\"".URLHelper::getLink()."\" method=\"post\">\n";
    echo CSRFProtection::tokenTag();
    echo "<input type=\"hidden\" name=\"studipticket\" value=\"$studipticket\">\n";
    echo "<input type=\"hidden\" name=\"cmd\" value=\"csv\">\n";
    echo "<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\" ";
    echo "align=\"center\">\n";
    if (!sizeof($csv_mult_founds)) {
        $accessible_df = array();
        //check accessible datafields for ("user" => 1, "autor" => 2, "tutor" => 4, "dozent" => 8)
        foreach (DataFieldStructure::getDataFieldStructures('user', (1|2|4|8), true) as $df) {
            if ($df->accessAllowed($perm) && in_array($df->getId(), $TEILNEHMER_IMPORT_DATAFIELDS)) {
                $accessible_df[] = $df;
            }
        }
        echo "<tr><td width=\"40%\" class=\"steel1\">\n<div style=\"font-size: small; margin-left:6px; width:300px;\">";
        echo '<b>' . _("Teilnehmerliste übernehmen") . '</b><br>';
        echo _("In das nebenstehende Textfeld können Sie eine Liste mit Namen von NutzerInnen eingeben, die in die Veranstaltung aufgenommen werden sollen.");
        echo '<br>' . _("Wählen Sie in der Auswahlbox das gewünschte Format, in dem Sie die Namen eingeben möchten:");
        echo '<br>' . _("<b>Eingabeformat: Nachname, Vorname &crarr;</b>");
        echo '<br>' . _("Geben Sie dazu in jede Zeile den Nachnamen und (optional) den Vornamen getrennt durch ein Komma oder ein Tabulatorzeichen ein.");
        echo '<br>' . _("<b>Eingabeformat: Nutzername &crarr;</b>");
        echo '<br>' . _("Geben Sie dazu in jede Zeile den Stud.IP Nutzernamen ein.");
        echo "</div></td>\n";
        echo '<td width="40%" colspan="2" class="steel1">';
        echo '<div style="margin-top:10px;margin-bottom:10px;">' . _("Eingabeformat:");
        echo '<select style="margin-left:10px;" name="csv_import_format">';
        echo '<option value="realname">'._("Nachname, Vorname").' &crarr;</option>';
        echo '<option value="username" '.($_REQUEST['csv_import_format'] == 'username' ? 'selected': '').'>'. _("Nutzername"). '&crarr;</option>';
        foreach ($accessible_df as $df) {
            echo '<option value="' . $df->getId() . '" '.($_REQUEST['csv_import_format'] ==  $df->getId()? 'selected': '').'>'. htmlReady($df->getName()) . '&crarr;</option>';
        }
        echo '</select></div>';
        echo "<textarea name=\"csv_import\" rows=\"6\" cols=\"50\">";
        foreach ($csv_not_found as $line) {
            echo htmlReady($line) . chr(10);
        }
        echo "</textarea>";
        if ($sem->isAdmissionEnabled()) {
            echo '<br><br><img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/black/info.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzufügen auf die Kontingentplätze angerechnet werden."),1,1).' >';
            echo '<font size="-1"><label for="kontingent2">'._("Kontingent berücksichtigen:");
            echo '&nbsp;<select name="consider_contingent" id="kontingent2">';
            echo '<option value="">'._("Kein Kontingent").'</option>';
            if(is_array($sem->admission_studiengang)) {
                foreach($sem->admission_studiengang as $studiengang => $data){
                    echo '<option value="'.$studiengang.'" '.($_REQUEST['consider_contingent'] == $studiengang ? 'selected' : '').'>'.htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')').'</option>';
                }
            }
            echo '</select></label></font>';
        }
        echo "</td>\n";
        echo "<td width=\"20%\" class=\"steel1\" align=\"center\"> ";
        echo Button::create(_('Eintragen'),'submit') ;
        if (sizeof($csv_not_found)) {
           echo "<img border=\"0\" " . LinkButton::create(_('Löschen'),URLHelper::getURL());
        }
        echo "\n</td></tr>\n";
    } else {
    //  if (sizeof($csv_mult_founds)) {
            echo "<tr><td class=\"steel1\" colspan=\"2\">";
            echo "<div style=\"font-size: small; margin-left:8px; width:350px;\">";
            echo '<b>' . _("Manuelle Zuordnung") . '</b><br>';
            echo _("Folgende NutzerInnen konnten <b>nicht eindeutig</b> zugewiesen werden. Bitte wählen Sie aus der jeweiligen Trefferliste:");
            echo "</div></td></tr>\n";
            $cssSw->resetClass();
            foreach ($csv_mult_founds as $csv_key => $csv_mult_found) {
                printf("<tr%s><td%s width=\"40%%\"><div style=\"font-size:small; margin-left:8px;\">%s</div></td>",
                        $cssSw->getHover(), $cssSw->getFullClass(),
                        htmlReady(mila($csv_key, 50)));
                printf("<td%s width=\"60%%\">", $cssSw->getFullClass());
                echo "<select name=\"selected_users[]\">\n";
                echo '<option value=""> - - ' . _("bitte ausw&auml;hlen") . " - - </option>\n";

                foreach ($csv_mult_found as $csv_found) {
                    if ($csv_found['is_present']) {
                        continue;
                    }

                    echo "<option value=\"{$csv_found['username']}\">";
                    echo htmlReady(my_substr($csv_found['fullname'], 0, 50)) . " ({$csv_found['username']}) - {$csv_found['perms']}</option>\n";
                }

                echo "</select>\n</td></tr>\n";
                $cssSw->switchClass();
            }
            $cssSw->resetClass();
            $cssSw->switchClass();
            echo "<tr><td class=\"steel1\" colspan=\"2\" align=\"right\" nowrap=\"nowrap\">";
            if($sem->isAdmissionEnabled()){
                echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/grey/info-circle.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzufügen auf die Kontingentplätze angerechnet werden."),1,1).' >';
                echo '<font size="-1"><label for="kontingent2">'._("Kontingent berücksichtigen:");
                echo '&nbsp;<select name="consider_contingent" id="kontingent2">';
                echo '<option value="">'._("Kein Kontingent").'</option>';
                if(is_array($sem->admission_studiengang)){
                    foreach($sem->admission_studiengang as $studiengang => $data){
                        echo '<option value="'.$studiengang.'" '.($_REQUEST['consider_contingent'] == $studiengang ? 'selected' : '').'>'.htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')').'</option>';
                    }
                }
                echo '</select></label></font>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
            }
            echo  Button::create(_('Eintragen'));
            echo '&nbsp; &nbsp; ';
            echo  LinkButton::create(_('Abbrechen'),URLHelper::getURL());
            echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </td></tr>\n";

        if (sizeof($csv_not_found)) {
            echo "<tr><td width=\"40%\" class=\"steel1\">\n<div style=\"font-size: small; margin-left:8px; width:250px;\">";
            echo '<b>' . _("Nicht gefundene NutzerInnen") . '</b><br>';
            echo _("Im nebenstehende Textfeld sehen Sie eine Auflistung der Suchanfragen, zu denen <b>keine</b> NutzerInnen gefunden wurden.");
            echo "</div></td>\n";
            echo "<td width=\"40%\" class=\"steel1\">";
            echo "<textarea name=\"csv_import\" rows=\"6\" cols=\"40\">";
            foreach($csv_not_found as $line) echo htmlReady($line) . chr(10);
            echo "</textarea></td>\n";
            echo "<td width=\"20%\" class=\"steel1\" align=\"center\">&nbsp;";
            echo "\n</td></tr>\n";
        }
    }

    echo "</table>\n</form>";
} // end insert autor

if (get_config('EXPORT_ENABLE') AND $perm->have_studip_perm("tutor", $SessSemName[1])) {
    include_once($PATH_EXPORT . "/export_linking_func.inc.php");
    echo chr(10) . '<tr>';
    echo chr(10) . "<td class=\"blank\"><b>" . export_link($SessSemName[1], "person", _("TeilnehmerInnen") . ' '. $SessSemName[0], "rtf", "rtf-teiln", "", Assets::img('icons/16/blue/file-text.png', array('class' => 'text-top')) . ' ' . _("TeilnehmerInnen exportieren als rtf Dokument"), 'passthrough'). "</b></td>";
    echo chr(10) . "<td class=\"blank\"><b>" . export_link($SessSemName[1], "person", _("TeilnehmerInnen") . ' '. $SessSemName[0], "csv", "csv-teiln", "", Assets::img('icons/16/blue/file-xls.png', array('class' => 'text-top')) . ' ' . _("TeilnehmerInnen exportieren als csv Dokument"), 'passthrough') . "</b></td>";
    echo chr(10) . '</tr>';

    if ($awaiting) {
        echo chr(10) . '<tr>';
        echo chr(10) . "<td class=\"blank\"><b>" . export_link($SessSemName[1], "person", _("Warteliste") .' ' . $SessSemName[0], "rtf", "rtf-warteliste", "awaiting", Assets::img('icons/16/blue/file-text.png', array('class' => 'text-top')) . ' ' . _("Warteliste exportieren als rtf Dokument"), 'passthrough') . "</b></td>";
        echo chr(10) . "<td class=\"blank\"><b>" . export_link($SessSemName[1], "person", _("Warteliste") .' ' . $SessSemName[0], "csv", "csv-warteliste", "awaiting", Assets::img('icons/16/blue/file-xls.png', array('class' => 'text-top')) . ' ' . _("Warteliste exportieren csv Dokument"), 'passthrough') . "</b></td>";
        echo chr(10) . '</tr>';
    }
}
?>
        </table>
        </td>
    </tr>
</table>

<?php
    include ('lib/include/html_end.inc.php');
    page_close();
?>
