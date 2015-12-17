<? foreach ($group->members as $user): ?>
    <tr data-userid="<?= $user->user_id ?>">
        <td <?=($tutor ? 'class="dragHandle"' : '')?>></td>
        <td><?= $user->position + 1 ?></td>
        <td><?= $user->avatar() ?></td>
        <td><?= htmlReady($user->name()) ?></td>
        <td class="actions">
            <a href="<?= $controller->url_for("settings/statusgruppen/switch/{$group->id}/1?username={$user->user->username}") ?>">
                <?= Icon::create('edit', 'clickable', ['title' => _('Benutzer in dieser Rolle bearbeiten')])->asImg() ?>
            </a>
            <? if ($tutor) : ?>
                <a data-dialog="size=auto" href="<?= $controller->url_for("admin/statusgroups/delete/{$group->id}/{$user->user_id}") ?>">
                    <?= Icon::create('trash', 'clickable', ['title' => _('Person aus Gruppe austragen')])->asImg() ?>
                </a>
            <? endif ?>
        </td>
    </tr>
<? endforeach; ?>