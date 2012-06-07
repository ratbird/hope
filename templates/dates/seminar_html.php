<? if (!$dates['regular']['turnus_data'] && (!sizeof($dates['irregular']))) : ?>
  <?= _("Die Zeiten der Veranstaltung stehen nicht fest."); ?>
<? else : ?>

  <?
  if (!isset($link)) $link = true;
  if (!isset($show_room)) :
    // show rooms only if there is more than one
    if (sizeof($dates['rooms']) <= 1) :
        $show_room = false;
    else :
        $show_room = true;
    endif;
  endif;

  $output = array();

  if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
    $first_date = sprintf(_("ab %s"), strftime('%x', $cycle['first_date']['date']));
    if ($cycle['cycle'] == 1) :
      $cycle_output = $cycle['tostring'] . ' ' . sprintf(_("(zweiwöchentlich, %s)"), $first_date);
    elseif ($cycle['cycle'] == 2) :
      $cycle_output = $cycle['tostring'] . ' ' .  sprintf(_("(dreiwöchentlich, %s)"), $first_date);
    else :
      $cycle_output = $cycle['tostring'] . ' (' . $first_date . ')';
    endif;
    if ($cycle['desc'])
      $cycle_output .= ', <i>'. htmlReady($cycle['desc']) .'</i>';

    if ($show_room) :
      $cycle_output .= $this->render_partial('dates/_seminar_rooms',
        array('assigned' => $cycle['assigned_rooms'],
          'freetext'     => $cycle['freetext_rooms'],
          'link'         => $link
        ));
    endif;

    $output[] = $cycle_output;
  endforeach;

  echo implode('<br>', $output);
  echo sizeof($output) ? '<br>' : '';

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
                $freetext_rooms['('. htmlReady($date['raum']) .')']++;
            endif;
        endif;
    endforeach;
    unset($irregular_rooms['']);

    $rooms = array_merge(getFormattedRooms($irregular_rooms, $link), array_keys($freetext_rooms));
    
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

  if ($link_to_dates) : ?>
    <br>
    <?= sprintf(_("Details zu allen Terminen im %sAblaufplan%s"),
      '<a href="'.URLHelper::getLink('seminar_main.php', array('auswahl' => $seminar_id, 'redirect_to' => 'dates.php')).'">', '</a>') ?><?
  endif;
endif;
