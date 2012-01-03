<?php
# Lifter001: TEST
# Lifter002: TEST (mriehe)
# Lifter003: TEST
# Lifter005: TODO
# Lifter007: TODO
# Lifter010: TODO
/**
 * score.php - Stud.IP Highscore List
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */


require '../lib/bootstrap.php';
unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check('user');

//Imports
require_once 'lib/seminar_open.php'; // initialise Stud.IP-Session
require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/classes/score.class.php';
require_once 'lib/object.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/classes/Avatar.class.php';
require_once 'lib/classes/StudipKing.class.php';

//Basics
PageLayout::setHelpKeyword("Basis.VerschiedenesScore"); // external help keyword
PageLayout::setTitle(_("Rangliste"));
Navigation::activateItem('/community/score');

/* --- Actions -------------------------------------------------------------- */
$score = new Score($user->id);
if(Request::option('cmd')=="write")
{
    $score->PublishScore();
}
if(Request::option('cmd')=="kill")
{
    $score->KillScore();
}

$stmt=DBManager::get()->query("SELECT COUNT(*) FROM user_info a LEFT JOIN auth_user_md5 b USING (user_id) WHERE score > 0 AND locked=0 AND ".get_vis_query('b') );

$anzahl=$stmt->fetchColumn();

$page = Request::int('page', 1);

if($page < 1 || $page > ceil($anzahl/get_config('ENTRIES_PER_PAGE'))) $page = 1;

// Liste aller die mutig (oder eitel?) genug sind
$query = "SELECT a.user_id,username,score,geschlecht, " .$_fullname_sql['full'] ." AS fullname FROM user_info a LEFT JOIN auth_user_md5 b USING (user_id) WHERE score > 0 AND locked=0 AND ".get_vis_query('b')." ORDER BY score DESC LIMIT ".(($page-1)*get_config('ENTRIES_PER_PAGE')).",".get_config('ENTRIES_PER_PAGE');
$result = DBManager::get()->query($query);
while ($row = $result->fetch()) {
    $is_king = StudipKing::is_king($row["user_id"], TRUE);
    $person = array(
        "userid" => $row["user_id"],
        "username" => $row["username"],
        "avatar" => Avatar::getAvatar($row["user_id"])->getImageTag(Avatar::SMALL),
        "name" => htmlReady($row["fullname"]),
        "content" => $score->GetScoreContent($row["user_id"]),
        "score" => $row["score"],
        "title" => $score->GetTitel($row["score"], $row["geschlecht"]),
        "is_king" => $is_king
    );
    $persons[] = $person;
}
/* --- View ----------------------------------------------------------------- */
$template = $GLOBALS['template_factory']->open('score');
$template->set_attribute('persons', $persons);
$template->set_attribute('user', $user);
$template->set_attribute('score', $score);
$template->set_attribute('numberOfPersons', $anzahl);
$template->set_attribute('page', $page);
$template->set_layout("layouts/base");
echo $template->render();
page_close();
