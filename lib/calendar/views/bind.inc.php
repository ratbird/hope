<?

# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * bind.inc.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */
// add skip link
SkipLinks::addIndex(_("Veranstaltungstermine"), 'main_content', 100);
// Semesterauswahl

$semester_data = new SemesterData();
$current_semester = $semester_data->getCurrentSemesterData();

if (Request::submitted('sem_auswahl')) {
    $selected_sem = Request::get('sem_auswahl');
} else {
    $selected_sem = $current_semester['semester_id'];
}
// alle vom user abonnierten Seminare
$db = DBManager::get();
$sortby = Request::option('sortby', 'seminar_user.gruppe, seminare.Name');
$conds = array($user->id, $user->id);
if ($order == 'ASC') {
    $order = 'DESC';
} else {
    $order = 'ASC';
}
$query = "SELECT bind_calendar, visitdate, seminare.Name, seminare.Seminar_id, seminar_user.status, seminar_user.gruppe, count(termin_id) as count,
    sd1.name AS startsem,IF(duration_time=-1, '" . _("unbegrenzt") . "', sd2.name) AS endsem
    FROM seminar_user LEFT JOIN seminare ON seminare.Seminar_id=seminar_user.seminar_id
    LEFT JOIN object_user_visits  ouv ON ouv.object_id = seminare.Seminar_id AND ouv.user_id = ? AND ouv.type = 'sem'
    LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
    LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
    LEFT JOIN termine ON range_id=seminare.Seminar_id WHERE seminar_user.user_id = ? ";
if ($selected_sem != "0") {
    $conds[] = $selected_sem;
    $query .= "AND sd1.semester_id = ? ";
} else {
    
}
$query .= "GROUP BY Seminar_id ORDER BY " . $sortby . " " . $order;

$db = DBManager::get()->prepare($query);
$db->execute($conds);
$result = $db->fetchAll(PDO::FETCH_ASSOC);

$template = $GLOBALS['template_factory']->open('calendar/bind'); 
$template->set_layout('layouts/base'); 
$template->calendar_sess_control_data = $calendar_sess_control_data; 
$template->order                      = $order; 
$template->result                     = $result;
$template->selected_sem               = $selected_sem;
$template->atime                      = $atime; 

$template->infobox = array(
    'picture' => 'infobox/dates.jpg',
    'content' => array(
        array("kategorie" => _("Semesterauswahl:"),
        "eintrag" => array(
            array("icon" => "",
                "text" => '<form method="post" id="sem_auswahl" name="semester" action="' . URLHelper::getUrl('calendar.php', array('cmd' => 'bind')) . '">' .
                $semester_data->GetSemesterSelector(array('name' => 'sem_auswahl', 'onchange' => 'jQuery(\'#sem_auswahl\').submit()'), $selected_sem)
                . '</form>'
                )
            )
        ),
        array('kategorie' => _('Information:'), 
            'eintrag'   => array(
                array( 
                    'icon' => 'icons/16/black/info.png',
                    'text' => _('Termine aus den ausgew&auml;hlten Veranstaltungen 
                        werden in Ihren Terminkalender &uuml;bernommen.') 
                )
            ) 
        )
    )
);
echo $template->render(); 

