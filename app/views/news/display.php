<?
/*
 * This should really be controller code
 */
// Check if user wrote a comment
if (Request::submitted('accept')) {
    CSRFProtection::verifySecurityToken();
    StudipComment::create(array(
        'object_id' => Request::get('comsubmit'),
        'user_id' => $GLOBALS['user']->id,
        'content' => Request::get('comment_content')
    ));
}

// Check if user wants to remove a announcement
if ($news_id = Request::get('remove_news')) {
    $news = new StudipNews($news_id);
    $range = Request::get('news_range');
    if ($news->havePermission('unassign', $range)) {
        if (Request::get('confirm')) {
        $news->deleteRange($range);
        $news->store();
        } else {
            $question = createQuestion(_('Ankündigung wirklich aus diesem Bereich entfernen?'), array('remove_news' => $news_id, 'news_range' => $range, 'confirm' => true));
        }
    }
}

// Check if user wants to delete an announcement
if ($news_id = Request::get('delete_news')) {
    $news = new StudipNews($news_id);
    if ($news->havePermission('delete')) {
        if (Request::get('confirm')) {
        $news->delete();
        } else {
            $question = createQuestion(_('Ankündigung wirklich löschen?'), array('delete_news' => $news_id, 'confirm' => true));
        }
    }
}

use Studip\Button;

$news = StudipNews::GetNewsByRange($range, true, true);
$perm = StudipNews::haveRangePermission('edit', $range);
$rss_id = get_config('NEWS_RSS_EXPORT_ENABLE') ? StudipNews::GetRssIdFromRangeId($range_id) : false;
?>

<?= $question ?>
<div class="content_box">
    <div class="head">
        <div class="actions">
            <a href="<?= URLHelper::getLink('dispatch.php/news/edit_news/new/' . $range); ?>" rel="get_dialog">
            <?= Assets::img('icons/16/blue/add.png'); ?>
            </a>
<? if ($rss_id): ?>
                <a href="rss.php?id=<?= $rss_id ?>">
                    <img src="<?= Assets::image_path('icons/16/blue/rss.png') ?>"
                <?= tooltip(_('RSS-Feed')) ?>>
                </a>
        <? endif; ?>      
        </div>
        <?= Assets::img('icons/16/black/news.png') ?>
<?= _('Ankündigungen') ?>
    </div>
    <div class="content">
<? foreach ($news as $new): ?>
            <div class="box">
                <div class="head">
                    <div class="actions">
    <?= $this->render_partial('news/_actions.php', array('new' => $new, 'range' => $range)) ?>
                    </div>
                    <a href="<?= URLHelper::getLink('', array('nopen' => $new->id)) ?>">
                        <? if ($new->id == Request::get('nopen')): ?>
                            <?= Assets::img('icons/16/blue/arr_1down.png'); ?>
                        <? else: ?>
                            <?= Assets::img('icons/16/blue/arr_1right.png'); ?>
                        <? endif; ?>
                        <?= Assets::img('icons/16/grey/news.png'); ?>
    <?= htmlReady($new['topic']); ?>
                    </a>
                </div>
                <div class="content" <?= $new->id == Request::get('nopen') ? "" : 'style="display: none;"' ?>>
                    <?= formatReady($new['body']) ?>
                        <? if ($new['allow_comments']): ?>
                        <div align="center">
        <? if (Request::get('comments')): ?>
                                <a name="anker"></a>
                                <b><?= _('Kommentare') ?></b>
                                <? foreach (StudipComments::GetCommentsForObject($new['news_id']) as $index => $comment): ?>
                                    <?= $this->render_partial('news/_commentbox', compact('index', 'comment')) ?>
                                    <? endforeach; ?>
                                <form action="<?= URLHelper::getLink("#anker") ?>" method="POST">
            <?= CSRFProtection::tokenTag() ?>
                                    <input type="hidden" name="comsubmit" value="<?= $new['news_id'] ?>">
                                    <div align="center">
                                        <textarea class="add_toolbar" name="comment_content" style="width:70%" rows="8" cols="38" wrap="virtual" placeholder="<?= _('Geben Sie hier Ihren Kommentar ein!') ?>"></textarea>
                                        <br>
            <?= Button::createAccept(_('Absenden')) ?>
                                    </div>
                                </form>

                                <? else: ?>
                                <a href="<?= URLHelper::getLink('', array('nopen' => $new['news_id'], 'comments' => 1)) ?>">
                                    <?= sprintf(_('Kommentare lesen (%s) / Kommentar schreiben'), StudipComments::NumCommentsForObject($new['news_id']))
                                    ?>
                                </a>       
                        <? endif; ?>
                        </div>
    <? endif; ?>
                </div>
            </div>
<? endforeach; ?>
    </div>
</div>