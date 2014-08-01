<? if ($admin || $termine): ?>
<section class="contentbox">
    <header>
        <h1>
            <?= Assets::img('icons/16/black/schedule.png') ?>
            <?= htmlReady($title) ?>
        </h1>
        <nav>
    <? if ($admin): ?>
        <? if ($isProfile): ?>
        <a href="<?= URLHelper::getLink("calendar.php", array('cmd' => 'edit', 'termin_id' => $termin->id, 'source_page' => 'dispatch.php/profile')) ?>">
            <?= Assets::img('icons/16/blue/add.png', array('class' => 'text-bottom')) ?>
        </a>
        <? else: ?>
        <a href="<?= URLHelper::getLink("raumzeit.php", array('cid' => $range_id)) ?>">
            <?= Assets::img('icons/16/blue/admin.png', array('class' => 'text-bottom')) ?>
        </a>
        <? endif; ?>
    <? endif; ?>
        </nav>
    </header>
  <? if($termine): ?>

    <? foreach ($termine as $termin): ?>
        <?= $this->render_partial('calendar/contentbox/_termin.php', array('termin' => $termin)); ?>    
    <? endforeach; ?>
<? else: ?>
    <section>
    <? if($isProfile): ?>
        <?= _('Es sind keine aktuellen Termine vorhanden. Um neue Termine zu erstellen, klicken Sie rechts auf das Plus.') ?>
    <? else: ?>
        <?= _('Es sind keine aktuellen Termine vorhanden. Um neue Termine zu erstellen, klicken Sie rechts auf die Zahnräder.') ?>
    <? endif; ?>
    </section>
  <? endif; ?>
</section>
<? endif; ?>