<?php
# Lifter010: TODO
$zoom = Request::get('zoom', 0);

$text  = _("Der Stundenplan zeigt die regelm‰ﬂigen Veranstaltungen dieser Einrichtung.");

if ($zoom) {
    $zoom_text = '<a href="'. UrlHelper::getLink('', array('zoom' => 0)) .'">'. _("Normalansicht") .'</a>';
} else {
    $zoom_text = '<a href="'. UrlHelper::getLink('', array('zoom' => 7)) .'">'. _("Groﬂansicht") .'</a>';
}

$infobox = array();
$infobox['picture'] = 'infobox/schedules.jpg';

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
    'text' => '<a href="'. $controller->url_for('calendar/instschedule/index/'. implode(',', $days) .'?printview=true') .'" target="_blank">'._("Druckansicht") .'</a>',
    'icon' => 'icons/16/black/print.png'
);

// Infobox-entries for viewport size
$infobox['content'][2]['eintrag'] = array (
    array (
        'icon' => 'icons/16/'. ($zoom == 0 ? 'red' : 'black') . '/schedule.png',
        'text' => '<a href="'. UrlHelper::getLink('', array('zoom' => 0)) .'">'. _("klein") .'</a>'
    ),
    array (
        'icon' => 'icons/16/'. ($zoom == 2 ? 'red' : 'black') . '/schedule.png',
        'text' => '<a href="'. UrlHelper::getLink('', array('zoom' => 2)) .'">'. _("mittel") .'</a>'
    ),
    array (
        'icon' => 'icons/16/'. ($zoom == 4 ? 'red' : 'black') . '/schedule.png',
        'text' => '<a href="'. UrlHelper::getLink('', array('zoom' => 4)) .'">'. _("groﬂ") .'</a>'
    )
);

$semester_chooser  = '<form method="post" action="'. $controller->url_for('calendar/instschedule') .'">';
$semester_chooser .= CSRFProtection::tokenTag();
$semester_chooser .= '<select name="semester_id">';
foreach (array_reverse($semesters) as $semester) :
    $semester_chooser .= '<option value="'. $semester['semester_id'] .'"';
    if ($current_semester['semester_id'] == $semester['semester_id']) :
        $semester_chooser .= ' selected="selected"';
    endif;
    $semester_chooser .= '>'. htmlReady($semester['name']) .'</option>';
endforeach;
$semester_chooser .= '</select> ';
$semester_chooser .= '<input type="image" src="'. Assets::image_path('icons/16/blue/accept.png') .'"></form>';

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
<?= $this->render_partial('calendar/schedule/_entry.php'); ?>
<?= $this->render_partial('calendar/schedule/_entry_details') ?>
