<?php
# Lifter001: TEST
# Lifter002: TEST (mriehe)
# Lifter003: TEST
# Lifter005: TODO
# Lifter007: TODO
/**
 * score.php - Stud.IP Highscore List
 *
 * PHP Version 5
 *
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @access      public
 * @copyright   2000-2009 Stud.IP
 * @license     http://www.gnu.org/licenses/gpl.html GPL Licence 3
*/


require '../lib/bootstrap.php';

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
$HELP_KEYWORD="Basis.VerschiedenesScore"; // external help keyword
$CURRENT_PAGE=_("Stud.IP-Score");
Navigation::activateItem('/community/score');

define("ELEMENTS_PER_PAGE", 20);

/* --- Actions -------------------------------------------------------------- */
$score = new Score($user->id);
if($_REQUEST['cmd']=="write")
{
    $score->PublishScore();
}
if($_REQUEST['cmd']=="kill")
{
    $score->KillScore();
}

$stmt=DBManager::get()->query("SELECT COUNT(*) FROM user_info a LEFT JOIN auth_user_md5 b USING (user_id) WHERE score > 0 AND locked=0 AND ".get_vis_query('b') );

$anzahl=$stmt->fetchColumn();

if($_REQUEST['page']){
    $page=$_REQUEST['page'];
} else {
    $page=1;
}

if($page < 1 || $page > ceil($anzahl/ELEMENTS_PER_PAGE)) $page = 1;

// Liste aller die mutig (oder eitel?) genug sind
$query = "SELECT a.user_id,username,score,geschlecht, " .$_fullname_sql['full'] ." AS fullname FROM user_info a LEFT JOIN auth_user_md5 b USING (user_id) WHERE score > 0 AND locked=0 AND ".get_vis_query('b')." ORDER BY score DESC LIMIT ".(($page-1)*ELEMENTS_PER_PAGE).",".ELEMENTS_PER_PAGE;
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
?>
