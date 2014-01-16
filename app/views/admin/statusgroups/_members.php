<? foreach ($group->members->orderBy('position') as $user): ?>
    <tr data-userid="<?= $user->user_id ?>">
        <td class="dragHandle"></td>
        <td><?= $user->position + 1 ?></td>
        <td><?= $user->avatar() ?></td>
        <td><?= $user->name() ?></td>
        <td class="actions">
            <a class="datafields" href="<?= $controller->url_for("settings/statusgruppen/switch/{$group->id}/1?username={$user->user->username}") ?>">
                <?= Assets::img("icons/16/blue/edit.png", tooltip2(_('Benutzer in dieser Rolle bearbeiten'))) ?>
            </a>
            <a title="<?= _('Aus Gruppe austragen') ?>" class="modal" href="<?= $controller->url_for("admin/statusgroups/delete/{$group->id}/{$user->user_id}") ?>">
                <?= Assets::img("icons/16/blue/trash.png", tooltip2(_('Person aus Gruppe austragen'))) ?>
            </a>
        </td>
    </tr>
<? endforeach; ?>