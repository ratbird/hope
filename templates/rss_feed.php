<table id="feed_<?= $id ?>" class="default">
    <tbody>
    <? foreach ($items as $item): ?>
        <tr class="<?= $cycle = TextHelper::cycle('hover_even', 'hover_odd') ?>">
            <td>
                <?= Assets::img(sprintf('icons/16/grey/link-%stern.png', $internal ? 'in' : 'ex'), array(
                        'class' => 'text-top',
                    )) ?>
                <a href="<?= $item['url'] ?>" <? if (!$internal) echo 'target="_blank"'; ?>>
                    <?= htmlReady($item['title']) ?>
                </a>
            <? if ($item['content']): ?>
                <br>
                <small><?= htmlReady($item['content']) ?></small>
            <? endif; ?>
            <? if ($item['attachment']): ?>
                <br>
                <small>
                    <a href="<?= $item['attachment']['url'] ?>" target="_blank">
                        <?= Assets::img('icons/16/grey/download', array('class' => 'text-bottom')) ?>
                        <?= basename($item['attachment']['url']) ?>
                    </a>
                    (<?= htmlReady($item['attachment']['type']) ?> - <?= $item['attachment']['length'] >> 10 ?> kb)
                </small>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td>
                Copyright &copy; <?= $domain ?>
            <? if ($url): ?>
                <br><?= formatReady($url, 1, 1) ?>
            <? endif; ?>
            </td>
        </tr>
    </tfoot>
</table>

<? if ($truncated): ?>
<div class="topic">
    <a href="<?= URLHelper::getLink('?more=' . $id . '#feed_' . $id) ?>" style="display: block;text-align: center;color:#fff;font-weight:bold;">
        <?= Assets::img('icons/16/white/arr_1down', array('class' => 'text-bottom')) ?>
        <?= sprintf(_('%u weitere Einträge...'), $truncated) ?>
    </a>
</div>
<? endif; ?>
