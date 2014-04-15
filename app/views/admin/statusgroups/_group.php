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
                <?
                $searchType = new SQLSearch("SELECT auth_user_md5.user_id, CONCAT(" . $GLOBALS['_fullname_sql']['full'] .
                ", \" (\", auth_user_md5.username, \")\") as fullname " .
                "FROM auth_user_md5 " .
                "LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                "WHERE (CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input " .
                "OR auth_user_md5.username LIKE :input) " .
                "AND auth_user_md5.perms IN ('autor', 'tutor', 'dozent') " .
                " AND auth_user_md5.visible <> 'never' " .
                "ORDER BY Vorname, Nachname", _("Teilnehmer suchen"), "username");
                
                $mp = MultiPersonSearch::get("add_statusgroup" . $group->id)
                        ->setLinkText()
                        ->setDefaultSelectedUser($group->members->pluck('user_id'))
                        ->setTitle(_('MitgliederInnen hinzufügen'))
                        ->setExecuteURL("admin/statusgroups/memberAdd/{$group->id}")
                        ->setSearchObject($searchType)
                        ->addQuickfilter(_("aktuelle Einrichtung"), $membersOfInstitute)
                        ->render();
                    //$this->addToInfobox(_('Aktionen'), $mp, 'icons/16/black/add/community.png');
                    print $mp;
                    ?>
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
