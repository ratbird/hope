<? if (!$dates['regular']['turnus_data'] && (!sizeof($dates['irregular']))) : ?>
  <?= _("Die Zeiten der Veranstaltung stehen nicht fest."); ?>
<? else : ?>

  <?
  if (!isset($link)) $link = true; 
  if (!isset($show_room)) $show_room = true; 
  $output = array();

  if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
    if ($dates['regular']['turnus'] == 1) : 
      $cycle_output = $cycle['tostring'] . ' ' . _("(zweiwöchentlich)");
    else : 
      $cycle_output = $cycle['tostring'];
    endif;
    if ($cycle['desc']) 
      $cycle_output .= ', <i>'. htmlReady($cycle['desc']) .'</i>';

    if ($show_room) :
      $cycle_output .= $this->render_partial('dates/_seminar_rooms', 
        array('assigned' => $cycle['assigned_rooms'],
          'freetext' => $cycle['freetext_rooms'],
          'link'     => $link));
    endif;

    $output[] = $cycle_output;
  endforeach ?>
  <?= implode('<br>', $output); ?>
  <?= sizeof($output) ? '<br>' : '' ?>

  <? $presence_types = getPresenceTypes(); ?>
  <? if (is_array($dates['irregular'])):
    foreach ($dates['irregular'] as $date) :
        if (in_array($date['typ'], $presence_types) !== false) :
            $irregular[] = $date; $irregular_strings[] = $date['tostring']; $irregular_rooms[$date['resource_id']]++;
        endif;
    endforeach ?>
    <?= _("Termine am") ?> <?= implode('', shrink_dates($irregular));
    if (is_array($irregular_rooms) && sizeof($irregular_rooms) > 0) :
        $irregular_rooms = array_slice($irregular_rooms, sizeof($irregular_rooms) - 3, sizeof($irregular_rooms));
        ?><?= _(", Ort:") ?>
        <?= implode(', ', getFormattedRooms($irregular_rooms, $link)); ?>
    <? endif ?>
  <? endif ?>

  <? if ($link_to_dates) : ?>
    <br>
    <br>
    <?= sprintf(_("Details zu allen Terminen im %sAblaufplan%s"),
      '<a href="'.URLHelper::getLink('seminar_main.php?redirect_to=dates.php').'">', '</a>') ?>
  <? endif ?>
<? endif ?>
