<section class="contentbox">
    <header>
        <nav>
            <? if($admin): ?>
            <a href="<?= URLHelper::getLink("calendar.php", array('cmd' => 'edit', 'termin_id' => $termin->id, 'source_page' => 'dispatch.php/profile')) ?>">
                <?= Assets::img('icons/16/blue/add.png', array('class' => 'text-bottom')) ?>
            </a>
            <? endif; ?>
        </nav>
        <h1>
            <?= Assets::img('icons/16/black/schedule.png') ?>
            <? if($termine): ?>
            <?= sprintf(_("Termine für die Zeit vom %s bis zum %s"), strftime("%d. %B %Y", time()), strftime("%d. %B %Y", time() + 7 * 24 * 3600)) ?>
            <? else: ?>
            <?= _('Termine'); ?>
            <? endif; ?>
        </h1>
    </header>
    <? if($termine): ?>

    <? foreach ($termine as $termin): ?>
    <?= $this->render_partial('calendar/contentbox/_termin.php', array('termin' => $termin)); ?>    
    <? endforeach; ?>
    <? else: ?>
    <p>
        <?= _('Es sind keine aktuellen Termine vorhanden. Um neue Termine zu erstellen, klicken Sie rechts auf die Zahnräder.') ?>
    </p>
    <? endif; ?>
</section>