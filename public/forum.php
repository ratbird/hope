<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter005: TEST
# Lifter007: TODO
# Lifter003: TODO
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
$txt = $message = $count = $verschoben = '';
$flatviewstartposting = (int)$flatviewstartposting;
(isset($view) && preg_match('/^[a-z]*$/', $view)) or $view = '';
(isset($open) && preg_match('/^[a-z0-9]{1,32}$/', $open)) or $open = '';

checkObject();
checkObjectModule("forum");
object_set_visit_module("forum");

mark_public_course();

if ($forumsend && $forumsend!="bla") {
    $HELP_KEYWORD="Basis.ForumEinstellungen";
    $CURRENT_PAGE = _("Einstellungen des Forums anpassen");
    Navigation::activateItem('/course/forum/settings');
} elseif(isset($neuesthema)) {
    $HELP_KEYWORD="Basis.ForumBeteiligen";
    $CURRENT_PAGE = $SessSemName["header_line"]. " - " . _("Forum");
    Navigation::activateItem('/course/forum/view');
} else {
    switch($view) {
        case "neue":
            $HELP_KEYWORD="Basis.ForumNeu";
            Navigation::activateItem('/course/forum/unread');
            break;
        case "flat":
            $HELP_KEYWORD="Basis.Forumlast4";
            Navigation::activateItem('/course/forum/recent');
            break;
        case "search":
            $HELP_KEYWORD="Basis.ForumSuche";
            Navigation::activateItem('/course/forum/search');
            break;
        default:
            $HELP_KEYWORD="Basis.Forum";
            Navigation::activateItem('/course/forum/view');
    }
    $CURRENT_PAGE = $SessSemName["header_line"]. " - " . _("Forum");
}
ob_start();
?>
<script type="text/javascript">

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

STUDIP.Forum.rate_template = new Template(
'<form method="post" action="<?=URLHelper::getLink("?view=$view&open=$open&flatviewstartposting=$flatviewstartposting#anker")?>">\
<div style="text-align:center">\
<?=_("Bewertung des Beitrags")?>\
<br>\
<?=_("Schulnote")?>\
<br>\
<span style="color:#009900;font-weight:bold;">1</span>\
<?php foreach(range(1,5) as $r) :?>
<input type="radio" name="rate[#{id}]" value="<?=$r?>">\
<?php endforeach?>
<span style="color:#990000;font-weight:bold;">5</span>\
<br>\
\<?=makebutton('bewerten','input',_("Bewertung abgeben"),'sidebar')?>\
</form>\
</div>\
');
</script>
<?php 
$_include_additional_header .= ob_get_clean();

// Start of Output
if (!$update) {
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
}
            
require_once 'lib/functions.php';
require_once ('lib/visual.inc.php');
require_once ('lib/forum.inc.php');
require_once ('lib/object.inc.php');
require_once ('lib/msg.inc.php');
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
//Daten aus der Einstellungsseite verarbeiten
//////////////////////////////////////////////////////////////////////////////////

if ($forumsend) {
    if ($forumsend=="bla"){
        if ($presetview == "theme")
            $presetview = $themeview;
        $forum["neuauf"] = $neuauf;
        $forum["postingsperside"] = $postingsperside;
        $forum["flatallopen"] = $flatallopen;
        $forum["rateallopen"] = $rateallopen;
        $forum["showimages"] = $showimages;
        $forum["sortthemes"] = $sortthemes;
        $forum["themeview"] = $themeview;
        $forum["presetview"] = $presetview;
        $forum["shrink"] = $shrink*604800; // Anzahl der Sekunden pro Woche
        $forum["changed"] = "TRUE";
        $txt = _("Anpassungen durchgeführt.");
    } else
        include('lib/include/forumsettings.inc.php');
}

//////////////////////////////////////////////////////////////////////////////////
// Anzeige und View-Logik
//////////////////////////////////////////////////////////////////////////////////


if ($forum["view"]=="mixed" && $open) {
    $forum["flatfolder"] = $open;
}

if (!$forum["themeview"])
    $forum["themeview"]="tree";

if ($themeview) { // Umschaltung tree/flat über die Kopfleiste
    $forum["themeview"]=$themeview;
    if ($forum["presetview"]=="tree" || $forum["presetview"]=="mixed")
        $forum["presetview"] = $themeview;
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

if ($_REQUEST['suchbegriff'] != '' || $_REQUEST['author'] != '') {
    $forum['searchstring'] = remove_magic_quotes($_REQUEST['suchbegriff']);
    $forum['searchauthor'] = remove_magic_quotes($_REQUEST['author']);

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

if ($_REQUEST['reset']) {
    $forum['search'] = '';
}

//////////////////////////////////////////////////////////////////////////////////
// verschiedene GUI-Konstanten werden gesetzt
//////////////////////////////////////////////////////////////////////////////////

if ($indikator) {
    $forum["indikator"] = $indikator;
    URLHelper::addLinkParam('indikator', $indikator);
}

if ($sort) {
    $forum["sort"] = $sort;
    URLHelper::addLinkParam('sort', $sort);
}
if (!$forum["sort"])
    $forum["sort"] = "age";

if (!$forum["indikator"])
    $forum["indikator"] = "age";

if ($toolbar=="open") {
    $forum["toolbar"] = "open";
    URLHelper::addLinkParam('toolbar', $toolbar);
}
if ($toolbar=="close")
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
// loeschen von nicht zuende getippten Postings
//////////////////////////////////////////////////////////////////////////////////

if ($forum["lostposting"]!="" AND !isset($update)) {
    $writemode = $forum["lostposting"];
    $db=new DB_Seminar;
    $db->query("SELECT topic_id FROM px_topics WHERE topic_id='$writemode' AND mkdate=chdate+1");
    if ($db->num_rows()) {
        $count = 0;
        $result = forum_lonely(array('id'=>$writemode));
        if ($result['lonely']==TRUE) // nur löschen wenn noch keine Antworten, sonst stehenlassen
            delete_topic($writemode,$count);
        unset($result);
    }
    $forum["lostposting"]="";
}

//////////////////////////////////////////////////////////////////////////////////
// Rekursives Löschen von Postings, Warnung
//////////////////////////////////////////////////////////////////////////////////

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
            forum_draw_topicline();
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
            $msg.="<a href=\"".URLHelper::getLink("?topic_id=$root&open=$topic_id&view=$view&mehr=$mehr#anker")."\">" . makeButton("nein", "img") . "</a>\n";
            parse_msg($msg, '§', 'blank', '1', FALSE);
            echo "</table>";

        // Darstellung des zu loeschenden Postings

            echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0" align="center"><tr><td class="blank"><br><br>';
            echo '<table width="80%" class="blank" border="0" cellpadding="0" cellspacing="0" align="center"><tr>';



            printposting($forumposting);

            echo "<br></td></tr></table>\n<br></td></tr></table>";
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
            if ($nurneu!=1) { // es wurde wirklich was gelöscht und nicht nur ein Anlegen unterbrochen
                $message = "kill";
            }
            $forum["lostposting"]="";
        }
    }
}

//////////////////////////////////////////////////////////////////////////////////
// neuer Beitrag als Antwort wird eingeleitet
//////////////////////////////////////////////////////////////////////////////////

if ($answer_id) {
    $db=new DB_Seminar;
    $db->query("SELECT name, topic_id, root_id FROM px_topics WHERE topic_id = '$answer_id'");
    while($db->next_record()){
        $name = $db->f("name");
        if (substr($name,0,3)!="Re:")
            $name = "Re: ".$name; // Re: vor Überschriften bei Antworten
        $author = get_fullname();
        $postinginhalt = _("Dieser Beitrag wird gerade bearbeitet.");
        $edit_id = CreateNewTopic($name, $postinginhalt, $answer_id, $db->f("root_id"), $_REQUEST['anonymous']);
        $open = $edit_id;
        $forum["lostposting"] = $edit_id;
    }
}

//////////////////////////////////////////////////////////////////////////////////
// Update eines Beitrags
//////////////////////////////////////////////////////////////////////////////////

if ($update) {
    // check whether we should create a new posting or update an existing one
    if (isset($_REQUEST['parent_id'])) {
        $author = get_fullname();
        $parent_id = $_REQUEST['parent_id'];
        $root_id = $parent_id != "0" ? $_REQUEST['root_id'] : "0";
        $user_id = $auth->auth['uid'];
        $update = CreateTopic($titel, $author, $description, $parent_id, $root_id, 0, $user_id, true, $_REQUEST['anonymous']);
    } else {
        if (!ForumFreshPosting($update)) // editiert von nur dranhängen wenn nicht frisch erstellt
            $description = forum_append_edit($description);
        UpdateTopic ($titel, $update, $description, $_REQUEST['anonymous']);
    }
    $open = $update; //gerade bearbeiteten Beitrag aufklappen
    $forum["lostposting"] = "";

    // redirect to normal view to avoid duplicate postings on reload or back/forward
    header('Location: ' . URLHelper::getURL("?open=$update&flatviewstartposting=$flatviewstartposting#anker"));
    page_close();
    die;
}

//////////////////////////////////////////////////////////////////////////////////
// Neues Thema wird angelegt
//////////////////////////////////////////////////////////////////////////////////

if ($neuesthema==TRUE && ($rechte || $SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["topic_create_autor"])) {            // es wird ein neues Thema angelegt
        $name = _("Name des Themas");
        $author = get_fullname();
        $edit_id = CreateNewTopic($name, "Beschreibung des Themas", 0, 0, $_REQUEST['anonymous']);
        $open = $edit_id;
        $forum["lostposting"] = $edit_id;
}

//////////////////////////////////////////////////////////////////////////////////
// weitere Konstanten setzen
//////////////////////////////////////////////////////////////////////////////////

if ($zitat==TRUE)
    $zitat = $answer_id;

if ($edit_id)
    $open = $edit_id;

if ($flatallopen=="TRUE")
    $forum["flatallopen"] = "TRUE";

if ($flatallopen=="FALSE")
    $forum["flatallopen"] = "FALSE";

if ($fav)
    $forum["anchor"] = $fav; // Anker auf Favoriten
else
    $forum["anchor"] = $open; // Anker setzen

if ($rate) { // Objekt bewerten
    while(list($key,$value) = each($rate)) {
        $txt = object_add_rate ($key, $value);
        $forum["anchor"] = $key;
    }
}

if ($fav)   // zu den Favoriten hinzufügen/entfernen
    $fav = object_switch_fav($fav);




//////////////////////////////////////////////////////////////////////////////////
//Anzeige des Kopfes mit Meldungen und Toolbar
//////////////////////////////////////////////////////////////////////////////////

if ($forumsend!="anpassen") {

    forum_draw_topicline();

    // Ausgabe für Zusatzinfos
    if ($message=="kill") echo parse_msg("msg§" . sprintf(_("%s Posting(s) gel&ouml;scht"), $count));
    if ($message=="move") echo parse_msg("msg§" . sprintf(_("%s Posting(s) verschoben."), $verschoben));
    if ($txt) echo parse_msg("msg§" . $txt);
    if ($cmd == "move" && $topic_id !="" && $rechte)
        forum_move_navi ($topic_id);

    echo "\n</table>\n";
}

if (($forum["view"] != "search" || $forum["search"] != "") && $user->id != "nobody" && $cmd != "move")   // wenn Suchformular aufgerufen wird keine toolbar
    echo forum_print_toolbar($edit_id);
elseif ($user->id == "nobody" || $cmd=="move") {
    echo "\n<table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"blank\"><br></td></tr>";
    if ($edit_id)
        echo "<form name=forumwrite onsubmit=\"return pruefe_name()\" method=post action=\"".URLHelper::getLink("#anker")."\">";
}
//////////////////////////////////////////////////////////////////////////////////
// Verzweigung zu den Anzeigemodi
//////////////////////////////////////////////////////////////////////////////////

if ($forum["view"]=="flat" || $forum["view"]=="neue" || $forum["view"]=="flatfolder" || $forum["view"]=="search")
    flatview ($open, $mehr, $show, $edit_id, $name, $description, $zitat);
else
    DisplayFolders ($open, $edit_id, $zitat);

//////////////////////////////////////////////////////////////////////////////////
// Rest
//////////////////////////////////////////////////////////////////////////////////


// echo "Zeit:".(getMsTime()-$stoppuhr);
    include ('lib/include/html_end.inc.php');
    page_close();
?>
