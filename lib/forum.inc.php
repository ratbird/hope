<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter005: TEST
# Lifter010: TODO
/**
* helper functions for handling the board
*
* helper functions for handling boards
*
* @author           Ralf Stockmann <rstockm@uni-goettingen.de>
* @access           public
* @package          studip_core
* @modulegroup          library
* @module           forum.inc.php
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// forum.inc.php
// Copyright (c) 2003 Ralf Stockmann <rstockm@gwdg.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

use Studip\Button, Studip\LinkButton;

require_once 'lib/classes/Avatar.class.php';
require_once 'lib/classes/Modules.class.php';

/**
 * deletes the edit-string from content
 *
 * @param   string  description
 *
 * @return  string  description
 *
 */
function forum_kill_edit ($description) {
    if (preg_match('/^(.*)(<admin_msg.*?)$/s',$description, $match)) { // wurde schon mal editiert
        return $match[1];
    }
    return $description;
}

/**
 * adds the edit-string to a content
 *
 * @param   string  description
 *
 * @return  string  description
 *
 */
function forum_append_edit ($description) {
    $edit = "<admin_msg autor=\"".addslashes(get_fullname())."\" chdate=\"".time()."\">";
    //$description = forum_kill_edit($description).$edit;
    return $description . $edit;
}

/**
 * parses content for output with added edit-string
 *
 * @param   string  description
 * @param   boolean anonymous
 *
 * @return  string  description
 *
 */
function forum_parse_edit ($description, $anonymous) {
    global $perm;
    if (preg_match('/^.*(<admin_msg.*?)$/s',$description, $match)) { // wurde schon mal editiert
        $tmp = explode('"',$match[1]);
        // Check if posting was anonymous and hide author accordingly.
        if (get_config('FORUM_ANONYMOUS_POSTINGS') && ($anonymous && !$perm->have_perm('root'))) {
            $message = _("Zuletzt editiert:").' ';
        } else {
            $message = _("Zuletzt editiert von").' '.$tmp[1].' - ';
        }
        // use special markup [admin_msg]. (cf. http://develop.studip.de/trac/ticket/335 )
        $append = "\n\n[admin_msg][".$message.date ("d.m.y - H:i", $tmp[3])."][/admin_msg]";
        $description = forum_kill_edit($description) . $append;
    }
    return $description;
}

/**
* Builds the edit-Area for created postings or postings being re-editet
*
* @param    array   forumposting contains several data of the actual posting
*
* @return   string  description contains the complete html-data of the edit-area
*
**/
function editarea($forumposting) {
    global $forum, $user, $auth;

    if ($auth->auth["jscript"]) {
        $max_col = round($auth->auth["xres"] / 12 );
    }
    else
        $max_col =  64 ; //default für 640x480

    $cols = round($max_col*0.45);
    if ($cols < 28) $cols = 28;

    $url = URLHelper::getURL("?#anker",
                             array("open" =>
                                   Request::get("neuesthema")
                                   ? null
                                   : $forumposting["rootid"]));
    $zusatz = LinkButton::createCancel(_("Abbrechen"), $url);

    $help_url = format_help_url("Basis.VerschiedenesFormat");
    $zusatz .= "&nbsp;&nbsp;<a href=\"".URLHelper::getLink('dispatch.php/smileys')."\" target=\"_blank\"><font size=\"-1\">"._("Smileys")."</a>&nbsp;&nbsp;"."<a href=\"".$help_url."\" target=\"_blank\"><font size=\"-1\">"._("Formatierungshilfen")."</a>";
    if ($forumposting["writestatus"] == "new") { // es ist ein neuer Beitrag, der Autor sieht dann:
        $description = _("Ihr Beitrag");
    } else {
        $description = $forumposting["description"];  // bereits bestehender Text
    }

    $description = forum_kill_edit($description);

    if ($forum["zitat"]!="") {
        $zitat = quote($forum["zitat"]);
        $description="";
    }
    if ($user->id == "nobody") {  // nicht angemeldete muessen Namen angeben
        $description =  "<label><b>" . _("Ihr Name:") . "</b>&nbsp; <input id=\"namenobody\" type=text size=50 name=nobodysname onchange=\"STUDIP.Forum.pruefe_name()\" value=\"" . _("unbekannt") . "\"></label><br><br><input type=hidden name=update value='".$forumposting["id"]."'>"
                ."<div align=center><textarea aria-label=\"" . _("Text des Beitrags") . "\" name=\"description\" class=\"add_toolbar resizable\" style=\"width:70%\" cols=\"". $cols."\" rows=12 wrap=virtual>"
                .htmlReady($description)
                .htmlReady($zitat)
                ."</textarea>";
    } else {
        $description =  "<input type=hidden name=update value='".$forumposting["id"]."'>"
                ."<div align=center><textarea aria-label=\"" . _("Text des Beitrags") . "\" name=\"description\" class=\"add_toolbar resizable\" style=\"width:70%\" cols=\"". $cols."\"  rows=12 wrap=virtual>"
                .htmlReady($description)
                .htmlReady($zitat)
                ."</textarea>";
        }
    if ($forumposting['new']) {
        $description .= '<input type="hidden" name="root_id" value="'.$forumposting['rootid'].'">';
        $description .= '<input type="hidden" name="parent_id" value="'.$forumposting['parent_id'].'">';
        if (get_config('FORUM_ANONYMOUS_POSTINGS')) {
          $description .= '<div align="center"><label><input id="input_anonymous" type="checkbox" name="anonymous" value="1">'._('Beitrag anonym verfassen').'</label></div>';
        }
    } else {
        if (get_config('FORUM_ANONYMOUS_POSTINGS') && $forumposting['anonymous']) {
            $description .= '<input type="hidden" name="anonymous" value="1">';
        }
    }
    $description .= "<br><br><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"160\" height=\"1\">";
    $description .= Button::createAccept(_("Abschicken"), "abschicken");

    $description .= $zusatz ."</div>";
    return $description;
}

/**
* Builds a unique topic_id in table px_topics
*
* @return   string  tmp_id is a unique id
*
**/
function MakeUniqueID ()
{   // baut eine ID die es noch nicht gibt

    $hash_secret = "kertoiisdfgz";
    $db=new DB_Seminar;
    $tmp_id=md5(uniqid($hash_secret));

    $db->query ("SELECT topic_id FROM px_topics WHERE topic_id = '$tmp_id'");
    if ($db->next_record())
        $tmp_id = MakeUniqueID(); //ID gibt es schon, also noch mal
    return $tmp_id;
}

/**
* Moves postings into a different lecture
*
* @param    string topic_id posting to be moved (inc. childs)
* @param    string sem_id id of the target
* @param    string root
* @param    string verschoben count of moved postings
*
* @return   string  verschoben count of moved postings
*
**/
function move_topic($topic_id, $sem_id, $root, &$verschoben)  //rekursives Verschieben von topics, in anderes Seminar
{
    global $rechte;
    if ($rechte) {
        $db=new DB_Seminar;
        $db->query("SELECT topic_id FROM px_topics WHERE parent_id='$topic_id'");
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $next_topic=$db->f("topic_id");
                move_topic($next_topic,$sem_id,$root,$verschoben);
                }
            }
        if ($root == $topic_id)
            $db->query("UPDATE px_topics SET parent_id=0, root_id='$topic_id', Seminar_id='$sem_id' WHERE topic_id='$topic_id'");
        else
            $db->query("UPDATE px_topics SET root_id='$root', Seminar_id='$sem_id' WHERE topic_id='$topic_id'");
        $verschoben++;
        return $verschoben;
    }
}

/**
* Moves postings into a different folder
*
* @param    string topic_id posting to be moved (inc. childs)
* @param    string root
* @param    string verschoben count of moved postings
* @param    string thema id of the target
*
* @return   string  verschoben count of moved postings
*
**/
function move_topic2($topic_id, $root, &$verschoben,$thema)  //rekursives Verschieben von topics, diesmal in ein Thema
{   global $rechte;
    if ($rechte) {
        $db=new DB_Seminar;
        $db->query("SELECT topic_id FROM px_topics WHERE parent_id='$topic_id'");
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $next_topic=$db->f("topic_id");
                move_topic2($next_topic,$root,$verschoben,$thema);
                }
            }
        if ($root == $topic_id)
            $db->query("UPDATE px_topics SET parent_id='$thema', root_id='$thema' WHERE topic_id='$topic_id'");
        else
        $db->query("UPDATE px_topics SET root_id='$thema' WHERE topic_id='$topic_id'");
        $verschoben++;
        return $verschoben;
    }
}

/**
* Checks whether there can be ditet or not (seeks childs an rights)
*
* @param    string topic_id posting to be checked
*
* @return   bool    lonely
*
**/
function lonely($topic_id)  //Sucht nach Kindern und den Rechten (für editieren)
{   global $user,$auth,$rechte;
    $lonely=TRUE;
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db2->query("SELECT topic_id FROM px_topics WHERE parent_id='$topic_id'");
    if (!$db2->num_rows()) {
        $db->query("SELECT user_id, chdate, mkdate FROM px_topics WHERE topic_id='$topic_id'");
        if ($db->num_rows())
            while ($db->next_record()) {
                if ($db->f("user_id")==$user->id OR $rechte)
                    $lonely=FALSE;
                elseif ($user->id=="nobody" AND $db->f("chdate") < $db->f("mkdate"))     // nobody schreibt an seinem anderen Beitrag, nachträgliches editieren nicht möglich
                    $lonely=FALSE;
            }
    }

    return $lonely;
}

/**
* builds a string of opened postings, separated by ;
*
* @param    string the original posting
*
* @return   string  open the string of opened postings
*
**/
function suche_kinder($topic_id)  //Sucht alle aufgeklappten Beitraege raus
{   global $_open;
    $db=new DB_Seminar;
    $db->query("SELECT topic_id FROM px_topics WHERE parent_id='$topic_id'");
    if ($db->num_rows()) {
        while ($db->next_record()) {
            $next_topic=$db->f("topic_id");
            suche_kinder($next_topic);
            }
        }
    $_open .= ";".$topic_id;
    return $_open;
}

/**
* Ckeck whether a posting is opened or not
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array   forumposting whith additional openclose flag
*
**/
function ForumOpenClose ($forumposting) {
    global $forum;
    if (strstr($forum["openlist"],$forumposting["id"])!=TRUE
    AND !(Request::option('openall') == "TRUE" && $forumposting["rootid"] == Request::option('folderopen'))
    AND !(($forum["view"]=="flat" || $forum["view"]=="neue" || $forum["view"]=="flat" || $forum["view"]=="flatfolder" || $forum["view"]=="search") && $forum["flatallopen"]=="TRUE")
    AND !($forumposting["newold"]=="new" && $forum["neuauf"]==1)
    AND !Request::option('delete_id')
    AND ($forumposting["writestatus"]=="none")) {
        $forumposting["openclose"] = "close";
    } else {
        $forumposting["openclose"] = "open";
    }
    return $forumposting;
}

/**
* Ckeck whether a posting is new or old
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array   forumposting whith additional newold flag
*
**/
function ForumNewPosting ($forumposting) {
    global $SessSemName;
    $datumtmp = object_get_visit($SessSemName[1], "forum");
    if ($datumtmp < $forumposting["chdate"]) {
        $forumposting["newold"] = "new";  //Beitrag neu
    } else {
        $forumposting["newold"] = "old";  //Beitrag alt
    }
    return $forumposting;
}

/**
* Ckeck whether a posting has childs or not
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array   forumposting whith additional lonely flag
*
**/
function forum_lonely($forumposting) {  //Sieht nach ob das Posting kinderlos ist

    $topic_id = $forumposting["id"];
    $db=new DB_Seminar;
    $db->query("SELECT topic_id FROM px_topics WHERE parent_id='$topic_id'");
        if (!$db->num_rows())
            $forumposting["lonely"]=TRUE;
        else
            $forumposting["lonely"]=FALSE;
    return $forumposting;
}

/**
* Gets the id of the root-posting (theme)
*
* @param    string id of the child-posting
*
* @return   string  root_id the id of the root-posting
*
**/
function ForumGetRoot($id) {  //Holt die ID des Root-Postings

    $db=new DB_Seminar;
    $db->query("SELECT root_id FROM px_topics WHERE topic_id='$id'");
    if ($db->next_record())
        $root_id = $db->f("root_id");
    return $root_id;
}

/**
* Gets the id of the parent-posting
*
* @param    string id of the child-posting
*
* @return   string  parent_id the id of the root-posting
*
**/
function ForumGetParent($id) {  //Holt die ID des Parent-Postings (wird für Schreibanzeige gebraucht)

    $db=new DB_Seminar;
    $db->query("SELECT parent_id FROM px_topics WHERE topic_id='$id'");
    if ($db->next_record())
        $parent_id = $db->f("parent_id");
    return $parent_id;
}

/**
* check whether a posting is fresh or not
*
* @param    string id of the posting
*
* @return   bool    fresh indikates freshness
*
**/
function ForumFreshPosting($id) {  //Sieht nach ob das Posting frisch angelegt ist (mkdate ist gleich chdate)
    $db=new DB_Seminar;
    $db->query("SELECT chdate, mkdate FROM px_topics WHERE topic_id='$id' AND chdate < mkdate");
    IF ($db->num_rows()) {
        $fresh = TRUE;
    } else {
        $fesh = FALSE;
    }
    return $fresh;
}

/**
* Check whether a posting is a folder (theme) or not
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array forumposting with additional type flag
*
**/
function ForumFolderOrPosting ($forumposting) {
    if ($forumposting["id"]==$forumposting["rootid"]) {
        $forumposting["type"] = "folder";  //Beitrag ist ein folder
    } else {
        $forumposting["type"] = "posting";  //Beitrag alt
    }
    return $forumposting;
}

/**
* Check of the write-state of a posting
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array forumposting with additional writestatus flag
*
**/
function ForumGetWriteStatus($forumposting) {
    global $forum;
    if ($forumposting["id"] == $forum["update"]) {              // das Posting ist im Schreibmodus
        if ($forumposting["chdate"] < $forumposting["mkdate"]) {    // das Posting ist frisch angelegt und noch nicht geschrieben
            $forumposting["writestatus"] = "new";
        } else {                    // das Posting wird editiert
            $forumposting["writestatus"] = "update";
        }
    } else {                        // das Posting ist nicht im Schreibmodus
        $forumposting["writestatus"] = "none";
    }
    return $forumposting;
}

/**
* Check whether user has rights on posting or not
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array forumposting with additional perms flag
*
**/
function ForumGetRights ($forumposting) {
    global $user,$auth,$rechte;
    if ($forumposting["userid"]==$user->id || $rechte)
        $forumposting["perms"] = "write";
    else
        $forumposting["perms"] = "none";
    return $forumposting;
}

/**
 * Checks whether a given posting was posted anonymously and overwrites
 * the author if necessary.
 *
 * @param mixed $forumposting
 *
 * @return mixed The forum posting with obscured author and user id.
 */
function ForumGetAnonymity ($forumposting) {

    global $perm, $user;

    // die Anonymität wird immer auf false gesetzt, wenn Root den Beitrag anzeigt
    if ($perm->have_perm("root")) {
        $forumposting["anonymous"] = false;
    }

    // Falls anonym, den Namen des Posters unkenntlich machen
    if (get_config('FORUM_ANONYMOUS_POSTINGS') && $forumposting["anonymous"]) {
        // Falls ich selber der Autor bin, steht mein Name mit Anonymitäts-Hinweis auf der Seite
        if ($forumposting["userid"]==$user->id) {
            $forumposting["author"] = $forumposting["author"] . " " . _("(anonym)");
        }
        else {
            $forumposting["author"] = _("anonym");
            $forumposting["username"] = "";
        }
    }

    return $forumposting;
}

/**
* builds the icon for the printhead of a posting
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array forumposting with additional icon flag
*
**/
function ForumIcon ($forumposting) {
    global $rechte, $forum, $auth, $perm;
    $topic_id = Request::option('topic_id');
    $anonymous = false;
    if (get_config('FORUM_ANONYMOUS_POSTINGS') && ($forumposting["anonymous"] || $perm->have_perm('root'))) {
        $anonymous = true;
    }
    if ($forumposting["type"]=="folder") {
        if ($forumposting["lonely"]==FALSE)
            $bild = $GLOBALS['ASSETS_URL']."images/icons/16/blue/folder-full.png";
        else
            $bild = $GLOBALS['ASSETS_URL']."images/icons/16/blue/folder-empty.png";
    } else {
        if ($forumposting["shrink"] == TRUE && $forumposting["lonely"]==FALSE) {
            $bild = $GLOBALS['ASSETS_URL']."images/icons/16/blue/forum-shrink.png";
            $addon = tooltip(sprintf(_("komprimiertes Thema mit %s Forenbeiträgen"), $forumposting["shrinkcount"]));
        } else
            $bild = $GLOBALS['ASSETS_URL']."images/icons/16/blue/forum.png";
    }

    if ($forum["jshover"]==1 AND $auth->auth["jscript"] AND $forumposting["description"]!="" && $forumposting["openclose"]=="close") {
        $forumposting["icon"] = "<img class=\"forum-icon\" src=\"".$bild."\" border=0 data-forumid=\"'".$forumposting["id"]."'\">";
        if ($forum["view"]=="tree" && $forumposting["type"]=="folder") { // wir kommen aus der Themenansicht
            $forumposting["icon"] = "<a href=\"".URLHelper::getLink("?open=".$forumposting["id"]."&openall=TRUE#anker")."\">"
              . $forumposting["icon"] . "</a>";
        }
    } else {
        if ($forum["view"]=="tree" && $forumposting["type"]=="folder")
            $forumposting["icon"] = "<a href=\"".URLHelper::getLink("?open=".$forumposting["id"]."&folderopen=".$forumposting["id"]."&openall=TRUE#anker")."\"><img src=\"".$bild."\" border=0 " . tooltip(_("Alle Forenbeiträge im Thema öffnen")) . "></a>";
        else
            $forumposting["icon"] = "<img src=\"".$bild."\" $addon>";
    }
    if (Request::option('cmd') == "move" && $rechte && $topic_id != $forumposting["id"] )  // ein Beitrag wird verschoben, gelbe Pfeile davor
        $forumposting["icon"] =  "<a href=\"".URLHelper::getLink("?target=Thema&move_id=".$topic_id."&parent_id=".$forumposting["id"])."\">"
                    ."<img src=\"" . Assets::image_path('icons/16/yellow/arr_2right.png') . "\" " . tooltip(_("Forenbeiträge in dieses Thema verschieben")) . "></a>"
                    .$forumposting["icon"];
    return $forumposting;
}

/**
* quote engine for a quoted posting
*
* @param    string zitat_id id of the posting to be quoted
*
* @return   string zitat is the quoted string
*
**/
function quote($zitat_id)  {
    global $perm;
// Hilfsfunktion, die sich den zu quotenden Text holt, encodiert und zurueckgibt.
    $db=new DB_Seminar;
    $db->query("SELECT description, author, anonymous FROM px_topics WHERE topic_id='$zitat_id'");
        while ($db->next_record()) {
            $description = $db->f("description");
            if (get_config('FORUM_ANONYMOUS_POSTINGS') && ($db->f("anonymous") && !$perm->have_perm("root"))) {
                $author = _("anonym");
            } else {
                $author = $db->f("author");
            }
        }
    $description = forum_kill_edit($description);
    $zitat = quotes_encode($description,$author);
    return $zitat;
}

/**
* Gets the title of a posting
*
* @param    string  id of the posting
*
* @return   $name the name of the posting
*
**/
function ForumGetName($id)  {
// Hilfsfunktion, die sich den Titel eines Beitrags holt
    $db=new DB_Seminar;
    $db->query("SELECT name FROM px_topics WHERE topic_id='$id'");
        if ($db->next_record())
            $name = $db->f("name");
    return $name;
}

/**
* builds the button-line for an opened posting
*
* @param    array forumposting contains several data of the actual posting
*
* @return   string edit contains the HTML of the button-line
*
**/
function forum_get_buttons ($forumposting) {
    global $rechte, $forum, $user;

    $view = Request::option('view');
    if (!(have_sem_write_perm())) {  // nur mit Rechten...
        $edit = forum_get_buttons_authorized($forumposting);
    } elseif ($user->id == "nobody") {  // darf Nobody hier schreiben?
        $edit = forum_get_buttons_nobody($forumposting);
    } else { // nix mit Rechten
        $edit = "";
    }
    return $edit;
}


function forum_get_buttons_authorized($forumposting)
{
    global $rechte, $forum;

    $view = Request::option('view');

    $tmp = array();
    if ($view == "search") {
        $tmp = array("view" => "tree");
    } else if ($view == "mixed") {
        $tmp = array("open" => $forumposting["id"], "view" => "flatfolder");
    }

    $edit = "";

    $attributes = $tmp + array(
        "answer_id"            => $forumposting["id"],
        "flatviewstartposting" => 0,
        "shrinkopen"           => $forumposting["rootid"],
        "sort"                 => "age");
    $edit .= LinkButton::create(_("Antworten"), URLHelper::getURL("#anker", $attributes));

    $attributes = $tmp + array(
        "answer_id" => $forumposting["id"],
        "zitat" => 1,
        "flatviewstartposting" => 0,
        "shrinkopen" => $forumposting["rootid"],
        "sort" => "age");
    $edit .= LinkButton::create(_("Zitieren"), URLHelper::getURL("#anker", $attributes));

    // ich darf bearbeiten
    if ($forumposting["lonely"] && ($rechte || $forumposting["perms"] == "write")) {
        $attributes = array(
            "edit_id"              => $forumposting["id"],
            "view"                 => $forum["view"],
            "flatviewstartposting" => $forum["flatviewstartposting"]
        );
        $edit .= LinkButton::create(_("Bearbeiten"), URLHelper::getURL("#anker", $attributes));
    }

    // ich darf löschen
    if ($rechte || ($forumposting["lonely"] && $forumposting["perms"] == "write")) {
        $attributes = array(
            "delete_id"            => $forumposting["id"],
            "view"                 => $forum["view"],
            "flatviewstartposting" => $forum["flatviewstartposting"]
        );
        $edit .= LinkButton::create(_("Löschen"), URLHelper::getURL("", $attributes));
    }

    // ich darf verschieben
    if ($rechte) {
        $attributes = array(
            "cmd"      => "move",
            "topic_id" => $forumposting["id"],
            "view"     => "tree"
        );
        $edit .= LinkButton::create(_("Verschieben"), URLHelper::getURL("", $attributes));
    }

    return $edit;
}


function forum_get_buttons_nobody($forumposting)
{
    $edit = '';
    $db = new DB_Seminar();
    $db->query("SELECT Seminar_id FROM seminare WHERE Seminar_id='{$_SESSION['SessionSeminar']}' AND Schreibzugriff=0");
    if ($db->num_rows()) {

        $attributes = array(
            "answer_id"            => $forumposting["id"],
            "flatviewstartposting" => 0
        );
        $edit .= LinkButton::create(_("Antworten"), URLHelper::getURL("#anker", $attributes));

        $attributes = array(
            "answer_id" => $forumposting["id"],
            "zitat" => 1,
            "flatviewstartposting" => 0
        );
        $edit .= LinkButton::create(_("Zitieren"), URLHelper::getURL("#anker", $attributes));
    }

    return $edit;
}


/**
* Debug Code for var-output
*
* @param    array debugvar ($forum or $forumposting)
*
* @return   string debug the debug-output of the array
*
**/
function DebugForum ($debugvar) {

    while(list($key,$value) = each($debugvar))
        $debug .= "$key: $value<br>";
    $debug .= "<hr>";
    while(list($key,$value) = each($_POST))
        $debug .= "$key: $value<br>";
    return $debug;
}

/**
* builds the output of an empty board
*
* @return   string empty contains the HTML of the empty board
*
**/
function ForumEmpty () {
    global $rechte, $SessSemName;
    if ($rechte)
        $text = _("In diesem Forum wurde noch kein Themenordner angelegt.<br>Sie können oben unter <b>neues Thema</b> einen Themenordner anlegen.");
    else {
        if ($SessSemName["class"]!="inst")
             $text = sprintf(_("In diesem Forum wurde noch kein Themenordner angelegt.<br>Kontaktieren Sie eine/n %s oder eine/n %s dieser Veranstaltung, um Themenordner anlegen zu lassen."),get_title_for_status('tutor',1),get_title_for_status('dozent',1));
        else
             $text = _("In diesem Forum wurde noch kein Themenordner angelegt.<br>Kontaktieren Sie den/die AdministratorIn der Einrichtung, um Themenordner anlegen zu lassen.");
    }
    $empty = parse_msg('info§'.$text);
    return $empty;
}

/**
* builds the output of an empty site (empty search for example)
*
* @return   string empty contains the HTML of the empty site
*
**/
function ForumNoPostings () {
    global $forum;
    if ($forum["view"] != "search")
        $text = _("In dieser Ansicht gibt es derzeit keine Forenbeiträge.");
    else
        $text = sprintf(_("Zu Ihrem Suchbegriff '%s' gibt es keine Treffer."), htmlReady($forum['searchstring'])) .
            "<br><a href=\"".URLHelper::getLink("?view=search&reset=1")."\">" . _("Neue Suche") . "</a>";
    $empty .= parse_msg("info§$text");
    return $empty;
}

// Berechnung und Ausgabe der Blätternavigation

/**
* builds the navigation element in page-view
*
* @param    array   forum contains several data of the actual board-site
*
* @return   string  navi contains the HTML of the navigation
*
**/
function forum_print_navi ($forum) {
    $i = 1;
    $maxpages = ceil($forum["forumsum"] / $forum["postingsperside"]);
    $ipage = ($forum["flatviewstartposting"] / $forum["postingsperside"])+1;
    if ($ipage != 1)
        $navi .= "<a href=\"".URLHelper::getLink("?flatviewstartposting=".($ipage-2)*$forum["postingsperside"])."\"><font size=-1>" . _("zurück") . "</a> | </font>";
    else
        $navi .= "<font size=\"-1\">Seite: </font>";
    while ($i <= $maxpages) {
        if ($i == 1 || $i+2 == $ipage || $i+1 == $ipage || $i == $ipage || $i-1 == $ipage || $i-2 == $ipage || $i == $maxpages) {
            if ($space == 1) {
                $navi .= "<font size=-1>... | </font>";
                $space = 0;
            }
            if ($i != $ipage)
                $navi .= "<a href=\"".URLHelper::getLink("?flatviewstartposting=".($i-1)*$forum["postingsperside"])."\"><font size=-1>".$i."</a></font>";
            else
                $navi .= "<font size=\"-1\"><b>".$i."</b></font>";
            if ($maxpages != 1)
                $navi .= "<font size=\"-1\"> | </font>";
        } else {
            $space = 1;
        }
        $i++;
    }
    if ($ipage != $maxpages)
        $navi .= "<a href=\"".URLHelper::getLink("?flatviewstartposting=".($ipage)*$forum["postingsperside"])."\"><font size=-1> " . _("weiter") . "</a></font>";
    return $navi;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
* creates a new posting in the DB
*
* @param    string name of the posting
* @param    string author of the posting (plaintext)
* @param    string description the content of the posting
* @param    string parent_id of the posting
* @param    string root_id of the posting
* @param    string tmpSessionSeminar
* @param    string user_id of the author
* @param    boolean writeextern
* @param    boolean anonymous posting?
*
* @return   string topic_id of the new posting
*
**/
function CreateTopic ($name="[no name]", $author="[no author]", $description="", $parent_id="0", $root_id="0", $tmpSessionSeminar=0, $user_id=FALSE, $writeextern=TRUE, $anonymous=false)
{
    static $count;

    global $auth, $user, $perm;
    if (!$tmpSessionSeminar)
        $tmpSessionSeminar = $_SESSION['SessionSeminar'];
    $db=new DB_Seminar;
    $mkdate = time();

    $mkdate += $count++; //übler Hack,um Sortierreihenfolge für den DateAssi zu bekommen :)

    if ($writeextern == FALSE) {
        $chdate = $mkdate-1;    // der Beitrag wird für alle ausser dem Author "versteckt"
    }
    else {
        $chdate = $mkdate;  // normales Anlegen
    }
    if (!$user_id) {
        $db->query ("SELECT user_id , username FROM auth_user_md5 WHERE username = '".$auth->auth["uname"]."' ");
        while ($db->next_record())
            $user_id = $db->f("user_id");
    }

    if ($root_id != "0")    {
        $db->query ("SELECT seminar_id FROM px_topics WHERE topic_id = '$root_id'");
        if ($db->next_record()){
            if ($db->f("seminar_id") != $tmpSessionSeminar)
                $tmpSessionSeminar = $db->f("seminar_id");
        }
    }

    $topic_id = MakeUniqueID();
    if ($root_id == "0")    {
        $root_id = $topic_id;
        }

    $query = 'INSERT INTO px_topics (topic_id,name,description, parent_id, root_id , author, author_host, Seminar_id, user_id, mkdate, chdate, anonymous) ';
    $query .= "values ('$topic_id', '$name', '$description', '$parent_id', '$root_id', '".mysql_escape_string($author)."', '".getenv("REMOTE_ADDR")."', '$tmpSessionSeminar', '$user_id', '$mkdate', '$chdate', ".($anonymous ? 1 : 0).") ";
    $db=new DB_Seminar;

    if ($user->id == "nobody") {    // darf Nobody hier schreiben?
        $db->query("SELECT Seminar_id FROM seminare WHERE Seminar_id='{$_SESSION['SessionSeminar']}' AND Schreibzugriff=0");
        if (!$db->num_rows()) {
            throw new AccessDeniedException(_("Ihnen fehlen die Rechte, in dieser Veranstaltung zu schreiben."));
        }
        else
            $db->query ($query);
    }

    if ($perm->have_perm("autor"))
        $db->query ($query);
    if  ($db->affected_rows() == 0) {
        throw new Exception(_("Fehler beim Anlegen eines Forenbeitrags."));
    }
    return $topic_id;
}

/**
 * Creates a new posting that is not yet stored in the DB
 *
 * @param   string name of the posting
 * @param   string description the content of the posting
 * @param   string parent_id of the posting (optional)
 * @param   string root_id of the posting (optional)
 * @param   boolean anonymous posting?
 *
 * @return  string topic_id of the new posting
 */
function CreateNewTopic ($name, $description, $parent_id="0", $root_id="0", $anonymous=false)
{
    global $auth, $new_topic;

    $mkdate = time();
    $chdate = $mkdate-1;
    $author = get_fullname();
    $username = get_username();
    $user_id = $auth->auth['uid'];
    $topic_id = MakeUniqueID();

    if ($root_id == "0") {
        $root_id = $topic_id;
    }

    $new_topic = array(
        'id'          => $topic_id,
        'name'        => $name,
        'description' => $description,
        'author'      => $author,
        'username'    => $username,
        'userid'      => $user_id,
        'rootid'      => $root_id,
        'mkdate'      => $mkdate,
        'chdate'      => $chdate,
        'parent_id'   => $parent_id,
        'new'         => true,
        'anonymous'   => $anonymous
    );

    return $topic_id;
}

/**
* Updates an existing posting
*
* @param    string  name of the posting
* @param    string  topic_id of the posting
* @param    string  description of the posting
* @param    boolean anonymous
*
**/
function UpdateTopic ($name="[no name]", $topic_id, $description, $anonymous)
{
    $db=new DB_Seminar;
    $chdate = time();
    if (lonely($topic_id)==FALSE) {
        $query = "UPDATE px_topics SET name = '$name', description = '$description', chdate= '$chdate', anonymous=".($anonymous ? 1 : 0)." WHERE topic_id = '$topic_id'";
        $db->query ($query);
        if ($db->affected_rows() == 0) {
            throw new Exception(_("Aktualisieren des Forenbeitrags fehlgeschlagen."));
        }
    } else {
        throw new AccessDeniedException(_("Ihnen fehlen die Rechte, diesen Forenbeitrag zu bearbeiten."));
    }
}

/**
* builds right side of the printhead
*
* @param    array   forumhead contains several GUI-Elements of the posting
*
* @return   string  zusatz the HTML-Output of the right printhead region
*
**/
function ForumParseZusatz($forumhead) {

    while(list($key,$value) = each($forumhead))
        $zusatz .= $value;
    return $zusatz;
}

/**
* engine to create the amazing HTML-Lines of child-postings
*
* @param    array   forumposting contains several data of the actual posting
*
* @return   string  striche the HTML-Output for the lines
*
**/
function ForumStriche($forumposting) {
    $striche = "<td class=\"blank tree-indent\" nowrap background='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'></td>";
    for ($i=0;$i<$forumposting["level"];$i++) {
        if ($forumposting["lines"][$i+1]==0)
            $striche .= "<td class=\"blank tree-indent\" nowrap background='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'></td>";
        else
            $striche .= "<td class=\"blank tree-indent\" nowrap background='".$GLOBALS['ASSETS_URL']."images/forumstrich.gif'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer2.gif'></td>";
    }
    if ($forumposting["lonely"]==FALSE)
        $striche.= "<td class=\"blank tree-indent\" nowrap background=\"".$GLOBALS['ASSETS_URL']."images/forumstrichgrau.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"></td>";
    else
        $striche.= "<td class=\"blank tree-indent\" nowrap background=\"".$GLOBALS['ASSETS_URL']."images/steel1.jpg\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"></td>";
    return $striche;
}

/**
* Builds the toolbar for indikators an sort-options
*
* @param    string  id
*
* @return   string  print the HTML-Output of the toolbar
*
**/
function forum_print_toolbar ($id="") {
        global $user, $forum, $indexvars;
        $open = Request::option('open');
        $flatviewstartposting = Request::int('flatviewstartposting');
        $print = "<table class=\"blank\" width=\"100%\" border=0 cellpadding=0 cellspacing=0><tr><td class=\"blank\">";
        if ($forum["toolbar"] == "open") {
            if ($forum["view"] != "tree" && $forum["view"] != "mixed") {
                $print .= "<form name=\"sortierung\" method=\"post\" action=\"".URLHelper::getLink("#anker")."\">";
                $print .= CSRFProtection::tokenTag();
            }
            $print .= "<table class=\"blank\" width=\"100%\" border=0 cellpadding=0 cellspacing=0><tr><td class=\"blank\">&nbsp;</td></tr><tr>";
            $print .= "<td class=\"steelkante\" valign=\"middle\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"22\" width=\"5\"></td>";
            $print .= "<td class=\"steelkante\" valign=\"middle\"><font size=\"-1\">"._("Indikator:")."&nbsp;</font>";

            if ($forum["indikator"] == "age")
                $print .=  "</td><td nowrap class=\"steelgraulight_shadow\" valign=\"middle\">&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" align=\"absmiddle\"><font size=\"-1\">".$indexvars["age"]["name"]." </font>&nbsp;";
            else
                $print .=  "</td><td nowrap class=\"steelkante\" valign=\"middle\">&nbsp;<a href=\"".URLHelper::getLink("?flatviewstartposting=$flatviewstartposting&open=$open&indikator=age")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/arr_1right.png\" border=\"0\" align=\"absmiddle\"><font size=\"-1\" color=\"#555555\">".$indexvars["age"]["name"]."</font></a> &nbsp;";
            if ($forum["indikator"] == "viewcount")
                $print .=  "</td><td nowrap class=\"steelgraulight_shadow\" valign=\"middle\">&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/green/arr_1right.png\" align=\"absmiddle\"><font size=\"-1\">".$indexvars["viewcount"]["name"]." </font>&nbsp;";
            else
                $print .=  "</td><td nowrap class=\"steelkante\" valign=\"middle\">&nbsp;<a href=\"".URLHelper::getLink("?flatviewstartposting=$flatviewstartposting&open=$open&indikator=viewcount")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/arr_1right.png\" border=\"0\" align=\"absmiddle\"><font size=\"-1\" color=\"#555555\">".$indexvars["viewcount"]["name"]."</font></a> &nbsp;";
            if ($forum["indikator"] == "rating")
                $print .=  "</td><td nowrap class=\"steelgraulight_shadow\" valign=\"middle\">&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_1right.png\" align=\"absmiddle\"><font size=\"-1\">".$indexvars["rating"]["name"]." </font>&nbsp;";
            else
                $print .=  "</td><td nowrap class=\"steelkante\" valign=\"middle\">&nbsp;<a href=\"".URLHelper::getLink("?flatviewstartposting=$flatviewstartposting&open=$open&indikator=rating")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/arr_1right.png\" border=\"0\" align=\"absmiddle\"><font size=\"-1\" color=\"#555555\">".$indexvars["rating"]["name"]."</font></a> &nbsp;";
            if ($forum["indikator"] == "score")
                $print .=  "</td><td nowrap class=\"steelgraulight_shadow\" valign=\"middle\">&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1right.png\" align=\"absmiddle\"><font size=\"-1\">".$indexvars["score"]["name"]." </font>&nbsp;";
            else
                $print .=  "</td><td nowrap class=\"steelkante\" valign=\"middle\">&nbsp;<a href=\"".URLHelper::getLink("?flatviewstartposting=$flatviewstartposting&open=$open&indikator=score")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/arr_1right.png\" border=\"0\" align=\"absmiddle\"><font size=\"-1\" color=\"#555555\">".$indexvars["score"]["name"]."</font></a> &nbsp;";

            if ($forum["view"] != "tree" && $forum["view"] != "mixed") { // Anzeige der Sortierung nicht in der Themenansicht
                $print .= "</td><td nowrap class=\"steelkante\" valign=\"middle\">&nbsp;|&nbsp;&nbsp;<font size=\"-1\">Sortierung:&nbsp;&nbsp;</font>";
                $print .= "</td><td nowrap class=\"steelkante\" valign=\"middle\"><select name=\"sort\" size=\"1\">";
                $tmp["age"] = "Alter";
                $tmp["viewcount"] = $indexvars["viewcount"]["name"];
                $tmp["rating"] = $indexvars["rating"]["name"];
                $tmp["score"] = $indexvars["score"]["name"];
                $tmp["fav"] = _("Favoriten");
                $tmp["nachname"] = _("Autor");
                $tmp["root_name"] = _("Thema");
                $tmp["name"] = _("Titel");
                while(list($key,$value) = each($tmp)) {
                    $print .= "<option value=\"$key\"";
                    if ($key == $forum["sort"]) $print.= " selected";
                    $print .= ">$value";
                }
                $print .= "</select>&nbsp;&nbsp;";
                $print .= "<input type=hidden name=flatviewstartposting value='".$flatviewstartposting."'>";
                $print .= "<input type=hidden name=view value='".$forum["view"]."'>";
                $print .= "<input type=image name=create value=\"abschicken\" src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/green/accept.png\" border=\"0\"".tooltip(_("Sortierung durchführen")).">";
            }
            $print .= "&nbsp;&nbsp;</td><td class=\"blank\"><a href=\"".URLHelper::getLink("?flatviewstartposting=$flatviewstartposting&toolbar=close&open=$open")."\" ".tooltip(_("Toolbar einfahren"))."><img src=\"".$GLOBALS['ASSETS_URL']."images/griff.png\" class=\"middle\"></a>";

            $print .= "</td><td class=\"blank\" width=\"99%\"></td></tr>";
            if ($forum["view"] != "tree" && $forum["view"] != "mixed")
                $print .= "</form>";
            $print .= "<tr><td class=\"blank\" colspan=\"9\">&nbsp;</td></tr></table>";

        } else {
            $print .= "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"blank\"><tr><td class=\"blank\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"22\" width=\"1\"></td>";
            $print .= "<td class=\"blank\"><font size=\"-1\"><a href=\"".URLHelper::getLink("?flatviewstartposting=$flatviewstartposting&toolbar=open&open=$open")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/griff2.png\" class=\"middle\"".tooltip(_("Toolbar ausfahren"))."></a>";
            $print .= '</td></tr></table>';
        }
        $print .= "</td></tr></table>\n";
        if ($id) {  // Schreibmodus, also form einbauen
            $print .= '<form name="forumwrite" method="post" action="'.URLHelper::getLink('#anker').'">';
            $print .= CSRFProtection::tokenTag();
        }

        return $print;
}

/**
* Builds the Indikator for printhead
*
* @param    array   forumposting contains several data of the actual posting
*
* @return   string  tmp HTML-Output of the Indikator
*
**/
function forum_get_index ($forumposting) {
    global $forum, $indexvars;
    $i = 0;
    if ($forum["sort"] == "viewcount" || $forum["sort"] == "rating" || $forum["sort"] == "score") {
        $color = $indexvars[$forum["sort"]]["color"];
        $name = $indexvars[$forum["sort"]]["name"];
        $i = 1;
    } else {
        $color = $indexvars[$forum["indikator"]]["color"];
        $name = $indexvars[$forum["indikator"]]["name"];

    }
    $tmp = "<font size=\"-1\" color =\"$color\">$name</font>";
    if ($forum["indikator"] == "age" && $i != 1) $tmp = "";
    return $tmp;
}


/**
* Builds a list of posting not to be shrinked
*
* @param    string  id of the first non-shrink posting
*
* @return   string  age is the list of postings not to be shrinked, separated by ;
*
**/
function ForumCheckShrink($id)  {
    global $age;
    $db=new DB_Seminar;
    $db->query("SELECT * FROM px_topics WHERE parent_id='$id'");
    while ($db->next_record()) {
        $next_topic=$db->f("topic_id");
        $age .= ";".$db->f("chdate");
        ForumCheckShrink($next_topic);
    }
    return $age;
}


/**
* Replaces edit-comment
*
* @param    array   forumposting contains several data of the actual posting
*
* @return   array forumposting
*
**/
function forum_check_edit($forumposting) {
    if (preg_match("/%%\[edit-/",$forumposting)) { // wurde schon mal editiert
        $tmp = "%%["._("editiert von: ");
        $forumposting = str_replace("%%[edit-", $tmp ,$forumposting);
    }
    return $forumposting;
}


/**
* prints the rating-bar
*
**/
function print_rating($rate, $id, $username) {
    global $forum, $user, $auth;
    if ($rate == "?"){
        $bar = "<img src=\"".$GLOBALS['ASSETS_URL']."images/rate_leer.gif\" width=\"50\" border=\"0\" height=\"11\">";
    } else {
        $ratecount = object_return_ratecount ($id);
        if ($ratecount > 10)
            $ratecount = 10;
        $ratecount = round($ratecount / 2);
        $rate = StringToFloat($rate);
        if ($rate > 3) {
            $grau = (5-$rate)*10;
            $rot = 25 - $grau;
            $bar = "<img src=\"".$GLOBALS['ASSETS_URL']."images/rate_leer.gif\" width=25 height=11 border=\"0\"><img src=\"".$GLOBALS['ASSETS_URL']."images/rate_rot$ratecount.gif\" width=\"$rot\" border=\"0\" height=\"11\"><img src=\"".$GLOBALS['ASSETS_URL']."images/rate_leer.gif\" width=\"$grau\" border=\"0\" height=11>";
        } elseif ($rate < 3) {
            $grau = ($rate-1)*10;
            $gruen = 25 - $grau;
            $bar = "<img src=\"".$GLOBALS['ASSETS_URL']."images/rate_leer.gif\" width=\"$grau\" height=\"11\" border=\"0\"><img src=\"".$GLOBALS['ASSETS_URL']."images/rate_gruen$ratecount.gif\" border=\"0\" width=\"$gruen\" height=11><img src=\"".$GLOBALS['ASSETS_URL']."images/rate_leer.gif\" border=\"0\" width=25 height=11>";
        } else {
            $bar = "<img src=\"".$GLOBALS['ASSETS_URL']."images/rate_neutral$ratecount.gif\" width=\"50\" height=\"11\" border=\"0\">";
        }
    }
    if (object_check_user($id, "rate") == TRUE || get_username($user->id) == $username) { // already rated / my own posting
        $bar = '<span ' . tooltip(sprintf(_("Bewertung: %s"),$rate), false) . '>' . $bar. '</span>';
    } else {
            $bar = '<span onClick="STUDIP.Forum.rate_template(\''.$id.'\')" '
                . tooltip(sprintf(_("Bewertung: %s Zum Abstimmen bitte klicken."),$rate), false) . '>'
                . $bar
                . '</span>';
     }


//  $bar .= " | ";
    return $bar;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Checks the state of a posting an prints it with printhead and printcontent
*
* @param    array   forumposting contains several data of the current posting
*
* @return   array forumposting with added flags
*
**/
function printposting ($forumposting) {
    global $forum,$auth,$user, $SessSemName, $indexvars, $openorig, $rechte, $perm;

  // Status des Postings holen
    // auf- zugeklappt
    // schreibmodus
    // Editiermodus
    // neu / alt
    // Ordner / Beitrag

    $forumposting = ForumGetWriteStatus($forumposting);
    $forumposting = ForumNewPosting($forumposting);
    $forumposting = ForumOpenClose($forumposting);
    $forumposting = ForumFolderOrPosting($forumposting);
    $forumposting = forum_lonely($forumposting);
    $forumposting = ForumIcon($forumposting);
    $forumposting = ForumGetAnonymity($forumposting);
    $anonymous = false;
    if ($forumposting["anonymous"]) {
        $anonymous = true;
    }

 // Kopfzeile zusammenbauen

    // Link zusammenbauen

        if ($forum["view"] == "mixed") {        // etwas umständlich: Weg von der Themenansicht zum Folderflatview
            $viewlink = "&view=flatfolder";
            $forum["flatviewstartposting"] = 0;
        } else {
            $viewlink = '';
        }

        if ($forumposting["openclose"] == "close" || $forum["view"] == "mixed") {
            $link = "?open=".$forumposting["id"]."&flatviewstartposting=".$forum["flatviewstartposting"].$viewlink;
            if ($forumposting["shrink"] == TRUE && $forumposting["lonely"]==FALSE)
                $link .= "&shrinkopen=".$forumposting["id"];
            if ($forum["view"] != "mixed")
                $link .= "#anker";
        } else {
            if ($forum["view"] == "tree" && $forumposting["type"] == "posting")
                $link = "?open=".$forumposting["rootid"]."#anker";
            else
                $link = "?flatviewstartposting=".$forum["flatviewstartposting"]."#anker";
            if ($forum["neuauf"]==1 AND $forumposting["newold"]=="new")
                $link = ""; // zuklappen nur m&ouml;glich wenn neueimmerauf nicht gesetzt
        }

        $link = URLHelper::getLink($link);

    // Views hochzählen

        if ($forumposting["openclose"] == "open" && $user->id != $forumposting["userid"])  // eigene Postings werden beim view nicht gezählt
            $objectviews = object_add_view($forumposting["id"]); // Anzahl der Views erhöhen

    // Indexe

        if (!$objectviews)
            $objectviews = $forumposting["viewcount"];
        if (($forumposting["rating"] == 99))
            $forumposting["rating"] = "?";

        $forumposting["score"] = round($forumposting["score"],1);

    // Anzahl der Postings in Ordnern

        if ($forumposting["foldercount"] && $forumposting["type"] == "folder" && $forumposting["openclose"] == "close")
            $forumhead[] = "<b>".($forumposting["foldercount"]-1)."</b> / ";

        if (!$forumposting["anonymous"] || $perm->have_perm("root")) {
              if ($user->id == "nobody" || $forumposting["author"]=="unbekannt" || $forumposting["username"]=="") // Nobody darf nicht auf die about...
                $forumhead[] = htmlReady($forumposting["author"]);
            else {
                $authortext = $forumposting["anonymous"] ? htmlReady($forumposting["author"])." ("._("anonym").")" : htmlReady($forumposting["author"]);
                $forumhead[] = "<a class=\"printhead\" href=\"".URLHelper::getLink("about.php?username=".$forumposting["username"])."\">".$authortext."&nbsp;</a>";
            }
        } else {
            $forumhead[] = _("anonym");
        }

    // Alter ausgeben

        $view = Request::option('view');

        if ($forumposting["type"] == "folder" && ($view=="tree" || $view=="mixed") && !Request::option('delete_id') && $forumposting["openclose"] == "close") {
            $forumhead[] =  "&nbsp;".date("d.m.Y - H:i", $forumposting["folderlast"])."&nbsp;";
            $age_tmp = $forumposting["folderlast"];
        } else {
            $forumhead[] =  "&nbsp;".date("d.m.Y - H:i", $forumposting["chdate"])."&nbsp;";
            $age_tmp = $forumposting["chdate"];
        }

    // Themennamen ausgeben (ausser Flatview)

        if ($forum["view"] != "flatfolder")
            $forumhead[] =  "<a href=\"".URLHelper::getLink("?open=".$forumposting["id"]
                    ."&folderopen=".$forumposting["rootid"]
                    ."&view=tree"
                    ."#anker")."\" class=\"printhead\">".htmlReady(mila($forumposting["rootname"],20))
                    ."</a>"
                    ."&nbsp; ";

        if ($forum["sort"] == "viewcount" || $forum["sort"] == "rating" || $forum["sort"] == "score") {
            $color = $indexvars[$forum["sort"]]["color"];
            $printindex = $forumposting[$forum["sort"]];
        } else {
            $color = $indexvars[$forum["indikator"]]["color"];
            $printindex = $forumposting[$forum["indikator"]];
        }
        if ($printindex=="" && ($forum["sort"]=="score" || $forum["indikator"]=="score")) $printindex="0";
        if ($printindex!= "") $forumhead[] = "| <font color=\"$color\">$printindex</font> | ";


    // Die Bewertungsanzeige

        $forumhead[] = print_rating($forumposting["rating"],$forumposting["id"],$forumposting["username"]);



    // die Favoritenanzeige

        if ($forumposting["fav"]!="0") {
            $favicon = $GLOBALS['ASSETS_URL']."images/icons/16/red/exclaim.png";
            $favtxt = _("aus den Favoriten entfernen");
        } else {
            $favicon = $GLOBALS['ASSETS_URL']."images/icons/16/grey/exclaim.png";
            $favtxt = _("zu den Favoriten hinzufügen");
        }
        $rand = "&random=".rand();
        if ($user->id != "nobody" && !Request::option('delete_id')) // Nobody kriegt keine Favoriten, auch nicht in der Löschen-Ansicht
            $forumhead[] = "<a href=\"".URLHelper::getLink("?fav=".$forumposting["id"]."&open=$openorig".$rand."&flatviewstartposting=".$forum["flatviewstartposting"]."#anker")."\"><img src=\"".$favicon."\" border=\"0\" ".tooltip($favtxt).">&nbsp;</a>";

    // Antwort-Pfeil

        if (!(have_sem_write_perm()) && !Request::option('delete_id'))
            $forumhead[] = "<a href=\"".URLHelper::getLink("write_topic.php?root_id=".$forumposting["rootid"]."&topic_id=".$forumposting["id"])."\" target=\"_blank\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/add/forum.png\" border=0 " . tooltip(_("Hier klicken um in einem neuen Fenster zu antworten")) . "></a>";

        $zusatz = ForumParseZusatz($forumhead);

        if ($forumposting["writestatus"]!="none") {    //wir sind im Schreibmodus
            echo '<input type="hidden" name="topic_id" value="'.$forumposting['id'].'">';
            $name = "<input aria-label=\"" . _("Titel des Beitrags") . "\" type=text size=50 style='font-size:8 pt;font-weight:normal;' name=titel value='".htmlReady($forumposting["name"])."'>";
            $zusatz = ""; // beim editieren brauchen wir den Kram nicht
        } else {
            $name = "<a href=\"$link\" class=\"tree\" >".htmlReady(mila($forumposting["name"]))."</a>";
        }

        if ($forumposting["newold"] == "new")
            $new = TRUE;

        if (($forum["view"]=="tree" || $forum["view"]=="mixed") && $forumposting["type"] == "folder") {
            if (object_get_visit($SessSemName[1], "forum") < $forumposting["folderlast"])
                $new = TRUE;
            $forumposting["mkdate"] = $forumposting["folderlast"];
        }

    // welcher Index liegt auf den Pfeilen?

        if ($forum["indikator"] == "viewcount")
            $index = $objectviews;
        elseif ($forum["indikator"] == "rating")
            $index = $forumposting["rating"];
        elseif ($forum["indikator"] == "score")
            $index = $forumposting["score"];

  // Kopfzeile ausgeben

        if ($forumposting["intree"]!=TRUE)
            echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0 align=center><tr>";
        if ($forum["anchor"] == $forumposting["id"])
            echo "<a name='anker'></a>";
        printhead ("100%","0",$link,$forumposting["openclose"],$new,$forumposting["icon"],$name,$zusatz,$age_tmp,"TRUE",$index,$forum["indikator"]);
        if ($forumposting["intree"]==TRUE)
            echo "<td class=\"blank\">&nbsp;&nbsp;&nbsp;</td>";
        echo "</tr></table>\n";

// Kontentzeile zusammenbauen

    if ($forumposting["openclose"] == "open") {
        $forumposting = ForumGetRights($forumposting);
        if ($forumposting["writestatus"] != "none") { // Posting wird geschrieben
            if ($forumposting["writestatus"] == "update" && $forumposting["lonely"] == FALSE) {
                throw new AccessDeniedException(_("Kein Zugriff auf dieses Element möglich."));
            } else {
                $description = editarea($forumposting);
            }
        } else {
            $forumposting["description"] = forum_parse_edit($forumposting["description"], $anonymous);
            $description = formatReady($forumposting["description"]);
            if ($forumposting["buttons"] == "no" || $forum["update"]) {
                $edit = "<br>";
            } else {
                $edit = forum_get_buttons($forumposting);
            }
        }

    // Anzeigen der Sidebar /////////////

        if ((Request::option('sidebar')==$forumposting["id"] || $forum["rateallopen"]==TRUE) && !Request::option('delete_id')) {

            $addon = "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"140\" height=\"5\">";

            // es werden Porträts angezeigt
            if ($forum["showimages"] == TRUE) {
                $addon .= "<br><div align=\"center\">";
				if ($anonymous){
					$addon .= "<img src=\"".URLHelper::getLink("pictures/user/nobody_medium.png")."\" title=\"anonymer User\">";
				}else{
                	$addon .= Avatar::getAvatar($forumposting["userid"])->getImageTag(Avatar::MEDIUM);
				}
                $addon .= "</div>";
            }

            $addon .= "<font size=\"-1\" color=\"555555\"><br>&nbsp;&nbsp;Views: $objectviews<br>&nbsp;&nbsp;Relevanz: ".$forumposting["score"]."<br>&nbsp;&nbsp;";
            if ($forumposting["rating"] != "?") {
                $addon .=_("Bewertung: ").$forumposting["rating"]."<br>";
                $rate = object_print_rates_detail($forumposting["id"]);
                while(list($key,$value) = each($rate))
                    $addon .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$key: $value<br>";
            } else {
                $addon .= _("Noch nicht bewertet")."<br><br>";
            }

            if (get_username($user->id) == $forumposting["username"]) {
                $addon .= "<font size=\"-1\">&nbsp;&nbsp;Sie können sich&nbsp;<br>&nbsp;&nbsp;nicht selbst bewerten.&nbsp;";
            } else {
                if (object_check_user($forumposting["id"], "rate") == FALSE) {  // wenn er noch nicht bewertet hat
                    $addon .= "<div align=\"center\"><font size=\"-1\">Dieser Beitrag war<br><font size=\"-2\">(Schulnote)</font><br><form method=post action=".URLHelper::getLink("#anker").">";
                    $addon .= CSRFProtection::tokenTag();
                    $addon .= "<b>&nbsp;<font size=\"2\" color=\"009900\">1";
                    $addon .= "<input type=radio name=rate[".$forumposting["id"]."] value=1>";
                    $addon .= "<input type=radio name=rate[".$forumposting["id"]."] value=2>";
                    $addon .= "<input type=radio name=rate[".$forumposting["id"]."] value=3>";
                    $addon .= "<input type=radio name=rate[".$forumposting["id"]."] value=4>";
                    $addon .= "<input type=radio name=rate[".$forumposting["id"]."] value=5><font size=\"2\" color=\"990000\">5&nbsp;";
                    $addon .= "<br><br>";
                    $addon .= "<input type=hidden name=open value='".$forumposting["id"]."'>";
                    $addon .= "<input type=hidden name=flatviewstartposting value='".$forum["flatviewstartposting"]."'>";
                    $addon .= Button::create(_("Bewerten"), "sidebar", array('value' => $forumposting["id"]));
                } else {
                    $addon .= "<font size=\"-1\">&nbsp;&nbsp;". sprintf(_("Sie haben diesen%sBeitrag bewertet."),'&nbsp;<br>&nbsp;&nbsp;');
                }
            }
        } elseif ($user->id != "nobody" && !Request::option('delete_id'))  // nur Aufklapppfeil
            $addon = "open:".URLHelper::getLink("?open=".$forumposting["id"]."&flatviewstartposting=".$forum["flatviewstartposting"]."&sidebar=".$forumposting["id"]."#anker");

  // Kontentzeile ausgeben

        echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0 align=center><tr>";

        if ($forumposting["intree"]==TRUE) // etwas Schmuckwerk für die Strichlogik
            echo ForumStriche($forumposting);
        printcontent ("100%",$forumposting,$description,$edit,TRUE,$addon);
        if ($forumposting["intree"]==TRUE)
            echo "<td class=\"blank\">&nbsp;&nbsp;&nbsp;</td>";
        echo "</tr></table>\n";
    }
    return $forumposting;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Builds the Flatview of a Board (for last Postings, New Postings, Search, Flatview)
*
* @param    string  open id of the opened posting
* @param    string  show ?
* @param    string  update the id of the posting to be updated
* @param    string  name ?
* @param    string  description ?
* @param    string  zitat id of the posting to be quoted
*
**/
function flatview ($open=0, $show=0, $update="", $name="", $description="",$zitat="")

{   global $SessSemName,$rechte,$forum,$user;
    global $new_topic;

/////////////////////////////// Konstanten setzen bzw. zuweisen die für die ganze Seite gelten

$forum["openlist"] = $open;
$forum["zitat"] = $zitat;
$forum["update"] = $update;
$forum["postingsperside"] = get_config('ENTRIES_PER_PAGE');
$postingsperside = (int) get_config('ENTRIES_PER_PAGE');
$flatviewstartposting = Request::int('flatviewstartposting', 0);
$forum["flatviewstartposting"] = $flatviewstartposting;

/////////////////////////////// Abfrage der Postings

$db = new DB_Seminar;

if ($forum["view"]=="flatfolder") {
    $folder_id = $forum["flatfolder"];
    $addon = " AND x.root_id = '$folder_id'";
}
$order = "DESC";

if (($forum["sort"] == "rating" || $forum["sort"]== "nachname" || $forum["sort"]== "root_name" || $forum["sort"]== "name") && ($forum["view"] != "tree" && $forum["view"] != "mixed"))
    $order = "ASC";

if ($forum["view"]=="search") {
    if ($forum["search"]!="") {
        $addon = " AND (".$forum["search"].")";
    } else {
        echo forum_search_field()."<br><br>";
        include 'lib/include/html_end.inc.php';
        page_close(); //Niemals vergessen, wenn Session oder Benutzervariablen benutzt werden !!!
        die;
    }
} elseif ($forum["view"]=="neue") {
    $datumtmp = object_get_visit($SessSemName[1], "forum");
    $addon = " AND x.chdate > '$datumtmp'";
}

function countTopics($db, $addon) {
    global $user;
    $query = "SELECT x.topic_id ".
             "FROM px_topics x, px_topics y ".
             "WHERE x.root_id = y.topic_id ".
             "AND x.Seminar_id = '{$_SESSION['SessionSeminar']}' ".
             "AND (x.chdate>=x.mkdate OR x.user_id='$user->id' OR x.author='unbekannt')".
             $addon;
    $db->query($query);
    return $db->num_rows();
}

// Forum ist nicht leer
if (countTopics($db, $addon) > 0 || isset($new_topic)) {
    $forum["forumsum"] = $db->num_rows();
}

// keine neuen, aber alte
elseif ($forum["view"] === "neue" && countTopics($db, "")) {
    $forum["view"] = "flat";
    $addon = "";
    ?>
    <div class="white" style="padding: 0.5em 0;">
    <?= MessageBox::info(_("Es liegen keine neuen Beiträge vor.")) ?>
    </div>
    <?
}

// das Forum ist leer
else {
    echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
    echo ForumNoPostings();
    echo "</table>";
    include 'lib/include/html_end.inc.php';
    page_close(); //Niemals vergessen, wenn Session oder Benutzervariablen benutzt werden !!!
    die;
}

// we proudly present: the longest SQl in Stud.IP :) regards to Suchi+Noack for inspirations

$query = "SELECT x.topic_id, x.name , x.author , x.mkdate, x.chdate as age, y.name AS root_name"
    .", x.description, x.Seminar_id, y.topic_id AS root_id, username, x.user_id, x.anonymous"
    .", IFNULL(views,0) as viewcount, nachname, IFNULL(ROUND(AVG(rate),1),99) as rating"
    .", IF(object_user.object_id!='',1,0) as fav"
    .", ((6-(IFNULL(AVG(rate),3))-3)*5)+(IFNULL(views,0)/(((UNIX_TIMESTAMP()-x.mkdate)/604800)+1)) as score "
    ."FROM px_topics x LEFT JOIN object_views ON(object_views.object_id=x.topic_id) LEFT JOIN object_rate ON(object_rate.object_id=x.topic_id) "
    ."LEFT JOIN auth_user_md5 ON(auth_user_md5.user_id = x.user_id) LEFT OUTER JOIN object_user ON(object_user.object_id=x.topic_id AND object_user.user_id='$user->id' AND flag='fav') , px_topics y "
    ."WHERE x.root_id = y.topic_id AND x.seminar_id = '{$_SESSION['SessionSeminar']}' AND (x.chdate>=x.mkdate OR x.user_id='$user->id' OR x.author='unbekannt')".$addon." "
    ."GROUP by x.topic_id ORDER BY ".$forum["sort"]." ".$order
    ." ,age DESC LIMIT $flatviewstartposting,$postingsperside";

$db->query($query);



/////////////////////////////////////// HTML und Navigation

?>
<table border=0 width="100%" cellspacing="0" cellpadding="0" align="center" id="main_content"><tr>
<td class="steelgraudunkel" align="left" width="45%" style="padding-left: 5px">
<?

if ($forum["view"]=="flatfolder")
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/folder-full.png\" align=\"baseline\"><font size=\"-1\"><b> Thema:</b> ".mila(ForumGetName($forum["flatfolder"]),40)." / ";

if ($forum["search"]!="" && $forum["view"]=="search") {
    echo "<font size=\"-1\">&nbsp;". _('Suchbegriff:');
    if ($forum['searchstring'] != '') {
        echo " '".htmlReady($forum['searchstring'])."' ";
    } else {
        echo ' '. _('alles'). ' ';
    }

    if ($forum['searchauthor'] != '') {
        echo _('von')." '".htmlReady($forum['searchauthor'])."' ";
    }
    echo "/ ". _('Treffer: ').$forum["forumsum"]."</font>";
} else {
   echo _('Forenbeiträge: ') . $forum["forumsum"];
}
echo "</td>";

echo "<td class=\"steelgraudunkel\" align=\"center\" width=\"10%\">";
if ($forum["flatallopen"]=="TRUE")
    echo "<a href=\"".URLHelper::getLink(
        "?flatviewstartposting=".$forum["flatviewstartposting"]."&flatallopen=FALSE")."\"><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0 height='10' align=middle><img src='".$GLOBALS['ASSETS_URL']."images/close_all.png' border=0 " . tooltip(_("Alle zuklappen")) . " align=middle><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0></a>";
else
    echo "<a href=\"".URLHelper::getLink(
        "?flatviewstartposting=".$forum["flatviewstartposting"]."&flatallopen=TRUE")."\"><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0 height='10' align=middle><img src='".$GLOBALS['ASSETS_URL']."images/open_all.png' border=0 " . tooltip(_("Alle aufklappen")) . " align=middle><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0></a>";

echo "</td><td class=\"steelgraudunkel\" align=\"right\" width=\"45%\">";
echo forum_print_navi($forum)."&nbsp;&nbsp;&nbsp;".forum_get_index($forumposting)."&nbsp;&nbsp;&nbsp;";
echo "</td></tr></table>";

// Antworten-Knopf ganz oben in der Flatview-Ansicht
if ($forum['view']=='flatfolder') {
    echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
    echo '<tr>';
    echo '<td class="blank" align="center" style="padding-top:12px; padding-bottom:5px;">';
    echo _("Zu diesem Thema") . " ";
    echo LinkButton::create(_("Antworten"), URLHelper::getURL("?answer_id=".$folder_id."&flatviewstartposting=0&sort=age#anker"));
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}


/////////////////// Konstanten für das gerade auszugebene Posting und Posting ausgeben

if (isset($new_topic)) {
    printposting($new_topic);
}

while($db->next_record()){
    $forumposting["id"] = $db->f("topic_id");
    $forumposting["name"] = $db->f("name");
    $forumposting["description"] = $db->f("description");
    $forumposting["author"] = $db->f("author");
    $forumposting["username"] = $db->f("username");
    $forumposting["userid"] = $db->f("user_id");
    $forumposting["rootid"] = $db->f("root_id");
    $forumposting["rootname"] = $db->f("root_name");
    $forumposting["mkdate"] = $db->f("mkdate");
    $forumposting["chdate"] = $db->f("age");
    $forumposting["viewcount"] = $db->f("viewcount");
    $forumposting["rating"] = $db->f("rating");
    $forumposting["score"] = $db->f("score");
    $forumposting["fav"] = $db->f("fav");
    $forumposting["anonymous"] = get_config('FORUM_ANONYMOUS_POSTINGS') ? $db->f("anonymous") : false;

    $forumposting = printposting($forumposting);
}

/////////// HTML für den Rest

echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" valign=\"top\" align=\"center\">";
echo "  <tr>";
echo "      <td class=\"blank\" valign=\"top\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" border=\"0\" height=\"4\">";
echo "</td>";
echo "  </tr>";
echo "  <tr>";
echo "      <td class=\"steelgraudunkel\" align=\"right\" ><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" border=\"0\" height=\"10\" align=\"middle\">";
echo forum_print_navi($forum)."&nbsp;&nbsp;&nbsp;".forum_get_index($forumposting);
echo "      &nbsp;&nbsp;</td>";
echo "  </tr>";
echo "  <tr>";
echo "      <td class=\"blank\">&nbsp;<br><br>";
echo "      </td>";
echo "  </tr>";
echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table><br>";


/*
echo DebugForum($forum);
echo "<hr>";
echo DebugForum($forumposting);
*/

if ($update)
    echo "</form>\n";
}

/////////////////////////////////////////////////////////////////////////


/**
* Builds the themeview of the board (shows all folders)
*
* @param    string  open id of the opened posting
* @param    string  update the id of the posting to be updated
* @param    string  zitat id of the posting to be quoted
*
**/
function DisplayFolders ($open=0, $update="", $zitat="") {
    global $SessSemName,$rechte, $forum,$auth,$user, $SEM_CLASS, $SEM_TYPE, $perm;
    global $new_topic;

//Zeigt im Treeview die Themenordner an

    $forum["update"] = $update;
    $forum["zitat"] = $zitat;
    $forum["sort"] = "age";

    $fields = array("topic_id", "parent_id", "root_id", "name"
        , "description", "author", "author_host", "mkdate"
        , "chdate", "user_id");
    $query = "select distinct ";
    $comma = "";

    if ($forum["sortthemes"] == "last")
        $order = "last DESC";
    else
        $order = "t.mkdate ". ($forum["sortthemes"] == 'asc' ? 'ASC' : 'DESC');

    while (list($key,$val)=each($fields)) {
        $query .= $comma."t.".$val;
        $comma = ", ";
    }

    $query .= ", count(distinct s.topic_id) as count, max(s.chdate) as last "
    .", IFNULL(views,0) as viewcount, IFNULL(ROUND(AVG(rate),1),99) as rating "
    .", ((6-(IFNULL(AVG(rate),3))-3)*5)+(IFNULL(views,0)/(((UNIX_TIMESTAMP()-t.mkdate)/604800)+1)) as score "
    .", IF(object_user.object_id!='',1,0) as fav "
    ."FROM px_topics t LEFT JOIN px_topics s ON(s.root_id=t.root_id AND s.chdate >= s.mkdate) "
    ."LEFT JOIN object_views ON(object_views.object_id=t.topic_id) LEFT JOIN object_rate ON(object_rate.object_id=t.topic_id) "
    ."LEFT OUTER JOIN object_user ON(object_user.object_id=t.root_id AND object_user.user_id='$user->id' AND flag='fav') "
    ."WHERE t.topic_id = t.root_id AND t.Seminar_id = '{$_SESSION['SessionSeminar']}' AND (t.chdate>=t.mkdate OR t.user_id='$user->id' OR t.author='unbekannt') GROUP BY t.root_id  ORDER BY $order";
    $db=new DB_Seminar;
    $db->query($query);
    if ($db->num_rows()==0 && !isset($new_topic)) {  // Das Forum ist leer
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
        echo ForumEmpty();
        echo "</table>";
        include 'lib/include/html_end.inc.php';
        page_close(); //Niemals vergessen, wenn Session oder Benutzervariablen benutzt werden !!!
        die;
    } else {

        // Berechnung was geöffnet ist

        $forum["openlist"] = "";
        if (isset($new_topic)) {
            $root_id = $new_topic['rootid'];
            $forum["openlist"] = $new_topic['parent_id'];
        } else {
            $root_id = ForumGetRoot($open);
            if ($open != $root_id && !$update)
                $forum["openlist"] = suche_kinder($open);
        }
        if ($update && ForumFreshPosting($update)==TRUE)
            $forum["openlist"] .= ForumGetParent($update);
        $forum["openlist"] .= ";".$open.";".$root_id;

        $shrinkopen = Request::option('shrinkopen');
        if ($shrinkopen) {
            $forum["shrinkopenlist"] = suche_kinder($shrinkopen);
            $forum["shrinkopenlist"] .= ";".$shrinkopen;
        }


        // HTML

        echo "<table class=\"blank\" width=\"100%\" border=0 cellpadding=0 cellspacing=0><tr>";
        echo "<td class=\"steelgraudunkel\" width=\"33%\"><b><font size=\"-1\">&nbsp;" . _("Thema") . "</font></b></td>";
        echo "<td class=\"steelgraudunkel\" width=\"33%\" align=\"center\"><font size=\"-1\">&nbsp;&nbsp;";
        if ($user->id != "nobody") { // Nobody kriegt nur treeview
            if ($forum["view"] == "tree")
                echo "<a href=\"".URLHelper::getLink("?view=mixed&themeview=mixed")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumtree.gif\" border=\"0\" align=\"top\"></a>";
            else
                echo "<a href=\"".URLHelper::getLink("?view=tree&themeview=tree")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumflat.gif\" border=\"0\" align=\"top\"></a>";
        }
        echo "</font><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" border=0 height=\"20\" align=\"middle\"></td>";
        echo "<td class=\"steelgraudunkel\" width=\"33%\"align=\"right\"><font size=\"-1\">" . _("<b>Forenbeiträge</b> / letzter Eintrag") . "&nbsp;&nbsp;".forum_get_index($forumposting)."&nbsp;&nbsp;</font></td></tr></table>\n";
        while ($db->next_record()) {
            $forumposting["id"] = $db->f("topic_id");
            $forumposting["name"] = $db->f("name");
            $forumposting["description"] = $db->f("description");
            $forumposting["author"] = $db->f("author");
            $forumposting["username"] = get_username($db->f("user_id"));
            $forumposting["userid"] = $db->f("user_id");
            $forumposting["rootid"] = $db->f("root_id");
            $forumposting["rootname"] = $db->f("root_name");
            $forumposting["mkdate"] = $db->f("mkdate");
            $forumposting["chdate"] = $db->f("chdate");
            $forumposting["foldercount"] = $db->f("count");
            $forumposting["folderlast"] = $db->f("last");
            $forumposting["viewcount"] = $db->f("viewcount");
            $forumposting["rating"] = $db->f("rating");
            $forumposting["score"] = $db->f("score");
            $forumposting["fav"] = $db->f("fav");
            $forumposting["anonymous"] = get_config('FORUM_ANONYMOUS_POSTINGS') ? $db->f("anonymous") : false;

            $forumposting = printposting($forumposting);

            if ($forum["view"] == "tree" && $forumposting["openclose"]=="open" && Request::option('cmd') != "move") {
                DisplayKids ($forumposting);
            }
        }

        if (isset($new_topic) && $new_topic['parent_id'] == "0") {
            printposting($new_topic);
        }
    }
    echo "<table class=blank border=0 cellpadding=0 cellspacing=0 width=\"100%\"><tr><td class='blank'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0 height='4'></td></tr><tr>";
    echo "<td align=center class=steelgraudunkel><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0 height='20' align=middle>";
    if (($perm->have_perm("autor")) && (($rechte) || ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["topic_create_autor"])))
        echo "<a href='".URLHelper::getLink("?neuesthema=TRUE#anker")."'><img src='".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1down.png' border=0 align=middle " . tooltip(_("Neues Thema anlegen")) . "><img src='".$GLOBALS['ASSETS_URL']."images/icons/16/blue/add/folder-empty.png' " . tooltip(_("Neues Thema anlegen")) . " border=0 align=middle></a>";
    echo "</td></tr><tr><td class=blank>&nbsp; <br>&nbsp; <br></td></tr></table>\n";


/*
    echo DebugForum($forum);
    echo "<hr>";
    echo DebugForum($forumposting);
*/

    if ($update)
        echo "</form>\n";
}

/////////////////////////////////

/**
 * Hier eine bezaubernde Routine um die Striche exakt wiederzugeben - keine Bange ich verstehe sie auch nicht mehr
 */
function indentPosting (&$forumposting, $level)
{
    echo "<table class=\"blank\" border=0 cellpadding=0 cellspacing=0 width=\"100%\" valign=\"top\"><tr valign=\"top\"><td class=\"blank tree-indent\" nowrap valign=\"top\" ><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'>";

    if ($level){
        $striche = "";
        for ($i=0;$i<$level;$i++) {
            if ($i==($level-1)) {
                if ($forumposting["lines"][$i+1]>1) $striche.= "<img src='".$GLOBALS['ASSETS_URL']."images/forumstrich3.gif'>";         //Kreuzung
                else $striche.= "<img src='".$GLOBALS['ASSETS_URL']."images/forumstrich2.gif'>";                //abknickend
                $forumposting["lines"][$i+1] -= 1;
            } else {
                if ($forumposting["lines"][$i+1]==0) $striche .= "<img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'>";      //Leerzelle
                else $striche .= "<img src='".$GLOBALS['ASSETS_URL']."images/forumstrich.gif'>";                //Strich
            }
        }
        echo $striche;
    }
    echo "</td>";
}


/**
* Amazing engine to build the treeview incl. shrinking
*
* @param    array   forumpostng contains several data of the curren posting
* @param    string  level contains the current level of the treeview
*
**/
function DisplayKids ($forumposting, $level=0) {
    global $SessSemName, $forum,$rechte,$auth,$user, $age;
    global $new_topic;

// stellt im Treeview alle Postings dar, die NICHT Thema sind

    $topic_id = $forumposting["id"];
    $new_topic_here = isset($new_topic) && $new_topic['parent_id'] == $topic_id;
    $forumposting["intree"]="TRUE";
    $query = "select topic_id, parent_id, name, author "
        .", px_topics.mkdate, px_topics.chdate, description, root_id, username, px_topics.user_id"
        .", IFNULL(views,0) as viewcount, IFNULL(ROUND(AVG(rate),1),99) as rating"
        .", ((6-(IFNULL(AVG(rate),3))-3)*5)+(IFNULL(views,0)/(((UNIX_TIMESTAMP()-px_topics.mkdate)/604800)+1)) as score "
        .", IF(object_user.object_id!='',1,0) as fav, anonymous"
        ." FROM px_topics LEFT JOIN auth_user_md5 USING(user_id)"
        ." LEFT JOIN object_views ON(object_views.object_id=topic_id) LEFT JOIN object_rate ON(object_rate.object_id=topic_id)"
        ." LEFT OUTER JOIN object_user ON(object_user.object_id=topic_id AND object_user.user_id='$user->id' AND flag='fav')"
        ." WHERE"
        ." parent_id = '$topic_id' AND (px_topics.chdate>=px_topics.mkdate OR px_topics.user_id='$user->id' OR px_topics.author='unbekannt')"
        ." GROUP BY topic_id ORDER by px_topics.mkdate";
    $db=new DB_Seminar;
    $db->query($query);
    $forumposting["lines"][$level] = $db->num_rows() + $new_topic_here;
    while ($db->next_record()) {
        $forumposting["id"] = $db->f("topic_id");
        $forumposting["name"] = $db->f("name");
        $forumposting["description"] = $db->f("description");
        $forumposting["author"] = $db->f("author");
        $forumposting["username"] = $db->f("username");
        $forumposting["userid"] = $db->f("user_id");
        $forumposting["rootid"] = $db->f("root_id");
        $forumposting["rootname"] = $db->f("root_name");
        $forumposting["mkdate"] = $db->f("mkdate");
        $forumposting["chdate"] = $db->f("chdate");
        $forumposting["level"] = $level;
        $forumposting["viewcount"] = $db->f("viewcount");
        $forumposting["rating"] = $db->f("rating");
        $forumposting["score"] = $db->f("score");
        $forumposting["fav"] = $db->f("fav");
        $forumposting["anonymous"] = get_config('FORUM_ANONYMOUS_POSTINGS') ? $db->f("anonymous") : false;

        indentPosting($forumposting, $level);

        $age = "";
        $forumposting["newold"] = "";

        // wird geshrinkt?

        if ($forum["shrink"]!=0 && $forum["neuauf"] ==1) {
            $forumposting = ForumNewPosting($forumposting);
            //echo $forumposting["newold"];
        }

        if (strstr($forum["shrinkopenlist"],$forumposting["id"])!=TRUE
            && strstr($forum["openlist"],$forumposting["id"])!=TRUE
            && $forum["shrink"]!=0 && Request::option('openall') != 'TRUE'
            && !($forum["neuauf"] == 1 && $forumposting["newold"] == "new")) {
                $age = ForumCheckShrink($forumposting["id"]);
                $age = explode(";",$age);
                $forumposting["shrinkcount"] = sizeof($age)-1;
                rsort($age);
        } else {
            $age[]=time();
        }


        if ($age[0] >= time()-$forum["shrink"]) {
            $forumposting["shrink"]=FALSE;
            $forumposting = printposting($forumposting);
            DisplayKids($forumposting, $level+1);
        } else {
            $forumposting["shrink"]=TRUE;
            if ($forumposting["shrinkcount"] > 0) $forumposting["name"] = "(".$forumposting["shrinkcount"].") ".$forumposting["name"];
            $forumposting = printposting($forumposting);
        }
        $age = "";

    }

    if ($new_topic_here) {
        $forumposting = array_merge($forumposting, $new_topic);

        indentPosting($forumposting, $level);
        printposting($forumposting);
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Builds the search-fields
*
* @return   string searchfield contains the complete HTML of the search-page
*
**/
function forum_search_field () {
$searchfield = "
<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\"><tr><td class=\"blank\">
<table border=\"0\" width=\"604\" cellspacing=\"5\" cellpadding=\"0\" align=\"center\">
<tr>
<td class=\"blank\">&nbsp;</td></tr>
<td class=\"blank\" width=\"302\" align=\"center\">
   <form name=\"search\" method=\"post\" action=\"".URLHelper::getLink('')."\">
   ". CSRFProtection::tokenTag() ."
    <table cellpadding=\"2\" cellspacing=\"0\" border=\"0\" valign=\"top\">
        <tr class=\"steel1\">
            <td style=\"vertical-align: top;\">
                <b><font size=\"-1\"><label for=\"suchbegriff\">"._("Suchbegriff:")."</label></font></b>
            </td>
            <td class=\"steel1\" style=\"text-align: right;\">
                <input  type=\"TEXT\" name=\"suchbegriff\" id=\"suchbegriff\">
            </td>
        </tr>
        <tr class=\"steel1\">
        <td>
            <b><font size=\"-1\"><label for=\"author\">"._("Von:")."</label></font></b>
        </td>
            <td>
                <input  type=\"TEXT\" name=\"author\" id=\"author\">
            </td>
        </tr>
        <tr>
            <td class=\"steelgraulight\" colspan=\"2\" align=\"center\">
                <input type=\"hidden\" name=\"view\" value=\"search\">
                <br>
                ".Button::create(_("Suche starten"))."
                <br><br>
            </td>
        </tr>
    </table>
   </form>
</td>
<td class=\"suche\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"207\" width=\"285\">
<tr>
</tr></table><br></td></tr></table>";
return $searchfield;
}

/////////////////////

/**
* Builds the HTML for the move-navigation
*
* @param    string  topic_id the id of the original posting to be moved
*
**/
function forum_move_navi ($topic_id) {
    global $perm, $user, $forum;

    $mutter = suche_kinder($topic_id);
    $mutter = explode (";",$mutter);
    $count = sizeof($mutter)-2;
    $check_modules = new Modules;

    // wohin darf ich schieben? Abfragen je nach Rechten

    if ($perm->have_perm("autor"))
        $query = "SELECT DISTINCT seminare.Seminar_id, seminare.Name FROM seminar_user LEFT JOIN seminare USING(Seminar_id) WHERE user_id ='$user->id ' AND (seminar_user.status = 'tutor' OR seminar_user.status = 'dozent') ORDER BY Name";
    if ($perm->have_perm("admin"))
        $query = "SELECT seminare.* FROM user_inst LEFT JOIN Institute USING (Institut_id) LEFT JOIN seminare USING(Institut_id) LEFT OUTER JOIN seminar_user USING(Seminar_id) WHERE user_inst.inst_perms='admin' AND user_inst.user_id='$user->id' AND seminare.Institut_id is not NULL GROUP BY seminare.Seminar_id ORDER BY seminare.Name";
    if ($perm->have_perm("root"))
        $query = "SELECT Seminar_id, Name FROM seminare ORDER BY Name";
    $db=new DB_Seminar;
    $db->query($query);

    if ($perm->have_perm("tutor") OR $perm->have_perm("dozent") OR $perm->have_perm("admin")) {
        $query2 = "SELECT Institute.Institut_id, Name FROM user_inst LEFT JOIN Institute USING(Institut_id) WHERE user_id = '$user->id' AND (inst_perms = 'tutor' OR inst_perms = 'dozent' OR inst_perms = 'admin') ORDER BY Name";
        $db2=new DB_Seminar;
        $db2->query($query2);
    }
    if ($perm->have_perm("root")) {
        $query2 = "SELECT Institut_id, Name FROM Institute ORDER BY Name";
        $db2=new DB_Seminar;
        $db2->query($query2);
    }

?>
            <tr><td class="blank" colspan="2"><br>
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td class="steel2" colspan="2">
                    &nbsp; <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/yellow/arr_2right.png" border="0">&nbsp;<b><font size="-1"><?=sprintf(_("Als Thema verschieben (zusammen mit %s Antworten):"), $count)?></font></b>
                </td>
            </tr>
            <tr>
                <td class="steel1" colspan="2">&nbsp;
                    
                </td>
            </tr>
            <tr>
                <td class="steel1" align="right" nowrap width="20%" valign="baseline">
                    <font size="-1"><?=_("in das Forum einer Veranstaltung:")?></font>&nbsp; &nbsp;
                </td>
                <td class="steel1" width="80%">
            <?      echo "<form action=\"".URLHelper::getLink('')."\" method=\"POST\">"; ?>
                    <?= CSRFProtection::tokenTag() ?>
                    <input type="image" name="SUBMIT" value="Verschieben" src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/yellow/arr_2right.png" border="0" <?=tooltip(_("dahin verschieben"))?>>&nbsp;
                    <select Name="sem_id" size="1">
            <?      while ($db->next_record()) {
                            if ($check_modules->checkLocal('forum',$db->f("Seminar_id"),'sem')) {
                                $sem_name=htmlReady(substr($db->f("Name"), 0, 50));
                                printf ("<option %s value=\"%s\">%s\n", $db->f("Seminar_id") == $SessSemName[1] ? "selected" : "", $db->f("Seminar_id"), $sem_name);
                            }
                        }
            ?>  </select>
                    <input type="hidden" name="target" value="Seminar">
                    <input type="hidden" name="topic_id" value="<?echo $topic_id;?>">
                </form>
                </td>
            </tr>
            <?

        if (is_object($db2) && $db2->num_rows()) {   // Es kann auch in Institute verschoben werden
        ?>
            <tr>
                <td class="steel1" align="right" nowrap width="20%" valign="baseline">
                    <font size="-1"><?=_("in das Forum einer Einrichtung:")?></font>&nbsp; &nbsp;
                </td>
                <td class="steel1" width="80%">
            <?      echo "<form action=\"".URLHelper::getLink('')."\" method=\"POST\">"; ?>
                    <?= CSRFProtection::tokenTag() ?>
                    <input type=image name="SUBMIT" value="Verschieben" src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/yellow/arr_2right.png" border=0 <?=tooltip(_("dahin verschieben"))?>>&nbsp;
                <select Name="inst_id" size="1">
            <?      while ($db2->next_record()) {
                            if ($check_modules->checkLocal('forum',$db2->f("Institut_id"),'inst')) {
                                $inst_name=htmlReady(substr($db2->f("Name"), 0, 50));
                                printf ("<option value=\"%s\">%s\n", $db2->f("Institut_id"), $inst_name);
                            }
                        }
            ?>  </select>
                    <input type="hidden" name="target" value="Institut">
                    <input type="hidden" name="topic_id" value="<?echo $topic_id;?>">
                </form>
                </td>
            </tr>
        <?
        }
        ?>
            <tr valign="middle">
                <td class="steel1" align="right" nowrap width="20%">&nbsp;
                    
                </td>
                <td class="steel1" width="80%">
                <br>
                <?= LinkButton::createCancel() ?>
                </td>
            </tr>
        </table></td></tr>
<?
}

function forum_count($parent_id, $seminar_id = '') {
    global $SessSemName;

    if ($seminar_id == '') {
        $seminar_id = $SessSemName[1];
    }

    $db = new DB_Seminar("SELECT COUNT(*) AS count FROM px_topics WHERE Seminar_id = '$seminar_id' AND parent_id != '0' AND root_id = '$parent_id'");
    if ($db->next_record()) {
        return $db->f('count');
    }

    return false;
}
