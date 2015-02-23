<div class="calendar-tooltip tooltip-content">
    <h4><?= htmlReady($event->getTitle()) ?></h4>
    <div>
        <b><?= _('Beginn') ?>:</b> <?= strftime('%c', $event->getStart()) ?>
    </div>
    <div>
        <b><?= _('Ende') ?>:</b> <?= strftime('%c', $event->getEnd()) ?>
    </div>
    <? if ($event instanceof CourseEvent) : ?>
    <div>
        <b><?= _('Veranstaltung') ?>:</b> <?= htmlReady($event->getSemName()) ?>
    </div>
    <? endif;?>
    <? if ($text = $event->getDescription()) : ?>
        <div>
            <b><?= _('Beschreibung') ?>:</b> <?= htmlReady(mila($text, 50)) ?>
        </div>
    <? endif; ?>
    <? if ($text = $event->toStringCategories()) : ?>
        <div>
            <b><?= _('Kategorie') ?>:</b> <?= htmlReady(mila($text, 50)) ?>
        </div>
    <? endif; ?>
    <? if ($text = $event->getLocation()) : ?>
        <div>
            <b><?= _('Raum/Ort') ?>:</b> <?= htmlReady(mila($text, 50)) ?>
        </div>
    <? endif; ?>
    <? if ($text = $event->toStringPriority()) : ?>
        <div>
            <b><?= _('Priorität') ?>:</b> <?= htmlReady(mila($text, 50)) ?>
        </div>
    <? endif; ?>
    <? if ($text = $event->toStringAccessibility()) : ?>
        <div>
            <b><?= _('Zugriff') ?>:</b> <?= htmlReady(mila($text, 50)) ?>
        </div>
    <? endif; ?>
    <? if ($text = $event->toStringRecurrence()) : ?>
        <div>
            <b><?= _('Wiederholung') ?>:</b> <?= htmlReady($text) ?>
        </div>
    <? endif; ?>
    <? if ($event instanceof CalendarEvent && get_config('CALENDAR_GROUP_ENABLE')) : ?>
        <div>
            <? $author = $event->getAuthor() ?>
            <? if ($author) : ?>
                <?= sprintf(_('Eingetragen am: %s von %s'),
                strftime('%x, %X', $event->mkdate),
                    htmlReady($author->getFullName('no_title'))) ?>
            <? endif; ?>
        </div>
        <? if ($event->mkdate < $event->chdate) : ?>
            <? $editor = $event->getEditor() ?>
            <? if ($editor) : ?>
            <div>
                <?= sprintf(_('Zuletzt bearbeitet am: %s von %s'),
                    strftime('%x, %X', $event->chdate),
                        htmlReady($editor->getFullName('no_title'))) ?>
            </div>
            <? endif; ?>
        <? endif; ?>
    <? endif; ?>
    <? if ($event instanceof CourseEvent) : ?>
        <? // related groups ?>
        <? $related_groups = $event->getRelatedGroups(); ?>
        <? if (sizeof($related_groups)) : ?>
        <div>
            <b><?= _('Betroffene Gruppen') ?>:</b>
            <?= htmlReady(implode(', ', array_map(function ($group) { return $group->name; }, $related_groups))) ?>
        </div>
        <? endif; ?>
    <? endif; ?>
</div>