<?
# Lifter001: TODO - in progress (session variables)
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
admin_search_form.inc.php - Suche fuer die Verwaltungsseiten von Stud.IP.
Copyright (C) 2001 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de

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

if (!Institute::findCurrent()) {
    ?>
    <form name="links_admin_search" action="<?=URLHelper::getLink(Request::path())?>" method="POST">
        <?= CSRFProtection::tokenTag() ?>
        <legend>
            <?=_("Bitte wählen Sie die Einrichtung aus, die Sie bearbeiten wollen:")?>
        </legend>
        <select name="cid">
         <option value=""><?=_("-- bitte Einrichtung auswählen --")?></option>
        <? foreach (Institute::getMyInstitutes($GLOBALS['user']->id) as $inst) : ?>
            <option value="<?=htmlReady($inst['Institut_id'])?>" <?=($inst['is_fak'] ? 'style="font-weight:bold"' : '')?>>
                <?=(!$inst['is_fak'] ? '&nbsp;&nbsp;' : '')?><?=htmlReady(my_substr($inst['Name'],0,80))?>
            </option>
        <? endforeach ?>
        </select>
        <?= Button::create(_('Einrichtung auswählen')) ?>
    </form>
    <?
    $template = $GLOBALS['template_factory']->open('layouts/base.php');
    $template->content_for_layout = ob_get_clean();
    echo $template->render();
    page_close();
    die;
}
