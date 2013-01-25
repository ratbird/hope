<?php
/*
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

//Standard herstellen
if ($flash->flash['message']) {
    echo MessageBox::success($flash->flash['message']);
} else if ($flash->flash['error']) {
    echo MessageBox::error($flash->flash['error']);
}

$infobox = array(
    array(
        'kategorie' => _("Information"),
        'eintrag'   => array(
            array(
                'icon' => "icons/16/black/info",
                'text' => _("Sie können hier einzelne Inhaltselemente nachträglich aktivieren oder deaktivieren.")
            )
        )
    )
    
);
$infobox = array(
    'picture' => "infobox/modules.jpg",
    'content' => $infobox
);
?>

<form action="<?= URLHelper::getURL('dispatch.php/profilemodules/update', array('username' => $username)) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="zebra" width="70%" align="center" cellpadding="8" cellspacing="0" border="0" id="main_content">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th align="center"><?= _("Plugin") ?></th>
                <th align="center"><?= _("Aktiv") ?></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($this->controller->modules as $module): ?>
            <tr>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <label for="module_<?= $module['id'] ?>">
                        <?= _($module['name']) ?>
                    </label>
                    <div class="setting_info">
                        <?= _($module['description']) ?>
                    </div>
                </td>
                <td>
                    <input type="checkbox" name="module_<?= $module['id'] ?>" <?= $module['activated'] ? ' checked="checked"' : '' ?>>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" align="middle">
                    <?= makeButton("uebernehmen", "input", _("Änderungen übernehmen")) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>