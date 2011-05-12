<?
if (!isset($show_room)) :
    // show rooms only if there is more than one
    if (sizeof($dates['rooms']) <= 1) :
        $show_room = false;
    else :
        $show_room = true;
    endif;
endif;

if ($dates['regular']['turnus_data'] || sizeof($dates['irregular'])) :
  $output = array();
  if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
    if ($cycle['cycle'] == 1) :
        $cycle_output = $cycle['tostring_short'] . ' ' . _("(zweiwöchentlich)");
    elseif ($cycle['cycle'] == 2) :
        $cycle_output = $cycle['tostring_short'] . ' ' . _("(dreiwöchentlich)");
    else :
      $cycle_output = $cycle['tostring_short'] . ' ' . _("(wöchentlich)");
    endif;
    if ($cycle['desc'])
      $cycle_output .= ' - '. $cycle['desc'];

    if ($show_room) :
        $cycle_output .= $this->render_partial('dates/_seminar_rooms',
            array(
                'assigned' => $cycle['assigned_rooms'],
                'freetext' => $cycle['freetext_rooms'],
                'plain'    => true)
        );
    endif;

    $output[] = $cycle_output;
  endforeach;

  echo implode(", \n", $output);

  $presence_types = getPresenceTypes();
  $freetext_rooms = array();

  if (is_array($dates['irregular'])):
    foreach ($dates['irregular'] as $date) :
        if (in_array($date['typ'], $presence_types) !== false) :
            $irregular[] = $date;
            $irregular_strings[] = $date['tostring'];
            if ($date['resource_id']) :
                $irregular_rooms[$date['resource_id']]++;
            elseif ($date['raum']) :
                $freetext_rooms['('. $date['raum'] .')']++;
            endif;
        endif;
    endforeach;
    unset($irregular_rooms['']);
    echo sizeof($output) ? ", \n" : '';

    $rooms = array_merge(getFormattedRooms($irregular_rooms, false), array_keys($freetext_rooms));

    if (is_array($irregular) && sizeof($irregular)) :
        echo _("Termine am") . implode(', ', shrink_dates($irregular));
        if (is_array($rooms) && sizeof($rooms) > 0) :
            if (sizeof($rooms) > 3) :
                $rooms = array_slice($rooms, sizeof($rooms) - 3, sizeof($rooms));
            endif;

            if ($show_room) :
                echo ', ' . _("Ort:") . ' ';
                echo implode(', ', $rooms);
            endif;
        endif;
    endif;
  endif;
endif;
