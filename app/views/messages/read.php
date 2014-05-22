<? if (!Request::isXhr()) : ?>
    <h1><?= _("Betreff").": ".htmlReady($message["subject"]) ?></h1>
<? endif ?>

<? if ($message["autor_id"] !== "____%system%____") : ?>
<div style="float:left; margin-right: 10px;"><?= Avatar::getAvatar($message["autor_id"])->getImageTag(Avatar::MEDIUM) ?></div>
<? endif ?>
<table id="message_metadata" data-message_id="<?= $message->getId() ?>">
    <tbody>
        <tr>
            <td><strong><?= _("Autor") ?></strong></td>
            <td>
            <? if ($message['autor_id'] === '____%system%____'): ?>
                <?= _('Stud.IP') ?>
            <? else: ?>
                <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($message["autor_id"]))) ?>"><?= htmlReady(get_fullname($message["autor_id"])) ?></a>
            <? endif; ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Adressaten") ?></strong></td>
            <td>
                <? if ($message["autor_id"] !== $GLOBALS["user"]->id) : ?>
                <?= count($message->getRecipients()) > 1 ? sprintf(_("%s Personen"), count($message->getRecipients())) : _("Eine Person") ?>
                <? else : ?>
                <ul class='clean'>
                <? foreach ($message->users->filter(function ($u) { return $u["snd_rec"] === "rec"; }) as $key => $message_user) : ?>
                    <li>
                        <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($message_user["user_id"]))) ?>">
                            <?= Avatar::getAvatar($message_user["user_id"])->getImageTag(Avatar::SMALL)?>
                            <?= htmlReady(get_fullname($message_user["user_id"])) ?>
                        </a>
                        <?= Assets::img("icons/16/grey/checkbox-".($message_user['readed'] ? "" : "un")."checked", array('class' => "text-bottom", "title" => ($message_user['readed'] ? _("Gelesen") : _("Noch ungelesen")))) ?>
                    </li>
                <? endforeach ?>
                </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Gelesen") ?></strong></td>
            <td>
                <? $read = 0;
                foreach ($message->users as $message_user) {
                    if ($message_user["snd_rec"] === "rec" && $message_user["readed"]) {
                        $read++;
                    }
                } ?>
                <?= sprintf(_("%s mal gelesen"), $read) ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Datum") ?></strong></td>
            <td><?= date("d.m.Y G.i", $message['mkdate']) ?></td>
        </tr>
        <tr>
            <td><strong><?= _("Tags") ?></strong></td>
            <td>
                <? foreach ($message->getTags() as $tag) : ?>
                    <span class="tag" data-tag="<?= htmlReady($tag) ?>">
                        <a href="<?= URLHelper::getLink("?", array('tag' => $tag)) ?>" class="tag"><?= Assets::img("icons/16/blue/star", array('class' => "text-bottom")).htmlReady($tag) ?></a><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom remove_tag")) ?>
                    </span>
                <? endforeach ?>
                <span>
                    <input type="text" name="new_tag" id="new_tag" style="width: 50px; opacity: 0.8;"><?= Assets::img("icons/16/blue/add", array('class' => "text-bottom add_new_tag")) ?>
                </span>
            </td>
        </tr>
    </tbody>
</table>
<div class="clear"></div>

<div class="message_body" style="font-size: 1.2em; margin: 3px; padding: 10px; background-color: #e7ebf1;">
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
        <a href="<?= URLHelper::getLink("dispatch.php/messages/write", array('answer_to' => $message->getId())) ?>"><?= \Studip\Button::create(_("Antworten"))?></a>
        <a href="<?= URLHelper::getLink("dispatch.php/messages/write", array('answer_to' => $message->getId(), 'quote' => $message->getId())) ?>"><?= \Studip\Button::create(_("Zitieren"))?></a>
    <? endif; ?>
        <a href="<?= URLHelper::getLink("dispatch.php/messages/write", array('answer_to' => $message->getId(), 'forward' => "rec")) ?>"><?= \Studip\Button::create(_("Weiterleiten"))?></a>
    </div>
    <a href="<?= URLHelper::getLink("dispatch.php/messages/print/".$message->getId()) ?>"><?= \Studip\Button::create(_("Drucken"))?></a>
    <form action="?" method="post" style="display: inline;">
        <input type="hidden" name="delete_message" value="<?= $message->getId() ?>">
        <?= \Studip\Button::create(_("Löschen"))?>
    </form>
</div>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/mail-sidebar.png"));