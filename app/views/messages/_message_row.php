        <tr id="message_<?= $message->getId() ?>" class="<?= $message->isRead() || $message['autor_id'] === $GLOBALS['user']->id ? "" : "unread" ?>">
            <td><?= count($message->attachments) ? Assets::img("icons/20/black/staple", array("title" => _("Mit Anhang"))) : "" ?></td>
            <td class="title"><a href="<?= URLHelper::getLink("dispatch.php/messages/read/".$message->getId()) ?>" data-lightbox="buttons=false"><?= htmlReady($message['subject']) ?></a></td>
            <td>
                <? if ($message['autor_id'] == "____%system%____") : ?>
                <?= _("Systemnachricht") ?>
                <? else : ?>
                <?= get_fullname($message['autor_id']) ?>
                <? endif ?>
            </td>
            <td><?= date("d.m.Y G.i", $message['mkdate']) ?></td>
            <td>
            <? foreach ($message->getTags() as $tag) : ?>
                <a href="<?= URLHelper::getLink("?", array('tag' => $tag)) ?>" class="tag"><?= Assets::img("icons/16/blue/star", array('class' => "text-bottom tag")).htmlReady(ucfirst($tag)) ?></a>
            <? endforeach ?>
            </td>
            <td>
                <form action="?" method="post" style="display: inline;">
                    <input type="hidden" name="delete_message" value="<?= $message->getId() ?>">
                    <button onClick="return window.confirm('<?= _("Nachricht wirklich löschen?") ?>');" style="background: none; border: none; cursor: pointer;"><?= Assets::img("icons/20/blue/trash") ?></button>
                </form>
            </td>
        </tr>