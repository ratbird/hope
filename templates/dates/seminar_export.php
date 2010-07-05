<? 
if (!isset($show_room)) $show_room = true;

if ($dates['regular']['turnus_data'] || sizeof($dates['irregular'])) :
  $output = array();
  if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
    if ($dates['regular']['turnus'] == 1) : 
      $cycle_output = $cycle['tostring_short'] . ' ' . _("(zweiwöchentlich)");
    else : 
      $cycle_output = $cycle['tostring_short'] . ' ' . _("(wöchentlich)");
    endif;
    if ($cycle['desc']) 
      $cycle_output .= ' - '. htmlReady($cycle['desc']);

    if ($show_room) :
        $cycle_output .= $this->render_partial('dates/_seminar_rooms', 
            array('assigned' => $cycle['assigned_rooms'], 'freetext' => $cycle['freetext_rooms']));
    endif;

    $output[] = $cycle_output;
  endforeach ?>
  <?= implode(", \n", $output); ?>
  <? $presence_types = getPresenceTypes(); ?>
  <? if (is_array($dates['irregular'])): ?>
      <? foreach ($dates['irregular'] as $date) : ?>
      <? if (in_array($date['typ'], $presence_types) !== false) : ?>
        <?  $irregular[] = $date; $irregular_strings[] = $date['tostring']; ?>
      <? endif ?>
    <? endforeach ?>
    <?= sizeof($output) ? ", \n" : '' ?>
    <?= _("Termine am")  ?> <?= implode('', shrink_dates($irregular)); ?>
  <? endif ?>
<? endif ?>
