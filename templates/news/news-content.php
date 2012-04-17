<? use Studip\Button, Studip\LinkButton; ?>
<table class="default">
    <tr>
        <td class="printcontent" width="22">&nbsp;</td>
        <td class="printcontent">
            <?= formatReady($content) ?><br>
        <? if ($admin_msg): ?>
            <br><i><?= htmlReady($admin_msg) ?></i>
        <? endif; ?>
    <? if ($news['allow_comments']): ?>
        <? if ($show_comments): ?>
            <a name="anker"></a>
            <table border="0" cellpadding="2" cellspacing="0" width="90%" align="center" style="margin-top:10px">
                <tr align="center">
                    <td>
                        <b><?= _('Kommentare') ?><b>
                    </td>
                </tr>
            <? foreach (StudipComments::GetCommentsForObject($news['news_id']) as $index => $comment): ?>
                <tr>
                    <td>
                        <?= $this->render_partial('news/comment-box', compact('index', 'comment')) ?>
                    </td>
                </tr>
            <? endforeach; ?>
            </table>
            <br>
            <form action="<?= URLHelper::getLink("#anker") ?>" method="POST">
                <?= CSRFProtection::tokenTag() ?>
                <input type="hidden" name="comsubmit" value="<?= $news['news_id'] ?>">
                <div align="center">
                    <textarea class="add_toolbar" name="comment_content" style="width:70%" rows="8" cols="38" wrap="virtual" placeholder="<?= _('Geben Sie hier Ihren Kommentar ein!') ?>"></textarea>
                    <br>
                    <?= Button::createAccept(_('Absenden')) ?>

                    <a href="<?= URLHelper::getLink('dispatch.php/smileys') ?>" target="_blank">
                        <?= _('Smileys') ?>
                    </a>
                    <a href="<?= format_help_url("Basis.VerschiedenesFormat") ?>" target="_blank">
                        <?= _('Formatierungshilfen') ?>
                    </a>
                    <br><br>
                </div>
            </form>
            <p></p>
        <? else: ?>
            <p align="center">
                <a href="<?= URLHelper::getLink('?comopen=' . $news['news_id'] . $unamelink . '&r=' . rand() . '#anker') ?>">
                    <?= sprintf(_('Kommentare lesen (%s) / Kommentar schreiben'), 
                                StudipComments::NumCommentsForObject($news['news_id'])) ?>
                </a>
            </p>            
        <? endif; ?>
    <? endif; ?>
        <? if ($may_edit): ?>
            <div align="center">
                <?= LinkButton::create(_('Bearbeiten'),
                                       URLHelper::getURL('admin_news.php?cmd=edit&edit_news='
                                                        . $news['news_id'] . '&' . $admin_link)) ?>
                <?= LinkButton::create(_('Aktualisieren'),
                                       URLHelper::getURL('?touch_news=' . $news['news_id'] . '#anker')) ?>
                
                <?= LinkButton::create(_('Löschen'),
                                       URLHelper::getURL('admin_news.php?cmd=kill&kill_news='
                                                        . $news['news_id'] . '&' . $admin_link)) ?>
            </div>
        <? endif; ?>
        </td>
    </tr>
</table>
