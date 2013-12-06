<?
use Studip\Button;
?>

<?= $question ?>
<div class="content_box">
    <div class="head">
        <div class="actions">
            <? if($perm): ?>
            <a href="<?= $controller->link_for('news/edit_news/new/' . $range); ?>" rel="get_dialog">
            <?= Assets::img('icons/16/blue/add.png'); ?>
            </a>
            <? endif; ?>
<? if ($rss_id): ?>
                <a href="<?= URLHelper::getLink('rss.php', array('id' => $rss_id)) ?>">
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
                    <a name="anker"></a>
                    <div class="actions">
    <?= $this->render_partial('news/_actions.php', array('new' => $new, 'range' => $range)) ?>
                    </div>
                    <a href="<?= $controller->link_for('#anker', array('nopen' => $new->id == Request::get('nopen') ? '' : $new->id)) ?>">
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
                                <b><?= _('Kommentare') ?></b>
                                <? foreach (StudipComments::GetCommentsForObject($new['news_id']) as $index => $comment): ?>
                                    <?= $this->render_partial('news/_commentbox', compact('index', 'comment')) ?>
                                    <? endforeach; ?>
                                <form action="<?= $controller->link_for('#anker', array('nopen' => $new['news_id'], 'comments' => 1)) ?>" method="POST">
            <?= CSRFProtection::tokenTag() ?>
                                    <input type="hidden" name="comsubmit" value="<?= $new['news_id'] ?>">
                                    <div align="center">
                                        <textarea class="add_toolbar" name="comment_content" style="width:70%" rows="8" cols="38" wrap="virtual" placeholder="<?= _('Geben Sie hier Ihren Kommentar ein!') ?>"></textarea>
                                        <br>
            <?= Button::createAccept(_('Absenden')) ?>
                                    </div>
                                </form>

                                <? else: ?>
                                <a href="<?= $controller->link_for('#anker', array('nopen' => $new['news_id'], 'comments' => 1)) ?>">
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