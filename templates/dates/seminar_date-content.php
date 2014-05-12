<?php use Studip\Button, Studip\LinkButton;

$content = '';
if ($termin_item['Info']) {
    $content .= formatReady($termin_item['Info'], TRUE, FALSE) . "<br><br>";
} else {
    $content .= _("Keine Beschreibung vorhanden") . "<br><br>";
}
if($termin_item['kat'])
    $content .= "<b>" . _("Kategorie:") . "</b> " . ($termin_item['kat']);
if($termin_item['type'])
$content .= '<b>' . _("Art des Termins:") . '</b> ' . $termin_item['type'] . ', ';

if ($termin_item['sem']) {
    $content .= '<b>' . _("Seminar:") . '</b>' . htmlReady($termin_item['sem']) . '<br>';
}

if ($termin_item['raum']) {

    $content .= "<b>" . _("Raum:") . " </b>";
    $content .= $termin_item['raum']. ' </b>';
    $content .= "&nbsp; &nbsp; ";
}
if ($termin_item['ort']) {
    
    $content .= "<b>" . _("Ort:") . " </b>";
    $content .= $termin_item['ort']. ' </b>';
    $content .= "&nbsp; &nbsp; ";
}
if ($termin_item['pri']) {
    
    $content .= '<br><b>' . _("Priorit&auml;t:") . ' </b>'
            . $termin_item['pri']. ' </b>';
    $content .= "&nbsp; &nbsp; ";
}

if ($termin_item['sicht']) {
    
    $content .= '<b>' . _("Sichtbarkeit:") . ' </b>'
            . $termin_item['sicht']. ' </b>';
    $content .= "&nbsp; &nbsp; ";
}
if ($termin_item['res']) {
    $content .= '&nbsp; &nbsp;';
    $content .= '<br>' .$termin_item['res']. ' </b>';
    $content .= "&nbsp; &nbsp; ";
}

if($termin_item['autor_id']) {

    $content.="<b>" . _("angelegt von:") . "</b> ".get_fullname($termin_item['autor_id'],'full',true)."<br>";
}

if($termin_item['seminar_date'] instanceof SingleDate) {
    $content .= "<b>" . _("durchführende Dozenten:") . "</b> ";
    foreach ($termin_item['seminar_date']->getRelatedPersons() as $key => $dozent_id) {
        $key < 1 || ($content .= ", ");
        $content .= htmlReady(get_fullname($dozent_id));
    }
    $content .= "<br>";
    $gruppen = $termin_item['seminar_date']->getRelatedGroups();
    $content .= "<b>" . _("Betroffene Gruppen:") . "</b> ";
    foreach ($gruppen as $key => $statusgruppe_id) {
        $key < 1 || ($content .= ", ");
        $content .= htmlReady(Statusgruppen::find($statusgruppe_id)->name);
    }
    $content .= "<br>";
    if ($show_admin) {
        $content .= '<div style="text-align:center">';
        $content .= LinkButton::create(_('Bearbeiten'), URLHelper::getURL("raumzeit.php", array('cmd' => 'open','open_close_id' => $termin_item['termin_id'] . '#' . $termin_item['termin_id'])));
        if (!$termin_item['seminar_date']->isExTermin() && !LockRules::Check($range_id, 'cancelled_dates')) {
            $content .= LinkButton::create(_('Ausfallen lassen'), "javascript:STUDIP.CancelDatesDialog.initialize('".URLHelper::getScriptURL('dispatch.php/course/cancel_dates', array('termin_id' =>  $termin_item['termin_id']))."');");
        }
        $content .= '</div>';
    }
}
if($termin_item['edit'])
    $content .= "<br><div align=\"center\"> " . LinkButton::create(_('Bearbeiten'), URLHelper::getURL('calendar.php', array('cmd' => 'edit', 'termin_id' => $termin_item['termin_id'], 'atime' => $termin->date, 'source_page' => URLHelper::getURL()))) ."</div>";
echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr>";
printcontent(0,0, $content,false);

echo  "</tr></table> ";

?>
