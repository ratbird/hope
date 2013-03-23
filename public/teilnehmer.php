<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter003: TEST
# Lifter005: TODO
# Lifter007: TODO
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
$username = Request::username('username');
$cmd = Request::option('cmd');
$open_areas = Request::optionArray('open_areas');

if ($cmd == "make_me_visible" && !$perm->have_studip_perm('tutor',$SessSemName[1])) {
    if (Request::option('mode') == "participant") {
        $query = "UPDATE seminar_user SET visible = 'yes' WHERE user_id = ? AND Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($auth->auth['uid'], $SessSemName[1]));
    } elseif (Request::option('mode')  == "awaiting") {
        $query = "UPDATE admission_seminar_user SET visible = 'yes' WHERE user_id = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($auth->auth['uid'], $SessSemName[1]));
    }
}

if ($cmd == "make_me_invisible" && !$perm->have_studip_perm('tutor',$SessSemName[1])) {
    if (Request::option('mode') == "participant" ) {
        $query = "UPDATE seminar_user SET visible = 'no' WHERE user_id = ? AND Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($auth->auth['uid'], $SessSemName[1]));
    } else {
        $query = "UPDATE admission_seminar_user SET visible = 'no' WHERE user_id = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($auth->auth['uid'], $SessSemName[1]));
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
$query = "SELECT VeranstaltungsNummer FROM seminare WHERE Seminar_id = ?";
$stmt = DBManager::get()->prepare($query);
$stmt->execute(array($SessSemName[1]));
$result = $stmt->fetchColumn();
$subject = ($result == '')
         ? '[' . $SessSemName['0'] . ']'
         : '[' . $result . ': ' . $SessSemName['0'] . ']';

// Send message to multiple user
if (Request::submitted('do_send_msg') && Request::intArray('send_msg') && Seminar_Session::check_ticket(Request::option('studipticket')) && !LockRules::Check($id, 'participants')){
        $post = NULL;
        $sms_data = array();
        $send_msg = array_keys(Request::intArray('send_msg'));
        page_close(NULL);

        header('Location: '.URLHelper::getURL('sms_send.php', array('sms_source_page' => 'teilnehmer.php?cid=' .$_SESSION['SessionSeminar'], 'subject' => $subject, 'tmpsavesnd' => 1, 'rec_uname' => $send_msg)));
        die;
}

    // Start  of Output
    ob_start();

$messaging=new messaging;

if ($_SESSION['sms_msg']) {
    $msg = $_SESSION['sms_msg'];
    unset($_SESSION['sms_msg']) ;
}
// Aenderungen nur in dem Seminar, in dem ich gerade bin...
    $id=$SessSemName[1];

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
        insert_or_remove($open_areas, Request::option('area'));
}

URLHelper::addLinkParam('open_areas', $open_areas);

if (!isset($open_users)) {
        $open_users = array();
}



if (($cmd == "moreinfos" || $cmd == "lessinfos") && $rechte) {
    // get user_id if somebody wants more infos about a user
    $user_id = get_userid($username);
    insert_or_remove($open_users, $user_id);
}

URLHelper::addLinkParam('open_users', $open_users);

// Aktivitaetsanzeige an_aus

if ($cmd =="showscore") {
    //erst mal sehen, ob er hier wirklich Dozent ist...
    if ($rechte) {
        $query = "UPDATE seminare SET showscore = 1 WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));

        $msg = "msg�" . _("Die Aktivit&auml;tsanzeige wurde aktiviert.") . "�";
    }
}

if ($cmd =="hidescore") {
    //erst mal sehen, ob er hier wirklich Dozent ist...
    if ($rechte) {
        $query = "UPDATE seminare SET showscore = 0 WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));

        $msg = "msg�" . _("Die Aktivit&auml;tsanzeige wurde deaktiviert.") . "�";
    }
}

if (Seminar_Session::check_ticket(Request::option('studipticket')) && !LockRules::Check($id, 'participants')){
    // edit special seminar_info of an user
    if ($cmd == "change_userinfo") {
        //first we have to check if he is really "Dozent" of this seminar
        if ($rechte) {
            $query = "UPDATE admission_seminar_user SET comment = ? WHERE seminar_id = ? AND user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::get('userinfo'), $id, Request::option('user_id')
            ));

            $query = "UPDATE seminar_user SET comment = ? WHERE Seminar_id = ? AND user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::get('userinfo'), $id, Request::option('user_id')
            ));

            $msg = "msg�" . _("Die Zusatzinformationen wurden ge&auml;ndert.") . "�";
        }
        $cmd = "moreinfos";
    }

    // Hier will jemand die Karriereleiter rauf...
    $autor_to_tutor = Request::usernameArray('autor_to_tutor');
    if ( ($cmd == "pleasure" && $username) || (Request::submitted('do_autor_to_tutor') && !empty($autor_to_tutor)) ){
        //erst mal sehen, ob er hier wirklich Dozent ist... Tutoren d&uuml;rfen andere nicht zu Tutoren befoerdern!
        if ($rechte AND $SemUserStatus != "tutor")  {
            $msgs = array();
            if ($cmd == "pleasure"){
                $pleasure = array($username);
            } else {
                $pleasure = $autor_to_tutor;
            }

            $query = "UPDATE seminar_user
                      SET status = 'tutor', position = ?, visible = 'yes'
                      WHERE Seminar_id = ? AND user_id = ? AND status = 'autor'";
            $pleasure_statement = DBManager::get()->prepare($query);

            foreach($pleasure as $username) {
                $temp_user = User::findByUsername($username);
                if ($temp_user && !in_array($temp_user->perms, words('user autor'))) {
                    $userchange = $temp_user['user_id'];
                    $fullname   = $temp_user->getFullName();
                    $next_pos   = get_next_position('tutor', $id);

                    $pleasure_statement->execute(array($next_pos, $id, $userchange));

                    if ($pleasure_statement->rowCount()) {
                        $msgs[] = $fullname;

                        // LOGGING
                        log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'tutor', 'Hochgestuft zum Tutor');
                    }
                }
            }
            $msg = "msg�" . sprintf(_("Bef&ouml;rderung von %s durchgef&uuml;hrt"), htmlReady(join(', ',$msgs))) . "�";
        }
        else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
    }

    // jemand ist der anspruchsvollen Aufgabe eines Tutors nicht gerecht geworden...
    $tutor_to_autor = Request::usernameArray('tutor_to_autor');
    if ( ($cmd == "pain" && $username) || (Request::submitted('do_tutor_to_autor') && !empty($tutor_to_autor)) ){
        //erst mal sehen, ob er hier wirklich Dozent ist... Tutoren d&uuml;rfen andere Tutoren nicht rauskicken!
        if ($rechte AND $SemUserStatus != "tutor") {
            $msgs = array();
            if ($cmd == "pain"){
                $pain = array($username);
            } else {
                $pain = $tutor_to_autor;
            }

            $query = "SELECT position FROM seminar_user WHERE user_id = ?";
            $position_statement = DBManager::get()->prepare($query);

            $query = "UPDATE seminar_user
                      SET status = 'autor', position = 0
                      WHERE Seminar_id = ? AND user_id = ? AND status = 'tutor'";
            $pain_statement = DBManager::get()->prepare($query);

            foreach($pain as $username) {
                $temp_user = User::findByUsername($username);

                $userchange = $temp_user['user_id'];
                $fullname   = $temp_user->getFullName();

                $position_statement->execute(array($userchange));
                $pos = $position_statement->fetchColumn();
                $position_statement->closeCursor();

                $pain_statement->execute(array($id, $userchange));
                if ($pain_statement->rowCount()) {
                    $msgs[] = $fullname;
                    re_sort_tutoren($id, $pos);

                    // LOGGING
                    log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'autor', 'Runtergestuft zum Autor');
                }
            }
            $msg = "msg�" . sprintf (_("%s %s wurde entlassen und auf den Status '%s' zur&uuml;ckgestuft."), get_title_for_status('tutor', count($msgs)), htmlReady(join(', ',$msgs)), get_title_for_status('autor', 1)) . "�";
        }
        else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
    }

    // jemand ist zu bloede, sein Seminar selbst zu abbonieren...
    $user_to_autor = Request::usernameArray('user_to_autor');
    if ( ($cmd == "schreiben" && $username) || (Request::submitted('do_user_to_autor') && !empty($user_to_autor)) ){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "schreiben"){
                $schreiben = array($username);
            } else {
                $schreiben = $user_to_autor;
            }

            $query = "UPDATE seminar_user
                      SET status = 'autor'
                      WHERE Seminar_id = ? AND user_id = ? AND status = 'user'";
            $schreiben_statement = DBManager::get()->prepare($query);

            foreach ($schreiben as $username) {
                $temp_user = User::findByUsername($username);

                if ($temp_user && $temp_user->perms !== 'user') {
                    $userchange = $temp_user['user_id'];
                    $fullname   = $temp_user->getFullName();

                    $schreiben_statement->execute(array($id, $userchange));
                    if ($schreiben_statement->rowCount()) {
                        $msgs[] = $fullname;

                        // LOGGING
                        log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'autor', 'Hochgestuft zum Autor');
                    }
                }
            }
            $msg = "msg�" . sprintf(_("User %s wurde als Autor in die Veranstaltung aufgenommen."), htmlReady(join(', ',$msgs))) . "�";
        }
        else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
    }

    // jemand sollte erst mal das Maul halten...
    $autor_to_user = Request::usernameArray('autor_to_user');
    if ( ($cmd == "lesen" && $username) || (Request::submitted('do_autor_to_user') && !empty($autor_to_user)) ){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "lesen"){
                $lesen = array($username);
            } else {
                $lesen = $autor_to_user;
            }

            $query = "UPDATE seminar_user
                      SET status = 'user'
                      WHERE Seminar_id = ? AND user_id = ? AND status = 'autor'";
            $lesen_statement = DBManager::get()->prepare($query);

            foreach ($lesen as $username) {
                $temp_user = User::findByUsername($username);

                $userchange = $temp_user['user_id'];
                $fullname   = $temp_user->getFullName();

                $lesen_statement->execute(array($id, $userchange));
                if ($lesen_statement->rowCount()) {
                    $msgs[] = $fullname;

                    // LOGGING
                    log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'user', 'Runtergestuft zum User, keine Schreibberechtigung mehr');
                }
            }
            $msg = "msg�" . sprintf(_("Der/die AutorIn %s wurde auf den Status 'Leser' zur&uuml;ckgestuft."), htmlReady(join(', ',$msgs))) . "�";
            $msg.= "info�" . _("Um jemanden permanent am Schreiben zu hindern, m&uuml;ssen Sie die Veranstaltung auf \"Schreiben nur mit Passwort\" setzen und ein Veranstaltungs-Passwort vergeben.") . "<br>\n"
                    . _("Dann k&ouml;nnen sich weitere BenutzerInnen nur noch mit Kenntnis des Veranstaltungs-Passworts als 'Autor' anmelden.") . "�";
        }
        else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
    }

    // und tschuess...
    $user_to_null = Request::getArray('user_to_null');
    if ( ($cmd == "raus" && $username) || (Request::submitted('do_user_to_null') && !empty($user_to_null)) ){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "raus"){
                $raus = array($username);
            } else {
                $raus = (!empty($user_to_null) ? array_keys($user_to_null) : array());
            }

            $query = "SELECT {$_fullname_sql['full']} AS fullname, user_id
                      FROM auth_user_md5
                      LEFT JOIN user_info USING (user_id)
                      WHERE username = ?";
            $data_statement = DBManager::get()->prepare($query);

            $query = "DELETE FROM seminar_user WHERE Seminar_id = ? AND user_id = ? AND status = 'user'";
            $raus_statement = DBManager::get()->prepare($query);

            foreach ($raus as $username) {
                $data_statement->execute(array($username));
                $data = $data_statement->fetch(PDO::FETCH_ASSOC);
                $data_statement->closeCursor();

                $userchange = $data['user_id'];
                $fullname   = $data['fullname'];

                $raus_statement->execute(array($id, $userchange));
                if ($raus_statement->rowCount()) {
                    setTempLanguage($userchange);
                    $message = sprintf(_("Ihr Abonnement der Veranstaltung **%s** wurde von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn aufgehoben."), $SessSemName[0], get_title_for_status('dozent', 1));
                    restoreLanguage();
                    $messaging->insert_message(mysql_escape_string($message), $username, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Abonnement aufgehoben"), TRUE);
                    // raus aus allen Statusgruppen
                    RemovePersonStatusgruppeComplete ($username, $id);

                    $msgs[] = $fullname;

                    // LOGGING
                    log_event('SEM_USER_DEL', $id, $userchange, 'Wurde aus der Veranstaltung rausgeworfen');
                }
            }
            //Pruefen, ob es Nachruecker gibt
            update_admission($id);

            $msg = "msg�" . sprintf(_("LeserIn %s wurde aus der Veranstaltung entfernt."), htmlReady(join(', ',$msgs))) . "�";
            $msg.= "info�" . _("Um jemanden permanent am Lesen zu hindern, m&uuml;ssen Sie die Veranstaltung auf \"Lesen nur mit Passwort\" setzen und ein Veranstaltungs-Passwort vergeben.") . "<br>\n"
                    . _("Dann k&ouml;nnen sich weitere BenutzerInnen nur noch mit Kenntnis des Veranstaltungs-Passworts anmelden.") . "�";
        }
        else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
    }
    $admission_delete = Request::getArray('admission_delete');
    //aus der Anmelde- oder Warteliste entfernen
    if ( ($cmd == "admission_raus" && $username)  || (Request::submitted('do_admission_delete') && !empty($admission_delete) ) ) {
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "admission_raus"){
                $adm_delete[] = $username;
            } else {
                $adm_delete = (!empty($admission_delete) ? array_keys($admission_delete) : array());
            }

            $query = "SELECT {$_fullname_sql['full']} AS fullname, user_id
                      FROM auth_user_md5
                      LEFT JOIN user_info USING (user_id)
                      WHERE username = ?";
            $data_statement = DBManager::get()->prepare($query);

            $query = "DELETE FROM admission_seminar_user WHERE seminar_id = ? AND user_id = ?";
            $admission_raus_statement = DBManager::get()->prepare($query);

            foreach ($adm_delete as $username) {
                $data_statement->execute(array($username));
                $data = $data_statement->execute(array($username));
                $data_statement->closeCursor();

                $userchange = $data['user_id'];
                $fullname   = $data['fullname'];

                $admission_raus_statement->execute(array($id, $userchange));
                if ($admission_raus_statement->rowCount()) {
                    setTempLanguage($userchange);
                    if (!Request::int('accepted')) {
                        $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn von der Warteliste der Veranstaltung **%s** gestrichen und sind damit __nicht__ zugelassen worden."), get_title_for_status('dozent', 1), $SessSemName[0]);
                    } else {
                        $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiterIn (%s) oder AdministratorIn aus der Veranstaltung **%s** gestrichen und sind damit __nicht__ zugelassen worden."), get_title_for_status('dozent', 1), $SessSemName[0]);
                    }
                    restoreLanguage();

                    $messaging->insert_message(mysql_escape_string($message), $username, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("nicht zugelassen in Veranstaltung"), TRUE);

                    $msgs[] = $fullname;

                    // LOGGING
                    log_event('SEM_CHANGED_RIGHTS', $id, $userchange, 'Wurde aus der Warteliste der Veranstaltung rausgeworfen');
                }
            }
            //Warteliste neu sortieren
            renumber_admission($id);
            if (Request::int('accepted')) update_admission($id);
            $msg = "msg�". sprintf(_("LeserIn %s wurde aus der Anmelde bzw. Warteliste entfernt."), htmlReady(join(', ', $msgs))) . '�';
        } else {
            $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
        }
    }
    $admission_rein = Request::getArray('admission_rein');
    if(!empty($admission_rein)){
        $cmd = 'admission_rein';
        $username = key($admission_rein);
    }
    $admission_insert == Request::getArray('admission_insert');
    //aus der Anmelde- oder Warteliste in die Veranstaltung hochstufen / aus der freien Suche als Tutoren oder Autoren eintragen
    if ((Request::submitted('do_admission_insert') && !empty($admission_insert)) || (($cmd ==  "admission_rein" || $cmd == "add_user") && $username)){
        //erst mal sehen, ob er hier wirklich Dozent ist...
        if ($rechte) {
            $msgs = array();
            if ($cmd == "admission_rein" || $cmd == "add_user"){
                $user_add[] = $username;
            } else {
                $user_add = (!empty($admission_insert) ? array_keys($admission_insert) : array());
            }

            $query = "SELECT {$_fullname_sql['full']} AS fullname, user_id, perms
                      FROM auth_user_md5
                      LEFT JOIN user_info USING (user_id)
                      WHERE username = ?";
            $data_statement = DBManager::get()->prepare($query);

            // query - VA und Person teilen sich die selbe Einrichtung und Person ist weder autor noch tutor in der Einrichtung
            $query = "SELECT DISTINCT user_id
                      FROM seminar_inst
                      LEFT JOIN user_inst USING (Institut_id)
                      WHERE user_id = ? AND seminar_id = ? AND inst_perms NOT IN ('user', 'autor')";
            $check_statement = DBManager::get()->prepare($query);

            foreach ($user_add as $username) {
                $data_statement->execute(array($username));
                $data = $data_statement->fetch(PDO::FETCH_ASSOC);
                $data_statement->closeCursor();

                $userchange = $data['user_id'];
                $fullname   = $data['fullname'];
                $perms      = $data['perms'];

                if ($cmd == "add_user" && $SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]
                    && $perm->have_studip_perm('dozent', $id)
                    && ($perms == 'tutor' || $perms == 'dozent')) {

                    if (!$SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["only_inst_user"]) {
                        $status = 'tutor';
                    } else {
                        $check_statement->execute(array($userchange, $SessSemName[1]));
                        $status = $check_statement->fetchColumn() ? 'tutor' : 'autor';
                        $check_statement->closeCursor();
                    }
                } else {
                    $status = 'autor';
                }

                $admission_user = insert_seminar_user($id, $userchange, $status, (Request::int('accepted') || Request::option('consider_contingent') ? TRUE : FALSE), Request::option('consider_contingent'));
                //Only if user was on the waiting list
                if($admission_user){
                    setTempLanguage($userchange);
                    if ($cmd == "add_user") {
                        $message = sprintf(_("Sie wurden vom einem/einer %s oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                    } else {
                        if (!Request::int('accepted')) {
                            $message = sprintf(_("Sie wurden vom einem/einer %s oder AdministratorIn aus der Warteliste in die Veranstaltung **%s** aufgenommen und sind damit zugelassen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                        } else {
                            $message = sprintf(_("Sie wurden von einem/einer %s oder AdministratorIn vom Status **vorl�ufig akzeptiert** zum/r TeilnehmerIn der Veranstaltung **%s** hochgestuft und sind damit zugelassen."), get_title_for_status('dozent', 1), $SessSemName[0]);
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
                    $msg = "msg�" . sprintf(_("NutzerIn %s wurde in die Veranstaltung mit dem Status <b>%s</b> eingetragen."), htmlReady($fullname), $status) . "�";
                } else {
                    if (!Request::int('accepted')) {
                        $msg = "msg�" . sprintf(_("NutzerIn %s wurde aus der Anmelde bzw. Warteliste mit dem Status <b>%s</b> in die Veranstaltung eingetragen."), htmlReady(join(', ', $msgs)), $status) . "�";
                    } else {
                        $msg = "msg�" . sprintf(_("NutzerIn %s wurde mit dem Status <b>%s</b> endg�ltig akzeptiert und damit in die Veranstaltung aufgenommen."), htmlReady(join(', ', $msgs)), $status) . "�";
                    }
                }
            } else if(Request::option('consider_contingent')){
                $msg = "error�" . _("Es stehen keine weiteren Pl�tze mehr im Teilnehmerkontingent zur Verf�gung.") . "�";
            }
        } else {
            $msg = "error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
        }
    }

    // import users from a csv-list
    if ($cmd == 'csv' && $rechte) {
        $csv_mult_founds = array();
        $csv_count_insert = 0;
        $csv_count_multiple = 0;
        $df_id = null;

        if (Request::get('csv_import_format') && !in_array(Request::get('csv_import_format'), words('realname username'))) {
            //check accessible datafields for ("user" => 1, "autor" => 2, "tutor" => 4, "dozent" => 8)
            foreach(DataFieldStructure::getDataFieldStructures('user', (1|2|4|8), true) as $df) {
                if ($df->accessAllowed($perm) && in_array($df->getId(), $TEILNEHMER_IMPORT_DATAFIELDS)
                    && $df->getId() == Request::quoted('csv_import_format')) {
                    $df_id = $df->getId();
                    break;
                }
            }
        }
        if (Request::get('csv_import')) {
            $csv_lines = preg_split('/(\n\r|\r\n|\n|\r)/', trim(Request::get('csv_import')));
            foreach ($csv_lines as $csv_line) {
                $csv_name = preg_split('/[,\t]/', substr($csv_line, 0, 100),-1,PREG_SPLIT_NO_EMPTY);
                $csv_nachname = trim($csv_name[0]);
                $csv_vorname = trim($csv_name[1]);
                if ($csv_nachname){
                    $parameters = array();
                    if(Request::quoted('csv_import_format') == 'realname'){
                        $query = "SELECT a.user_id, username, {$_fullname_sql['full_rev']} AS fullname,
                                         perms, b.Seminar_id AS is_present
                                  FROM auth_user_md5 AS a
                                  LEFT JOIN user_info USING (user_id)
                                  LEFT JOIN seminar_user AS b ON (b.user_id = a.user_id AND b.Seminar_id = :seminar_id)
                                  WHERE perms IN ('autor', 'tutor', 'dozent')
                                    AND Nachname LIKE :nachname AND (:vorname IS NULL OR Vorname LIKE :vorname)
                                  ORDER BY Nachname";
                        $parameters['seminar_id'] = $SessSemName[1];
                        $parameters['nachname']   = $csv_nachname;
                        $parameters['vorname']    = $csv_vorname ?: null;
                    } elseif (Request::quoted('csv_import_format') == 'username') {
                        $query = "SELECT a.user_id, username, {$_fullname_sql['full_rev']} AS fullname,
                                         perms, b.Seminar_id AS is_present
                                  FROM auth_user_md5 AS a
                                  LEFT JOIN user_info USING (user_id)
                                  LEFT JOIN seminar_user AS b ON (b.user_id = a.user_id AND b.Seminar_id = :seminar_id)
                                  WHERE perms IN ('autor', 'tutor', 'dozent')
                                    AND username LIKE :nachname
                                  ORDER BY Nachname";
                        $parameters['seminar_id'] = $SessSemName[1];
                        $parameters['nachname'] = $csv_nachname;
                    } else {
                        $query = "SELECT a.user_id, username, {$_fullname_sql['full_rev']} AS fullname,
                                         perms, b.Seminar_id AS is_present
                                  FROM datafields_entries AS de
                                  LEFT JOIN auth_user_md5 AS a ON (a.user_id = de.range_id)
                                  LEFT JOIN user_info USING (user_id)
                                  LEFT JOIN seminar_user AS b ON (b.user_id = a.user_id AND b.Seminar_id = :seminar_id)
                                  WHERE perms IN ('autor', 'tutor', 'dozent')
                                    AND de.datafield_id = :datafield_id AND de.content = :nchname
                                  ORDER BY Nachname";
                        $parameters['seminar_id']   = $SessSemName[1];
                        $parameters['datafield_id'] = $df_id;
                        $parameters['nachname']     = $csv_nachname;
                    }
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute($parameters);
                    $csv_users = $statement->fetchAll(PDO::FETCH_ASSOC);

                    if (count($csv_users) > 1) {
                        foreach ($csv_users as $row) {
                            if ($row['is_present']) {
                                $csv_count_present++;
                            } else {
                                $csv_mult_founds[$csv_line][] = $row;
                            }
                        }

                        if (is_array($csv_mult_founds[$csv_line])) {
                            $csv_count_multiple++;
                        }
                    } elseif (count($csv_users) > 0) {
                        $row = reset($csv_users);
                        if(!$row['is_present']){
                            $consider_contingent = Request::option('consider_contingent');
                            if(insert_seminar_user($id, $row['user_id'], 'autor', isset($consider_contingent), $consider_contingent)){
                                $csv_count_insert++;
                                setTempLanguage($userchange);
                                if ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]) {
                                    $message = sprintf(_("Sie wurden von einem/r LeiterIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                                } else {
                                    $message = sprintf(_("Sie wurden vom einem/r DozentIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                                }
                                restoreLanguage();
                                $messaging->insert_message(mysql_escape_string($message), $row['username'], "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                            } elseif (isset($consider_contingent)){
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
        $consider_contingent = Request::option('consider_contingent');
        $selected_users = Request::getArray('selected_users');
        if (sizeof($selected_users)) {
            foreach ($selected_users as $selected_user) {
                if ($selected_user) {
                    if(insert_seminar_user($id, get_userid($selected_user), 'autor', isset($consider_contingent), $consider_contingent)){
                        $csv_count_insert++;
                        setTempLanguage($userchange);
                        if ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]) {
                            $message = sprintf(_("Sie wurden von einem/r LeiterIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                        } else {
                            $message = sprintf(_("Sie wurden vom einem/r DozentIn oder AdministratorIn als TeilnehmerIn in die Veranstaltung **%s** eingetragen."), $SessSemName[0]);
                        }
                        restoreLanguage();
                        $messaging->insert_message(mysql_escape_string($message), $selected_user, "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                    } elseif (isset($consider_contingent)){
                        $csv_count_contingent_full++;
                    }
                }
            }
        }
        $msg = '';
        if (!$csv_count_multiple) {
            $cmd = '';
        }
        if (!sizeof($csv_lines) && !sizeof($selected_users)) {
            $msg = 'error�' . _("Keine NutzerIn gefunden!") . '�';
            $cmd = '';
        } else {
            if ($csv_count_insert) {
                $msg .=  'msg�' . sprintf(_("%s NutzerInnen als AutorIn in die Veranstaltung eingetragen!"),
                        $csv_count_insert) . '�';
            }
            if ($csv_count_present) {
                $msg .=  'info�' . sprintf(_("%s NutzerInnen waren bereits in der Veranstaltung eingetragen!"),
                        $csv_count_present) . '�';
            }
            if ($csv_count_multiple) {
                $msg .= 'info�' . sprintf(_("%s NutzerInnen konnten <b>nicht eindeutig</b> zugeordnet werden! Nehmen Sie die Zuordnung am Ende dieser Seite manuell vor."),
                        $csv_count_multiple) . '�';
            }
            if (sizeof($csv_not_found)) {
                $msg .= 'error�' . sprintf(_("%s NutzerInnen konnten <b>nicht</b> zugeordnet werden! Am Ende dieser Seite finden Sie die Namen, die nicht zugeordnet werden konnten."),
                        sizeof($csv_not_found)) . '�';
            }
            if($csv_count_contingent_full){
                $msg .= 'error�' . sprintf(_("%s NutzerInnen konnten <b>nicht</b> zugeordnet werden, da das ausgew�hlte Kontingent keine freien Pl�tze hat."),
                        $csv_count_contingent_full) . '�';
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
                // wer versucht denn da wen nicht zugelassenen zu berufen?
                $query = "SELECT DISTINCT b.user_id, username, Vorname, Nachname, inst_perms, perms
                          FROM seminar_inst d
                          LEFT JOIN user_inst a USING(Institut_id)
                          LEFT JOIN auth_user_md5 b USING(user_id)
                          LEFT JOIN seminar_user c ON (c.user_id = a.user_id AND c.seminar_id = :seminar_id)
                          WHERE d.seminar_id = :seminar_id AND a.inst_perms IN ('tutor','dozent') AND ISNULL(c.seminar_id)
                          ORDER BY Nachname";
                $statement = DBManager::get()->prepare($query);
                $statement->bindParam(':seminar_id', $SessSemName[1]);
                $statement->execute();
                if ($statement->fetchColumn()) {
                    // so, Berufung ist zulaessig
                    $query = "SELECT status FROM seminar_user WHERE Seminar_id = ? AND user_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($id, $u_id));
                    $status = $statement->fetchColumn();
                    if ($status) {
                        // der Dozent hat Tomaten auf den Augen, der Mitarbeiter sitzt schon im Seminar. Na, auch egal...
                        if ($status == "autor" || $status == "user") {
                            // gehen wir ihn halt hier hochstufen
                            $next_pos = get_next_position("tutor",$id);

                            $query = "UPDATE seminar_user
                                      SET status = 'tutor', position = ?
                                      WHERE Seminar_id = ? AND user_id = ?";
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array($next_pos, $id, $u_id));

                            //kill from waiting user
                            $query = "DELETE FROM admission_seminar_user WHERE seminar_id = ? AND user_id = ?";
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array($id, $u_id));

                            $msg = "msg�" . sprintf(_("%s wurde auf den Status '%s' bef&ouml;rdert."), get_fullname($u_id,'full',1), get_title_for_status('tutor', 1)) . "�";

                            //reordner waiting list
                            renumber_admission($id);
                            // LOGGING
                            log_event('SEM_USER_ADD', $id, $userchange, 'tutor', 'Wurde zum Tutor ernannt (add_tutor_x)');
                        } else {
                            ;   // na, das ist ja voellig witzlos, da tun wir einfach nix.
                                // Nicht das sich noch ein Dozent auf die Art und Weise selber degradiert!
                        }
                    } else {  // ok, einfach aufnehmen.
                        insert_seminar_user($id, $u_id, "tutor");

                        $msg = "msg�" . sprintf(_("%s wurde als %s in die Veranstaltung aufgenommen."), get_fullname($u_id,'full',1), get_title_for_status('tutor', 1));

                        setTempLanguage($userchange);
                        $message = sprintf(_("Sie wurden von einem/einer VeranstaltungsleiteriIn (%s) oder AdministratorIn in die Veranstaltung **%s** aufgenommen."), get_title_for_status('dozent', 1), $SessSemName[0]);
                        restoreLanguage();
                        $messaging->insert_message(mysql_escape_string($message), get_username($u_id), "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Eintragung in Veranstaltung"), TRUE);
                    }
                }
                else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
            }
            else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
        }
        else $msg ="error�" . _("Sie haben leider nicht die notwendige Berechtigung f�r diese Aktion.") . "�";
    }
}
//Alle fuer das Losen anstehenden Veranstaltungen bearbeiten (wenn keine anstehen wird hier nahezu keine Performance verbraten!)
check_admission();

if (LockRules::Check($SessSemName[1], 'participants')) {
    $lockdata = LockRules::getObjectRule($SessSemName[1]);
    if ($lockdata['description']) {
        $msg .= "info�" . formatLinks($lockdata['description']);
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

$multiaction['tutor'] = array('insert' => null, 'delete' => array('tutor_to_autor', sprintf(_("Ausgew�hlte %s entlassen"), get_title_for_status('tutor', 2))), 'send' => array('send_msg', 'Nachricht an ausgew�hlte Benutzer verfassen'));
$multiaction['autor'] = array('insert' => array('autor_to_tutor', sprintf(_("Ausgew�hlte Benutzer als %s eintragen"), get_title_for_status('tutor', 2))), 'delete' => array('autor_to_user', _("Ausgew�hlten Benutzern das Schreibrecht entziehen")), 'send' => array('send_msg', 'Nachricht an ausgew�hlte Benutzer verfassem'));
$multiaction['user'] = array('insert' => array('user_to_autor',_("Ausgew�hlten Benutzern das Schreibrecht erteilen")), 'delete' => array('user_to_null', _("Ausgew�hlte Benutzer aus der Veranstaltung entfernen")),'send' => array('send_msg', 'Nachricht an ausgew�hlte Benutzer verfassen'));
$multiaction['accepted'] = array('insert' => array('admission_insert',_("Ausgew�hlte Benutzer akzeptieren")), 'delete' => array('admission_delete', _("Ausgew�hlte Benutzer aus der Veranstaltung entfernen")), 'send' => array('send_msg', 'Nachricht an ausgew�hlte Benutzer verfassen'));

$query = "SELECT COUNT(user_id) AS teilnehmer, SUM(admission_studiengang_id != '') AS teilnehmer_kontingent
          FROM seminar_user
          WHERE Seminar_id = ? AND status IN ('user', 'autor')";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($SessSemName[1]));
$temp = $statement->fetch(PDO::FETCH_ASSOC);

$anzahl_teilnehmer            = $temp['teilnehmer'];
$anzahl_teilnehmer_kontingent = $temp['teilnehmer_kontingent'];

$query = "SELECT COUNT(user_id) AS teilnehmer, SUM(studiengang_id != '') AS teilnehmer_kontingent
          FROM admission_seminar_user
          WHERE seminar_id = ? AND status = 'accepted'";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($SessSemName[1]));
$temp = $statement->fetch(PDO::FETCH_ASSOC);

$anzahl_teilnehmer            += $temp['teilnehmer'];
$anzahl_teilnehmer_kontingent += $temp['teilnehmer_kontingent'];
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
            $query = "SELECT status, visible FROM seminar_user WHERE user_id = ? AND Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($auth->auth['uid'], $_SESSION['SessionSeminar']));
            $temp = $statement->fetch(PDO::FETCH_ASSOC);

            $visible_mode = 'false';

            if ($temp) {
                $iam_visible = $temp['visible'] == 'yes';

                if ($temp['status'] == 'user' || $temp['status'] == 'autor') {
                    $visible_mode = 'participant';
                } else {
                    $iam_visible  = true;
                    $visible_mode = false;
                }
            }

            $query = "SELECT status, visible FROM admission_seminar_user WHERE user_id = ? AND seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($auth->auth['uid'], $_SESSION['SessionSeminar']));
            $temp = $statement->fetch(PDO::FETCH_ASSOC);

            if ($temp) {
                $iam_visible  = $temp['visible'] == 'yes';
                $visible_mode = 'awaiting';
            }
        if (!$perm->have_studip_perm('tutor',$SessSemName[1])) {
            // add skip link
            SkipLinks::addIndex(_("Sichtbarkeit �ndern"), 'change_visibility');
            if ($iam_visible) {
        ?>
        <br>
            <b><?=  _("Sie erscheinen f�r andere TeilnehmerInnen sichtbar auf der Teilnehmerliste."); ?></b><br>
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
                <td class="table_header" valign="middle">
                            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="22" width="5">
                        </td>
                <td class="table_header" valign="middle">
                            <font size="-1"><?=_("Sortierung:")?>&nbsp;</font>
                        </td>
                        <? if (!(Request::option('view_order')) || (Request::option('view_order') == "abc")) { ?>
                        <td nowrap class="table_header_bold" valign="middle">
                            <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
                            <font size="-1"><?=_("Alphabetisch")?></font>&nbsp;
                        <? } else { ?>
                        <td nowrap class="table_header" valign="middle">
                            &nbsp;
                            <a href="<?= URLHelper::getLink('?view_order=abc') ?>">
                                <?= Assets::img('icons/16/grey/arr_1right.png', array('class' => 'text-top')) ?>
                                <font size="-1" color="#555555"><?=_("Alphabetisch")?></font>
                            </a>
                            &nbsp;
                        <? } ?>
                        </td>
                        <? if ((Request::option('view_order')) && (Request::option('view_order') == "date")) { ?>
                        <td nowrap class="table_header_bold" valign="middle">
                            <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
                            <font size="-1"><?=_("Anmeldedatum")?></font>&nbsp;
                        <? } else { ?>
                        <td nowrap class="table_header" valign="middle">
                            &nbsp;
                            <a href="<?= URLHelper::getLink('?view_order=date') ?>">
                                <?= Assets::img('icons/16/grey/arr_1right.png', array('class' => 'text-top')) ?>
                                <font size="-1" color="#555555"><?=_("Anmeldedatum")?></font>
                            </a>
                            &nbsp;
                        <? } ?>
                        </td>

                        <td nowrap align="right" class="table_header" valign="middle"> <?

            $query = "SELECT showscore FROM seminare WHERE Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($_SESSION['SessionSeminar']));
            $showscore = (bool)$statement->fetchColumn();

            if ($showscore) {
                if ($rechte) {
                    printf ("<a href=\"%s\"><img src=\"" . Assets::image_path('showscore1.png') . "\" %s> </a>", URLHelper::getLink('?cmd=hidescore'), tooltip(_("Aktivit�tsanzeige eingeschaltet. Klicken zum Ausschalten.")));
                } else {
                    echo "&nbsp; ";
                }
            } else {
                if ($rechte) {
                    printf ("<a href=\"%s\"><img src=\"" . Assets::image_path('showscore0.png') . "\" %s> </a>", URLHelper::getLink('?cmd=showscore'), tooltip(_("Aktivit�tsanzeige ausgeschaltet. Klicken zum Einschalten.")));
                } else {
                    echo "&nbsp; ";
                }
            }
        ?>
        </td>
                    <tr>
                </table>
        </td>
    </tr>
    <tr>
        <td class="blank" width="100%" colspan="2">
        <br />
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

    <table class="zebra" width="99%" border="0"  cellpadding="2" cellspacing="0" align="center">

<?
$studipticket = Seminar_Session::get_ticket();

//Index berechnen
$query = "SELECT COUNT(*) FROM dokumente WHERE seminar_id = ?";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($_SESSION['SessionSeminar']));
$aktivity_index_seminar = 5 * $statement->fetchColumn();

foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
    $aktivity_index_seminar += $plugin->getNumberOfPostingsForSeminar($_SESSION['SessionSeminar']);
}

$query = "SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ?";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($_SESSION['SessionSeminar']));
$temp = $statement->fetchColumn();
if ($temp) {
    $aktivity_index_seminar /= $temp;
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

    default:
        $sortby = "Nachname, Vorname";
        break;
}

while (list ($key, $val) = each ($gruppe)) {

    $counter=1;

    if ($key == "accepted") {  // modify query if user is in admission_seminar_user and not in seminar_user
        $table  = 'admission_seminar_user';
        $column = 'studiengang_id';
    } else {
        $table  = 'seminar_user';
        $column = 'admission_studiengang_id';
    }

    if($rechte && $key == 'autor'  && $sem->isAdmissionEnabled()){
        echo '<tr><td class="blank" colspan="'.$colspan.'" align="right"><font size="-1">';
        printf(_("<b>Teilnahmebeschr�nkte Veranstaltung</b> -  Teilnehmerkontingent: %s, davon belegt: %s, zus�tzlich belegt: %s"),
            $sem->admission_turnout, $anzahl_teilnehmer_kontingent, $anzahl_teilnehmer - $anzahl_teilnehmer_kontingent);
        echo '</font></td></tr>';
    }

    $query = "SELECT :table.visible, :table.mkdate, comment, :table.user_id,
                     {$_fullname_sql['full']} AS fullname, username, status,
                     studiengaenge.name, :table.:column AS studiengang_id,
                     COUNT(DISTINCT dokument_id) AS documents
             FROM :table
             LEFT JOIN dokumente AS docs ON (docs.user_id = :table.user_id AND docs.seminar_id = :table.Seminar_id)
             LEFT JOIN auth_user_md5 ON (:table.user_id = auth_user_md5.user_id)
             LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id)
             LEFT JOIN studiengaenge ON (:table.:column = studiengaenge.studiengang_id)
             WHERE :table.Seminar_id = :seminar_id AND status = :status
             GROUP BY :table.user_id
             ORDER BY {$sortby}"; // We have no mechanism to insert this otherwise but
                                  // since $sortby is defined only within the script this should be safe
    $statement = DBManager::get()->prepare($query);
    $statement->bindValue(':table', $table, StudipPDO::PARAM_COLUMN);
    $statement->bindValue(':column', $column, StudipPDO::PARAM_COLUMN);
    $statement->bindValue(':seminar_id', $_SESSION['SessionSeminar']);
    $statement->bindValue(':status', $key);
    $statement->execute();
    $users = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) { //Only if Users were found...
        $info_is_open = false;
        $tutor_count = 0;
        // die eigentliche Teil-Tabelle
        if ($key != 'dozent') {
            echo "<form name=\"$key\" action=\"".URLHelper::getLink("?studipticket=$studipticket")."\" method=\"post\">";
            echo CSRFProtection::tokenTag();
        }
        if ($key == 'accepted') echo '<input type="hidden" name="accepted" value="1">';

        echo "<tbody><tr>";
        if ($showscore==TRUE)
            echo "<td class=\"table_header\" width=\"1%\">&nbsp; </td>";
        print "<td class=\"table_header\" width=\"1%\" align=\"center\" valign=\"middle\">";
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

        echo '<td class="table_header" width="19%" align="left" id="member_group_' . $key . '">'.
               '<font size="-1"><b>' . $val . '</b></font>'.
             '</td>';

        // mail button einf�gen
        if ($rechte) {
            echo '<td class="table_header" width="10%">';
            // hier kann ne flag setzen um mail extern zu nutzen
            if ($ENABLE_EMAIL_TO_STATUSGROUP) {
                $seminar_user_table =
                    $key == 'accepted' ? 'admission_seminar_user' : 'seminar_user';

                $query = "SELECT GROUP_CONCAT(Email ORDER BY Email SEPARATOR ',')
                          FROM :table
                          JOIN auth_user_md5 USING (user_id)
                          WHERE seminar_id = :seminar_id AND status = :status
                          ORDER BY Email";
                $statement = DBManager::get()->prepare($query);
                $statement->bindParam(':table', $seminar_user_table, StudipPDO::PARAM_COLUMN);
                $statement->bindParam(':seminar_id', $SessSemName[1]);
                $statement->bindParam(':status', $key);
                $statement->execute();
                $all_user = $statement->fetchColumn();
                $text = sprintf(_('E-Mail an alle %s schicken'), $val);
                echo '<a href="mailto:'.$all_user.'" title="'.$text.'">';
                echo Assets::img('icons/16/blue/move_right/mail.png', array('alt'=>$text, 'align' => 'absmiddle'));
                echo '</a>&nbsp;';
            }

            if ($key == 'accepted') {
                $msg_params = array('filter' => 'prelim', 'sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'course_id' => $SessSemName[1], 'subject' => $subject);
            } else {
                $msg_params = array('filter' => 'send_sms_to_all', 'who' => $key, 'sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'course_id' => $SessSemName[1], 'subject' => $subject);
            }
            $text = sprintf(_('Nachricht an alle %s schicken'), $val);
            echo '<a href="'.URLHelper::getLink('sms_send.php', $msg_params).'" title="'.$text.'">';
            echo Assets::img('icons/16/blue/mail.png', array('alt'=>$text, 'align' => 'absmiddle'));
            echo '</a>';
            echo '</td>';
        } else {
            echo '<td class="table_header">&nbsp;</td>';
        }

        echo "</b></font></td>";

        if ($key != "dozent" && $rechte) {
            printf("<td class=\"table_header\" width=\"1%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Anmeldedatum"));
        } else if ($key == "dozent" && $rechte) {
            printf("<td class=\"table_header\" width=\"9%%\" align=\"center\" valign=\"bottom\">&nbsp;</td>");
        }

        if ($showscore) { //Einblenden wenn Aktivit�tsanzeige aktiviert wurde
            printf("<td class=\"table_header\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Forenbeitr�ge"));
            printf("<td class=\"table_header\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Dokumente"));
        }

        printf("<td class=\"table_header\" width=\"9%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Nachricht"));


        if ($rechte && !LockRules::Check($id, 'participants')) {
            $tooltip = tooltip(_("Klicken, um Auswahl umzukehren"),false);
            if ($sem->isAdmissionEnabled())
                $width=15;
            else
                $width=20;

            if ($key == "dozent") {
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\"><b>&nbsp;</b></td>", $width);
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\"><b>&nbsp;</b></td>", $width);
                if ($sem->isAdmissionEnabled())
                    echo"<td class=\"table_header\" width=\"10%\" align=\"center\" colspan=\"2\"><b>&nbsp;</b></td>";
            }

            if ($key == "tutor") {
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\"><font size=\"-1\"><b>&nbsp;</b></font></td>", $width);
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"tutor_to_autor\" onClick=\"return invert_selection('tutor_to_autor','%s');\" %s><b>%s</b></a></font></td>", $width, $key, $tooltip, sprintf(_("%s entlassen"), get_title_for_status('tutor', 1)));
                if ($sem->isAdmissionEnabled())
                    echo"<td class=\"table_header\" width=\"10%\" align=\"center\"><b>&nbsp;</b></td>";
            }

            if ($key == "autor") {
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"autor_to_tutor\" onClick=\"return invert_selection('autor_to_tutor','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, sprintf(_("als %s eintragen"), get_title_for_status('tutor', 1)));
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"autor_to_user\" onClick=\"return invert_selection('autor_to_user','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("Schreibrecht entziehen"));
                if ($sem->isAdmissionEnabled())
                    printf("<td class=\"table_header\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Kontingent"));
            }

            if ($key == "user") {
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"user_to_autor\" onClick=\"return invert_selection('user_to_autor','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("Schreibrecht erteilen"));
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"user_to_null\" onClick=\"return invert_selection('user_to_null','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("BenutzerIn entfernen"));
                if ($sem->isAdmissionEnabled())
                    print"<td class=\"table_header\" width=\"10%\" align=\"center\"><b>&nbsp;</b></td>";
            }

            if ($key == "accepted") {
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"admission_insert\" onClick=\"return invert_selection('admission_insert','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip,  _("Akzeptieren"));
                printf ("<td class=\"table_header\" width=\"%s%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><a name=\"admission_delete\" onClick=\"return invert_selection('admission_delete','%s');\" %s><b>%s</b></a></font></td>",  $width, $key, $tooltip, _("BenutzerIn entfernen"));
                if ($sem->isAdmissionEnabled())
                    printf("<td class=\"table_header\" width=\"10%%\" align=\"center\" valign=\"bottom\"><font size=\"-1\"><b>%s</b></font></td>", _("Kontingent"));
            }
        }

        echo "</tr>";
        $c=1;
        $invisible=0;
        $i_see_everybody = $perm->have_studip_perm('tutor', $SessSemName[1]);

        foreach ($users as $one_user) {
            if (($one_user['user_id'] == $user->id) && ($one_user['visible'] != 'yes')) {
                $one_user['fullname'] .= ' ('._("unsichtbar").')';
            }

            //  Elemente holen
            if (array_key_exists($documents, $one_user)) {
                $Dokumente = $one_user['documents'];
            } else {
                $query = "SELECT COUNT(*) FROM dokumente WHERE seminar_id = ? AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($_SESSION['SessionSeminar'], $one_user['user_id']));
                $Dokumente = $statement->fetchColumn() ?: 0;
            }

            $postings_user = 0;
            foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
                $postings_user += $plugin->getNumberOfPostingsForUser($one_user['user_id'], $_SESSION['SessionSeminar']);
            }
            
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
            if ($one_user['visible'] == 'yes' || $i_see_everybody || $one_user['user_id'] == $user->id) {
                echo "<tr>";
                if ($showscore == TRUE) {
                    printf("<td bgcolor=\"#%s%s%s\">", $red, $green,$blue);
                    printf("<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" %s width=\"10\"></td>", tooltip(_("Aktivit�t: ").round($aktivity_index_user)."%"));
                }

                if ($rechte) {
                    if (is_opened($one_user['user_id'])) {
                        $link = URLHelper::getLink("?cmd=lessinfos&username=".$one_user['username']."#".$one_user['username']);
                        $img = "icons/16/blue/arr_1down.png";
                    } else {
                        $link = URLHelper::getLink("?cmd=moreinfos&username=".$one_user['username']."#".$one_user['username']);
                        $img = "icons/16/blue/arr_1right.png";
                    }
                }
                if ($i_see_everybody) {
                    $anker = "<a name=\"".$one_user['username']."\">";
                    $anker2 = '</a>';
                } else {
                    $anker = $anker2 = '';
                }
                printf ("<td nowrap>%s<font size=\"-1\">&nbsp;%s.</font>%s</td>", $anker, $c, $anker2);
                echo "<td colspan=\"2\">";
                if ($rechte) {
                    printf ("<a href=\"%s\"><img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/%s\"", $link, $img);
                    echo tooltip(sprintf(_("Weitere Informationen �ber %s"), $one_user['username']));
                    echo ">&nbsp;</a>";
                }
                ?>
                    <span style="position: relative">
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username='.$one_user['username']) ?>">
                            <? if (!$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) :
                                $last_visitdate = time()+10;
                            endif ?>
                            <? $one_user['mkdate'] >= $last_visitdate
                                ? $options = array('title' => _('DieseR NutzerIn ist nach Ihrem '.
                                    'letzten Besuch dieser Veranstaltung beigetreten'))
                                :  $options = array() ?>
                            <? $options['style'] = 'margin-right: 5px' ?>
                            <?= Avatar::getAvatar($one_user['user_id'])->getImageTag(Avatar::SMALL, $options) ?>
                            <?= $one_user['mkdate'] >= $last_visitdate ? Assets::img('red_star.png', array(
                                'style' => 'position: absolute; top: -4px; left: 13px'
                            )) : '' ?>
                            <?= htmlReady($one_user['fullname']) ?>
                        </a>
                    </span>
                    </td>
                <?
                if ($key != "dozent" && $rechte) {
                    if ($one_user['mkdate']) {
                        echo "<td align=\"center\"><font size=\"-1\">".date("d.m.y,",$one_user['mkdate'])."&nbsp;".date("H:i:s",$one_user['mkdate'])."</font></td>";
                    } else {
                        echo "<td align=\"center\"><font size=\"-1\">"._("unbekannt")."</font></td>";
                    }
                } else if ($key == "dozent" && $rechte) {
                    echo "<td align=\"center\">&nbsp;</td>";
                }

                if ($showscore) { //Einblenden wenn Aktivit�tsanzeige aktiviert wurde
                    echo '<td align="center">' . $postings_user . '</td>';
                    echo '<td align="center">' . $Dokumente . '</td>';
                } 
                echo "<td align=\"center\">";

                $username=$one_user['username'];
                if ($one_user['visible'] == 'yes' || $i_see_everybody) {

                    printf ("<a href=\"%s\">".Assets::img("icons/16/blue/mail.png", array('alt' => tooltip(_("Nachricht an Benutzer verschicken")), 'title' => tooltip(_("Nachricht an Benutzer verschicken"))))."</a>", URLHelper::getLink("sms_send.php", array("sms_source_page" => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], "subject" => $subject, "rec_uname" => $one_user['username'])));

                    if (isset($multiaction[$key]['send'][0]) && $rechte)
                        printf("<input class=\"text-top\" type=\"checkbox\" name=\"send_msg[%s]\" value=\"1\"></td>", $username);
                }

                echo "</td>";

                // Befoerderungen und Degradierungen
                if ($rechte && !LockRules::Check($id, 'participants')) {

                    // Tutor entlassen
                    if ($key == "tutor" AND $SemUserStatus!="tutor") {
                        echo "<td>&nbsp</td>";
                        echo "<td align=\"center\">";
                        echo "<a href=\"".URLHelper::getLink("?cmd=pain&username=$username&studipticket=$studipticket")."\">".Assets::img("icons/16/yellow/arr_2down.png", array('alt' => tooltip(_("Schreibrecht entziehen")), 'title' => tooltip(_("Schreibrecht entziehen"))))."</a>";
                        echo "<input type=\"checkbox\" name=\"tutor_to_autor[]\" value=\"$username\">";
                        echo "</td>";
                    }

                    elseif ($key == "autor") {
                        // zum Tutor bef�rdern
                        if ($SemUserStatus!="tutor") {
                            if ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["only_inst_user"]) {
                                $query = "SELECT 1
                                          FROM seminar_inst
                                          LEFT JOIN user_inst USING (Institut_id)
                                          WHERE user_id = ? AND seminar_id = ? AND inst_perms NOT IN ('user', 'autor')";
                                $statement = DBManager::get()->prepare($query);
                                $statement->execute(array($one_user['user_id'], $SessSemName[1]));
                                $check = $statement->fetchColumn();
                            } else {
                                $query = "SELECT 1
                                          FROM auth_user_md5
                                          WHERE user_id = ? AND perms IN ('tutor', 'dozent')";
                                $statement = DBManager::get()->prepare($query);
                                $statement->execute(array($one_user['user_id']));
                                $check = $statement->fetchColumn();
                            }
                            if ($check) {
                                ++$tutor_count;
                                echo "<td align=\"center\">";
                                echo "<a href=\"".URLHelper::getLink("?cmd=pleasure&username=$username&studipticket=$studipticket")."\">".Assets::img("icons/16/yellow/arr_2up.png", array('alt' => tooltip(_("Tutor ernennen")), 'title' => tooltip(_("Tutor ernennen"))))."</a>";
                                echo "<input type=\"checkbox\" name=\"autor_to_tutor[]\" value=\"$username\">";
                                echo "</td>";
                            } else echo "<td>&nbsp;</td>";
                        } else echo "<td>&nbsp;</td>";
                        // Schreibrecht entziehen
                        echo "<td align=\"center\">";
                        echo "<a href=\"".URLHelper::getLink("?cmd=lesen&username=$username&studipticket=$studipticket")."\">".Assets::img("icons/16/yellow/arr_2down.png", array('alt' => tooltip(_("Schreibrecht entziehen")), 'title' => tooltip(_("Schreibrecht entziehen"))))."</a>";
                        echo "<input type=\"checkbox\" name=\"autor_to_user[]\" value=\"$username\">";
                        echo "</td>";
                    }

                    // Schreibrecht erteilen
                    elseif ($key == "user") {
                        $query = "SELECT 1 FROM auth_user_md5 WHERE user_id = ? AND perms != 'user'";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($one_user['user_id']));
                        $check = $statement->fetchColumn();

                        if ($check) { // Leute, die sich nicht zurueckgemeldet haben duerfen auch nicht schreiben!
                            echo "<td align=\"center\">";
                            echo "<a href=\"".URLHelper::getLink("?cmd=schreiben&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\"></a>";
                            echo "<input type=\"checkbox\" name=\"user_to_autor[]\" value=\"$username\">";
                            echo "</td>";
                        } else echo "<td>&nbsp;</td>";
                        // aus dem Seminar werfen
                        echo "<td align=\"center\">";
                        echo "<a href=\"".URLHelper::getLink("?cmd=raus&username=$username&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a>";
                        echo "<input type=\"checkbox\" name=\"user_to_null[$username]\" value=\"1\">";
                        echo "</td>";
                    }

                    elseif ($key == "accepted") { // temporarily accepted students
                        // forward to autor
                        echo "<td width=\"15%\" align=\"center\">";
                        echo "<a href=\"".URLHelper::getLink("?cmd=admission_rein&username=$username&accepted=1&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\"></a><input type=\"checkbox\" name=\"admission_insert[$username]\" value=\"1\">";
                        echo "</td>";
                        // kick
                        echo "<td align=\"center\">";
                        echo "<a href=\"".URLHelper::getLink("?cmd=admission_raus&username=$username&accepted=1&studipticket=$studipticket")."\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a><input type=\"checkbox\" name=\"admission_delete[$username]\" value=\"1\">";
                        echo "</td>";
                    }

                    else { // hier sind wir bei den Dozenten
                        echo "<td colspan=\"2\">&nbsp;</td>";
                    }

                    if ($sem->isAdmissionEnabled()) {
                        if ($key == "autor" || $key == "user" || $key == "accepted")
                            printf ("<td width=\"80%%\" align=\"center\"><font size=-1>%s%s</font></td>", ($one_user['studiengang_id'] == "all") ? _("alle Studieng&auml;nge") : $one_user['name'], (!$one_user['name'] && !$one_user['studiengang_id'] == "all") ?  "&nbsp; ": "");
                        else
                            echo "<td width=\"10%%\" align=\"center\">&nbsp;</td>";
                    }
                } // Ende der Dozenten/Tutorenspalten
                print("</tr>\n");
                // info-field for users
                if ((is_opened($one_user['user_id']) || in_array($key, $open_areas)) && $rechte) { // show further userinfosi
                    $info_is_open = true;
                    $user_data = array();

                    //get data for user, if dozent or higher
                    if ($perm->have_perm("dozent")) {
                        /* remark: if you change something in the data-acquisition engine
                        * please do not forget to change it also in "export/export_studipdata_func.inc.php"
                        * in the function export_teilis(...)
                        */

                        $additional_data = get_additional_data($one_user['user_id'], $id);

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
                    <tr>

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
                                <?= Avatar::getAvatar($one_user['user_id'])->getImageTag(Avatar::MEDIUM) ?>
                            </td>
                        <? endif ?>

                        <td colspan="<?= $colspan - 2 - ($show_user_picture ? 1 : 0) - ($showscore ? 1 : 0)?>">
                            <form action="<?= URLHelper::getLink('#'.$one_user['username']) ?>" method="POST">
                                <?= CSRFProtection::tokenTag() ?>
                                <font size="-1"><?=_("Bemerkungen:")?></font><br>
                                <textarea name="userinfo" rows="3" cols="50"><?= $one_user['comment'] ?></textarea>
                                <br>
                                <font size="-1"><?= _("&Auml;nderungen") ?></font>
                                <?= Button::create(_('�bernehmen')) ?>
                                <input type="hidden" name="user_id" value="<?= $one_user['user_id'] ?>">
                                <input type="hidden" name="cmd" value="change_userinfo">
                                <input type="hidden" name="username" value="<?= $one_user['username'] ?>">
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
            echo '<tr><td class="blank" colspan="'.($showscore ? 7 : 4).'">&nbsp;</td>';

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
    echo '</tbody>';
}

echo "</table>\n";

echo "</td></tr>\n";  // Auflistung zuende

// Warteliste
$awaiting = false;
if ($rechte) {
    $query = "SELECT user_id, {$_fullname_sql['full']} AS fullname, username,
                     studiengaenge.name, position, admission_seminar_user.studiengang_id, status
              FROM admission_seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              LEFT JOIN studiengaenge USING (studiengang_id)
              WHERE admission_seminar_user.seminar_id = ? AND admission_seminar_user.status != 'accepted'
              ORDER BY position, name";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($_SESSION['SessionSeminar']));
    $waiting_users = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($waiting_users)) { //Only if Users were found...
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
        echo "<tr>";
        printf ("<td class=\"table_header\" width=\"%s%%\" align=\"left\"><b>%s</b></td>", ($sem->admission_type == 1 && $sem->admission_selection_take_place !=1) ? "40" : "30",  ($sem->admission_type == 2 || $sem->admission_selection_take_place==1) ? _("Warteliste") : _("Anmeldeliste"));
        if ($sem->admission_type == 2 || $sem->admission_selection_take_place==1)
            printf("<td class=\"table_header\" width=\"10%%\" align=\"center\"><font size=\"-1\"><b>%s</b></font></td>", _("Position"));
        printf("<td class=\"table_header\" width=\"10%%\" align=\"center\">&nbsp; </td>");
        printf("<td class=\"table_header\" width=\"10%%\" align=\"center\"><font size=\"-1\"><b>%s</b></font></td>", _("Nachricht"));
        if(!LockRules::Check($id, 'participants')){
            printf("<td class=\"table_header\" width=\"15%%\" align=\"center\"><font size=\"-1\"><a name=\"blubb\" onClick=\"return invert_selection('admission_insert','waitlist');\" %s><b>%s</b></a></font></td>", tooltip(_("Klicken, um Auswahl umzukehren"),false), _("eintragen"));
            printf("<td class=\"table_header\" width=\"15%%\" align=\"center\"><font size=\"-1\"><a name=\"bla\" onClick=\"return invert_selection('admission_delete','waitlist');\" %s><b>%s</b></a></font></td>", tooltip(_("Klicken, um Auswahl umzukehren"),false), _("entfernen"));
        }
        printf("<td class=\"table_header\" width=\"10%%\" align=\"center\"><font size=\"-1\"><b>%s</b></font></td></tr>\n", _("Kontingent"));


        foreach ($waiting_users as $waiting_user) {
            if ($waiting_user['status'] == "claiming") { // wir sind in einer Anmeldeliste und brauchen Prozentangaben
                $admission_chance = $sem->getAdmissionChance($waiting_user['studiengang_id']);
            }

            printf ("<tr><td width=\"%s\" align=\"left\"><font size=\"-1\"><a name=\"%s\" href=\"%s\">%s</a></font></td>",  ($sem->admission_type == 1 && $sem->admission_selection_take_place !=1) ? "40%" : "30%", $waiting_user['username'], URLHelper::getLink('dispatch.php/profile?username='.$waiting_user['username']), htmlReady($waiting_user['fullname']));
            if ($sem->admission_type == 2 || $sem->admission_selection_take_place==1)
                printf ("<td width=\"10%%\" align=\"center\"><font size=\"-1\">%s</font></td>", $waiting_user['position']);
            echo "<td width=\"10%%\" align=\"center\">&nbsp; </td>";

            printf ("<td width=\"10%%\" align=\"center\"><a href=\"%s\"><img class=\"text-bottom\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\" %s></a></td>", URLHelper::getLink('sms_send.php', array('sms_source_page' => 'teilnehmer.php?cid=' . $_SESSION['SessionSeminar'], 'rec_uname' => $waiting_user['username'])), tooltip(_("Nachricht an Benutzer verschicken")));
            if(!LockRules::Check($id, 'participants')){
                printf ("<td width=\"15%%\" align=\"center\"><input type=\"image\" name=\"admission_rein[%s]\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2up.png\">
                        <input type=\"checkbox\" name=\"admission_insert[%s]\" value=\"1\"></td>", $waiting_user['username'], $waiting_user['username']);
                printf ("<td width=\"15%%\" align=\"center\"><a href=\"%s\"><img border=\"0\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2down.png\"></a>
                        <input type=\"checkbox\" name=\"admission_delete[%s]\" value=\"1\"></td>", URLHelper::getLink("?cmd=admission_raus&username=".$waiting_user['username']."&studipticket=$studipticket"), $waiting_user['username']);
            }
            printf ("<td width=\"10%%\" align=\"center\"><font size=\"-1\">%s</font></td></tr>\n", ($waiting_user['studiengang_id'] == "all") ? _("alle Studieng&auml;nge") : $waiting_user['name']);
        }
        if(!LockRules::Check($id, 'participants')){
            echo '<tr><td class="blank" colspan="4" align="right"><font size="-1">';
            echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/black/info.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzuf�gen auf die Kontingentpl�tze angerechnet werden."),1,1).' >';
            echo '<label for="kontingent">'._("Kontingent ber�cksichtigen:");
            echo '<input id="kontingent" type="checkbox" checked name="consider_contingent" value="1" style="vertical-align:middle"></label>';
            echo '&nbsp;</font></td>';
            echo '<td class="blank" align="center">' . Button::create(_('Eintragen'),'do_admission_insert',array('title'=>_("Ausgew�hlte Nutzer aus der Warteliste in die Veranstaltung eintragen"))) . '</td>';
            echo '<td class="blank" align="center">' . Button::create(_('Entfernen'),'do_admission_delete',array('title'=>_("Ausgew�hlte Nutzer aus der Warteliste entfernen"))) . '</td>';
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
    // ergibt alle berufbaren Personen
    $query = "SELECT a.user_id, username, {$_fullname_sql['full_rev']} AS fullname, inst_perms, perms
              FROM seminar_inst d
              LEFT JOIN user_inst a USING(Institut_id)
              LEFT JOIN auth_user_md5 b USING(user_id)
              LEFT JOIN user_info USING(user_id)
              LEFT JOIN seminar_user c ON (c.user_id = a.user_id AND c.seminar_id = :seminar_id)
              WHERE d.seminar_id = :seminar_id AND a.inst_perms IN ('tutor','dozent') AND ISNULL(c.seminar_id)
              GROUP BY a.user_id
              ORDER BY Nachname";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':seminar_id', $SessSemName[1]);
    $statement->execute();
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
        <td class="table_row_even" width="40%" align="left">&nbsp; <font size="-1"><b><?=_("MitarbeiterInnen der Einrichtung(en)")?></b></font></td>
        <td class="table_row_even" width="40%" align="left"><select name="u_id" size="1">
        <?
        printf('<option value="0">- -  %s - -</option>' . "\n", _('bitte ausw&auml;hlen'));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            printf('<option value="%s">%s - %s</option>' . "\n",
                   $row['user_id'],
                   htmlReady(my_substr($row['fullname'] . ' (' . $row['username'], 0, 35)) . ')',
                   $row['inst_perms']);
        }
        ?>
        </select></td>
        <td class="table_row_even" width="20%" align="center"><font size=-1><?= sprintf(_("als %s"), get_title_for_status('tutor', 1)) ?></font><br>
                 <?= Button::create(_('Eintragen'),'add_tutor' ,array('value'=> sprintf(_("als %s berufen"), get_title_for_status('tutor', 1)) )) ?></td>

          </tr></form></table>
<?

} // Ende der Berufung

//insert autors via free search form
if (!LockRules::Check($id, 'participants') && $rechte) {
    if ($cmd != 'csv') {
    if ($search_exp) {
        $search_exp = trim($search_exp);
        // results all users which are not in the seminar
        $query = "SELECT a.user_id, username, {$_fullname_sql['full_rev']} AS fullname, perms
                  FROM auth_user_md5 AS a
                  LEFT JOIN user_info USING^(user_id)
                  LEFT JOIN seminar_user b ON (b.user_id = a.user_id AND b.seminar_id = :seminar_id)
                  WHERE perms IN ('autor','tutor','dozent') AND ISNULL(b.seminar_id)
                    AND (username LIKE CONCAT('%', :needle, '%') OR
                         Vorname LIKE CONCAT('%', :needle, '%') OR
                         Nachname LIKE CONCAT('%', :needle, '%')
                  ORDER BY Nachname";
        $statement = DBManager::get()->prepare($query);
        $statement->bindParam(':seminar_id', $SessSemName[1]);
        $statement->bindParam(':needle', $search_exp);
        $statement->execute();
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
            <td class="table_row_even" width="40%" align="left">&nbsp; <font size="-1"><b><?=_("Gefundene Nutzer")?></b></font></td>
            <td class="table_row_even" width="40%" align="left"><select name="username" size="1">
            <?
            printf("<option value=\"0\">- -  %s - -\n", _("bitte ausw&auml;hlen"));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                printf('<option value="%s">%s - %s</option>' . "\n", $row['username'],
                       htmlReady(my_substr($row['fullname'] . ' ('.$row['username'], 0, 35)) . ')',
                       $row['perms']);
            }
            ?>
            </select>
            <?if($sem->isAdmissionEnabled()){
                echo '<br><br><img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/black/info.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzuf�gen auf die Kontingentpl�tze angerechnet werden."),1,1).' >';
                echo '<font size="-1"><label for="kontingent2">'._("Kontingent ber�cksichtigen:");
                echo '&nbsp;<select name="consider_contingent" id="kontingent2">';
                echo '<option value="">'._("Kein Kontingent").'</option>';
                if(is_array($sem->admission_studiengang)){
                    foreach($sem->admission_studiengang as $studiengang => $data){
                        echo '<option value="'.$studiengang.'" '.(Request::option('consider_contingent') == $studiengang ? 'selected' : '').'>'.htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')').'</option>';
                    }
                }
                echo '</select></label></font>';
            }
            ?>
            </td>
            <td class="table_row_even" width="20%" align="center"><font size=-1>
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
        <td class="table_row_even" width="40%" align="left">&nbsp; <font size=-1><b><?=_("Nutzer in die Veranstaltung eintragen")?></b></font>
            <a name="suchergebnisse"></a>
            <br><font size=-1>&nbsp; <? printf(_("Bitte geben Sie den Vornamen, Nachnamen %s oder Benutzernamen zur Suche ein"), "<br>&nbsp;")?> </font></td>
        <td class="table_row_even" width="20%" align="left">
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
        <td class="table_row_even" width="20%" align="center">
            <? if($sem->isAdmissionEnabled()) : ?>
            <select name="consider_contingent">
                <option value=""><?= _("Kein Kontingent") ?></option>
                <? if(is_array($sem->admission_studiengang))
                    foreach($sem->admission_studiengang as $studiengang => $data) : ?>
                    <option value="<?= $studiengang ?>" <?= Request::option('consider_contingent') == $studiengang ? 'selected' : '' ?>>
                        <?= htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')') ?>
                    </option>
                <? endforeach ?>
            </select>
            <? endif ?>
        </td>
        <td class="table_row_even" width="20%" align="center">
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
        echo "<tr><td width=\"40%\" class=\"table_row_even\">\n<div style=\"font-size: small; margin-left:6px; width:300px;\">";
        echo '<b>' . _("Teilnehmerliste �bernehmen") . '</b><br>';
        echo _("In das nebenstehende Textfeld k�nnen Sie eine Liste mit Namen von NutzerInnen eingeben, die in die Veranstaltung aufgenommen werden sollen.");
        echo '<br>' . _("W�hlen Sie in der Auswahlbox das gew�nschte Format, in dem Sie die Namen eingeben m�chten:");
        echo '<br>' . _("<b>Eingabeformat: Nachname, Vorname &crarr;</b>");
        echo '<br>' . _("Geben Sie dazu in jede Zeile den Nachnamen und (optional) den Vornamen getrennt durch ein Komma oder ein Tabulatorzeichen ein.");
        echo '<br>' . _("<b>Eingabeformat: Nutzername &crarr;</b>");
        echo '<br>' . _("Geben Sie dazu in jede Zeile den Stud.IP Nutzernamen ein.");
        echo "</div></td>\n";
        echo '<td width="40%" colspan="2" class="table_row_even">';
        echo '<div style="margin-top:10px;margin-bottom:10px;">' . _("Eingabeformat:");
        echo '<select style="margin-left:10px;" name="csv_import_format">';
        echo '<option value="realname">'._("Nachname, Vorname").' &crarr;</option>';
        echo '<option value="username" '.(Request::get('csv_import_format') == 'username' ? 'selected': '').'>'. _("Nutzername"). '&crarr;</option>';
        foreach ($accessible_df as $df) {
            echo '<option value="' . $df->getId() . '" '.(Request::get('csv_import_format') ==  $df->getId()? 'selected': '').'>'. htmlReady($df->getName()) . '&crarr;</option>';
        }
        echo '</select></div>';
        echo "<textarea name=\"csv_import\" rows=\"6\" cols=\"50\">";
        foreach ($csv_not_found as $line) {
            echo htmlReady($line) . chr(10);
        }
        echo "</textarea>";
        if ($sem->isAdmissionEnabled()) {
            echo '<br><br><img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/black/info.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzuf�gen auf die Kontingentpl�tze angerechnet werden."),1,1).' >';
            echo '<font size="-1"><label for="kontingent2">'._("Kontingent ber�cksichtigen:");
            echo '&nbsp;<select name="consider_contingent" id="kontingent2">';
            echo '<option value="">'._("Kein Kontingent").'</option>';
            if(is_array($sem->admission_studiengang)) {
                foreach($sem->admission_studiengang as $studiengang => $data){
                    echo '<option value="'.$studiengang.'" '.(Request::option('consider_contingent') == $studiengang ? 'selected' : '').'>'.htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')').'</option>';
                }
            }
            echo '</select></label></font>';
        }
        echo "</td>\n";
        echo "<td width=\"20%\" class=\"table_row_even\" align=\"center\"> ";
        echo Button::create(_('Eintragen'),'submit') ;
        if (sizeof($csv_not_found)) {
           echo "<img border=\"0\" " . LinkButton::create(_('L�schen'),URLHelper::getURL());
        }
        echo "\n</td></tr>\n";
    } else {
    //  if (sizeof($csv_mult_founds)) {
            echo "<tr><td class=\"table_row_even\" colspan=\"2\">";
            echo "<div style=\"font-size: small; margin-left:8px; width:350px;\">";
            echo '<b>' . _("Manuelle Zuordnung") . '</b><br>';
            echo _("Folgende NutzerInnen konnten <b>nicht eindeutig</b> zugewiesen werden. Bitte w�hlen Sie aus der jeweiligen Trefferliste:");
            echo "</div></td></tr>\n";
            foreach ($csv_mult_founds as $csv_key => $csv_mult_found) {
                printf("<tr><td width=\"40%%\"><div style=\"font-size:small; margin-left:8px;\">%s</div></td>",
                        htmlReady(mila($csv_key, 50)));
                echo "<td width=\"60%%\">";
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
            }
            echo "<tr><td class=\"table_row_even\" colspan=\"2\" align=\"right\" nowrap=\"nowrap\">";
            if($sem->isAdmissionEnabled()){
                echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/grey/info-circle.png" align="absmiddle" hspace="3" border="0" '.tooltip(_("Mit dieser Einstellung beeinflussen Sie, ob Teilnehmer die Sie hinzuf�gen auf die Kontingentpl�tze angerechnet werden."),1,1).' >';
                echo '<font size="-1"><label for="kontingent2">'._("Kontingent ber�cksichtigen:");
                echo '&nbsp;<select name="consider_contingent" id="kontingent2">';
                echo '<option value="">'._("Kein Kontingent").'</option>';
                if(is_array($sem->admission_studiengang)){
                    foreach($sem->admission_studiengang as $studiengang => $data){
                        echo '<option value="'.$studiengang.'" '.(Request::option('consider_contingent') == $studiengang ? 'selected' : '').'>'.htmlReady($data['name'] . ' ' . '('.$sem->getFreeAdmissionSeats($studiengang).')').'</option>';
                    }
                }
                echo '</select></label></font>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
            }
            echo  Button::create(_('Eintragen'));
            echo '&nbsp; &nbsp; ';
            echo  LinkButton::create(_('Abbrechen'),URLHelper::getURL());
            echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </td></tr>\n";

        if (sizeof($csv_not_found)) {
            echo "<tr><td width=\"40%\" class=\"table_row_even\">\n<div style=\"font-size: small; margin-left:8px; width:250px;\">";
            echo '<b>' . _("Nicht gefundene NutzerInnen") . '</b><br>';
            echo _("Im nebenstehende Textfeld sehen Sie eine Auflistung der Suchanfragen, zu denen <b>keine</b> NutzerInnen gefunden wurden.");
            echo "</div></td>\n";
            echo "<td width=\"40%\" class=\"table_row_even\">";
            echo "<textarea name=\"csv_import\" rows=\"6\" cols=\"40\">";
            foreach($csv_not_found as $line) echo htmlReady($line) . chr(10);
            echo "</textarea></td>\n";
            echo "<td width=\"20%\" class=\"table_row_even\" align=\"center\">&nbsp;";
            echo "\n</td></tr>\n";
        }
    }

    echo "</table>\n</form>";
} // end insert autor

?>
        </table>
        </td>
    </tr>
</table>

<?php
    if (get_config('EXPORT_ENABLE') AND $perm->have_studip_perm("tutor", $SessSemName[1])) {
        include_once($PATH_EXPORT . "/export_linking_func.inc.php");

        $infobox[1] = array(
                "eintrag" => array(
                    array(
                        'icon' => "icons/16/black/doc-text.png",
                        'text' => export_link($SessSemName[1], "person", _("TeilnehmerInnen") . ' '. $SessSemName[0], "rtf", "rtf-teiln", "",
                                  _("TeilnehmerInnen exportieren als rtf Dokument"), 'passthrough')
                    ),
                    array(
                        'icon' => 'icons/16/black/doc-office.png',
                        'text' => export_link($SessSemName[1], "person", _("TeilnehmerInnen") . ' '. $SessSemName[0], "csv", "csv-teiln", "",
                                  _("TeilnehmerInnen exportieren als csv Dokument"), 'passthrough')
                    )
                )
            );

        if ($awaiting) {
            $infobox[2] = array(
                "eintrag" => array(
                    array(
                        'icon' => "icons/16/blue/file-text.png",
                        'text' => export_link($SessSemName[1], "person", _("Warteliste") .' ' . $SessSemName[0], "rtf", "rtf-warteliste", "awaiting",
                                  _("Warteliste exportieren als rtf Dokument"), 'passthrough')
                    ),
                    array(
                        'icon' => 'icons/16/blue/file-xls.png',
                        'text' => export_link($SessSemName[1], "person", _("Warteliste") .' ' . $SessSemName[0], "csv", "csv-warteliste", "awaiting",
                                  _("Warteliste exportieren csv Dokument"), 'passthrough')
                    )
                )
            );
        }
        $layout = $GLOBALS['template_factory']->open('layouts/base.php');
        $layout->infobox = array('content' => $infobox, 'picture' => "infobox/groups.jpg");
    } else {
        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox.php');
    }

    $layout->content_for_layout = ob_get_clean();

    echo $layout->render();
    page_close();
?>
