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


$actions = new ActionsWidget();
$actions->addLink(_('Neue Sperrebene anlegen'), $controller->url_for('admin/lockrules/new'), 'icons/16/blue/add.png');
$sidebar->addWidget($actions);
if ($GLOBALS['perm']->have_perm('root')) {
    $list    = new SelectWidget(_('Bereichsauswahl'), $controller->url_for('admin/lockrules'), 'lock_rule_type');
    foreach (array('sem' => _("Veranstaltung"), 'inst' => _("Einrichtung"), 'user' => _("Nutzer")) as $type => $desc) {
        $list->addElement(new SelectElement($type, $desc, Request::get('lock_rule_type') == $type), 'lock_rule_type-' . $type);
    }
    $sidebar->addWidget($list);
}

