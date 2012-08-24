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
$cssSw = new cssClassSwitcher;

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

<form action="profilemodules/update" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table width="70%" align="center" cellpadding="8" cellspacing="0" border="0" id="main_content">
        <tr>
            <th width="50%" align="center"><?= _("Plugin") ?></th>
            <th align="center"><?= _("Aktiv") ?></th>
        </tr>
        <? foreach ($this->controller->modules as $id => $module) { ?>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <label for="module_<?= $id ?>">
                        <?= _($module['name']) ?></label>
                    <div class="setting_info">
                        <?= _($module['description']) ?>
                    </div>
                </td>
                <td <?= $cssSw->getFullClass() ?>>
                    <input type="checkbox" name="module_<?= $id ?>" <?= $module['activated'] ? ' checked="checked"' : '' ?>>
                </td>
            </tr>
            <?
        }
        ?>
        <tr <? $cssSw->switchClass() ?>>
        <td  <?= $cssSw->getFullClass() ?> colspan="2" align="middle">
            <input type="hidden" name="username" value="<?= get_username($this->controller->user_id); ?>"/>
            <?= makeButton("uebernehmen", "input", _("Änderungen übernehmen")) ?>&nbsp;
        </td>
        </tr>
    </table>
</form>