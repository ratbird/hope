<style>
    .person {
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
    <div class="edit_dialog" id="edit_<?= $group->id ?>" title="<?= formatReady($group->name) ?> ">
        <form action="" id="form_<?= $group->id ?>" method="POST">
            <input type="hidden" name="id" value="<?= $group->id ?>">
            <label class="caption"><?= _('Gruppenname') ?>
                <input name="name" class="groupname" type="text" size="30" placeholder="<?= _('Mitarbeiter(in)') ?>" value="<?= formatReady($group->name) ?>" >
            </label>
            <label class="caption"><?= _('Weiblicher Name') ?>
                <input name="name_w" type="text" size="30" placeholder="<?= _('Mitarbeiterin') ?>" value="<?= formatReady($group->name_w) ?>" >
            </label>
            <label class="caption"><?= _('Männlicher Name') ?>
                <input name="name_m" type="text" size="30" placeholder="<?= _('Mitarbeiter') ?>" value="<?= formatReady($group->name_m) ?>" >
            </label>
            <label class="caption"><?= _('Größe') ?>
                <input name="size" type="text" size="30" placeholder="<?= _('Unbegrenzt') ?>" value="<?= formatReady($group->size) ?>" >
            </label>
            <label class="caption"><?= _('Selbsteintrag') ?>
                <input name="selfassign" type="checkbox" value="1" <?= $group->selfassign ? "CHECKED" : "" ?>>
            </label>
            <label class="caption"><?= _('Gruppe löschen') ?>
                <input name="delete" type="checkbox" value="1" >
            </label>
        </form>
    </div>


    <table id="<?= $group->id ?>" class="default moveable dropable">
        <colgroup>
            <col width="1">
            <col width="10">
            <col>
            <col width="10%">
        </colgroup>
        <caption class="nodrag">
            <?= $numbers[$group->id] ?> <?= formatReady($group->name) ?>
            <?= $group->getPlaces() ?> 
            <? if ($tutor): ?>
                <? if ($group->selfassign): ?>
                    <?= Assets::img("icons/16/grey/lock-unlocked.png") ?>
                <? else: ?> 
                    <?= Assets::img("icons/16/grey/lock-locked.png") ?>
                <? endif; ?>
                <a class="edit">
                    <?= Assets::img("icons/16/blue/admin.png") ?>

                </a>
            <? else: ?>
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
            <? foreach ($group->members->orderBy('position') as $user): ?>
                <tr class="drag" id="<?= $user->user_id ?>" style="<?= $group->isMember($user->user_id) ? "" : "color: #aaaaaa;" ?>">
                    <td><?= $user->position + 1 ?></td>
                    <td><?= $user->avatar() ?></td>
                    <td><?= $user->name() ?></td>
                    <td style="text-align: right">
                        <? if ($tutor): ?>
                            <a class="delete" href="javascript: void()">
                                <?= Assets::img("icons/16/blue/trash.png") ?>
                            </a>
                        <? endif; ?>
                    </td>
                </tr>
            <? endforeach; ?>
        </tbody>
        <tfoot>
        </tfoot>
    </table>
<? endforeach; ?>

<!-- Dialog for new groups -->

<div class="edit_dialog" id="edit_newgroup" title="<?= _('Neue Gruppe anlegen') ?>">
    <form action="" id="form_newgroup" method="POST">
        <input type="hidden" name="id" value="newgroup">
        <label class="caption"><?= _('Gruppenname') ?>
            <input name="name" class="groupname" type="text" size="30" placeholder="<?= _('Mitarbeiter(in)') ?>" >
        </label>
        <label class="caption"><?= _('Weiblicher Name') ?>
            <input name="name_w" type="text" size="30" placeholder="<?= _('Mitarbeiterin') ?>" >
        </label>
        <label class="caption"><?= _('Männlicher Name') ?>
            <input name="name_m" type="text" size="30" placeholder="<?= _('Mitarbeiter') ?>" >
        </label>
        <label class="caption"><?= _('Größe') ?>
            <input name="size" type="text" size="30" placeholder="<?= _('Unbegrenzt') ?>" >
        </label>
        <label class="caption"><?= _('Selbsteintrag') ?>
            <input name="selfassign" type="checkbox" >
        </label>
    </form>
</div>

<!-- Dialog for ordering -->

<div class="order_dialog" id="edit_order" title="<?= _('Gruppenreihenfolge ändern') ?>">
    <div class="dd">
        <? createLi($groups) ?>
    </div>
</div>

<?

function createLi($item) {
    echo '<ol class="dd-list">';
    foreach ($item as $group) {
        echo '<li class="dd-item" data-id="' . $group->id . '">
        <div class="dd-handle">' . formatReady($group->name) . '</div>';
        createLi($group->children);
        echo '</li>';
    }
    echo '</ol>';
}
?>