<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
institut_main.php - Die Eingangsseite fuer ein Institut
Copyright (C) 200 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(Request::get('again') && ($auth->auth["uid"] == "nobody"));

if (Request::option('auswahl')) {
    Request::set('cid', Request::option('auswahl'));
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/dates.inc.php'; //Funktionen zur Anzeige der Terminstruktur
require_once 'lib/visual.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/classes/InstituteAvatar.class.php';
require_once 'lib/classes/Institute.class.php';

if (get_config('CHAT_ENABLE')){
    include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
    if ($_REQUEST['kill_chat']){
        chat_kill_chat($_REQUEST['kill_chat']);
    }

}
if (get_config('VOTE_ENABLE')) {
    include_once ("lib/vote/vote_show.inc.php");
}

$institute_id = $_SESSION['SessionSeminar'];

//set visitdate for institute, when coming from meine_seminare
if (Request::get('auswahl')) {
    object_set_visit($institute_id, "inst");
}

// gibt es eine Anweisung zur Umleitung?
if(Request::get('redirect_to')) {
    $query_parts = explode('&', stristr($_SERVER['QUERY_STRING'], 'redirect_to'));
    list( , $where_to) = explode('=', array_shift($query_parts));
    $new_query = $where_to . '?' . join('&', $query_parts);
    page_close();
    $new_query = preg_replace('/[^0-9a-z+_#?&=.-\/]/i', '', $new_query);
    header('Location: '.URLHelper::getURL($new_query, array('cid' => $course_id)));
    die;
}

if (get_config('NEWS_RSS_EXPORT_ENABLE') && $institute_id){
    $rss_id = StudipNews::GetRssIdFromRangeId($institute_id);
    if ($rss_id) {
        PageLayout::addHeadElement('link', array('rel'   => 'alternate',
                                                 'type'  => 'application/rss+xml',
                                                 'title' => 'RSS',
                                                 'href'  => 'rss.php?id='.$rss_id));
    }
}

checkObject();

PageLayout::setHelpKeyword("Basis.Einrichtungen");
PageLayout::setTitle($SessSemName["header_line"]. " - " ._("Kurzinfo"));
Navigation::activateItem('/course/main/info');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

include 'lib/showNews.inc.php';

// list of used modules
$Modules = new Modules;
$modules = $Modules->getLocalModules($institute_id);

URLHelper::bindLinkParam("inst_data", $institut_main_data);

//Auf und Zuklappen News
process_news_commands($institut_main_data);

?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank" valign="top">
        <div style="padding:0 1.5em 1.5em 1.5em">
        <ul style="list-style-type:none;padding:0px;">
    <?
    $institute = Institute::find($institute_id);
    if ($institute->strasse) {
        echo "<li><b>" . _("Straﬂe:") . " </b>"; echo htmlReady($institute->strasse); echo"</li>";
    }

    if ($institute->Plz) {
        echo "<li><b>" . _("Ort:") . " </b>"; echo htmlReady($institute->Plz); echo"</li>";
    }

    if ($institute->telefon) {
        echo "<li><b>" . _("Tel.:") . " </b>"; echo htmlReady($institute->telefon); echo"</li>";
    }

    if ($institute->fax) {
        echo "<li><b>" . _("Fax:") . " </b>"; echo htmlReady($institute->fax); echo"</li>";
    }

    if ($institute->url) {
        echo "<li><b>" . _("Homepage:") . " </b>"; echo formatReady($institute->url); echo"</li>";
    }

    if ($institute->email) {
        echo "<li><b>" . _("E-Mail:") . " </b>"; echo formatReady($institute->email); echo"</li>";
    }

    if ($institute->fakultaets_id) {
        echo "<li><b>" . _("Fakult&auml;t:") . " </b>"; echo htmlReady(Institute::find($institute->fakultaets_id)->name); echo"</li>";
    }

    $localEntries = DataFieldEntry::getDataFieldEntries($institute_id);
    foreach ($localEntries as $entry) {
        if ($entry->structure->accessAllowed($perm) && $entry->getValue()) {
            echo "<li><b>" .htmlReady($entry->getName()) . ": </b>";
            echo $entry->getDisplayValue();
            echo "</li>";
        }
    }

?>
    </ul>
    </div>
    </td>
        <td class="blank" align="right" valign="top" style="padding:10px;">
            <?= InstituteAvatar::getAvatar($institute_id)->getImageTag(Avatar::NORMAL) ?>
        </td>
        </tr>
    </table>
<br>
<?php

// Anzeige von News
($rechte) ? $show_admin=TRUE : $show_admin=FALSE;
show_news($institute_id,$show_admin, 0, $institut_main_data["nopen"], "100%", object_get_visit($institute_id, "inst"), $institut_main_data);

// include and show votes and tests
if (get_config('VOTE_ENABLE')) {
    show_votes ($institute_id, $auth->auth["uid"], $perm, YES);
}


// display plugins
$plugins = PluginEngine::getPlugins('StandardPlugin', $institute_id);
$layout = $GLOBALS['template_factory']->open('shared/index_box');

foreach ($plugins as $plugin) {
    $template = $plugin->getInfoTemplate($institute_id);

    if ($template) {
        echo $template->render(NULL, $layout);
        $layout->clear_attributes();
    }
}

//show chat info
if (get_config('CHAT_ENABLE') && $modules["chat"]) {
    chat_show_info($institute_id);
}

include ('lib/include/html_end.inc.php');
page_close();
