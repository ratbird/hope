<?
$turnus_list = array(
    0 => _("wöchentlich"),
    1 => _("zweiwöchentlich"),
    2 => _("dreiwöchentlich")
);

$output = array();

if (is_array($dates['regular']['turnus_data'])) foreach ($dates['regular']['turnus_data'] as $cycle) :
?>
<raumzeit>
    <datum><?= $turnus_list[$cycle['cycle']] ?></datum>
    <wochentag><?= getWeekDay($cycle['day']) ?></wochentag>
    <zeit><?= $cycle['start_hour'] ?>:<?= $cycle['start_minute'] ?>-<?= $cycle['end_hour'] ?>:<?= $cycle['end_minute'] ?></zeit>
    <raum>
        <gebucht><?= implode(', ', getFormattedRooms($cycle['assigned_rooms'], false)) ?></gebucht>
        <freitext><?= implode(', ', array_keys($cycle['freetext_rooms'])) ?></freitext>
    </raum>
</raumzeit>
<? endforeach ?>
<? $presence_types = getPresenceTypes(); ?>
<? if (is_array($dates['irregular'])) foreach ($dates['irregular'] as $date) : ?>
<raumzeit>
    <datum><?= date('d.m.Y', $date['start_time']) ?></datum>
    <wochentag><?= getWeekDay(date('d', $date['start_time'])) ?></wochentag>
    <zeit><?= date('H:i', $date['start_time']) ?>-<?= date('H:i', $date['end_time']) ?></zeit>
    <raum>
        <gebucht><?= implode(', ', getFormattedRooms(array($date['resource_id'] => 1), false)) ?></gebucht>
        <freitext><?= $date['raum'] ?></freitext>
    </raum>
</raumzeit>
<? endforeach ?>
