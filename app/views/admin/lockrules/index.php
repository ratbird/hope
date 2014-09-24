<?php
# Lifter010: TODO
echo $message;
?>
    <table class="default">
    <caption>
        <?=_("Sperrebenen für den Bereich:")?>
        &nbsp;
        <?=$rule_type_names[$lock_rule_type];?>
    </caption>
    <thead>
        <tr>
            <th width="30%"><?= _('Name') ?></th>
            <th width="50%"><?= _('Beschreibung')?></th>
            <th width="20%"><?= _('Besitzer') ?></th>
            <th><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($lock_rules as $rule): ?>
        <tr>
        <td>
        <?=htmlReady($rule['name'])?>
        </td>
        <td width="30">
        <?=htmlReady(my_substr($rule['description'],0,100))?>
        </td>
        <td width="30">
        <?=htmlReady($rule['user_id'] ? get_fullname($rule['user_id']) : '')?>
        </td>
        <td class="actions">
        <a href="<?= $controller->url_for('admin/lockrules/edit/'.$rule['lock_id']) ?>">
            <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Diese Regel bearbeiten'))) ?>
        </a>
        <a href="<?= $controller->url_for('admin/lockrules/delete/'.$rule['lock_id']) ?>">
            <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Diese Regel löschen'))) ?>
        </a>
        </td>
    </tr>
    <? endforeach;?>
    </tbody>
    </table>
<?
$sidebar = Sidebar::Get();
$sidebar->setTitle(_('Sperrebenen'));
$sidebar->setImage('sidebar/lock-sidebar.png');

if ($GLOBALS['perm']->have_perm('root')) {
$actions = new ActionsWidget();
$actions->addLink(_('Neue Sperrebene anlegen'), $controller->url_for('admin/lockrules/new'), 'icons/16/blue/add.png');
$sidebar->addWidget($actions);
    $this->render_partial('admin/lockrules/_chooser.php');
}

