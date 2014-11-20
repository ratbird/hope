<? use Studip\Button, Studip\LinkButton; ?>
<table cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="printcontent" width="22">&nbsp;</td>
        <td class="printcontent">
            <?= formatReady($content) ?><br>
        <? if ($admin_msg): ?>
            <br><i><?= htmlReady($admin_msg) ?></i>
        <? endif; ?>
    <? if ($news['allow_comments']): ?>
        <? if ($show_comments): ?>
            <br>
            <a name="anker"></a>
            <table class="default" style="margin-top:10px; width: 95%;">
                <tr class="nohover">
                    <td style="text-align: center" colspan="3">
                        <b><?= _('Kommentare') ?></b>
                        <? if ($may_edit): ?>
                            &nbsp;
                            <a href="<?=URLHelper::getLink('dispatch.php/news/edit_news/'.$news['news_id'].(count(StudipComment::GetCommentsForObject($news['news_id'])) ? '?news_comments_js=toggle&news_basic_js=toggle' : ''))?>"
                                       rel = "get_dialog" target = "_blank">
                                <?= Assets::img('icons/16/blue/admin.png', tooltip2(_('Bearbeiten'))) ?>
                            </a>
                        <? endif; ?>
                    </td>
                </tr>
            <? foreach (StudipComment::GetCommentsForObject($news['news_id']) as $index => $comment): ?>
                <?= $this->render_partial('news/comment-box', compact('index', 'comment')) ?>
            <? endforeach; ?>
            <tr>
                <td style="text-align: center" colspan="3">
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
                </td>
            </tr>
            </table>
            <p></p>
        <? else: ?>
            <p align="center">
                <a href="<?= URLHelper::getLink('?comopen=' . $news['news_id'] . $unamelink . '&r=' . rand() . '#anker') ?>">
                    <?= sprintf(_('Kommentare lesen (%s) / Kommentar schreiben'),
                                StudipComment::NumCommentsForObject($news['news_id'])) ?>
                </a>
            </p>
        <? endif; ?>
    <? endif; ?>
        <? if ($may_edit): ?>
            <div align="center">
            <?= LinkButton::create(_('Bearbeiten'),
                                       URLHelper::getURL('dispatch.php/news/edit_news/'.$news['news_id']),
                                       array('rel' => 'get_dialog', 'target' => '_blank')) ?>
            <? if ($may_unassign): ?>
                <?= LinkButton::create(_('Entfernen'),
                                       URLHelper::getURL('?nremove='.$news['news_id'].'#anker')) ?>
                            <? endif; ?>
            <? if ($may_delete): ?>
                <?= LinkButton::create(_('Löschen'),
                                       URLHelper::getURL('?ndelete='.$news['news_id'].'#anker')) ?>
            <? endif; ?>
            </div>
        <? endif; ?>
        </td>
    </tr>
</table>