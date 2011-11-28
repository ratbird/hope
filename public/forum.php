<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter005: TEST
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
forum.php - Anzeige und Verwaltung des Forensystems
Copyright (C) 2003 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
$txt = $message = $count = $verschoben = '';
$flatviewstartposting = Request::int('flatviewstartposting', 0);
$view = Request::option('view');
$open = Request::option('open');

checkObject();
checkObjectModule("forum");
object_set_visit_module("forum");

if (Request::option('neuesthema')) {
    PageLayout::setHelpKeyword("Basis.ForumBeteiligen");
    PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Forum"));
    Navigation::activateItem('/course/forum/view');
    SkipLinks::addIndex(Navigation::getItem('/course/forum/view')->getTitle(), 'main_content', 100);
} else {
    switch($view) {
        case "neue":
            PageLayout::setHelpKeyword("Basis.ForumNeu");
            Navigation::activateItem('/course/forum/unread');
            SkipLinks::addIndex(Navigation::getItem('/course/forum/unread')->getTitle(), 'main_content', 100);
            break;
        case "flat":
            PageLayout::setHelpKeyword("Basis.Forumlast4");
            Navigation::activateItem('/course/forum/recent');
            SkipLinks::addIndex(Navigation::getItem('/course/forum/recent')->getTitle(), 'main_content', 100);
            break;
        case "search":
            PageLayout::setHelpKeyword("Basis.ForumSuche");
            Navigation::activateItem('/course/forum/search');
            SkipLinks::addIndex(Navigation::getItem('/course/forum/search')->getTitle(), 'main_content', 100);
            break;
        default:
            PageLayout::setHelpKeyword("Basis.Forum");
            Navigation::activateItem('/course/forum/view');
            SkipLinks::addIndex(Navigation::getItem('/course/forum/view')->getTitle(), 'main_content', 100);
    }
    PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Forum"));
}
ob_start();
?>
STUDIP.Forum = {};

STUDIP.Forum.pruefe_name = function(){
 var re_nachname = /^([a-zA-ZÄÖÜ][^0-9"´'`\/\\\(\)\[\]]+)$/;
 var checked = true;
 if (re_nachname.test(document.forumwrite.nobodysname.value)==false) {
 alert("<?=_("Bitte geben Sie Ihren tatsächlichen Namen an.")?>");
 document.forumwrite.nobodysname.focus();
 checked = false;
 }
  if (document.forumwrite.nobodysname.value=="unbekannt") {
 alert("<?=_("Bitte geben Sie Ihren Namen an.")?>");
 document.forumwrite.nobodysname.focus();
 checked = false;
 }
 return checked;
}

STUDIP.Forum.rate_template = function (id) {
  STUDIP.Dialogbox.openBox("Rating_for_<?= $open ?>", "<?= _("Bewertung des Beitrags") ?>",
'<form method="post" action="<?=URLHelper::getLink("?view=$view&open=$open&flatviewstartposting=$flatviewstartposting#anker")?>">\
<?= CSRFProtection::tokenTag() ?>\
<div style="text-align:center">\
<?=_("Schulnote")?>\
<br>\
<span style="color:#009900;font-weight:bold;">1</span>\
<?php foreach(range(1,5) as $r) :?>
<input type="radio" name="rate[' + id + ']" value="<?=$r?>">\
<?php endforeach?>
<span style="color:#990000;font-weight:bold;">5</span>\
<br>\
\<?=makebutton('bewerten','input',_("Bewertung abgeben"),'sidebar')?>\
</form>\
</div>\
', "center");
}
<?php
PageLayout::addHeadElement('script', array('type' => 'text/javascript'), ob_get_clean());

// Start of Output
if (!Request::option('update')) {
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
}

require_once 'lib/functions.php';
require_once ('lib/visual.inc.php');
require_once ('lib/forum.inc.php');
require_once ('lib/object.inc.php');
require_once ('lib/msg.inc.php');
require_once 'lib/classes/NotificationCenter.class.php';
require_once ('lib/dates.inc.php');

//////////////////////////////////////////////////////////////////////////////////
// Debug Funktion zur Zeitmessung
//////////////////////////////////////////////////////////////////////////////////

function getMsTime(){
    $microtime = explode(' ', microtime());
    return (double)($microtime[1].substr($microtime[0],1));
}

//$stoppuhr=getMsTime();


//////////////////////////////////////////////////////////////////////////////////
// Anzeige und View-Logik
//////////////////////////////////////////////////////////////////////////////////


if ($forum["view"]=="mixed" && $open) {
    $forum["flatfolder"] = $open;
}

if (!$forum["themeview"])
    $forum["themeview"]="tree";

if (Request::option('themeview')) { // Umschaltung tree/flat über die Kopfleiste
    $forum["themeview"] = Request::option('themeview');
    if ($forum["presetview"]=="tree" || $forum["presetview"]=="mixed")
        $forum["presetview"] = $forum["themeview"];
}

if ($presetview) {
    if ($presetview == "theme")
        $forum["presetview"]=$forum["themeview"];
    else
        $forum["presetview"] = $presetview;
}

if (!$forum["presetview"])
    $forum ["presetview"] = $forum["themeview"];
if (!$forum["sortthemes"])
    $forum["sortthemes"] = "asc";

if ($view) {
    if ($view=="reset")
        $forum["view"] = $forum["presetview"];
    else
        $forum["view"] = $view;
}

if (!$forum["view"]) {
    $view = $forum["themeview"];
    $forum["view"] = $view;
}

$view = $forum["view"];

URLHelper::addLinkParam('view', $view);

//////////////////////////////////////////////////////////////////////////////////
// Behandlung der Suche
//////////////////////////////////////////////////////////////////////////////////

$forum['search'] = '';

if (Request::get('suchbegriff') != '' || Request::get('author') != '') {
    $forum['searchstring'] = Request::get('suchbegriff');
    $forum['searchauthor'] = Request::get('author');

    $meta_search = array('_', '%', '*');
    $meta_replace = array('\_', '\%', '%');

    $searchstring = str_replace($meta_search, $meta_replace, $forum['searchstring']);
    $searchauthor = str_replace($meta_search, $meta_replace, $forum['searchauthor']);

    $search_words = preg_split('/[\s,]+/', $searchstring, -1, PREG_SPLIT_NO_EMPTY);
    $search_author = preg_split('/[\s,]+/', $searchauthor, -1, PREG_SPLIT_NO_EMPTY);
    $search_items = array();

    foreach ($search_words as $item) {
        if (substr($item, 0, 8) == 'intitle:') {
            $search_items[] = "x.name LIKE '%".addslashes(substr($item, 8))."%'";
        } else {
            $search_items[] = "(x.name LIKE '%".addslashes($item)."%' OR x.description LIKE '%".addslashes($item)."%')";
        }
    }

    if (count($search_author) > 0) {
        foreach ($search_author as $key => $value) {
            $search_author[$key] = "x.author LIKE '%".addslashes($value)."%'";
        }
        $search_items[] = '('.join(' OR ', $search_author).')';
    }

    $forum['search'] = join(' AND ', $search_items);
    if (get_config('FORUM_ANONYMOUS_POSTINGS')) {
        $forum['search'] .= " AND x.anonymous=0";
    }

    URLHelper::addLinkParam('suchbegriff', $forum['searchstring']);
    URLHelper::addLinkParam('author', $forum['searchauthor']);
}

if (Request::int('reset')) {
    $forum['search'] = '';
}

//////////////////////////////////////////////////////////////////////////////////
// verschiedene GUI-Konstanten werden gesetzt
//////////////////////////////////////////////////////////////////////////////////

if (Request::option('indikator')) {
    $forum["indikator"] = Request::option('indikator');
    URLHelper::addLinkParam('indikator', $forum['indikator']);
}

// use whitelist to prevent sql-injections
if (Request::option('sort')) {
    $forum['sort'] = Request::option('sort');
    URLHelper::addLinkParam('sort', $forum['sort']);
}

if (!$forum["sort"])
    $forum["sort"] = "age";

if (!$forum["indikator"])
    $forum["indikator"] = "age";

if (Request::option('toolbar') == "open") {
    $forum["toolbar"] = "open";
    URLHelper::addLinkParam('toolbar', 'open');
}
if (Request::option('toolbar') == "close")
    $forum["toolbar"] = "close";

$indexvars["age"]["name"]=_("Alter");
$indexvars["age"]["color"]="#FF0000";
$indexvars["viewcount"]["name"]=_("Views");
$indexvars["viewcount"]["color"]="#008800";
$indexvars["rating"]["name"]=_("Bewertung");
$indexvars["rating"]["color"]="#CC7700";
$indexvars["score"]["name"]=_("Relevanz");
$indexvars["score"]["color"]="#0000FF";

$openorig = $open;  // wird gebraucht für den open-Link wenn im Treeview $open überschrieben wird

//////////////////////////////////////////////////////////////////////////////////
// Sind wir da wo wir hinwollen?
//////////////////////////////////////////////////////////////////////////////////

$answer_id = Request::option('answer_id');
$topic_id = Request::option('topic_id');
$update = Request::option('update');
$sql_topic_id = false;

if ($topic_id AND !$update) {
    $sql_topic_id = $topic_id;
} elseif ($open AND !$update) {
    $sql_topic_id = $open;
} elseif ($answer_id) {
    $sql_topic_id = $answer_id;
}
if ($sql_topic_id) {
    $db=new DB_Seminar;
    $db->query('SELECT * FROM px_topics WHERE topic_id=\''.$sql_topic_id. '\' AND Seminar_id =\''.$SessSemName[1].'\'');
    if (!$db->num_rows()) { // wir sind NICHT im richtigen Seminar!
        echo '<br><br>';
        parse_window ('error§' . _("Sie versuchen, mit zwei Browserfenstern innerhalb verschiedener Foren zu navigieren.") . '<br><font size="-1" color="black">' . _("Um unerw&uuml;nschte Effekte - wie falsch einsortierten Postings - zu vermeiden,<br>empfehlen wir, Stud.IP nur in einem Browserfenster zu verwenden.") . '</font>', '§',
                _("zuviele Browserfenster im Forenbereich!"),
                '');
        die;
    }
}

//////////////////////////////////////////////////////////////////////////////////
// Rekursives Löschen von Postings, Warnung
//////////////////////////////////////////////////////////////////////////////////

$delete_id = Request::option('delete_id');

if ($delete_id) {
    $db=new DB_Seminar;
    $mutter = suche_kinder($delete_id);
    $mutter = explode (";",$mutter);
    $count = sizeof($mutter)-2;
    $db->query("SELECT *, IFNULL(ROUND(AVG(rate),1),99) as rating FROM px_topics LEFT JOIN object_rate ON(object_rate.object_id=topic_id) WHERE topic_id='$delete_id' AND Seminar_id ='$SessSemName[1]' GROUP BY topic_id");
    if ($db->num_rows()) { // wir sind im richtigen Seminar!
        $db->next_record();
        if ($rechte || (($db->f("user_id") == $user->id) && ($count == 0))) {  // noch mal checken ob alles o.k.
            $root = $db->f("root_id");
            $forumposting["id"] = $db->f("topic_id");
            $forumposting["name"] = $db->f("name");
            $forumposting["description"] = $db->f("description");
            $forumposting["author"] = $db->f("author");
            $forumposting["username"] = $db->f("username");
            $forumposting["rootid"] = $db->f("root_id");
            $forumposting["rootname"] = $db->f("root_name");
            $forumposting["mkdate"] = $db->f("mkdate");
            $forumposting["chdate"] = $db->f("chdate");
            $forumposting["buttons"] = "no";
            $forumposting["rating"] = $db->f("rating");
            $forumposting["anonymous"] = get_config('FORUM_ANONYMOUS_POSTINGS') ? $db->f("anonymous") : false;
            $forumposting = ForumGetAnonymity ($forumposting);

            if ($forumposting["id"] == $forumposting["rootid"])
                $tmp_label = _("das untenstehende Thema");
            else
                $tmp_label = _("das untenstehende Posting");
            echo "\n\n<table class=\"blank\" cellspacing=0 cellpadding=5 border=0 width=\"100%\"><colgroup span=1></colgroup>\n";
            echo "<tr><td class=\"blank\"></td></tr>";
            $msg="info§" . sprintf(_("Wollen Sie %s %s von %s wirklich löschen?"), $tmp_label, "<b>".htmlReady($db->f("name"))."</b>", "<b>".($forumposting["anonymous"] ? _("anonym") : htmlReady($db->f("author")))."</b>") . "<br>\n";
            if ($count)
                $msg.= sprintf(_("Alle %s Antworten auf diesen Beitrag werden ebenfalls gelöscht!"), $count) . "<br>\n<br>\n";
            $msg.="<a href=\"".URLHelper::getLink("?really_kill=$delete_id&view=$view#anker")."\">" . makeButton("ja2", "img") . "</a>&nbsp; \n";
            $msg.="<a href=\"".URLHelper::getLink("?topic_id=$root&open=$delete_id&view=$view#anker")."\">" . makeButton("nein", "img") . "</a>\n";
            parse_msg($msg, '§', 'blank', '1', FALSE);
            echo "</table>";

        // Darstellung des zu loeschenden Postings

            echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0" align="center"><tr><td class="blank"><br><br>';
            echo '<table width="80%" class="blank" border="0" cellpadding="0" cellspacing="0" align="center"><tr>';



            printposting($forumposting);

            echo "<br></td></tr></table>\n<br></td></tr></table>";
            include 'lib/include/html_end.inc.php';
            page_close();
            die;
        }
    }
} else {
    $forumposting["buttons"] = "yes";
}

//////////////////////////////////////////////////////////////////////////////////
// Verschieben von Postings
//////////////////////////////////////////////////////////////////////////////////

$target = Request::option('target');
$sem_id = Request::option('sem_id');
$inst_id = Request::option('inst_id');
$move_id = Request::option('move_id');
$parent_id = Request::option('parent_id');

if ($target =="Seminar"){ //Es soll in ein anderes Seminar verschoben werden
    $verschoben = 0;
    move_topic($topic_id,$sem_id,$topic_id,$verschoben);
    $message = "move";
}

if ($target =="Institut"){ //Es soll in ein Institut verschoben werden
    $verschoben = 0;
    move_topic($topic_id,$inst_id,$topic_id,$verschoben);
    $message = "move";
}

if ($target =="Thema"){ //Es soll in ein anderes Thema verschoben werden
    $verschoben = 0;
    move_topic2($move_id,$move_id,$verschoben,$parent_id);
    $message = "move";
}

//////////////////////////////////////////////////////////////////////////////////
// Rekursives Löschen von Postings, jetzt definitiv!
//////////////////////////////////////////////////////////////////////////////////

$really_kill = Request::option('really_kill');

if ($really_kill) {
    $db=new DB_Seminar;
    $db->query("SELECT * FROM px_topics WHERE topic_id='$really_kill' AND Seminar_id ='$SessSemName[1]'");
    if ($db->num_rows()) { // wir sind im richtigen Seminar!
        $db->next_record();
        $mutter = suche_kinder($really_kill);
        $mutter = explode (";",$mutter);
        $count = sizeof($mutter)-2;
        $open = $db->f("root_id");
        if ($rechte || (($db->f("user_id") == $user->id || $db->f("user_id") == "") && ($count == 0))) {  // noch mal checken ob alles o.k.
            $count = 0;
            delete_topic($really_kill, $count);
            $db->next_record();
            $message = "kill";
        }
    }
}

//////////////////////////////////////////////////////////////////////////////////
// neuer Beitrag als Antwort wird eingeleitet
//////////////////////////////////////////////////////////////////////////////////

$edit_id = Request::option('edit_id');

if ($answer_id) {
    $db=new DB_Seminar;
    $db->query("SELECT name, topic_id, root_id FROM px_topics WHERE topic_id = '$answer_id'");
    while($db->next_record()){
        $name = $db->f("name");
        if (substr($name,0,3)!="Re:")
            $name = "Re: ".$name; // Re: vor Überschriften bei Antworten
        $author = get_fullname();
        $postinginhalt = _("Dieser Beitrag wird gerade bearbeitet.");
        $edit_id = CreateNewTopic($name, $postinginhalt, $answer_id, $db->f("root_id"), Request::int('anonymous'));
    }
}

//////////////////////////////////////////////////////////////////////////////////
// Update eines Beitrags
//////////////////////////////////////////////////////////////////////////////////

$titel = Request::quoted('titel');
$description = Request::quoted('description');

if ($update) {
    // check whether we should create a new posting or update an existing one
    if (Request::option('parent_id') !== NULL) {
        $parent_id = Request::option('parent_id');
        $root_id = $parent_id != "0" ? Request::option('root_id') : "0";
        $user_id = $auth->auth['uid'];
        $author = $user_id == 'nobody' ? Request::get('nobodysname') : get_fullname();
        NotificationCenter::postNotification('PostingWillCreate', $update);
        $update = CreateTopic($titel, $author, $description, $parent_id, $root_id, 0, $user_id, true, Request::int('anonymous'));
        NotificationCenter::postNotification('PostingDidCreate', $update);
    } else {
        if (!ForumFreshPosting($update)) // editiert von nur dranhängen wenn nicht frisch erstellt
            $description = forum_append_edit($description);
        NotificationCenter::postNotification('PostingWillUpdate', $update);
        UpdateTopic($titel, $update, $description, Request::int('anonymous'));
        NotificationCenter::postNotification('PostingDidUpdate', $update);
    }

    // redirect to normal view to avoid duplicate postings on reload or back/forward
    header('Location: ' . URLHelper::getURL("?open=$update&flatviewstartposting=$flatviewstartposting#anker"));
    page_close();
    die;
}

//////////////////////////////////////////////////////////////////////////////////
// Neues Thema wird angelegt
//////////////////////////////////////////////////////////////////////////////////

if (Request::option('neuesthema') && ($rechte || $SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["topic_create_autor"])) {            // es wird ein neues Thema angelegt
        $name = _("Name des Themas");
        $author = get_fullname();
        $edit_id = CreateNewTopic($name, "Beschreibung des Themas", 0, 0, Request::int('anonymous'));
        // add skip link right before the link to the main content
        SkipLinks::addIndex(_("Neues Thema anlegen"), 'newposting_form', 99);
}

//////////////////////////////////////////////////////////////////////////////////
// weitere Konstanten setzen
//////////////////////////////////////////////////////////////////////////////////

if (Request::int('zitat'))
    $zitat = $answer_id;

if ($edit_id)
    $open = $edit_id;

if (Request::option('flatallopen') == "TRUE")
    $forum["flatallopen"] = "TRUE";

if (Request::option('flatallopen') == "FALSE")
    $forum["flatallopen"] = "FALSE";

if (Request::option('fav')) {
    // zu den Favoriten hinzufügen/entfernen
    object_switch_fav(Request::option('fav'));
    $forum["anchor"] = Request::option('fav'); // Anker auf Favoriten
} else {
    $forum["anchor"] = $open; // Anker setzen
}

$rate = Request::intArray('rate');

if ($rate) { // Objekt bewerten
    while(list($key,$value) = each($rate)) {
        $txt = object_add_rate ($key, $value);
        $forum["anchor"] = $key;
    }
}


//////////////////////////////////////////////////////////////////////////////////
//Anzeige des Kopfes mit Meldungen und Toolbar
//////////////////////////////////////////////////////////////////////////////////

echo "\n<table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

// Ausgabe für Zusatzinfos
if ($message=="kill") echo parse_msg("msg§" . sprintf(_("%s Posting(s) gel&ouml;scht"), $count));
if ($message=="move") echo parse_msg("msg§" . sprintf(_("%s Posting(s) verschoben."), $verschoben));
if ($txt) echo parse_msg("msg§" . $txt);
if (Request::option('cmd') == "move" && $topic_id !="" && $rechte)
    forum_move_navi ($topic_id);

echo "\n</table>\n";

if (($forum["view"] != "search" || $forum["search"] != "") && $user->id != "nobody" && Request::option('cmd') != "move")   // wenn Suchformular aufgerufen wird keine toolbar
    echo forum_print_toolbar($edit_id);
elseif ($user->id == "nobody" || Request::option('cmd') == "move") {
    echo "\n<table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"blank\"><br></td></tr></table>";
    if ($edit_id) {
        echo "<form name=\"forumwrite\" onsubmit=\"return STUDIP.Forum.pruefe_name()\" method=\"post\" action=\"".URLHelper::getLink("#anker")."\">";
        echo CSRFProtection::tokenTag();
    }
}
//////////////////////////////////////////////////////////////////////////////////
// Verzweigung zu den Anzeigemodi
//////////////////////////////////////////////////////////////////////////////////
echo '<div id="main_content">';
if ($forum["view"]=="flat" || $forum["view"]=="neue" || $forum["view"]=="flatfolder" || $forum["view"]=="search")
    flatview ($open, $show, $edit_id, $name, $description, $zitat);
else
    DisplayFolders ($open, $edit_id, $zitat);
echo '</div>';
//////////////////////////////////////////////////////////////////////////////////
// Rest
//////////////////////////////////////////////////////////////////////////////////


// echo "Zeit:".(getMsTime()-$stoppuhr);
    include ('lib/include/html_end.inc.php');
    page_close();
?>
