<? foreach ($group->members->orderBy('position') as $user): ?>
    <tr id="<?= $user->user_id ?>">
        <td><?= $user->position + 1 ?></td>
        <td><?= $user->avatar() ?></td>
        <td><?= $user->name() ?></td>
        <td style="text-align: right">
            <a class="datafields"href="<?= $controller->url_for("settings/statusgruppen/switch/{$group->id}/1?username={$user->user->username}") ?>">
                <?= Assets::img("icons/16/blue/edit.png", tooltip2(_('Benutzer in dieser Rolle bearbeiten'))) ?>
            </a>
            <a title="<?= _('Aus Gruppe entfernen') ?>" class="modal" href="<?= $controller->url_for("admin/statusgroups/delete/{$group->id}/{$user->user_id}") ?>">
                <?= Assets::img("icons/16/blue/trash.png", tooltip2(_('Benutzer aus Gruppe entfernen'))) ?>
            </a>
        </td>
    </tr>
<? endforeach; ?>