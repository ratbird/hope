<?php 
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
register2.php - Benutzerregistrierung in Stud.IP, Part II
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

require_once('lib/msg.inc.php');

$my_auth = ($GLOBALS['ENABLE_SELF_REGISTRATION'] ? "Seminar_Register_Auth" : "Seminar_Default_Auth");

page_open(array("sess" => "Seminar_Session", "auth" => $my_auth, "perm" => "Seminar_Perm", "user" => "Seminar_User"));

if (!$GLOBALS['ENABLE_SELF_REGISTRATION']){
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    parse_window ("error§" . _("In dieser Installation ist die M&ouml;glichkeit zur Registrierung ausgeschaltet."), "§",
                _("Registrierung ausgeschaltet"), 
                '<div style="margin:10px">'.$UNI_LOGIN_ADD.'</div>'
                ."<a href=\"index.php\"><b>&nbsp;" . sprintf(_("Hier%s geht es zur Startseite."), "</b></a>") . "<br>&nbsp;");
page_close();
die;
}
if ($auth->auth["uid"] == "nobody") {
    $auth->logout();
    header("Location: register2.php");
    page_close();
    die;
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

PageLayout::setHelpKeyword("Basis.AnmeldungRegistrierung");
PageLayout::setTitle(_("Registrierung erfolgreich"));
// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

?>
<table width ="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td class="topic"><b>&nbsp;<?=_("Herzlich Willkommen")?></b>
    </td>
</tr>

<tr>
    <td class="blank">&nbsp;
        <blockquote>
        <?=_("Ihre Registrierung wurde erfolgreich vorgenommen.")?><br><br>
        <?=_("Das System wird Ihnen zur Best&auml;tigung eine E-Mail zusenden.")?><br>
        <?=_("Bitte rufen Sie die E-Mail ab und folgen Sie den Anweisungen, um Schreibrechte im System zu bekommen.")?><br>
        <br>
        <? printf(_("%sHier%s geht es wieder zur Startseite."), "<a href=\"index.php\">", "</a>");?>
        <br><br>
        </blockquote>
    </td>
</tr>   
</table>

<?php 
include ('lib/include/html_end.inc.php');
page_close();

?>
