<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

require '../lib/bootstrap.php';

use Studip\Button, Studip\LinkButton;
unregister_globals();

$_GET['cancel_login'] = 1;
page_open(array('sess' => 'Seminar_Session', 'auth' => 'Seminar_Default_Auth', 'perm' => 'Seminar_Perm', 'user' => 'Seminar_User'));

function head($headline, $red=False) {
$class = '';
if($red)
    $class = 'write';
?>
<div align="center">
<table width="70%" border=0 cellpadding=0 cellspacing=0>
<tr><td class="topic<?=$class ?>" colspan=3 align="left">
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
    echo _('Sollten Sie keine E-Mail erhalten haben, können Sie sich einen neuen Aktivierungsschlüssel zuschicken lassen. Geben Sie dazu Ihre gewünschte E-Mail-Adresse unten an:');
    echo '<form action="activate_email.php" method="post">'
        . CSRFProtection::tokenTag()
        .'<input type="hidden" name="uid" value="'. htmlReady($_REQUEST['uid']) .'">'
        .'<table><tr><td>'. _('E-Mail:') .'</td><td><input type="email" name="email1"></td></tr>'
        .'<tr><td>'. _('Wiederholung:') . '</td><td><input type="email" name="email2"></td></tr></table>'
        .Button::createAccept(). '</form>';
}

function mail_explain() {
    echo _('Sie haben Ihre E-Mail-Adresse geändert. Um diese frei zu schalten müssen Sie den Ihnen an Ihre neue Adresse zugeschickten Aktivierungs Schlüssel im unten stehenden Eingabefeld eintragen.');
    echo '<br><form action="activate_email.php" method="post">'
        . CSRFProtection::tokenTag()
        .'<input type="text" name="key"><input name="uid" type="hidden" value="'.htmlReady($_REQUEST['uid']).'"><br>'
        .Button::createAccept(). '</form><br><br>';

}

if(!$_REQUEST['uid'])
    header("Location: index.php");

// set up user session
include 'lib/seminar_open.php';

// display header
PageLayout::setTitle(_('E-Mail Aktivierung'));
include 'lib/include/html_head.inc.php'; // Output of html head
include 'lib/include/header.php';

$uid = $_REQUEST['uid'];
if(isset($_REQUEST['key'])) {
    
    $db = DBManager::get();
    $sth = $db->prepare("SELECT validation_key FROM auth_user_md5 WHERE user_id=?");
    $sth->execute(array($uid));
    $result = $sth->fetch();
    $key = $result['validation_key'];
    if($_REQUEST['key'] == $key) {
        $sth = $db->prepare("UPDATE auth_user_md5 SET validation_key='' WHERE user_id=?");
        $sth->execute(array($uid));
        unset($_SESSION['half_logged_in']);
        head(PageLayout::getTitle());
        echo _('Ihre E-Mail-Adresse wurde erfolgreich geändert.');
        printf(' <a href="index.php">%s</a>', _('Zum Login'));
        footer();
    } else if ($key == '') {
        head(PageLayout::getTitle());
        echo _('Ihre E-Mail-Adresse ist bereits geändert.');
        printf(' <a href="index.php">%s</a>', _('Zum Login'));
        footer();
    } else {
        head(_('Warnung'), True);
        echo _("Falscher Bestätigungscode.");
        footer();

        head(PageLayout::getTitle());
        mail_explain();
        if($_SESSION['semi_logged_in'] == $_REQUEST['uid']) {
            reenter_mail();
        } else {
            printf(_('Sie können sich %seinloggen%s und sich den Bestätigungscode neu oder an eine andere E-Mail-Adresse schicken lassen.'),
                    '<a href="index.php?again=yes">', '</a>');
        }
        footer();
    }

// checking semi_logged_in is important to avoid abuse
} else if(isset($_REQUEST['email1']) && isset($_REQUEST['email2']) && $_SESSION['semi_logged_in'] == $_REQUEST['uid']) {
    if($_REQUEST['email1'] == $_REQUEST['email2']) {
        // change mail
        require_once('lib/edit_about.inc.php');

        $send = edit_email($_REQUEST['uid'], $_REQUEST['email1'], True);

        if($send[0]) {
            $_SESSION['semi_logged_in'] = False;
            head(PageLayout::getTitle());
            printf(_('An %s wurde ein Aktivierungslink geschickt.'), $_REQUEST['email1']);
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
        printf('<b>%s</b>', _('Die eingegebenen E-Mail-Adressen stimmen nicht überein. Bitte überprüfen Sie Ihre Eingabe.'));
        reenter_mail();
        footer();
    }
} else {
    // this never happens unless someone manipulates urls (or the presented link within the mail is broken)
    head(PageLayout::getTitle());
    echo _('Der Aktivierungsschlüssel, der übergeben wurde, ist nicht korrekt.');
    mail_explain();
    footer();
}
include 'lib/include/html_end.inc.php';
page_close();
?>
