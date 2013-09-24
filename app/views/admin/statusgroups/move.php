<? foreach ($users->orderBy('position') as $user): ?>
    <tr id="<?= $user->user_id ?>">
        <td><?= $user->position + 1 ?></td>
        <td><?= $user->avatar() ?></td>
        <td><?= utf8_encode($user->name()) ?></td>
        <td style="text-align: right">
                <a class="delete" href="javascript: void()">
                    <?= Assets::img("icons/16/blue/trash.png") ?>
                </a>
        </td>
    </tr>
<? endforeach; ?>