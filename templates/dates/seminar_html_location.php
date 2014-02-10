<?
if (!isset($link)) $link = true;

// condense regular dates by room
if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
    if (is_array($cycle['assigned_rooms'])) foreach ($cycle['assigned_rooms'] as $room_id => $count) :
        $resObj = ResourceObject::Factory($room_id);
        if ($link) {
            $output[$resObj->getFormattedLink(TRUE, TRUE, TRUE)][] = $cycle['tostring_short'] .' ('. $count .'x)';
        } else {
            $output[htmlReady($resObj->getName())][] = $cycle['tostring_short'] .' ('. $count .'x)';
        }
    endforeach;

    if (is_array($cycle['freetext_rooms'])) foreach ($cycle['freetext_rooms'] as $room => $count) :
        if ($room) :
            $output['('. htmlReady($room) .')'][] = $cycle['tostring_short']  .' ('. $count .'x)';
        endif;
    endforeach;
endforeach;


// condense irregular dates by room
if (is_array($dates['irregular'])) foreach ($dates['irregular'] as $date) :
    if ($date['resource_id']) :
        $output_dates[$date['resource_id']][] = $date;
    elseif ($date['raum']) :
        $output_dates[$date['raum']][] = $date;
    endif;
endforeach;

// now shrink the dates for each room/freetext and add them to the output
if (is_array($output_dates)) foreach ($output_dates as $dates) :
    if ($dates[0]['resource_id']) :
        $resObj = ResourceObject::Factory($dates[0]['resource_id']);
        if ($link) {
            $output[$resObj->getFormattedLink(TRUE, TRUE, TRUE)][] = implode('<br>', shrink_dates($dates));
        } else {
            $output[htmlReady($resObj->getName())][] = implode('<br>', shrink_dates($dates));
        }
    elseif ($dates[0]['raum']) :
        $output['('. htmlReady($dates[0]['raum']) .')'][] = implode('<br>', shrink_dates($dates));
    endif;
endforeach;
?>

<? if (sizeof($output) == 0) : ?>
    <?= htmlReady($ort) ?: _("nicht angegeben") ?>
<? elseif (sizeof($output) == 1) : ?>
    <?= array_pop(array_keys($output)) ?>
<? else: ?>
<table class="default">
  <? foreach ($output as $room => $dates) : ?>
  <tr>
    <td style="vertical-align: top"><?= $room ?></td>
    <td>
        <? $dates = implode('<br>', $dates)?>

        <? if(strlen($dates) > 222) : ?>
            <?= substr($dates, 0, 228)?>
            <div class="more-location-dates-infos" style="display:none">
                <?=$dates?>
            </div>
            <div>
                <span class='more-location-digits'>...</span>
                <a class="more-location-dates" style="cursor: pointer; margin-left: 3px">(mehr)</a>
            </div>
        <? else : ?>
            <?= $dates ?>
        <? endif ?>
    </td>
  <? endforeach ?>
</table>
<? endif ?>
