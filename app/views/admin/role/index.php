<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>

<? if ($delete_role): ?>
    <?= $GLOBALS['template_factory']->render('shared/question',
        array('question' => sprintf(_('Wollen Sie wirklich die Rolle "%s" l�schen?'), $roles[$delete_role]->getRolename()),
              'approvalLink' => $controller->url_for('admin/role/remove_role', $delete_role).'?ticket='.get_ticket(),
              'disapprovalLink' => $controller->url_for('admin/role'))) ?>
<? endif ?>

<table class="default">
<caption>
    <?= _('Vorhandene Rollen') ?>
</caption>
<thead>
    <tr>
        <th><?= _('Name') ?></th>
        <th style="text-align: right;"><?= _('Benutzer') ?></th>
        <th style="text-align: right;"><?= _('Plugins') ?></th>
        <th></th>
    </tr>
</thead>
<tbody>
    <? foreach ($roles as $role): ?>
        <? $role_id = $role->getRoleid() ?>
        <tr>
            <td>
                <a href="<?= $controller->url_for('admin/role/show_role', $role_id) ?>">
                    <?= htmlReady($role->getRolename()) ?>
                    <? if ($role->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                </a>
            </td>
            <td style="text-align: right;">
                <?= $stats[$role_id]['users'] ?>
            </td>
            <td style="text-align: right;">
                <?= $stats[$role_id]['plugins'] ?>
            </td>
            <td class="actions">
                <? if (!$role->getSystemtype()): ?>
                    <a href="<?= $controller->url_for('admin/role/ask_remove_role', $role_id) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Rolle l�schen'))) ?>
                    </a>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
</tbody>
</table>
