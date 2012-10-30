<? use Studip\Button, Studip\LinkButton;


$termin = new SingleDate($termin_item['termin_id']);
$content = '';
if ($termin_item['info']) {
    $content .= formatReady($termin_item['info'], TRUE, FALSE) ."blahcont". "<br><br>";
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
$persons = ($termin->getRelatedPersons());
if(($persons)) {
    $content .= "<b>" . _("durchführende Dozenten:") . "</b> ";
    foreach ($persons as $key => $dozent_id) {
        $key < 1 || ($content .= ", ");
        $content .= htmlReady(get_fullname($dozent_id));
    }
}
$content .= "<br>";
if ($show_admin && !$termin_item['edit'])
    $content .= "<br><div align=\"center\"> ". LinkButton::create(_('Bearbeiten'), URLHelper::getURL("raumzeit.php", array('cmd' => 'open','open_close_id' => $termin_item['termin_id'] . '#' . $termin_item['termin_id']))) . "</div>";
if($termin_item['edit'])
    $content .= "<br><div align=\"center\"> " . LinkButton::create(_('Bearbeiten'), URLHelper::getURL('calendar.php', array('cmd' => 'edit', 'termin_id' => $termin_item['termin_id'], 'atime' => $termin->date, 'source_page' => URLHelper::getURL()))) ."</div>";
echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr>";
printcontent(0,0, $content,false);

echo  "</tr></table> ";

?>