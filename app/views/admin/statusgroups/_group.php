<a name="group-<?= $group->id ?>"></a>
<table id="<?= $group->id ?>" class="default movable">
    <colgroup>
        <col width="1">
        <col width="1">
        <col width="10">
        <col>
        <col width="10%">
    </colgroup>
    <caption>
        <?= formatReady($group->name) ?>
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
            <a data-dialog="size=auto" title="<?= _('Gruppe ändern') ?>" href="<?= $controller->url_for("admin/statusgroups/editGroup/{$group->id}") ?>">
                <?= Assets::img("icons/16/blue/edit.png", tooltip2(_('Gruppe ändern'))) ?>
            </a>
            <?= MultiPersonSearch::get("add_statusgroup" . $group->id)
                    ->setLinkText()
                    ->setDefaultSelectedUser($group->members->pluck('user_id'))
                    ->setTitle(_('MitgliederInnen hinzufügen'))
                    ->setExecuteURL($controller->url_for("admin/statusgroups/memberAdd/{$group->id}"))
                    ->setSearchObject($searchType)
                    ->addQuickfilter(_("aktuelle Einrichtung"), $membersOfInstitute)
                    ->render() ?>
            <a data-dialog="size=auto" title="<?= _('Gruppe löschen') ?>" href="<?= $controller->url_for("admin/statusgroups/deleteGroup/{$group->id}") ?>">
                <?= Assets::img("icons/16/blue/trash.png", tooltip2(_('Gruppe löschen'))) ?>
            </a>
            <a data-dialog="size=auto" title="<?= _('Gruppe alphabetisch sortieren') ?>" href="<?= $controller->url_for("admin/statusgroups/sortAlphabetic/{$group->id}") ?>">
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
            <th colspan="4">
                <?= sprintf(ngettext('%u Mitglied', '%u Mitglieder', count($group->members)),
                            count($group->members)) ?>
            </th>
            <th class="actions"></th>
        </tr>
    </thead>
    <tbody>
        <?= $this->render_partial('admin/statusgroups/_members.php', array('group' => $group)) ?>
    </tbody>
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
