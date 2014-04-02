<?php
# Lifter010: TODO
$icon = 'icons/16/black/schedule.png';
if ($GLOBALS['perm']->have_studip_perm('autor',$studygroup->getId()) || $membership_requested) {
    $action = _("Persönlicher Status:");
    if ($membership_requested) {
        $infotext= _("Mitgliedschaft bereits beantragt!");
    } else {
        $infolink = '<a href="'. URLHelper::getLink('seminar_main.php?auswahl='. $studygroup->getId()) .'">%s</a>';
        $infotext= sprintf($infolink, _("Direkt zur Studiengruppe"));
    }
} else if ($GLOBALS['perm']->have_perm('admin')) {
        $action = _("Hinweis:");
        $infotext= '<font color = red>' . _('Sie sind einE AdministratorIn und können sich daher nicht für Studiengruppen anmelden.') . '</font>';
        $icon = 'icons/16/red/decline.png';


} else {
    $action = _("Aktionen:");
    $infolink = '<a rel="lightbox" href="'. URLHelper::getLink('dispatch.php/course/enrolment/apply/'. $studygroup->getId()) .'">%s</a>';
    $infotext= sprintf( $infolink, $studygroup->admission_prelim ? _("Mitgliedschaft beantragen") : _("Studiengruppe beitreten"));
}

$all_mods = $studygroup->getMembers('dozent') + $studygroup->getMembers('tutor');

$mods = array();
foreach($all_mods as $mod) {
    $mods[] = '<a href="'.URLHelper::getLink("dispatch.php/profile?username=".$mod['username']).'">'.htmlready($mod['fullname']).'</a>';
}

/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = 'sidebar/studygroup-sidebar.png';
$infobox['content'] = array(
    array(
        'kategorie' => _("Information"),
        'eintrag'   => array(
            array(
                'text' => _("Hier sehen Sie weitere Informationen zur Studiengruppe. Außerdem können Sie ihr beitreten/eine Mitgliedschaft beantragen."),
                "icon" => "icons/16/black/info.png"
            )
        )
    ),
    array(
        'kategorie' => $action,
        'eintrag'   => array(
            array(
                'text' => $infotext,
                'icon' => $icon
            ),
        )
    )
);

$search = array(
    'text' => '<a href="'. URLHelper::getLink($send_from_search_page) . '">'. _("zurück zur Suche") .'</a>',
    'icon' => 'icons/16/black/schedule.png'
);

if ($send_from_search_page) {
    $infobox['content'][1]['eintrag'][] = $search;
}

/* * * * * * * * * * * *
 * * * O U T P U T * * *
 * * * * * * * * * * * */
?>
<h1><?= htmlReady($studygroup->getName()) ?></h1>
<b><?= _("Moderiert von:") ?></b> <?= implode(',', $mods) ?><br>
<br>
<b><?= _("Beschreibung:") ?></b><br>
<?= formatLinks($studygroup->description) ?>
