<?php

if ($GLOBALS['perm']->have_studip_perm('autor',$studygroup->getId())) {
    $participate_link = '<a href="'. UrlHelper::getLink('seminar_main.php?auswahl='. $studygroup->getId()) .'">%s</a>';
    $participate = sprintf($participate_link, _("Direkt zur Studiengruppe"));
} else if ($membership_requested) {
    $participate = _("Mitgliedschaft bereits beantragt!");
} else {
    $participate_link = '<a href="'. UrlHelper::getLink('sem_verify.php?id='. $studygroup->getId()) .'">%s</a>';
    $participate = sprintf( $participate_link, $studygroup->admission_prelim ? _("Mitgliedschaft beantragen") : _("Studiengruppe beitreten"));
}

$all_mods = $studygroup->getMembers('dozent') + $studygroup->getMembers('tutor');
unset($all_mods[md5('studygroup_dozent')]);

$mods = array();
foreach($all_mods as $mod) {
    $mods[] = '<a href="'.URLHelper::getLink("about.php?username=".$mod['username']).'">'.htmlready($mod['fullname']).'</a>';
}

/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = 'groups.jpg';
$infobox['content'] = array(
    array(
        'kategorie' => _("Information"), 
        'eintrag'   => array(
            array(
                'text' => _("Hier sehen Sie weitere Informationen zur Studiengruppe. Außerdem können sie ihr beitreten/eine Mitgliedschaft beantragen."),
                'icon' => 'ausruf_small.gif'
            )
        )
    ),
    array(
        'kategorie' => _("Aktionen"),
        'eintrag'   => array(
            array(
                'text' => $participate, 
                'icon' => 'link_intern.gif'
            ),
        )
    )
);

$search = array(
    'text' => '<a href="'. UrlHelper::getLink($send_from_search_page) . '">'. _("zurück zur Suche") .'</a>',
    'icon' => 'link_intern.gif'
);

if ($send_from_search_page) {
    $infobox['content'][1]['eintrag'][] = $search;
}

/* * * * * * * * * * * *
 * * * O U T P U T * * * 
 * * * * * * * * * * * */
?>
<h1><?= $studygroup->getName() ?></h1>
<b><?= _("Moderiert von:") ?></b> <?= implode(',', $mods) ?><br>
<br>
<b><?= _("Beschreibung:") ?></b><br>
<?= FixLinks(htmlReady($studygroup->description)) ?>
