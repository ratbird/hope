<?php
# Lifter010: TODO
$zoom = Request::get('zoom', 0);

$text  = _("Der Stundenplan zeigt die regelm‰ﬂigen Veranstaltungen dieser Einrichtung.");

if ($zoom) {
    $zoom_text = '<a href="'. URLHelper::getLink('', array('zoom' => 0)) .'">'. _("Normalansicht") .'</a>';
} else {
    $zoom_text = '<a href="'. URLHelper::getLink('', array('zoom' => 7)) .'">'. _("Groﬂansicht") .'</a>';
}

$infobox = array();
$infobox['picture'] = 'sidebar/schedule-sidebar.png';

$infobox['content'] = array(
    array(
        'kategorie' => _("Information:"),
        'eintrag'   => array(
            array("text" => $text, "icon" => "icons/16/black/info.png")
        )
    ),

    array(
        'kategorie' => _("Aktionen:")
    ),

    array(
        'kategorie' => _("Darstellungsgrˆﬂe:")
    )
);

$infobox['content'][1]['eintrag'][] = array (
    'text' => '<a href="'. $controller->url_for('calendar/instschedule/index/'. implode(',', $days) 
           . '?printview=true&semester_id=' . $current_semester['semester_id']) 
           . '" target="_blank">'._("Druckansicht") .'</a>',
    'icon' => 'icons/16/black/print.png'
);

// Infobox-entries for viewport size
$infobox['content'][2]['eintrag'] = array (
    array (
        'icon' => 'icons/16/'. ($zoom == 0 ? 'red' : 'black') . '/schedule.png',
        'text' => '<a href="'. URLHelper::getLink('', array('zoom' => 0)) .'">'. _("klein") .'</a>'
    ),
    array (
        'icon' => 'icons/16/'. ($zoom == 2 ? 'red' : 'black') . '/schedule.png',
        'text' => '<a href="'. URLHelper::getLink('', array('zoom' => 2)) .'">'. _("mittel") .'</a>'
    ),
    array (
        'icon' => 'icons/16/'. ($zoom == 4 ? 'red' : 'black') . '/schedule.png',
        'text' => '<a href="'. URLHelper::getLink('', array('zoom' => 4)) .'">'. _("groﬂ") .'</a>'
    )
);

$semester_chooser = $this->render_partial('calendar/schedule/_semester_chooser.php', array(
    'inst_mode' => true,
    'semesters' => array_reverse($semesters),
    'current_semester' => $current_semester
));

$infobox['content'][1]['eintrag'][] = array (
    'text' => $semester_chooser,
    'icon' => 'icons/16/black/schedule.png'
);
?>
<div style="text-align: center; font-weight: bold; font-size: 1.2em">
    <?= htmlReady($GLOBALS['SessSemName']['header_line']) ?>  <?= _("im") ?>
    <?= htmlReady($current_semester['name']) ?>
</div>

<?= $calendar_view->render() ?>
