<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>

<h3><?= _('Rollenverwaltung f�r Plugins') ?></h3>

<form action="<?= $controller->url_for('admin/role/assign_plugin_role') ?>" style="margin-bottom: 1em;" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <select name="pluginid" style="min-width: 300px;">
        <? foreach ($plugins as $plugin): ?>
            <option value="<?= $plugin['id'] ?>" <?= $plugin['id'] == $pluginid ? 'selected' : '' ?>>
                <?= htmlReady($plugin['name']) ?>
            </option>
        <? endforeach ?>
    </select>

    <?= Button::create(_('Ausw�hlen'), 'select', array('title' => _('Plugin ausw�hlen'))) ?>
</form>

<? if ($pluginid): ?>
    <form action="<?= $controller->url_for('admin/role/save_plugin_role', $pluginid) ?>" method="POST">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
        <table class="default nohover">
            <tr>
                <th style="text-align: center;"><?= _('Gegenw�rtig zugewiesene Rollen') ?></th>
                <th></th>
                <th><?= _('Verf�gbare Rollen') ?></th>
            </tr>
            <tr class="table_row_even">
                <td style="text-align: right;">
                    <select multiple name="assignedroles[]" size="10" style="width: 300px;">
                        <? foreach ($assigned as $assignedrole): ?>
                            <option value="<?= $assignedrole->getRoleid() ?>">
                                <?= htmlReady($assignedrole->getRolename()) ?>
                                <? if ($assignedrole->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
                <td style="text-align: center;">
                    <?= Icon::create('arr_2left', 'sort', ['title' => _('Markierte Rollen dem Plugin zuweisen')])->asInput(["type" => "image", "class" => "middle", "name" => "assign_role"]) ?>
                    <br>
                    <br>
                    <?= Icon::create('arr_2right', 'sort', ['title' => _('Markierte Rollen entfernen')])->asInput(["type" => "image", "class" => "middle", "name" => "remove_role"]) ?>
                </td>
                <td>
                    <select multiple name="rolesel[]" size="10" style="width: 300px;">
                        <? foreach ($roles as $role): ?>
                            <option value="<?= $role->getRoleid() ?>">
                                <?= htmlReady($role->getRolename()) ?>
                                <? if ($role->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>
<? endif ?>

