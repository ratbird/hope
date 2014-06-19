<? use Studip\Button, Studip\LinkButton; ?>

<table class="default">
    <tr>
        <td class="printcontent" width="22">&nbsp;</td>
        <td class="printcontent">
            <?= formatReady($content) ?><br>
            <? if ($admin_msg): ?>
            <br><i><?= htmlReady($admin_msg) ?></i>
            <? endif; ?>
            <? if ($news_item['allow_comments']) : ?>
            <div id="show_comments_<?= $news_item['news_id'] . $widgetId ?>" style="display:none;">
                <a name="anker"></a>
                <table border="0" cellpadding="2" cellspacing="0" width="90%" align="center" id="commentstable<?= $widgetId ?>" style="margin-top:10px">
                    <tr align="center">
                        <td>
                            <b><?= _('Kommentare') ?></b>
                        </td>
                    </tr>
                        <? foreach (StudipComments::GetCommentsForObject($news_item['news_id']) as $index => $comment): ?>
                    <tr id="<?= $comment[4] ?>">
                        <td >
                                    <?= $this->render_partial('comment-box', compact('index', 'comment')) ?>
                        </td>
                    </tr>
                        <? endforeach; ?>
                </table>

                <br>
                <form data-url="<?= PluginEngine::getURL($plugin, array(),"comsubmit") ?>" onsubmit="return NEWSWIDGET.comsubmit('<?= $widgetId ?>');" id="comsubmit">
                        <?= CSRFProtection::tokenTag() ?>
                    <input type="hidden" name="id" value="<?= $news_item['news_id'] ?>">

                    <div align="center" class="toolbar">
                        <textarea   name="comment_content" class="add_toolbar" style="width:70%" rows="8" cols="38" wrap="virtual" placeholder="<?= _('Geben Sie hier Ihren Kommentar ein!') ?>"></textarea>
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
            </div>
            <p></p>
            <div id="show_no_comments_<?= $news_item['news_id'] . $widgetId?>" data-url="<?= htmlReady(PluginEngine::getURL($plugin, array(), '<%= action %>')) ?>" >
                <p align="center">
                        <?=  sprintf('<a href="#anker" onclick="NEWSWIDGET.comopenNews(\'%s\',\'%s\'); return false;" >',
                                $news_item['news_id'],$widgetId);  ?>

                        <?= sprintf(_('Kommentare lesen (%s) / Kommentar schreiben'),
                                StudipComments::NumCommentsForObject($news_item['news_id'])) ?>

                    </a>
                </p>
            </div>

                <? if ($may_edit) : ?>
            <div align="center">
                        <?= LinkButton::create(_('Bearbeiten'),
                                URLHelper::getURL('dispatch.php/news/edit_news/'
                                . $news_item['news_id'])) ?>


                        <?= LinkButton::create(_('L&ouml;schen'),
                                URLHelper::getURL('admin_news.php?cmd=kill&kill_news='
                                . $news_item['news_id'] . '&' . $admin_link)) ?>
            </div>
                    <? endif; ?>
                <? endif; ?>
        </td>
    </tr>
</table>
