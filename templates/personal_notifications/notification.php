<li class="notification" data-id="<?= $notification['personal_notification_id'] ?>">
    <a class="mark_as_read" href="#">
        <?= Assets::img("icons/16/blue/visibility-visible", array('title' => _("Als gelesen markieren"))) ?>
    </a>
    <a href="<?= URLHelper::getLink($notification['url']) ?>">
    <? if ($notification['avatar']): ?>
        <img src="<?= $notification['avatar'] ?>" alt="">
    <? endif; ?>
        <?= htmlReady($notification['text']) ?>
    </a>
</li>