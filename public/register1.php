<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
register1.php - Benutzerregistrierung in Stud.IP, Part I
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Oliver Brakel <obrakel@gwdg.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

PageLayout::setHelpKeyword("Basis.AnmeldungRegistrierung");
PageLayout::setTitle(_("Nutzungsbedingungen"));
// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

require_once('lib/msg.inc.php');

if (!$GLOBALS['ENABLE_SELF_REGISTRATION']){
    parse_window ("error§" . _("In dieser Installation ist die M&ouml;glichkeit zur Registrierung ausgeschaltet."), "§",
                _("Registrierung ausgeschaltet"),
                '<div style="margin:10px">'.$UNI_LOGIN_ADD . '</div>'
                ."<a href=\"index.php\"><b>&nbsp;" . sprintf(_("Hier%s geht es zur Startseite."), "</b></a>") . "<br>&nbsp;");
page_close();
die;
}
if ($auth->is_authenticated() && $user->id != "nobody") {
    parse_window ("error§" . _("Sie sind schon als BenutzerIn am System angemeldet!"), "§",
                _("Bereits angemeldet"),
                "<a href=\"index.php\"><b>&nbsp;" . sprintf(_("Hier%s geht es zur Startseite."), "</b></a>") . "<br>&nbsp;");
} else {
    $auth->logout();
?>

<table width="80%" align="center" border=0 cellpadding=0 cellspacing=0>
<tr><td class="topic"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/white/door-enter.png" border="0"><b>&nbsp;<?=_("Nutzungsbedingungen")?></b></td></tr>
<tr><td class="blank">
<blockquote><br><br>
<?=_("Stud.IP ist ein Open Source Projekt und steht unter der Gnu General Public License (GPL). Das System befindet sich in der st&auml;ndigen Weiterentwicklung.")?>

<? printf(_("F&uuml;r Vorschl&auml;ge und Kritik findet sich immer ein Ohr. Wenden Sie sich hierzu entweder an die %sStud.IP Crew%s oder direkt an die %sEntwickler%s."),"<a href=\"mailto:studip-users@lists.sourceforge.net\">", "</a>", "<a href=\"dispatch.php/siteinfo/show\">", "</a>")?>
<br><br>
<?=_("Um den vollen Funktionsumfang von Stud.IP nutzen zu k&ouml;nnen, m&uuml;ssen Sie sich am System anmelden.")?><br>
<?=_("Das hat viele Vorz&uuml;ge:")?><br>
<blockquote><li><?=_("Zugriff auf Ihre Daten von jedem internetf&auml;higen Rechner weltweit,")?>
<li><?=_("Anzeige neuer Mitteilungen oder Dateien seit Ihrem letzten Besuch,")?>
<li><?=_("Eine eigenes Profil im System,")?>
<li><?=_("die M&ouml;glichkeit anderen TeilnehmerInnen Nachrichten zu schicken oder mit ihnen zu chatten,")?>
<li><?=_("und vieles mehr.")?></li></blockquote><br>

<?=_("Mit der Anmeldung werden die nachfolgenden Nutzungsbedingungen akzeptiert:")?><br><br>

<?
include("locale/$_language_path/LC_HELP/pages/nutzung.html");
?>

<a href="register2.php"><b><?=_("Ich erkenne die Nutzungsbedingungen an")?></b></a><br>
<br>
<a href="index.php"><?=_("Abbruch")?></a><br>
</blockquote>
</td></tr>
<tr><td class="blank">&nbsp;</td></tr>
</table>
<?php
}
?>

<?php
include ('lib/include/html_end.inc.php');
page_close()

?>
