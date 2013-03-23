<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

require '../lib/bootstrap.php';

use Studip\Button, Studip\LinkButton;

$_GET['cancel_login'] = 1;
page_open(array('sess' => 'Seminar_Session', 'auth' => 'Seminar_Default_Auth', 'perm' => 'Seminar_Perm', 'user' => 'Seminar_User'));

function head($headline, $red=False) {
$class = '';
if($red)
    $class = '_red';
?>
<div align="center">
<table width="70%" border=0 cellpadding=0 cellspacing=0>
<tr><td class="table_header_bold<?=$class ?>" colspan=3 align="left">
 <?=Assets::img('icons/16/white/mail.png', array('class' => 'text-top')) ?>
 <b>&nbsp;<?= $headline; ?></b>
</td></tr>
<tr><td style="background-color: #fff; padding: 1.5em;">
<?php
}

function footer() {
    echo '</td></tr></table></div> <br>';
}

function reenter_mail() {
    echo _('Sollten Sie keine E-Mail erhalten haben, k�nnen Sie sich einen neuen Aktivierungsschl�ssel zuschicken lassen. Geben Sie dazu Ihre gew�nschte E-Mail-Adresse unten an:');
    echo '<form action="activate_email.php" method="post">'
        . CSRFProtection::tokenTag()
        .'<input type="hidden" name="uid" value="'. htmlReady(Request::option('uid')) .'">'
        .'<table><tr><td>'. _('E-Mail:') .'</td><td><input type="email" name="email1"></td></tr>'
        .'<tr><td>'. _('Wiederholung:') . '</td><td><input type="email" name="email2"></td></tr></table>'
        .Button::createAccept(). '</form>';
}

function mail_explain() {
    echo _('Sie haben Ihre E-Mail-Adresse ge�ndert. Um diese frei zu schalten m�ssen Sie den Ihnen an Ihre neue Adresse zugeschickten Aktivierungs Schl�ssel im unten stehenden Eingabefeld eintragen.');
    echo '<br><form action="activate_email.php" method="post">'
        . CSRFProtection::tokenTag()
        .'<input type="text" name="key"><input name="uid" type="hidden" value="'.htmlReady(Request::option('uid')).'"><br>'
        .Button::createAccept(). '</form><br><br>';

}

if(!Request::option('uid'))
    header("Location: index.php");

// set up user session
include 'lib/seminar_open.php';

// display header
PageLayout::setTitle(_('E-Mail Aktivierung'));
include 'lib/include/html_head.inc.php'; // Output of html head
include 'lib/include/header.php';

$uid = Request::option('uid');
if(Request::get('key')) {
    
    $db = DBManager::get();
    $sth = $db->prepare("SELECT validation_key FROM auth_user_md5 WHERE user_id=?");
    $sth->execute(array($uid));
    $result = $sth->fetch();
    $key = $result['validation_key'];
    if(Request::quoted('key') == $key) {
        $sth = $db->prepare("UPDATE auth_user_md5 SET validation_key='' WHERE user_id=?");
        $sth->execute(array($uid));
        unset($_SESSION['half_logged_in']);
        head(PageLayout::getTitle());
        echo _('Ihre E-Mail-Adresse wurde erfolgreich ge�ndert.');
        printf(' <a href="index.php">%s</a>', _('Zum Login'));
        footer();
    } else if ($key == '') {
        head(PageLayout::getTitle());
        echo _('Ihre E-Mail-Adresse ist bereits ge�ndert.');
        printf(' <a href="index.php">%s</a>', _('Zum Login'));
        footer();
    } else {
        head(_('Warnung'), True);
        echo _("Falscher Best�tigungscode.");
        footer();

        head(PageLayout::getTitle());
        mail_explain();
        if($_SESSION['semi_logged_in'] == Request::option('uid')) {
            reenter_mail();
        } else {
            printf(_('Sie k�nnen sich %seinloggen%s und sich den Best�tigungscode neu oder an eine andere E-Mail-Adresse schicken lassen.'),
                    '<a href="index.php?again=yes">', '</a>');
        }
        footer();
    }

// checking semi_logged_in is important to avoid abuse
} else if(Request::get('email1') && Request::get('email2') && $_SESSION['semi_logged_in'] == Request::option('uid')) {
    if(Request::get('email1') == Request::get('email2')) {
        // change mail
        require_once('lib/edit_about.inc.php');

        $send = edit_email(Request::option('uid'), Request::quoted('email1'), True);

        if($send[0]) {
            $_SESSION['semi_logged_in'] = False;
            head(PageLayout::getTitle());
            printf(_('An %s wurde ein Aktivierungslink geschickt.'), Request::quoted('email1'));
            footer();
        } else {
            head(_('Fehler'), True);
            echo parse_msg($send[1]);
            footer();

            head(PageLayout::getTitle());
            reenter_mail();
            footer();
        }
    } else {
        head(PageLayout::getTitle());
        printf('<b>%s</b>', _('Die eingegebenen E-Mail-Adressen stimmen nicht �berein. Bitte �berpr�fen Sie Ihre Eingabe.'));
        reenter_mail();
        footer();
    }
} else {
    // this never happens unless someone manipulates urls (or the presented link within the mail is broken)
    head(PageLayout::getTitle());
    echo _('Der Aktivierungsschl�ssel, der �bergeben wurde, ist nicht korrekt.');
    mail_explain();
    footer();
}
include 'lib/include/html_end.inc.php';
page_close();
?>
