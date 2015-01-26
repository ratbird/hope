<? $i = 0 ?>
<? foreach ($calendars as $calendar) : ?>
    <? if ($count_lists[$i][$aday]) : ?>
        <? 
        $html .= '<div>'
                . sprintf(ngettext('%s hat 1 Termin', '%s hat %s Termine',
                        count($count_lists[$i][$aday])),
                        $calendar->range_object->getFullname('no_title'),
                        count($count_lists[$i][$aday]))
                . '</div>';
        ?>
    <? endif; ?>
    <? $i++ ?>
<? endforeach; ?>
<? if ($html) : ?>
<div class="calendar-tooltip tooltip-content">
    <?= $html ?>
</div>
<? endif; ?>