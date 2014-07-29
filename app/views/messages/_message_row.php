<tr id="message_<?= $message->getId() ?>" class="<?= $message->isRead() || $message['autor_id'] === $GLOBALS['user']->id ? "" : "unread" ?>">
    <td><?= count($message->attachments) ? Assets::img("icons/20/black/staple", array("title" => _("Mit Anhang"))) : "" ?></td>
    <td class="title">
        <a href="<?= URLHelper::getLink("dispatch.php/messages/read/".$message->getId()) ?>" data-dialog>
            <?= $message['subject'] ? htmlReady($message['subject']) : htmlReady(mila($message['message'], 40)) ?>
        </a>
    </td>
    <td>
    <? if ($message['autor_id'] == "____%system%____") : ?>
        <?= _("Systemnachricht") ?>
    <? else : if(!$received): ?>
        <? if (count($message->receivers) > 1) : ?>
            <?= sprintf(_("%s Personen"), count($message->receivers)) ?>
        <? else : ?>
        <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' =>  get_username($message['autor_id']))) ?>">
            <?= htmlReady(get_fullname($message->receivers[0]['user_id'])) ?>
        </a>
        <? endif ?>
    <? else: ?>
        <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' =>  get_username($message['autor_id']))) ?>">
            <?= htmlReady(get_fullname($message['autor_id'])) ?>
        </a>
    <? endif; ?>
    <? endif; ?>
    </td>
    <td><?= date("d.m.Y G.i", $message['mkdate']) ?></td>
    <td class="tag-container">
    <? foreach ($message->getTags() as $tag) : ?>
        <a href="<?= URLHelper::getLink("?", array('tag' => $tag)) ?>" class="message-tag" title="<?= _("Alle Nachrichten zu diesem Schlagwort") ?>">
            <?= htmlReady(ucfirst($tag)) ?>
        </a>
    <? endforeach ?>
    </td>
    <td>
        <form action="?" method="post" style="display: inline;">
            <input type="hidden" name="delete_message" value="<?= $message->getId() ?>">
            <button onClick="return window.confirm('<?= _("Nachricht wirklich löschen?") ?>');" style="background: none; border: none; cursor: pointer;"><?= Assets::img("icons/20/blue/trash") ?></button>
        </form>
    </td>
</tr>