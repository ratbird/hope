<a class="tooltip">
<? if ($important): ?>
    <?= Assets::img('icons/16/grey/info-circle.png', array('class' => 'text-top')) ?>
<? else: ?>
    <?= Assets::img('icons/16/red/info-circle.png', array('class' => 'text-top')) ?>
<? endif; ?>
    <span><?= $text ?></span>
</a>