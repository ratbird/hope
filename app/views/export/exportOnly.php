<h2><?= _("Exportieren als:") ?></h2>
<? foreach($formats as $format): ?>
<a href="<?= $exportpath ?>?format=<?= $format ?>">
    <img src="<?= $iconpath ?>/<?= $format ?>" />
    <?= $format ?>
</a><br />
<? endforeach; ?>
