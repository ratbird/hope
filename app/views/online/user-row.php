<tr>
    <td>
        <a href="<?= $controller->url_for('profile?username=' . $user['username']) ?>">
            <?= Avatar::getAvatar($user['user_id'], $user['username'])->getImageTag(Avatar::SMALL) ?>
        </a>
    </td>
    <td>
        <a href="<?= $controller->url_for('profile?username=' . $user['username']) ?>">
            <?= htmlReady($user['name']) ?>
        </a>
    <? foreach (StudipKing::is_king($user['user_id'], true) as $text) : ?>
        <?= Assets::img('icons/16/yellow/crown.png', tooltip2($text)) ?>
    <? endforeach ?>
    </td>
    <td style="white-space: nowrap;">
        <?= ucfirst(reltime(time() - $user['last_action'])) ?>
    </td>
    <td class="actions" nowrap="nowrap">
    <? if (class_exists("Blubber")) : ?>
        <a href="<?= URLHelper::getLink('plugins.php/blubber/streams/global', array('mention' => $user['username'])) ?>">
            <?= Assets::img('icons/16/blue/blubber.png', array('title' => _('Blubber diesen Nutzer an'))) ?>
        </a>
    <? endif ?>

        <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array('rec_uname' => $user['username'])) ?>">
            <?= Assets::img('icons/16/blue/mail.png', array('title' => _('Nachricht an Benutzer verschicken'))) ?>
        </a>
    <? if ($user['is_buddy']): ?>
        <a href="<?= $controller->url_for('online/buddy/remove?username=' . $user['username']) ?>">
            <?= Assets::img('icons/16/blue/remove/person.png', tooltip2(_('Aus der Buddy-Liste entfernen'))) ?>
        </a>
    <? else: ?>
        <a href="<?= $controller->url_for('online/buddy/add?username=' . $user['username']) ?>">
            <?= Assets::img('icons/16/blue/add/person.png', tooltip2(_('Zu den Buddies hinzufügen'))) ?>
        </a>
    <? endif; ?>
    </td>
</tr>
