<article class="<?= ContentBoxHelper::classes($termin['id']) ?>" id="<?= $termin['id'] ?>">
    <header>
        <nav>
            <span>
                <?= $termin['room'] ? _('Raum') . ': ' . htmlReady($termin['room']) : '' ?>
            </span>
            <? if($admin && $isProfile): ?>
            <a href="<?= URLHelper::getLink("calendar.php", array('cmd' => 'edit', 'termin_id' => $termin['id'], 'atime' => time(), 'source_page' => 'dispatch.php/profile')) ?>">
                <?= Assets::img('icons/16/blue/admin.png', array('class' => 'text-bottom')) ?>
            </a>
            <? endif; ?>
        </nav>
        <h1>
            <a href="<?= ContentBoxHelper::href($termin['id']) ?>">
                <?= Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom')) ?>
                <?= htmlReady($termin['title']) ?>
            </a>
        </h1>
    </header>
    <p>
        <?= $termin['description'] ? : _('Keine Beschreibung vorhanden') ?>
    </p>
    <footer>
        <? foreach($termin['info'] as $type => $info): ?>
        <? if (!is_numeric($type)): ?>
            <em><?= htmlReady($type) ?>: </em>
        <? endif; ?>
        <?= htmlReady($info) ?> 
        <? endforeach; ?>
    </footer>
</article>