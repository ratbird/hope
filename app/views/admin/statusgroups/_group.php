<a name="group-<?= $group->id ?>"></a>
<table id="<?= $group->id ?>" class="default moveable" style="margin-top: 30px;">
    <colgroup>
        <col width="1">
        <col width="1">
        <col width="10">
        <col>
        <col width="10%">
    </colgroup>
    <caption>
        <?= $numbers[$group->id] ?> <?= formatReady($group->name) ?>
        <? if ($type['needs_size']): ?>
            <?= $group->getPlaces() ?> 
        <? endif; ?>
        <span class="actions">
            <? if ($tutor): ?>
                <? if ($type['needs_self_assign']): ?>
                    <? if ($group->selfassign): ?>
                        <?= Assets::img("icons/16/grey/lock-unlocked.png") ?>
                    <? else: ?> 
                        <?= Assets::img("icons/16/grey/lock-locked.png") ?>
                    <? endif; ?>
                <? endif; ?>
                <a class='modal' title="<?= _('Gruppe ändern') ?>" href="<?= $controller->url_for("admin/statusgroups/editGroup/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/edit.png", tooltip2(_('Gruppe ändern'))) ?>
                </a>
                <a class='modal' title="<?= _('Mitglieder hinzufügen') ?>" href="<?= $controller->url_for("admin/statusgroups/memberAdd/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/add/community.png", tooltip2(_('Mitglieder hinzufügen'))) ?>
                </a>
                <a class='modal' title="<?= _('Gruppe löschen') ?>" href="<?= $controller->url_for("admin/statusgroups/deleteGroup/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/trash.png", tooltip2(_('Gruppe löschen'))) ?>
                </a>
                <a class='modal' title="<?= _('Gruppe alphabetisch sortieren') ?>" href="<?= $controller->url_for("admin/statusgroups/sortAlphabetic/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/arr_2down.png", tooltip2(_('Gruppe alphabetisch sortieren'))) ?>
                </a>
            <? else: ?>
                <? if ($type['needs_self_assign']): ?>
                    <? if ($group->isMember() && $group->selfassign): ?>
                        <a href="<?= $group->path['leave'] ?>">
                            <?= Assets::img("icons/16/blue/door-leave.png", tooltip2(_('Gruppe verlassen'))) ?>
                        </a>
                    <? endif; ?>
                    <? if ($group->userMayJoin($user_id)): ?>
                        <a href="<?= $group->path['join'] ?>">
                            <?= Assets::img("icons/16/blue/door-enter.png", tooltip2(_('Gruppe beitreten'))) ?>
                        </a>
                    <? endif; ?>
                <? endif; ?>
            <? endif; ?>
        </span>
    </caption>
    <thead>
        <tr>
            <th colspan="4"><?= count($group->members) ?> <?= count($group->members) != 1 ? _('Mitglieder') : _('Mitglied'); ?></th>
            <th class="actions"></th>
        </tr>
    </thead>
    <tbody>
        <?= $this->render_partial("admin/statusgroups/_members.php", array('group' => $group)) ?>
    </tbody>
    <tfoot>
    </tfoot>
</table>
<? if ($group->children): ?>
    <ul class='tree-seperator'>
        <li>
            <? foreach ($group->children as $child): ?>
                <?= $this->render_partial('admin/statusgroups/_group.php', array('group' => $child)) ?>
            <? endforeach ?>
        </li>
    </ul>
<? endif; ?>
