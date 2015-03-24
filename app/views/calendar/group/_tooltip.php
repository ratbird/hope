<? $events = $events ?: $calendar->events ?>
<? if (sizeof($events)) : ?>
<div class="calendar-tooltip tooltip-content">
    <h4><?= htmlReady($calendar->range_object->getFullname('no_title')) ?></h4>
    <? foreach ($events as $event) : ?>
    <div>
        <? if (date('Ymd', $event->getStart()) == date('Ymd', $event->getStart())) : ?>
        <?= strftime('%x %X', $event->getStart()) . strftime(' - %X', $event->getStart()) ?>
        <? else : ?>
        <?= strftime('%x %X', $event->getStart()) . strftime(' - %x %X', $event->getStart()) ?>
        <? endif; ?>
    </div>
    <div>
        <?= htmlReady($event->getTitle()) ?>
    </div>
    <hr>
    <? endforeach; ?>
</div>
<? endif; ?>