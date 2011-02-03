<?php
if (!$calendar_view || get_class($calendar_view) != 'CalendarWeekView') {
    throw new Exception('You need to pass a variable named $calendar_view, which holds an instance of CalendarView, to this template ('. __FILE__ .')!');
}

$entries = $calendar_view->getEntries();
$matrix  = $calendar_view->getMatrix();

list($start_hour, $end_hour) = $calendar_view->getRange();

$cell_height = $calendar_view->getHeight() + 2;
$cell_steps = $cell_height / 60;
?>

<script>
  STUDIP.Calendar.cell_height = <?= $cell_height ?>;
  STUDIP.Calendar.the_entry_content = '<?= str_replace("\n", '', $this->render_partial('calendar/empty_entry')) ?>';
  STUDIP.Calendar.start_hour = <?= $start_hour ?>;
    
  <? if (!$calendar_view->isReadOnly()) : ?>
  jQuery(window).load(function() {
    <? foreach ($days as $day) : ?> 
    jQuery('#day_<?= $day ?>').click(function(e) {
        STUDIP.Calendar.newEntry(e, <?= $day ?>); 
    });  
    <? endforeach ?>
  });
  <? endif ?>
</script>

<!-- show messages (if any) -->
<?= $this->render_partial($GLOBALS['template_factory']->open('shared/message_list'), array('messages' => $flash['messages'])); ?>

<!-- the view -->
<div id="schedule">
<div id="schedule_headings">
    <table style="width: 100%;" class="schedule_headings" cellspacing="0" cellpadding="0">
        <tr>
            <? foreach ($calendar_view->getColumns() as $number => $column) : ?>
            <td style="text-align: center; width: <?= floor(100 / sizeof($days)) ?>%">
                <a href="<?= URLHelper::getLink($column->getURL()) ?>">
                    <?= $column->getTitle() ?>
                </a>
                <? if (sizeof($calendar_view->getDays()) == 1) : ?>
                (<a href="<?= URLHelper::getLink($controller->url_for('calendar/'. $calendar_view->getContext())) ?>"><?= _("zurück zur Wochenansicht") ?></a>)
                <? endif ?>
            </td>
            <? endforeach; ?>
        </tr>
    </table>
</div>

<table id="schedule_data" style="width: 100%;" cellspacing="0" cellpadding="0">
    <tr height="1">
        <td style="width: 40px;">
        </td>
        <td colspan="7" style="padding: 0px">
            <!-- the lines separating the hours and half-hours -->
            <div style="position: relative">
                <div style="position: absolute; width: 100%;">
                    <? for ($i = $start_hour; $i <= $end_hour; $i++) : ?>
                    <div id="marker_<?= $i ?>" class="schedule_marker"></div>
                    <? endfor; ?>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td style="text-align: right; vertical-align: top; background-color: #E8EEF7; padding-right: 2px; padding: 0px;">
            <!-- the hours for the time-table -->
            <? for ($i = $start_hour; $i <= $end_hour; $i++) : ?>
            <div class="schedule_hours"><?= ($i < 10) ? '0'.$i : $i ?>:00</div>
            <? endfor; ?>
        </td>
        <? foreach ($calendar_view->getColumns() as $column) : ?>
        <td style="vertical-align: top">
            <!-- the days with the date for the timetable -->
            <div id="day_<?= $column->getId() ?>" class="schedule_day" style="overflow: hidden">
                <? if (!empty($entries['day_'. $column->getId()])) :
                $width = floor( 98 / sizeof($entries['day_'. $column->getId()])); $col = 0;
                foreach ($column->getGroupedEntries() as $grouped_entries) :

                    foreach ($grouped_entries as $entry) :
                        // if we have a grouped entry, use start- and end-time of any entry, the are all the same
                        if ($calendar_view->isGrouped()) $calc_entry = $entry[0]; else $calc_entry = $entry;

                        // move the box up, if the user has set a later start-hour for his schedule
                        $calc_entry['end'] -= $start_hour * 100;

                        // if the start is out of range of the displayed hours, clip the box
                        if ($calc_entry['start'] < $start_hour * 100) {
                            $calc_entry['start'] = 0;
                        } else {
                            $calc_entry['start'] -= $start_hour * 100;
                        }

                        // calculate the position and height of the entry
                        $top = floor($cell_height * floor($calc_entry['start'] / 100) 
                            + $cell_steps * floor($calc_entry['start'] % 100));
                        $bottom = floor($cell_height * floor($calc_entry['end'] / 100)
                            + $cell_steps * floor($calc_entry['end'] % 100)) - 1;

                        // how many concurring entries has this entry?
                        $max = 0;
                        for ($i = ($calc_entry['start'] + 1 + ($start_hour * 100)); $i < $calc_entry['end'] + ($start_hour * 100); $i++) {
                            $max = max($max, $matrix['day_'.$column->getId()][$i]);
                        }

                        // set height and width
                        $height = $bottom - $top;

                        $this->top    = $top;
                        $this->width  = $width;
                        $this->height = $height;
                        $this->entry  = $entry;
                        $this->col    = $col;

                        // if we have no concurring entries set the maximum useful width
                        if ($max == 1) $this->width = '98';

                        if ($calendar_view->getTemplate('entry')) :
                            echo $calendar_view->getTemplate('entry')->render();
                        else :
                            if ($calendar_view->isGrouped()) :
                                echo $this->render_partial('calendar/grouped_entry', array('controller' => $controller, 'context' => $calendar_view->getContext()));
                            else :
                                echo $this->render_partial('calendar/entry', array('controller' => $controller, 'context' => $calendar_view->getContext()));
                            endif;
                        endif;

                    endforeach; /* cycle thrugh entries  */
                    $col++; 

                endforeach; /* cycle through columns */
            endif; ?>
            </div>
        </td>
        <? endforeach; /* cycle through days*/ ?>
        </tr>
</table>
</div>

<!-- new entry and entry-edit-dialog -->
<? if (!$calendar_view->isReadOnly()) : ?>
    <? if ($calendar_view->getTemplate('newEntry')) : ?>
        <?= $calendar_view->getTemplate('newEntry')->render() ?>
    <? else : ?>
        <?= $this->render_partial('calendar/'. $calendar_view->getContext() .'/_entry') ?>
    <? endif ?>
<? endif ?>

<? if ($calendar_view->getTemplate('entryDetails')) : ?>
    <?= $calendar_view->getTemplate('entryDetails')->render() ?>
<? else : ?>
<?= $this->render_partial('calendar/'. $calendar_view->getContext() .'/_entry_details') ?>
<? endif ?>