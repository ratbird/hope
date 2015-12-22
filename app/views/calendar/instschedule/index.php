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
            array("text" => $text, "icon" => Icon::create('info', 'clickable'))
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
    'icon' => Icon::create('print', 'clickable')
);

// Infobox-entries for viewport size
$infobox['content'][2]['eintrag'] = array (
    array (
        'icon' => Icon::create('schedule', $zoom == 0 ? 'new' : 'info'),
        'text' => '<a href="'. URLHelper::getLink('', array('zoom' => 0)) .'">'. _("klein") .'</a>'
    ),
    array (
        'icon' => Icon::create('schedule', $zoom == 2 ? 'new' : 'info'),
        'text' => '<a href="'. URLHelper::getLink('', array('zoom' => 2)) .'">'. _("mittel") .'</a>'
    ),
    array (
        'icon' => Icon::create('schedule', $zoom == 4 ? 'new' : 'info'),
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
    'icon' => Icon::create('schedule', 'clickable')
);
?>
<div style="text-align: center; font-weight: bold; font-size: 1.2em">
    <?= htmlReady($GLOBALS['SessSemName']['header_line']) ?>  <?= _("im") ?>
    <?= htmlReady($current_semester['name']) ?>
</div>

<?= $calendar_view->render() ?>
