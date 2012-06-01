<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* request_new_password.php
*
* Handles password requests and sends a new password to the users email address.
*
*
* @author       Mike Barthel <m.barthel.goe@gmx.de>, Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @module       request_new_password.php
* @modulegroup  public
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// request_new_password.php
// Handles password requests and sends a new password to the users email address
//
// Copyright (C) 2007 Mike Barthel <m.barthel.goe@gmx.de>,
// Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
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


require '../lib/bootstrap.php';

unregister_globals();
page_open(array('sess' => 'Seminar_Session', 'auth' => 'Seminar_Default_Auth', 'perm' => 'Seminar_Perm', 'user' => 'Seminar_User'));
$step = Request::int('step');
// set up user session
include 'lib/seminar_open.php';

if (!($GLOBALS['ENABLE_REQUEST_NEW_PASSWORD_BY_USER'] && in_array('Standard', $GLOBALS['STUDIP_AUTH_PLUGIN']))) {
    require_once ('lib/msg.inc.php');
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    $message = _("Das Anfordern eines neuen Passwortes durch den Benutzer ist in dieser Stud.IP-Installation nicht möglich.");
    parse_window ("error§$message", "§", _("Passwortanforderung nicht möglich!"));
    include ('lib/include/html_end.inc.php');
    die();
}

require_once('lib/visual.inc.php');
require_once('config.inc.php');

require_once('lib/msg.inc.php');
include('lib/classes/UserManagement.class.php');


class UserManagementRequestNewPassword extends UserManagement {

    function UserManagementRequestNewPassword ($user_id) {
        parent::UserManagement($user_id);
    }

    function setPassword () {

        // Can we reach the email?
        if (!$this->checkMail($this->user_data['auth_user_md5.Email'])) {
            return FALSE;
        }

        $password = $this->generate_password(6);
        $this->user_data['auth_user_md5.password'] = md5($password);

        if (!$this->storeToDatabase()) {
            $this->msg .= "error§" . _("Die &Auml;nderung konnte nicht in die Datenbank geschrieben werden.") . "§";
            return FALSE;
        }

        $this->msg .= "msg§" . sprintf(_("Passwort von Benutzer \"%s\" neu gesetzt."), $this->user_data['auth_user_md5.username']) . "§";

        // include language-specific subject and mailbody
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']);
        include("locale/$user_language/LC_MAILS/password_mail.inc.php");

        // send mail
        StudipMail::sendMessage($this->user_data['auth_user_md5.Email'], $subject, $mailbody);

        log_event("USER_NEWPWD",$this->user_data['auth_user_md5.user_id']);
        return TRUE;

    }
}

$msg = array();
$email = '';
$admin_link = sprintf(_("Leider ist ein Fehler aufgetreten. Bitte fordern Sie gegebenenfalls %sper E-Mail%s ein neues Passwort an."), "<a href=\"mailto:{$GLOBALS['UNI_CONTACT']}?subject=" . rawurlencode( "Stud.IP Passwort vergessen - {$GLOBALS['UNI_NAME_CLEAN']}" ) . "&amp;body=" . rawurlencode( "Ich habe mein Passwort vergessen. Bitte senden Sie mir ein Neues.\nMein Nutzername: " . htmlReady( $uname ) . "\n" ) . "\">", "</a>");


/*
    ######################################################
    ### Formularauswertung: Eingabe der E-Mail-Adresse ###
    ######################################################
*/
if( $_POST['email'] != "" ) {
    $email = trim( stripslashes( $_POST['email'] ) );
    $validator = new email_validation_class();
    if( !$validator->ValidateEmailAddress( $email ) ) {
        // E-Mail ungültig
        $msg[] = array( 'error', _("Die E-Mail-Adresse ist ungültig!") . '<br>' );
    } else {
        // Suche Benutzer über E-Mail-Adresse
        $email = mysql_escape_string( $email );
        $db = DBManager::get();
        $stmt = $db->prepare("SELECT user_id, username, Vorname, Nachname, Email, IFNULL(auth_plugin, 'standard') AS auth_plugin FROM auth_user_md5 WHERE Email=?");
        $success = $stmt->execute(array($email));
        if(!$success || $stmt->rowCount() === 0) {
            // kein Benutzer mit eingegebener E-Mail
            $msg[] = array('error', _("Es konnte kein Benutzer mit dieser E-Mail-Adresse<br>gefunden werden!"));
            $msg[] = array('info', $admin_link);
        } elseif ($stmt->rowCount() === 1) {
            $row = $stmt->fetch();
            if (strtolower($row['auth_plugin']) != 'standard') {
                $msg[] = array('error', sprintf(_("Ihr Passwort kann nur durch einen Adminstrator ge&auml;ndert werden. Bitte fordern Sie gegebenenfalls %sper E-Mail%s ein neues Passwort an."), "<a href=\"mailto:{$GLOBALS['UNI_CONTACT']}?subject=" . rawurlencode( "Stud.IP Passwort vergessen - {$GLOBALS['UNI_NAME_CLEAN']}" ) . "&amp;body=" . rawurlencode( "Ich habe mein Passwort vergessen. Bitte senden Sie mir ein Neues.\nMein Nutzername: " . htmlReady( $uname ) . "\n" ) . "\">", "</a>"));
            } else {
                // Bestätigungslink senden
                $step = 2;
                $msg[] = array( 'info', sprintf(_("In Kürze wird Ihnen eine E-Mail an die Adresse %s mit einem Bestätigungslink geschickt. Bitte beachten Sie die Hinweise in dieser E-Mail. Sollten Sie keine E-Mail erhalten haben, vergewissern Sie sich, ob diese evtl. in einem Spam-Ordner abgelegt wurde."), $row['Email']));
                $username = $row['username'];
                $vorname  = $row['Vorname'];
                $nachname = $row['Nachname'];
                $id = md5($username . $GLOBALS['REQUEST_NEW_PASSWORD_SECRET']);

                // include language-specific subject and mailbody
                $user_language = getUserLanguagePath($row['user_id']);
                include("locale/$user_language/LC_MAILS/request_new_password_mail.inc.php");

                StudipMail::sendMessage($row['Email'], $subject, $mailbody);
            }
        } else {
            // Mehrere Benutzer für E-Mail
            $msg[] = array( 'error', _("Diese E-Mail-Adresse wird von mehreren Benutzern genutzt!"));
            $msg[] = array('info', $admin_link);
        }
    }
} else {
    // E-Mail leer
    if ($_POST['step']) {
        $msg[] = array('error', _("Sie haben keine E-Mail-Adresse eingegeben!"));
    }
}

/*
    #################################################
    ### Auswerten des Bestätigungslinks           ###
    #################################################
*/
if ($_GET['id'] != '') {
    $step = 4;
    if ($_GET['uname'] != '') {
        $username = trim($_GET['uname']);
        $username = mysql_escape_string($username);
        $db = DBManager::get();
        $stmt = $db->prepare("SELECT user_id FROM auth_user_md5 WHERE username=?");
        $success = $stmt->execute(array($username));
        if ($success && $stmt->rowCount() === 1 && trim($_GET['id']) == md5($username . $GLOBALS['REQUEST_NEW_PASSWORD_SECRET'])) {
            $row = $stmt->fetch();
            $user_management = new UserManagementRequestNewPassword($row['user_id']);
            if ($user_management->setPassword()) {
                $msg[] = array( 'msg', sprintf(_("Ihnen wird in Kürze eine E-Mail an die Adresse %s mit Ihrem neuen Passwort geschickt. Bitte beachten Sie die Hinweise in dieser E-Mail."), $user_management->user_data['auth_user_md5.Email']));
            } else {
                $msg[] = array( 'error', _("Das Passwort konnte nicht gesetzt werden. Bitte wiederholen Sie den Vorgang oder fordern Sie ein neues Passwort per E-Mail an."));
                $msg[] = array('info', $admin_link);
            }
        } else {
            $msg[] = array( 'error', _("Fehler beim Aufruf dieser Seite. Stellen Sie sicher, dass Sie den gesamten Link in die Adressleiste eingetragen haben. Bitte wiederholen Sie den Vorgang oder fordern Sie ein neues Passwort per E-Mail an."));
            $msg[] = array('info', $admin_link);
        }
    } else {
        $msg[] = array( 'error', _("Fehler beim Aufruf dieser Seite. Bitte wiederholen Sie den Vorgang oder fordern Sie ein neues Passwort per E-Mail an."));
        $msg[] = array('info', $admin_link);
    }
}

if (!$_POST['step'] && empty($step)) {
    $step = 1;
}

$request_template = $GLOBALS['template_factory']->open('request_password');
$request_template->set_attribute('step', intval($step));
$request_template->set_attribute('messages', $msg);
$request_template->set_attribute('link_startpage', sprintf(_("Zurück zur %sStartseite%s."), '<a href="./index.php?cancel_login=1">', '</a>'));
$request_template->set_attribute('email', $email);

PageLayout::setHelpKeyword('Basis.AnmeldungPasswortAnfrage');
$header_template = $GLOBALS['template_factory']->open('header');
$header_template->current_page = _('Passwort anfordern');

include('lib/include/html_head.inc.php');
echo $header_template->render();
echo $request_template->render();
include 'lib/include/html_end.inc.php';

page_close()
?>
