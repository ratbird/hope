<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* terms.inc.php
*
* show terms on first login and check if user accept them
*
*
* @author       Zentrum VirtuOS, Osnabrueck
* @access       public
* @modulegroup      admission
* @module       admission.inc.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// terms.inc.php
// Zeigt die Nutzungsbedingungen und wartet, bis diese akzeptiert wurden
// Copyright (C) 2003 Zentrum VirtUOS Osnabrueck
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


function check_terms($userid, $_language_path) {

    if (Request::get('i_accept_the_terms') == "yes") {
        UserConfig::get($userid)->store('TERMS_ACCEPTED', 1);
        return;
    }

    if ($GLOBALS['auth']->auth['uid'] != 'nobody' && !empty($GLOBALS['user']) && !$GLOBALS['user']->cfg->getValue('TERMS_ACCEPTED'))
    {
?>

<table align="center" border="0" cellpadding="1" cellspacing="0">
    <tr>
        <td class="topic">
            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/white/door-enter.png" border="0" align="absmiddle"> <b><?=_("Nutzungsbedingungen")?></b>
        </td>
    </tr>
    <tr>
        <td class="blank">
        <p><br><?=_("Stud.IP ist ein Open Source Projekt und steht unter der Gnu General Public License (GPL). Das System befindet sich in der st&auml;ndigen Weiterentwicklung.")?></p>
        <p><?=_("Um den vollen Funktionsumfang von Stud.IP nutzen zu k&ouml;nnen, m&uuml;ssen Sie sich am System anmelden.")?><br>
        <?=_("Das hat viele Vorz&uuml;ge:")?></p>
        <ul>
            <li><?=_("Zugriff auf Ihre Daten von jedem internetf&auml;higen Rechner weltweit,")?></li>
            <li><?=_("Anzeige neuer Mitteilungen oder Dateien seit Ihrem letzten Besuch,")?></li>
            <li><?=_("Ein eigenes Profil im System,")?></li>
            <li><?=_("die M&ouml;glichkeit anderen TeilnehmerInnen Nachrichten zu schicken oder mit ihnen zu chatten,")?></li>
            <li><?=_("und vieles mehr.")?></li>
        </ul>
        <p><?=_("Mit der Anmeldung werden die nachfolgenden Nutzungsbedingungen akzeptiert:")?></p>
        <? include("locale/$_language_path/LC_HELP/pages/nutzung.html"); ?>
        <p align="center">
        <a href="index.php?i_accept_the_terms=yes"><b><?=_("Ich erkenne die Nutzungsbedingungen an")?></b></a>
        </p>
        <br>
        </td>
    </tr>
</table>

<?php
    include ('lib/include/html_end.inc.php');
    die;
    }
}
?>

