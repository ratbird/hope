<?php
# Lifter001: TEST
# Lifter002: TEST
# Lifter007: TEST
# Lifter003: TEST
# Lifter010: TEST
/*
show_bereich.php - Anzeige von Veranstaltungen eines Bereiches oder Institutes
Copyright (C) 2000 Cornelis Kater <ckater@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require '../lib/bootstrap.php';

unregister_globals();

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once('lib/visual.inc.php');
require_once 'lib/classes/SemBrowse.class.php';
require_once 'lib/classes/Institute.class.php';
require_once 'lib/export/export_linking_func.inc.php';

$intro_text = $head_text = '';

$level = Request::option('level');
$id = Request::option('id');

if ($id) {
    URLHelper::bindLinkParam('id',$id);
    URLHelper::bindLinkParam('level',$level);
}

$group_by = Request::int('group_by', 0);

 // store the seleced semester in the session
if (Request::option('select_sem')) {
    $_SESSION['_default_sem'] = Request::option('select_sem');
}

$show_semester = Request::option('select_sem', $_SESSION['_default_sem']);
$sem_browse_obj = new SemBrowse(array('group_by' => 0));
$sem_browse_obj->sem_browse_data['default_sem'] = "all";
$sem_browse_obj->sem_number = false;
$sem_browse_obj->target_url = "details.php";  //teilt der nachfolgenden Include mit, wo sie die Leute hinschicken soll
$sem_browse_obj->target_id = "sem_id";        //teilt der nachfolgenden Include mit, wie die id die &uuml;bergeben wird, bezeichnet werden soll
$sem_browse_obj->sem_browse_data['level'] = $level;
if ($show_semester) {
    $sem_number = SemesterData::GetSemesterIndexById($show_semester);
    $sem_browse_obj->sem_browse_data['default_sem'] = $sem_number;
    $sem_browse_obj->sem_number[0] = $sem_number;
}

switch ($level) {
case "sbb":
    $sem_browse_obj->sem_browse_data['start_item_id'] = $id;
    $sem_browse_obj->get_sem_range($id, false);
    $sem_browse_obj->show_result = true;
    $sem_browse_obj->sem_browse_data['sset'] = false;

    $the_tree = $sem_browse_obj->sem_tree->tree;
    $bereich_typ = _("Studienbereich");
    $head_text = _("Übersicht aller Veranstaltungen eines Studienbereichs");
    $intro_text = sprintf(_("Alle Veranstaltungen, die dem Studienbereich: <br><b>%s</b><br> zugeordnet wurden."),
        htmlReady($the_tree->getShortPath($id)));
    $excel_text = strip_tags(DecodeHtml($intro_text));
    break;
case "s":
    $db = DbManager::get();
    $bereich_typ=_("Einrichtung");
    $head_text = _("Übersicht aller Veranstaltungen einer Einrichtung");
    $intro_text = sprintf(_("Alle Veranstaltungen der Einrichtung: <b>%s</b>"), htmlReady(Institute::find($id)->name));
    $excel_text = strip_tags(DecodeHtml($intro_text));

    $parameters = array($id);
    if ($show_semester) {
        $query = "SELECT seminar_inst.seminar_id
                  FROM seminar_inst
                  LEFT JOIN seminare AS s ON (seminar_inst.seminar_id = s.Seminar_id)
                  INNER JOIN semester_data sd
                     ON ((s.start_time <= sd.beginn AND sd.beginn <= (s.start_time + s.duration_time )
                         OR (s.start_time <= sd.beginn AND s.duration_time = -1))
                      AND semester_id = ?)
                  WHERE seminar_inst.Institut_id = ?";
        array_unshift($parameters, $show_semester);
    } else {
        $query = "SELECT seminar_inst.seminar_id
                  FROM seminar_inst
                  LEFT JOIN seminare AS s ON (seminar_inst.seminar_id = s.Seminar_id)
                  WHERE seminar_inst.Institut_id = ?";
    }
    if (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'))) {
        $query .= " AND s.visible = 1";
    }
    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN);
    $sem_browse_obj->sem_browse_data['search_result'] = array_flip($seminar_ids);
    $sem_browse_obj->show_result = true;
    break;
}

if (Request::int('send_excel')){
    $tmpfile = basename($sem_browse_obj->create_result_xls($excel_text));
    if($tmpfile){
        header('Location: ' . getDownloadLink( $tmpfile, _("Veranstaltungsübersicht.xls"), 4));
        page_close();
        die;
    }
}

PageLayout::setHelpKeyword("Basis.Informationsseite");
PageLayout::setTitle(($level == "s" ? $SessSemName["header_line"]." - " : "").$head_text);
if ($level == "s" && $SessSemName[1] && $SessSemName["class"] == "inst") {
    Navigation::activateItem('/course/main/courses');
}

$group_by_links = "";
for ($i = 0; $i < count($sem_browse_obj->group_by_fields); ++$i){
    $group_by_links .= '<div ';
    if($group_by != $i){
        $group_by_links .=  ' style="padding-left:20px"><a href="'.URLHelper::getLink("",array('group_by'=> $i)).'">';
    } else {
        $group_by_links .= ' style="padding-left:20px;background: url(\''.$GLOBALS['ASSETS_URL'].'images/icons/16/red/arr_1right.png' . '\') no-repeat">';
    }
    $group_by_links .= htmlReady($sem_browse_obj->group_by_fields[$i]['name']);
    if($group_by != $i){
        $group_by_links .= "</a>";
    }
    $group_by_links .= "</div>";
}
$infobox = array();
$infobox[] = array("kategorie" => _("Anzeige gruppieren:"),
    "eintrag" => array(array(
        "text" => $group_by_links))
    );
if (get_config('EXPORT_ENABLE') && $perm->have_perm("tutor")) {
    if ($level == "s") {
        $infobox[] =    array(  "kategorie" => _("Daten ausgeben:"),
            "eintrag" => array(array(   "icon" => "icons/16/black/download.png",
                "text" => '<a href="' . UrlHelper::getLink("export.php", array('range_id' => $SessSemName[1], 'o_mode' => 'choose', 'ex_type' => "veranstaltung",'xslt_filename' => $SessSemName[0], 'ex_sem' => $show_semester)).'">' . _("Diese Daten exportieren") . '</a>'),
                array( 'icon' => 'icons/16/black/file-xls.png',
                    "text" => '<a href="' . UrlHelper::getLink('?send_excel=1&group_by='.(int)$group_by) . '">'._("Download als Excel Tabelle").'</a>')
                )
            );
    }
    if ($level == "sbb") {

        $infobox[] =    array(  "kategorie" => _("Daten ausgeben:"),
            "eintrag" => array(array(   "icon" => "icons/16/black/download.png",
                "text" => '<a href="' . UrlHelper::getLink("export.php", array('range_id' => $id, 'o_mode' => 'choose', 'ex_type' => "veranstaltung",'xslt_filename' => $id, 'ex_sem' => $show_semester)).'">' . _("Diese Daten exportieren") . '</a>'),
                array( 'icon' => 'icons/16/black/file-xls.png',
                    "text" => '<a href="' . UrlHelper::getLink('?send_excel=1&group_by='.(int)$group_by) . '">'._("Download als Excel Tabelle").'</a>')
                )
            );
    }
}

?>
<div><?= $intro_text ?></div>
<div style="text-align:right">
    <form method="post" name="sem_form">
    <?= _("Semester:") ?>
    <?= SemesterData::GetSemesterSelector(array('name'=>'select_sem'), $show_semester, 'semester_id', false) ?>
    <?= \Studip\Button::create(_("Auswählen"), 'choose_sem', array('title' => _("anderes Semester auswählen"))); ?>
    </form>
</div>
<? $sem_browse_obj->print_result(); ?>

<?php
$layout = $GLOBALS['template_factory']->open('layouts/base.php');

$layout->infobox = array('content' => $infobox, 'picture' => "infobox/board1.jpg");
$layout->content_for_layout = ob_get_clean();

echo $layout->render();
page_close();
