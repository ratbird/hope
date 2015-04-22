<article class="<?= ContentBoxHelper::classes($termin['id']) ?>" id="<?= $termin['id'] ?>">
    <header>
        <h1>
            <a href="<?= ContentBoxHelper::href($termin['id']) ?>">
                <?= Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom')) ?>
                <?= htmlReady($termin['title']) ?>
            </a>
        </h1>
        <nav>
            <span>
                <?= $termin['room'] ? _('Raum') . ': ' . htmlReady($termin['room']) : '' ?>
            </span>
            <? if($admin && $isProfile && $termin['type'] === 'CalendarEvent'): ?>
            <a href="<?= URLHelper::getLink('dispatch.php/calendar/single/edit/' . $termin['range_id'] . '/' . $termin['event_id'], array('source_page' => 'dispatch.php/profile')) ?>">
                <?= Assets::img('icons/16/blue/admin.png', array('class' => 'text-bottom')) ?>
            </a>
            <? endif; ?>
        </nav>
    </header>
    <div>
        <? $themen = $termin['topics'] ?>
        <? if ($termin['description'] || count($themen)) : ?>
        <p><?= formatReady($termin['description']) ?></p>
        <? if (count($themen)) : ?>
            <? foreach ($themen as $thema) : ?>
                <h3>
                    <?= Assets::img("icons/grey/20/topic", array('class' => "text-bottom")) ?>
                    <?= htmlReady($thema['title']) ?>
                </h3>
                <div>
                    <?= formatReady($thema['description']) ?>
                </div>
            <? endforeach ?>
        <? endif ?>
        <? else : ?>
            <?= _('Keine Beschreibung vorhanden') ?>
        <? endif ?>
    </div>
    <footer>
        <? foreach($termin['info'] as $type => $info): ?>
        <? if (trim($info)) : ?>
            <? if (!is_numeric($type)): ?>
                <em><?= htmlReady($type) ?>: </em>
            <? endif; ?>
            <?= htmlReady(trim($info)) ?>
        <? endif ?>
        <? endforeach; ?>
    </footer>
</article>