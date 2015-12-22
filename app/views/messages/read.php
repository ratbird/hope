<? if (!Request::isXhr()) : ?>
    <h1><?= _("Betreff").": ".htmlReady($message["subject"]) ?></h1>
<? endif ?>

<? if ($message["autor_id"] !== "____%system%____") : ?>
<div style="float:left; margin-right: 10px;"><?= Avatar::getAvatar($message["autor_id"])->getImageTag(Avatar::MEDIUM) ?></div>
<? endif ?>
<table id="message_metadata" data-message_id="<?= $message->getId() ?>">
    <tbody>
        <tr>
            <td><strong><?= _("Von") ?></strong></td>
            <td>
            <? if ($message['autor_id'] === '____%system%____'): ?>
                <?= _('Stud.IP') ?>
            <? else: ?>
                <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($message["autor_id"]))) ?>"><?= htmlReady(get_fullname($message["autor_id"])) ?></a>
            <? endif; ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("An") ?></strong></td>
            <td>
                <? if ($message["autor_id"] !== $GLOBALS["user"]->id) : ?>
                <? $num_recipients = $message->getNumRecipients() ?>
                <?= $num_recipients > 1 ? sprintf(_("%s Personen"), $num_recipients) : _("Eine Person") ?>
                <? else : ?>
                <ul class='clean' id="adressees">
                <? foreach ($message->getRecipients() as $message_user) : ?>
                    <li>
                        <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => $message_user["username"])) ?>">
                            <?= htmlReady($message_user['fullname']) ?>
                        </a>
                    </li>
                <? endforeach ?>
                </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Datum") ?></strong></td>
            <td><?= date("d.m.Y G:i", $message['mkdate']) ?></td>
        </tr>
        <tr>
            <td><strong><?= _("Schlagworte") ?></strong></td>
            <td>
                <form id="message-tags" action="<?= $controller->url_for('messages/tag/' . $message->id) ?>" method="post" data-dialog>
                <? foreach ($message->getTags() as $tag) : ?>
                    <span>
                        <a href="<?= URLHelper::getLink("?", array('tag' => $tag)) ?>" class="message-tag" title="<?= _("Alle Nachrichten zu diesem Schlagwort") ?>">
                            <?= htmlReady($tag) ?>
                        </a>
                        <?= Icon::create('trash', 'clickable', ['title' => _("Schlagwort entfernen")])->asInput(["class" => 'text-bottom', "name" => 'remove_tag', "value" => htmlReady($tag)]) ?>
                    </span>
                <? endforeach ?>
                    <span>
                        <input type="text" name="add_tag" style="width: 50px; opacity: 0.8;">
                        <?= Icon::create('add', 'clickable', ['title' => _("Schlagwort hinzuf�gen")])->asInput(["class" => 'text-bottom']) ?>
                    </span>
                </form>
            </td>
        </tr>
    </tbody>
</table>
<div class="clear"></div>

<div class="message_body">
    <?= formatReady($message["message"]) ?>
</div>
<? if (count($message->attachments)) : ?>
<h3><?= Icon::create('staple', 'inactive')->asImg(20, ["class" => "text-bottom"]) ?><?= _("Anhang") ?></h3>
<ul class="message_attachments">
    <? foreach ($message->attachments as $attachment) : ?>
    <li>
        <? $mime_type = get_mime_type($attachment['filename']) ?>
        <h4><a href="<?= GetDownloadLink($attachment->getId(), $attachment['filename'], 7, 'force') ?>"><?= GetFileIcon(substr($attachment['filename'], strrpos($attachment["filename"], ".") + 1))->asImg() ?><?= htmlReady($attachment['name']) ?></a></h4>
        <? if (substr($mime_type, 0, 5) === "image") : ?>
        <div><img src="<?= GetDownloadLink($attachment->getId(), $attachment['filename'], 7, 'normal') ?>" style="max-width: 400px;"></div>
        <? endif ?>
    </li>
    <? endforeach ?>
</ul>
<? endif ?>

<div align="center" data-dialog-button>
    <div class="button-group">
    <? if ($message['autor_id'] !== '____%system%____'): ?>
        <a href="<?= URLHelper::getLink("dispatch.php/messages/write", array('answer_to' => $message->getId())) ?>" data-dialog="buttons"><?= \Studip\Button::create(_("Antworten"))?></a>
        <a href="<?= URLHelper::getLink("dispatch.php/messages/write", array('answer_to' => $message->getId(), 'quote' => $message->getId())) ?>" data-dialog="buttons"><?= \Studip\Button::create(_("Zitieren"))?></a>
    <? endif; ?>
        <a href="<?= URLHelper::getLink("dispatch.php/messages/write", array('answer_to' => $message->getId(), 'forward' => "rec")) ?>" data-dialog="buttons"><?= \Studip\Button::create(_("Weiterleiten"))?></a>
    </div>
    <a href="<?= URLHelper::getLink("dispatch.php/messages/print/".$message->getId()) ?>" class="print_action"><?= \Studip\Button::create(_("Drucken"))?></a>
    <form action="<?= $controller->url_for('messages/delete/' . $message->id) ?>" method="post" style="display: inline;">
        <input type="hidden" name="studip-ticket" value="<?= get_ticket() ?>">
        <?= \Studip\Button::create(_("L�schen"), 'delete', array(
                'onClick' => 'return window.confirm("' . _('Nachricht wirklich l�schen?') . '");',
        ))?>
    </form>
</div>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/mail-sidebar.png');