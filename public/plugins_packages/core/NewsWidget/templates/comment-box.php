<table  role="article" style="border: 1px black solid;" cellpadding="3" cellspacing="0" width="100%">
    <tr style="background: #ffc;">
        <td align="left" style="border-bottom: 1px black dotted">
            #<?= $index + 1 ?> -
            <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $comment[2]) ?>">
                <?= htmlReady($comment[1]) ?>
            </a>
            <?= sprintf(_('hat am %s geschrieben:'), strftime('%x - %H:%M', $comment[3])) ?>
        </td>
        <td align="right" style="border-bottom: 1px black dotted">
        <? if ($show_admin): ?>
          <a   href="#" onclick="NEWSWIDGET.delNews('<?= PluginEngine::getURL('NewsWidget', array(), "comdel") ?>','<?= $comment[4] ?>', '<?=$news['news_id']?>'); return false;" >

                <?= Assets::img('icons/16/blue/trash.png', array('alt' => _('L�schen'))) ?>
            </a>
        <? endif; ?>
        </td>
    </tr>
    <tr style="background:#ffc;">
        <td colspan="2">
            <?= formatReady($comment[0]) ?>
        </td>
    </tr>
</table>
