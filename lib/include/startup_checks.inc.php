<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* startup_checks.php
*
* checks if all requirements to create Veranstaltungen are set up. If evreything is fine, no output will be generated.
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @module       startup_checks.php
* @modulegroup      admin
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_modules.php
// ueberprueft, oba alle Voraussetzungen zum Anlegen von Veranstaltungen erf&uuml;llt sind. Wenn alles in Ordnung ist, wird keine Ausgabe erzeugt.
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

$perm->check("dozent");

require_once('lib/msg.inc.php');    //Ausgaben
require_once('lib/classes/StartupChecks.class.php');

$checks=new StartupChecks;
$list = $checks->getCheckList();

$problems_found = 0;

foreach ($list as $key=>$val) {
    if ($val){
        if (($checks->registered_checks[$key]["msg_fak_admin"]) && ($perm->is_fak_admin())) 
            $msgText = $checks->registered_checks[$key]["msg_fak_admin"]; 
        else {
            $msgText = $checks->registered_checks[$key]["msg"];
            $msgText .= ' <br><i> Aktion: '.formatReady("=)");
            $msgText .= '&nbsp;<a href="'.($checks->registered_checks[$key]["link_fak_admin"] && $perm->is_fak_admin() ? 
            $checks->registered_checks[$key]["link_fak_admin"] : $checks->registered_checks[$key]["link"]).'" > '.
            ($checks->registered_checks[$key]["link_name_fak_admin"] && $perm->is_fak_admin() ? 
            $checks->registered_checks[$key]["link_name_fak_admin"] : $checks->registered_checks[$key]["link_name"]).' </a></i>';
        }
        $problems[$problems_found] = $msgText;
        $problems_found++;
    }
}

if ($problems_found > 1)
    $moreProbs = " (Beachten Sie bitte die angegebene Reihenfolge!)";

if ($problems_found) {
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
             <td class="blank" colspan=2>
                <?= MessageBox::info(_("Das Anlegen einer Veranstaltung ist leider zu diesem Zeitpunkt noch nicht möglich, 
                da zunächst die folgenden Voraussetzungen geschaffen werden m&uuml;ssen.".$moreProbs), $problems); ?>
            </td>
        </tr>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" align="center" colspan=2>
                <?= LinkButton::create(_('Aktualisieren'), URLHelper::getURL(''))?>
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>&nbsp;</td>
        </tr>
    </table>
<?php
include ('lib/include/html_end.inc.php');
page_close();
die;
}
