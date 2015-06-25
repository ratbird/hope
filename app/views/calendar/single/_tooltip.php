<div class="calendar-tooltip tooltip-content">
    <h4><?= htmlReady($event->getTitle()) ?></h4>
    <div>
        <b><?= _('Beginn') ?>:</b> <?= strftime('%c', $event->getStart()) ?>
    </div>
    <div>
        <b><?= _('Ende') ?>:</b> <?= strftime('%c', $event->getEnd()) ?>
    </div>
    <? if ($event->havePermission(Event::PERMISSION_READABLE)) : ?>
        <? if ($event instanceof CourseEvent) : ?>
        <div>
            <b><?= _('Veranstaltung') ?>:</b> <?= htmlReady($event->course->getFullname()) ?>
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
    <? endif; ?>
    <? if ($text = $event->toStringRecurrence()) : ?>
        <div>
            <b><?= _('Wiederholung') ?>:</b> <?= htmlReady($text) ?>
        </div>
    <? endif; ?>
    <? if ($event->havePermission(Event::PERMISSION_READABLE)) : ?>
        <? if ($event instanceof CalendarEvent
                && get_config('CALENDAR_GROUP_ENABLE')) : ?>
            <? $attendees = $event->getAttendees() ?>
            <? $count_attendees = count(array_filter($attendees,
                function ($att) use ($calendar) {
                    return ($att->user->user_id != $calendar->getRangeId());
                })) ?>
            <? if ($count_attendees) : ?>
            <div>
                <b><?= _('Teilnehmer:') ?></b> <?= sizeof($attendees) ?>
            </div>
            <? endif; ?>
        <? endif; ?>
        <? if ($event instanceof CourseEvent) : ?>
            <? // durchführende Dozenten ?>
            <? $related_persons = $event->dozenten; ?>
            <? if (sizeof($related_persons)) : ?>
            <div>
                <b><?= ngettext('Durchführender Dozent', 'Durchführende Dozenten', sizeof($related_persons)) ?>:</b>
                <ul class="list-unstyled">
                <? foreach ($related_persons as $related_person) : ?>
                    <li>
                        <?= htmlReady($related_person->getFullName()) ?>
                    </li>
                <? endforeach; ?>
                </ul>
            </div>
            <? endif; ?>
            <? // related groups ?>
            <? $related_groups = $event->getRelatedGroups(); ?>
            <? if (sizeof($related_groups)) : ?>
            <div>
                <b><?= _('Betroffene Gruppen') ?>:</b>
                <?= htmlReady(implode(', ', $related_groups->pluck('name'))) ?>
            </div>
            <? endif; ?>
        <? endif; ?>
    <? endif; ?>
</div>