<?php
$zoom = Request::get('zoom', 0);

$text  = _("Der Stundenplan zeigt Ihre regelm‰ﬂigen Veranstaltungen dieses Semesters sowie von Ihnen selbst erstellte Belegungen.");
$text2 = sprintf( _("Um neue Veranstaltungen hinzuzuf¸gen, verwenden Sie die %sVeranstaltungssuche%s."),
        '<a href="'. UrlHelper::getLink('sem_portal.php') .'">', '</a>');
if ($zoom) {
    $zoom_text = '<a href="'. UrlHelper::getLink('', array('zoom' => 0)) .'">'. _("Normalansicht") .'</a>';
} else {
    $zoom_text = '<a href="'. UrlHelper::getLink('', array('zoom' => 7)) .'">'. _("Groﬂansicht") .'</a>';
}

if (!$show_hidden) {
    $hidden_text = '<a href="'. $controller->url_for('calendar/schedule/?show_hidden=true') .'">'. _("Ausgeblendete Veranstaltungen anzeigen") .'</a>';
} else {
    $hidden_text = '<a href="'. $controller->url_for('calendar/schedule') .'">'. _("Ausgeblendete Veranstaltungen verbergen") .'</a>';
}

$infobox = array();
$infobox['picture'] = 'schedule.jpg';

$infobox['content'] = array(
    array(
        'kategorie' => _("Information:"),
        'eintrag'   => array(
            array("text" => $text, "icon" => "ausruf_small2.gif"),
            array("text" => $text2, "icon" => "ausruf_small2.gif")
        )
    ),
    
    array(
        'kategorie' => _("Aktionen:")
    )
);

if (!$inst_mode) :
    $infobox['content'][1]['eintrag'][] = array (
        'text' => '<a href="'. $controller->url_for('calendar/schedule/entry') .'">'._("Neuer Eintrag") .'</a>',
        'icon' => 'link_intern.gif'
    );
endif;

$infobox['content'][1]['eintrag'][] = array (
    'text' => '<a href="'. $controller->url_for('calendar/schedule/index/'. implode(',', $days) 
           .  '?printview=true' . (Request::get('show_hidden') ? '&show_hidden=true' : '')) 
           .  '" target="_blank">'._("Druckansicht") .'</a>',
    'icon' => 'link_intern.gif'
);

$infobox['content'][1]['eintrag'][] = array (
    'text' => '<a href="'. $controller->url_for('calendar/schedule/index?show_settings=true') .'">'. _("Einstellungen ‰ndern") .'</a>',
    'icon' => 'link_intern.gif'
);

$infobox['content'][1]['eintrag'][] = array (
    'text' => $hidden_text,
    'icon' => 'link_intern.gif'
);

?>
<div style="text-align: center; font-weight: bold; font-size: 1.2em">
    <?= _("Mein Stundenplan im") ?>
    <?= $current_semester['name'] ?>
</div>
<? if (Request::get('show_settings')) : ?>
    <?= $this->render_partial('calendar/schedule/settings', array('settings' => $GLOBALS['my_schedule_settings']));?>
<? endif ?>
<?= $this->render_partial('calendar/daily_weekly.php', compact('calendar_view')); ?>
