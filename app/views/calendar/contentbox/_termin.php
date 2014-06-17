<article class="<?= ContentBoxHelper::classes($termin->id) ?>" id="<?= $termin->id ?>">
    <header>
        <nav>
            <span>
                <?= $termin->getLocation() ? _('Raum') . ': ' . htmlReady($termin->getLocation()) : '' ?>
            </span>
            <? if($admin): ?>
            <a href="<?= URLHelper::getLink("calendar.php", array('cmd' => 'edit', 'termin_id' => $termin->id, 'atime' => time(), 'source_page' => 'dispatch.php/profile')) ?>">
                <?= Assets::img('icons/16/blue/admin.png', array('class' => 'text-bottom')) ?>
            </a>
            <? endif; ?>
        </nav>
        <h1>
            <a href="<?= ContentBoxHelper::href($termin->id) ?>">
                <?= Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom')) ?>
                <?= htmlReady($termin->titel) ?>
            </a>
        </h1>
    </header>
    <p>
        <?= $termin->getDescription() ? : _('Keine Beschreibung vorhanden') ?>
    </p>
    <footer>
        <em><?= _('Kategorie') ?>: </em><?= htmlReady($termin->toStringCategories()) ?> 
        <em><?= _('Priorität') ?>: </em><?= htmlReady($termin->toStringPriority()) ?> 
        <em><?= _('Sichtbarkeit') ?>: </em><?= htmlReady($termin->toStringAccessibility()) ?> 
        <?= htmlReady($termin->toStringRecurrence()) ?>
    </footer>
</article>