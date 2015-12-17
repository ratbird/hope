<li class="notification item" data-id="<?= $notification['personal_notification_id'] ?>" data-timestamp="<?= (int) $notification['mkdate'] ?>">
    <a class="options mark_as_read" href="#">
        <?= Icon::create('decline', 'clickable', ['title' => _("Als gelesen markieren")])->asImg(12) ?>
    </a>
    <a href="<?= URLHelper::getLink('dispatch.php/jsupdater/mark_notification_read/' . $notification['personal_notification_id']) ?>">
    <? if ($notification['avatar']): ?>
        <div class="avatar" style="background-image: url('<?= $notification['avatar'] ?>');"></div>
    <? endif; ?>
        <?= htmlReady($notification['text']) ?>
    </a>
<? if ($notification->more_unseen > 0): ?>
    <div class="more">
        <?= htmlReady(sprintf(_('... und %s weitere'), $notification->more_unseen)) ?>
    </div>
<? endif; ?>
</li>