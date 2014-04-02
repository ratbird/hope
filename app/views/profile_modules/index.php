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

use Studip\Button, Studip\LinkButton;

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
                'text' => _("Sie können hier einzelne Inhaltselemente ".
                            "nachträglich aktivieren oder deaktivieren.")
            ),
            array(
                'icon' => "icons/16/black/plugin",
                'text' => _("Aktivierte Inhaltselemente fügen neue Funktionen ".
                            "zu Ihrem Profil oder Ihren Einstellungen hinzu.
                            Diese werden meist als neuer Reiter im Menü ".
                            "erscheinen.")
            ),
            array(
                'icon' => "icons/16/black/info",
                'text' => _("Wenn Sie bestimmte dieser Funktionalitäten nicht ".
                            "benötigen, können Sie sie einfach hier ".
                            "deaktivieren, die entsprechenden Menüpunkte ".
                            "werden dann ausgeblendet.")
            )
        )
    )

);
$infobox = array(
    'picture' => "sidebar/plugin-sidebar.png",
    'content' => $infobox
);
?>

<form action="<?= URLHelper::getURL('dispatch.php/profilemodules/update', array('username' => $username)) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default" width="70%" align="center" cellpadding="8" cellspacing="0" border="0" id="main_content">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th><?= _("Inhaltselement") ?></th>
                <th><?= _("Aktiv") ?></th>
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
                        <?= $module['description'] ? _($module['description']) : '' ?>
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
                    <?= Button::createAccept(_('Speichern'), 'submit') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>