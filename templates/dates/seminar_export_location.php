<?
// condense regular dates by room
if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
  if (is_array($cycle['assigned_rooms'])) foreach ($cycle['assigned_rooms'] as $room_id => $count) :
    $resObj = ResourceObject::Factory($room_id);
    $output[$resObj->getName()][] = $cycle['tostring_short'] .' ('. $count .'x)';
  endforeach;

  if (is_array($cycle['freetext_rooms'])) foreach ($cycle['freetext_rooms'] as $room => $count) :
    if ($room) :
      $output['('. $room .')'][] = $cycle['tostring_short']  .' ('. $count .'x)';
    endif;
  endforeach;

endforeach;


// condense irregular dates by room
if (is_array($dates['irregular'])) foreach ($dates['irregular'] as $cycle) :

endforeach;

if (sizeof($output) == 1) :
    echo array_pop(array_keys($output));
elseif (sizeof($output) > 0) :
    $pos = 1;
    foreach ($output as $room => $dates) :
        echo $room .': '. implode("\n", $dates) . (sizeof($output) > $pos ? ', ' : '') . "\n";
        $pos++;
    endforeach;
endif;
