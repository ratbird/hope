<? $user = new User($new['user_id']); ?>
<? if (Config::get()->NEWS_DISPLAY >= 1 || $new->havePermission('edit')): ?>
    <a class='news_user' href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $user->username) ?>">
        <?= htmlReady($user->getFullName()) ?>
    </a>
<? endif; ?>

<span class='news_date' title="<?= ($perm ? _("Ablaufdatum") . ': ' . date('d.m.Y', $new['date'] + $new['expire']) : '') ?>">
    <?= date('d.m.Y', $new['date']) ?>
</span>

<? if (Config::get()->NEWS_DISPLAY >= 2 || $new->havePermission('edit')): ?>
    <span title="<?= _('Aufrufe') ?>" class='news_visits' style="color: #050">
        <?= object_return_views($new['news_id']) ?>
    </span>
<? endif; ?>


<? if ($new->havePermission('edit')): ?>
    <a href=" <?= URLHelper::getLink('dispatch.php/news/edit_news/' . $new->id) ?>" rel='get_dialog' >
        <?= Assets::img('icons/16/blue/admin.png'); ?>
    </a>
    <? if ($new->havePermission('unassign', $range)): ?>
        <a href=" <?= URLHelper::getLink('', array('remove_news' => $new->id, 'news_range' => $range)) ?>" >
            <?= Assets::img('icons/16/blue/remove.png'); ?>
        </a>
    <? endif; ?>
    <? if ($new->havePermission('delete')): ?>
        <a href=" <?= URLHelper::getLink('', array('delete_news' => $new->id)) ?>" >
            <?= Assets::img('icons/16/blue/trash.png'); ?>
        </a>
    <? endif; ?>
<? endif; ?>

<?
if ($new['allow_comments']):
    $num = StudipComment::NumCommentsForObject($new['news_id']);
    $visited = object_get_visit($new['news_id'], 'news', false, false);
    $isnew = StudipComment::NumCommentsForObjectSinceLastVisit($new['news_id'], $visited, $GLOBALS['user']->id);
    ?>
<? if ($num): ?>
    <? if ($isnew): ?>
        <span class="news-comments-count news-comments-unread" title="<?= sprintf(_('%s neue(r) Kommentar(e)'), $isnew) ?>">
        <? else: ?>
            <span class="news-comments-count">
            <? endif; ?>
            <?= $num ?>
        </span>
    <? endif; ?>
<? endif; ?>
