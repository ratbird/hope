<? foreach ($entries as $area): ?>
<ul style="margin: 0;">
    <li data-id="<?= $area['topic_id'] ?>">
        <? if ($area['content_raw']) : ?>
        <a class="tooltip2">
            <?= Assets::img('icons/16/grey/info-circle.png', array('class' => 'text-top')) ?>
            <span><?= nl2br(htmlReady($area['content_raw'])) ?></span>
        </a>
        <? endif ?>

        <? if ($area['depth'] < 3) : ?>
        <a href="javascript:STUDIP.Forum.adminLoadChilds('<?= $area['topic_id'] ?>')"><?= htmlReady($area['name_raw']) ?></a>
        <? else : ?>
        <?= htmlReady($area['name_raw']) ?>
        <? endif ?>

        <a href="javascript:STUDIP.Forum.cut('<?= $area['topic_id'] ?>');" data-role="cut">
        <?= Assets::img('icons/16/blue/export.png') ?>
        </a>


        <a href="javascript:STUDIP.Forum.cancelCut('<?= $area['topic_id'] ?>');" data-role="cancel_cut" style="display: none">
        <?= Assets::img('icons/16/red/export.png') ?>
        </a>

        <a href="javascript:STUDIP.Forum.paste('<?= $area['topic_id'] ?>');" data-role="paste" style="display: none">
        <?= Assets::img('icons/16/yellow/arr_2left.png') ?>
        </a>
    </li>
</ul>
<? endforeach ?>