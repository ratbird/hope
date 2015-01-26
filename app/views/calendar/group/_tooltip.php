<? $events = $events ?: $calendar->events ?>
<div class="calendar-tooltip tooltip-content">
    <h4><?= htmlReady($calendar->range_object->getFullname('no_title')) ?></h4>
    <? foreach ($events as $event) : ?>
    <div>
        <?= strftime('%X %x', $event->getStart()) . strftime(' - %X %x', $event->getEnd()) ?>
    </div>
    <div>
        <?= htmlReady($event->getTitle()) ?>
    </div>
    <hr>
    <? endforeach; ?>
</div>