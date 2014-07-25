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
                <?= count($message->receivers) > 1 ? sprintf(_("%s Personen"), count($message->receivers)) : _("Eine Person") ?>
                <? else : ?>
                <ul class='clean' id="adressees">
                <? $read = 0;?>
                <? foreach ($message->getRecipients() as $message_user) : ?>
                    <li>
                        <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => $message_user["username"])) ?>">
                            <?= Avatar::getAvatar($message_user["user_id"], $message_user["username"])->getImageTag(Avatar::SMALL, array('title' => ''))?>
                            <?= htmlReady($message_user->getFullname()) ?>
                        </a>
                    </li>
                    
                <? endforeach ?>
                </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Datum") ?></strong></td>
            <td><?= date("d.m.Y G.i", $message['mkdate']) ?></td>
        </tr>
        <tr>
            <td><strong><?= _("Tags") ?></strong></td>
            <td>
                <form id="message-tags" action="<?= $controller->url_for('messages/tag/' . $message->id) ?>" method="post" data-dialog>
                <? foreach ($message->getTags() as $tag) : ?>
                    <span>
                        <a href="<?= URLHelper::getLink("?", array('tag' => $tag)) ?>" class="message-tag">
                            <?= htmlReady($tag) ?>
                        </a>
                        <?= Assets::input('icons/16/blue/trash.png', array('class' => 'text-bottom', 'name' => 'remove_tag', 'value' => $tag)) ?>
                    </span>
                <? endforeach ?>
                    <span>
                        <input type="text" name="add_tag" style="width: 50px; opacity: 0.8;">
                        <?= Assets::input('icons/16/blue/add.png', array('class' => 'text-bottom')) ?>
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
<h3><?= Assets::img("icons/20/grey/staple", array("class" => "text-bottom")) ?><?= _("Anhang") ?></h3>
<ul class="message_attachments">
    <? foreach ($message->attachments as $attachment) : ?>
    <li>
        <? $mime_type = get_mime_type($attachment['filename']) ?>
        <h4><a href="<?= GetDownloadLink($attachment->getId(), $attachment['filename'], 7, 'force') ?>"><?= GetFileIcon(substr($attachment['filename'], strrpos($attachment["filename"], ".") + 1), true) ?><?= htmlReady($attachment['name']) ?></a></h4>
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
    <form action="?" method="post" style="display: inline;">
        <input type="hidden" name="delete_message" value="<?= $message->getId() ?>">
        <?= \Studip\Button::create(_("L�schen"))?>
    </form>
</div>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/mail-sidebar.png"));