<li id="notification_<?= $notification['personal_notification_id'] ?>">
    <div class="mark_as_read">
        <a href=""><?= Assets::img("icons/16/blue/visibility-visible", array('title' => _("Als gelesen markieren"))) ?></a>
    </div>
    <a href="<?= URLHelper::getLink($notification['url']) ?>"><?= htmlReady($notification['text']) ?></a>
</li>