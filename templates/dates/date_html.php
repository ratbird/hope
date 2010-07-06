<? if (!isset($link)) $link = true ?>
<?= $date->toString() ?>
<? if ($date->getResourceId()) : ?>
    <?= _(", Ort:") ?>
    <?= implode(', ', getFormattedRooms(array($date->getResourceId() => '1'), $link)); ?>
<? endif ?>
