<style>
    .person, .dd {
        cursor: move;
    }
</style>

<? foreach ($path as $name => $p): ?>
    <input type="hidden" id="<?= $name ?>" value="<?= $p ?>" />
<? endforeach; ?>
<? if (!$unfolded): ?>
    <?= _('Es wurden noch keine Gruppen angelegt') ?>
<? endif; ?>
<? foreach ($unfolded as $group): ?>
    <table id="<?= $group->id ?>" class="default moveable dropable">
        <colgroup>
            <col width="1">
            <col width="10">
            <col>
            <col width="10%">
        </colgroup>
        <caption class="nodrag">
            <?= $numbers[$group->id] ?> <?= formatReady($group->name) ?>
            <? if ($type['needs_size']): ?>
                <?= $group->getPlaces() ?> 
            <? endif; ?>
            <? if ($tutor): ?>
                <? if ($type['needs_self_assign']): ?>
                    <? if ($group->selfassign): ?>
                        <?= Assets::img("icons/16/grey/lock-unlocked.png") ?>
                    <? else: ?> 
                        <?= Assets::img("icons/16/grey/lock-locked.png") ?>
                    <? endif; ?>
                <? endif; ?>
                <a class='modal' title="<?= _('Gruppe ändern') ?>" href="<?= $controller->url_for("admin/statusgroups/editGroup/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/admin.png") ?>
                </a>
            <? else: ?>
                <? if ($type['needs_self_assign']): ?>
                    <? if ($group->isMember() && $group->selfassign): ?>
                        <a href="<?= $group->path['leave'] ?>">
                            <?= Assets::img("icons/16/blue/door-leave.png") ?>
                        </a>
                    <? endif; ?>
                    <? if ($group->userMayJoin($user_id)): ?>
                        <a href="<?= $group->path['join'] ?>">
                            <?= Assets::img("icons/16/blue/door-enter.png") ?>
                        </a>
                    <? endif; ?>
                <? endif; ?>
            <? endif; ?>
        </caption>
        <thead class="nodrag">
            <tr>
                <th colspan="3"><?= _('Mitglieder') ?></th>
                <th style="text-align: right">
                    <!-- Gruppe leeren icon -->
                    <a href="<?= $controller->url_for('admin/statusgroups/truncate/' . $group->id) ?>">
                        <?= Assets::img("icons/16/blue/trash.png") ?>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?= $this->render_partial("admin/statusgroups/_members.php", array('group' => $group)) ?>
        </tbody>
        <tfoot>
        </tfoot>
    </table>
<? endforeach; ?>