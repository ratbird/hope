<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
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
    $edit = "<admin_msg autor=\"".get_fullname()."\" chdate=\"".time()."\">";
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
        $description = '';
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
                ."<div align=center><textarea aria-label=\"" . _("Text des Beitrags") . "\" name=\"description\" class=\"add_toolbar resizable\" style=\"width:70%\" cols=\"". $cols."\" rows=12 wrap=virtual placeholder=\"" . _("Ihr Beitrag") . "\">"
                .htmlReady($description)
                .htmlReady($zitat)
                ."</textarea>";
    } else {
        $description =  "<input type=hidden name=update value='".$forumposting["id"]."'>"
                ."<div align=center><textarea aria-label=\"" . _("Text des Beitrags") . "\" name=\"description\" class=\"add_toolbar resizable\" style=\"width:70%\" cols=\"". $cols."\"  rows=12 wrap=virtual placeholder=\"" . _("Ihr Beitrag") . "\">"
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
    $hash_secret = 'kertoiisdfgz';

    $query = "SELECT 1 FROM px_topics WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);

    do {
        $tmp_id = md5(uniqid($hash_secret));

        $statement->execute(array($tmp_id));
        $check = $statement->fetchColumn();
        $statement->closeCursor();
    } while ($check);

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
        $query = "SELECT topic_id FROM px_topics WHERE parent_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($topic_id));
        while ($id = $statement->fetchColumn()) {
            move_topic($id, $sem_id, $root, $verschoben);
        }
        if ($root == $topic_id) {
            $query = "UPDATE px_topics
                      SET parent_id = 0, root_id = :topic_id, Seminar_id= :sem_id
                      WHERE topic_id = :topic_id";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':topic_id', $topic_id);
            $statement->bindValue(':sem_id', $sem_id);
            $statement->execute();
        } else {
            $query = "UPDATE px_topics
                      SET root_id = ?, Seminar_id = ?
                      WHERE topic_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $root,
                $sem_id,
                $topic_id
            ));
        }

        $verschoben += 1;
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
        $query = "SELECT topic_id FROM px_topics WHERE parent_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($topic_id));
        while ($id = $statement->fetchColumn()) {
            move_topic2($id, $root, $verschoben, $thema);
        }
        if ($root == $topic_id) {
            $query = "UPDATE px_topics
                      SET parent_id = :thema, root_id = :thema
                      WHERE topic_id = :topic_id";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':thema', $thema);
            $statement->bindValue(':topic_id', $topic_id);
            $statement->execute();
        } else {
            $query = "UPDATE px_topics
                      SET root_id = ?
                      WHERE topic_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($thema, $topic_id));
        }

        $verschoben += 1;
        return $verschoben;
    }
}

/**
* Checks whether there can be edited or not (seeks childs an rights)
*
* @param    string topic_id posting to be checked
*
* @return   bool    lonely
*
**/
function lonely($topic_id)  //Sucht nach Kindern und den Rechten (für editieren)
{
    global $user, $rechte;

    $query = "SELECT 1 FROM px_topics WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));
    if ($statement->fetchColumn()) {
        return true;
    }
    
    $query = "SELECT user_id, chdate, mkdate
              FROM px_topics
              WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if ($row['user_id'] == $user->id || $rechte) {
            return false;
        }

        // nobody schreibt an seinem anderen Beitrag, nachträgliches editieren nicht möglich
        if ($user->id == 'nobody' && $row['chdate'] < $row['mkdate']) {
            return false;
        }
    }

    return true;
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
{   
    global $_open;

    $query = "SELECT topic_id FROM px_topics WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));
    while ($id = $statement->fetchColumn()) {
        suche_kinder($id);
    }

    $_open .= ';' . $topic_id;
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
* Ckeck whether a posting has children or not
*
* @param    array forumposting contains several data of the actual posting
*
* @return   array   forumposting whith additional lonely flag
*
**/
function forum_lonely($forumposting)
{  //Sieht nach ob das Posting kinderlos ist
    $query = "SELECT 1 FROM px_topics WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($forumposting['id']));
    $forumposting['lonely'] = !$statement->fetchColumn();

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
function ForumGetRoot($id)
{  //Holt die ID des Root-Postings
    $query = "SELECT root_id FROM px_topics WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    return $statement->fetchColumn() ?: null;
}

/**
* Gets the id of the parent-posting
*
* @param    string id of the child-posting
*
* @return   string  parent_id the id of the root-posting
*
**/
function ForumGetParent($id)
{  //Holt die ID des Parent-Postings (wird für Schreibanzeige gebraucht)
    $query = "SELECT parent_id FROM px_topics WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    return $statement->fetchColumn() ?: null;
}

/**
* check whether a posting is fresh or not
*
* @param    string id of the posting
*
* @return   bool    fresh indikates freshness
*
**/
function ForumFreshPosting($id)
{  //Sieht nach ob das Posting frisch angelegt ist (mkdate ist gleich chdate)
    $query = "SELECT 1
              FROM px_topics
              WHERE topic_id = ? AND chdate < mkdate";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    return $statement->fetchColumn() > 0;
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
function quote($zitat_id)
{
    global $perm;
    // Hilfsfunktion, die sich den zu quotenden Text holt, encodiert und zurueckgibt.
    $query = "SELECT description, author, anonymous
              FROM px_topics
              WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($zitat_id));
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $description = $row['description'];
        if (get_config('FORUM_ANONYMOUS_POSTINGS') && $row['anonymous'] && !$perm->have_perm('root')) {
            $author = _('anonym');
        } else {
            $author = $row['author'];
        }
    }
    $description = forum_kill_edit($description);
    $zitat = quotes_encode($description, $author);

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
function ForumGetName($id)
{ // Hilfsfunktion, die sich den Titel eines Beitrags holt
    $query = "SELECT name FROM px_topics WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    return $statement->fetchColumn() ?: null;
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

    $query = "SELECT 1
              FROM seminare
              WHERE Seminar_id = ? AND Schreibzugriff = 0";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($_SESSION['SessionSeminar']));
    if ($statement->fetchColumn()) {
        $attributes = array(
            'answer_id'            => $forumposting['id'],
            'flatviewstartposting' => 0
        );
        $edit .= LinkButton::create(_('Antworten'), URLHelper::getURL('#anker', $attributes));

        $attributes = array(
            'answer_id'            => $forumposting['id'],
            'zitat'                => 1,
            'flatviewstartposting' => 0
        );
        $edit .= LinkButton::create(_('Zitieren'), URLHelper::getURL('#anker', $attributes));
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

    $mkdate = time();
    $mkdate += $count++; //übler Hack,um Sortierreihenfolge für den DateAssi zu bekommen :)

    $chdate = $mkdate;  // normales Anlegen
    if (!$writeextern) {
        $chdate -= 1; // der Beitrag wird für alle ausser dem Author "versteckt"
    }
    
    if (!$user_id) {
        $user_id = $user->id;
    }

    $topic_id = MakeUniqueID();

    if ($root_id != '0') {
        $query = "SELECT seminar_id FROM px_topics WHERE topic_id = '$root_id'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($root_id));
        $temp = $statement->fetchColumn();
        if ($temp && $temp != $tmpSessionSeminar) {
            $tmpSessionSeminar = $temp;
        }
    } else {
        $root_id = $topic_id;
    }

    if ($user->id == 'nobody') {    // darf Nobody hier schreiben?
        $query = "SELECT 1 FROM seminare WHERE Seminar_id = ? AND Schreibzugriff = 0";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($_SESSION['SessionSeminar']));
        $may_write = $statement->fetchColumn() > 0;
    } else {
        $may_write = $perm->have_perm('autor');
    }

    if (!$may_write) {
        throw new AccessDeniedException(_('Ihnen fehlen die Rechte, in dieser Veranstaltung zu schreiben.'));
    }

    $query = "INSERT INTO px_topics
                (topic_id, name, description, parent_id, root_id, author,
                 author_host, Seminar_id, user_id, mkdate, chdate, anonymous)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $topic_id,
        $name,
        $description,
        $parent_id,
        $root_id,
        $author,
        getenv('REMOTE_ADDR'),
        $tmpSessionSeminar,
        $user_id,
        $mkdate, 
        $chdate,
        $anonymous ? 1 : 0
    ));
    if ($statement->rowCount() == 0) {
        throw new Exception(_('Fehler beim Anlegen eines Forenbeitrags.'));
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
function UpdateTopic($name = '[no name]', $topic_id, $description, $anonymous)
{
    if (lonely($topic_id)) {
        throw new AccessDeniedException(_('Ihnen fehlen die Rechte, diesen Forenbeitrag zu bearbeiten.'));
    }

    $query = "UPDATE px_topics
              SET name = ?, description = ?, anonymous = ?, chdate= UNIX_TIMESTAMP()
              WHERE topic_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $name,
        $description,
        $anonymous ? 1 : 0,
        $topic_id
    ));
    if ($statement->rowCount() == 0) {
        throw new Exception(_('Aktualisieren des Forenbeitrags fehlgeschlagen.'));
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
        $striche.= "<td class=\"printcontent tree-indent\" nowrap background-color: #f3f5f8;><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"></td>";
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
function ForumCheckShrink($id)
{
    global $age;
    $query = "SELECT topic_id, chdate
              FROM px_topics
              WHERE parent_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $age .= ';' . $row['chdate'];
        ForumCheckShrink($row['topic_id']);
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
function print_rating($rate, $id, $username)
{
    global $forum, $user, $auth;
    if ($rate == '?') {
        $bar = Assets::img('rate_leer.gif', array('width' => 50, 'height' => 11));
    } else {
        $ratecount = object_return_ratecount($id);
        if ($ratecount > 10) {
            $ratecount = 10;
        }
        $ratecount = round($ratecount / 2);
        $rate = StringToFloat($rate);
        if ($rate > 3) {
            $grau = (5-$rate)*10;
            $rot = 25 - $grau;
            $bar  = Assets::img('rate_leer.gif', array('width' => 25, 'height' => 11));
            $bar .= Assets::img('rate_rot' . $ratecount . '.gif', array('width' => $rot, 'height' => 11));
            $bar .= Assets::img('rate_leer.gif', array('width' => $grau, 'height' => 11));
        } elseif ($rate < 3) {
            $grau = ($rate-1)*10;
            $gruen = 25 - $grau;
            $bar  = Assets::img('rate_leer.gif', array('width' => $grau, 'height' => 11));
            $bar .= Assets::img('rate_gruen' . $ratecount . '.gif', array('width' => $gruen, 'height' => 11));
            $bar .= Assets::img('rate_leer.gif', array('width' => 25, 'height' => 11));
        } else {
            $bar = Assets::img('rate_neutral' . $ratecount . '.gif', array('width' => 50, 'height' => 11));
        }
    }
    if (object_check_user($id, "rate") == TRUE || get_username($user->id) == $username) { // already rated / my own posting
        $bar = '<span class="rating" ' . tooltip(sprintf(_("Bewertung: %s"), $rate), false) . '>' . $bar. '</span>';
    } else {
        $url = URLHelper::getLink('?open=' . $id . '&flatviewstartposting=' . $forum['flatviewstartposting'] . '&sidebar=' . $id . '#anker');
        $bar = '<a class="rating" href="' . $url . '" onClick="STUDIP.Forum.rate_template(\''.$id.'\'); return false;" '
            . tooltip(sprintf(_('Bewertung: %s Zum Abstimmen bitte klicken.'),$rate), false) . '>'
            . $bar
            . '</a>';
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

        if ($forumposting['fav']) {
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
            $name = "<input aria-label=\"" . _("Titel des Beitrags") . "\" type=text size=50 style='font-size:8 pt;font-weight:normal;' name=titel value='".htmlReady($forumposting["name"])."' placeholder=\"" . _('Name des Themas') . "\">";
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
            echo '<table class="forum_headline" width="100%" border="0" cellpadding="0" cellspacing="0"><tr>';
        if ($forum["anchor"] == $forumposting["id"])
            echo "<a name='anker'></a>";
        printhead ("100%","0",$link,$forumposting["openclose"],$new,$forumposting["icon"],$name,$zusatz,$age_tmp,"TRUE",$index,$forum["indikator"]);
        //if ($forumposting["intree"]==TRUE)
        //    echo "<td class=\"blank\">&nbsp;&nbsp;&nbsp;</td>";
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
                //while(list($key,$value) = each($rate))
                //    $addon .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$key: $value<br>";
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

        echo '<table class="forum_content" width="100%" border="0" cellpadding="0" cellspacing="0"><tr>';

        if ($forumposting["intree"]==TRUE) // etwas Schmuckwerk für die Strichlogik
            echo ForumStriche($forumposting);
        printcontent ("100%",$forumposting,$description,$edit,TRUE,$addon);
        //if ($forumposting["intree"]==TRUE)
        //    echo "<td class=\"blank\">&nbsp;&nbsp;&nbsp;</td>";
        echo "</tr></table>\n";
    }
    return $forumposting;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function countTopics($addon = '', $parameters = array())
{
    global $user;
    $query = "SELECT COUNT(*)
              FROM px_topics x, px_topics y 
              WHERE x.root_id = y.topic_id AND x.Seminar_id = :seminar_id
                AND (x.chdate >= x.mkdate OR x.user_id = :user_id OR x.author = 'unbekannt')
              {$addon}";
    $parameters[':user_id']    = $user->id;
    $parameters[':seminar_id'] = $_SESSION['SessionSeminar'];

    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    return $statement->fetchColumn() ?: 0;
}

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
function flatview($open = 0, $show = 0, $update = '', $name = '', $description = '', $zitat = '')
{
    global $SessSemName, $rechte, $forum, $user, $new_topic;

    /////////////////////////////// Konstanten setzen bzw. zuweisen die für die ganze Seite gelten
    $forum['openlist'] = $open;
    $forum['zitat']    = $zitat;
    $forum['update']   = $update;

    $postingsperside          = (int) get_config('ENTRIES_PER_PAGE');
    $forum['postingsperside'] = $postingsperside;

    $flatviewstartposting          = Request::int('flatviewstartposting', 0);
    $forum['flatviewstartposting'] = $flatviewstartposting;

    /////////////////////////////// Abfrage der Postings

    $addon = '';
    $parameters= array();

    $order = 'DESC';
    if (in_array($forum['sort'], words('rating nachname root_name name')) && !in_array($forum['view'], words('tree mixed'))) {
        $order = 'ASC';
    }

    if ($forum['view'] == 'flatfolder') {
        $addon = " AND x.root_id = :flatfolder";
        $parameters[':flatfolder'] = $forum['flatfolder'];
    } else if ($forum['view'] == 'search') {
        if ($forum['search'] != '') {
            $addon = ' AND (' . $forum['search'] . ')';
        } else {
            echo forum_search_field() . '<br><br>';
            return;
        }
    } else if ($forum['view'] == 'neue') {
        $addon = ' AND x.chdate > :last_visit';
        $parameters[':last_visit'] = object_get_visit($SessSemName[1], 'forum');
    }


    // Forum ist nicht leer
    $count = countTopics($addon, $parameters);
    if ($count > 0 || isset($new_topic)) {
        $forum['forumsum'] = $count;
    } else if ($forum['view'] === 'neue' && countTopics()) {
        // keine neuen, aber alte
        $forum['view'] = 'flat';
        $addon = '';
        ?>
        <div class="white" style="padding: 0.5em 0;">
            <?= MessageBox::info(_('Es liegen keine neuen Beiträge vor.')) ?>
        </div>
        <?
    } else {
        // das Forum ist leer
        echo '<table class="default">';
        echo ForumNoPostings();
        echo '</table>';
        return;
    }

    // we proudly present: the longest SQl in Stud.IP :) regards to Suchi+Noack for inspirations
    $query = "SELECT x.topic_id AS id, x.name, x.author, x.mkdate, x.chdate AS age,
                     x.chdate,
                     x.description, x.Seminar_id, x.user_id AS userid,
                     y.name AS rootname, y.topic_id AS rootid, username,
                     IFNULL(views, 0) AS viewcount, nachname,
                     IFNULL(ROUND(AVG(rate), 1), 99) AS rating,
                     object_user.object_id != '' AS fav,
                     IF(:allow_anonymous, x.anonymous, 0) AS anonymous,
                     ((6-(IFNULL(AVG(rate),3))-3)*5)+(IFNULL(views,0)/(((UNIX_TIMESTAMP()-x.mkdate)/604800)+1)) AS score
              FROM px_topics AS y, px_topics AS x
              LEFT JOIN object_views ON (object_views.object_id = x.topic_id)
              LEFT JOIN object_rate ON (object_rate.object_id = x.topic_id) 
              LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = x.user_id)
              LEFT OUTER JOIN object_user
                ON (object_user.object_id = x.topic_id AND object_user.user_id = :user_id AND flag = 'fav')
              WHERE x.root_id = y.topic_id AND x.seminar_id = :seminar_id
                AND (x.chdate >= x.mkdate OR x.user_id = :user_id OR x.author = 'unbekannt')
                {$addon}
              GROUP by x.topic_id
              ORDER BY {$forum['sort']} {$order}, age DESC
              LIMIT {$flatviewstartposting}, {$postingsperside}";
    $parameters[':user_id']    = $user->id;
    $parameters[':seminar_id'] = $_SESSION['SessionSeminar'];
    $parameters[':allow_anonymous'] = get_config('FORUM_ANONYMOUS_POSTINGS') ? 1 : 0;

    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);

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

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        unset($row['age']);
        $forumposting = printposting($row);
    }

    /////////// HTML für den Rest

    echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
    echo "  <tr>";
    echo "      <td class=\"steelgraudunkel\" align=\"right\" ><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" border=\"0\" height=\"10\" align=\"middle\">";
    echo forum_print_navi($forum)."&nbsp;&nbsp;&nbsp;".forum_get_index($forumposting);
    echo "      &nbsp;&nbsp;</td>";
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
function DisplayFolders ($open = 0, $update = '', $zitat = '')
{
    global $SessSemName, $rechte, $forum, $auth, $user, $SEM_CLASS, $SEM_TYPE, $perm;
    global $new_topic;

//Zeigt im Treeview die Themenordner an

    $forum['update'] = $update;
    $forum['zitat']  = $zitat;
    $forum['sort']   = 'age';

    if ($forum['sortthemes'] == 'last') {
        $order = 'last DESC';
    } else {
        $order = 't.mkdate '. ($forum['sortthemes'] == 'asc' ? 'ASC' : 'DESC');
    }

    $query = "SELECT DISTINCT t.topic_id AS id, t.parent_id, t.root_id AS rootid, t.name, t.description,
                     t.author, t.author_host, t.mkdate, t.chdate, t.user_id AS userid, username, 
                     IF (:allow_anonymous, t.anonymous, 0) AS anonymous,
                     COUNT(DISTINCT s.topic_id) AS foldercount, MAX(s.chdate) AS folderlast,
                     IFNULL(views, 0) AS viewcount,
                     IFNULL(ROUND(AVG(rate), 1), 99) AS rating,
                     ((6-(IFNULL(AVG(rate),3))-3)*5)+(IFNULL(views,0)/(((UNIX_TIMESTAMP()-t.mkdate)/604800)+1)) AS score,
                     object_user.object_id != '' AS fav
              FROM px_topics AS t
              LEFT JOIN px_topics AS s ON (s.root_id = t.root_id AND s.chdate >= s.mkdate)
              LEFT JOIN auth_user_md5 AS aum ON (t.user_id = aum.user_id)
              LEFT JOIN object_views ON (object_views.object_id = t.topic_id)
              LEFT JOIN object_rate ON (object_rate.object_id = t.topic_id)
              LEFT OUTER JOIN object_user
                ON (object_user.object_id = t.root_id AND object_user.user_id = :user_id AND flag = 'fav')
              WHERE t.topic_id = t.root_id AND t.Seminar_id = :seminar_id
                AND (t.chdate >= t.mkdate OR t.user_id = :user_id OR t.author = 'unbekannt')
              GROUP BY t.root_id
              ORDER BY {$order}";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        ':user_id'         => $user->id,
        ':seminar_id'      => $_SESSION['SessionSeminar'],
        ':allow_anonymous' => get_config('FORUM_ANONYMOUS_POSTINGS') ? 1 : 0,
    ));
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) == 0 && !isset($new_topic)) {  // Das Forum ist leer
        echo '<table class="default">';
        echo ForumEmpty();
        echo '</table>';
        return;
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
        echo "<td class=\"steelgraudunkel\" width=\"33%\" align=\"center\">";
        if ($user->id != "nobody") { // Nobody kriegt nur treeview
            if ($forum["view"] == "tree")
                echo "<a href=\"".URLHelper::getLink("?view=mixed&themeview=mixed")."\">" . Assets::img('forumtree.gif') . "</a>";
            else
                echo "<a href=\"".URLHelper::getLink("?view=tree&themeview=tree")."\">" . Assets::img('forumflat.gif') . "</a>";
        }
        echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" border=0 height=\"20\" align=\"middle\"></td>";
        echo "<td class=\"steelgraudunkel\" width=\"33%\"align=\"right\">" . _("<b>Forenbeiträge</b> / letzter Eintrag") . " ".forum_get_index($forumposting)." </td></tr></table>\n";
        foreach ($data as $row) {
            $forumposting = printposting($row);

            if ($forum["view"] == "tree" && $forumposting["openclose"]=="open" && Request::option('cmd') != "move") {
                DisplayKids ($forumposting);
            }
        }

        if (isset($new_topic) && $new_topic['parent_id'] == "0") {
            printposting($new_topic);
        }
    }
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>';
    echo "<td align=center class=steelgraudunkel><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif' border=0 height='20' align=middle>";
    if (($perm->have_perm("autor")) && (($rechte) || ($SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["topic_create_autor"])))
        echo "<a href='".URLHelper::getLink("?neuesthema=TRUE#anker")."'><img src='".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1down.png' align=middle " . tooltip(_("Neues Thema anlegen")) . "><img src='".$GLOBALS['ASSETS_URL']."images/icons/16/blue/add/folder-empty.png' " . tooltip(_("Neues Thema anlegen")) . " border=0 align=middle></a>";
    echo "</td></tr></table>\n";


    // echo DebugForum($forum);
    // echo "<hr>";
    // echo DebugForum($forumposting);

    if ($update) {
        echo "</form>\n";
    }
}

/////////////////////////////////

/**
 * Hier eine bezaubernde Routine um die Striche exakt wiederzugeben - keine Bange ich verstehe sie auch nicht mehr
 */
function indentPosting (&$forumposting, $level)
{
    echo "<table class=\"blank\" border=0 cellpadding=0 cellspacing=0 width=\"100%\" valign=\"top\"><tr valign=\"top\"><td class=\"blank tree-indent\" nowrap valign=\"top\" ><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'><img src='".$GLOBALS['ASSETS_URL']."images/forumleer.gif'>";

    if ($level) {
        $striche = '';
        for ($i = 0; $i < $level; $i += 1) {
            if ($i == $level - 1) {
                if ($forumposting['lines'][$i + 1] > 1) {
                    //Kreuzung
                    $striche .= Assets::img('forumstrich3.gif');
                } else {
                    //abknickend
                    $striche .= Assets::img('forumstrich2.gif');
                }
                $forumposting['lines'][$i + 1] -= 1;
            } else {
                if ($forumposting['lines'][$i + 1] == 0) {
                    //Leerzelle
                    $striche .= Assets::img('forumleer.gif');
                } else {
                    //Strich
                    $striche .= Assets::img('forumstrich.gif');
                }
            }
        }
        echo $striche;
    }
    echo '</td>';
}


/**
* Amazing engine to build the treeview incl. shrinking
*
* @param    array   forumpostng contains several data of the curren posting
* @param    string  level contains the current level of the treeview
*
**/
function DisplayKids ($forumposting, $level = 0)
{
    global $SessSemName, $forum,$rechte,$auth,$user, $age;
    global $new_topic;

    // stellt im Treeview alle Postings dar, die NICHT Thema sind
    $topic_id = $forumposting['id'];
    $new_topic_here = isset($new_topic) && $new_topic['parent_id'] == $topic_id;
    $forumposting["intree"]="TRUE";

    $query = "SELECT topic_id AS id, parent_id, name, author, pt.mkdate, pt.chdate,
                     description, root_id AS rootid, username, pt.user_id AS userid,
                     IFNULL(views,0) AS viewcount,
                     IFNULL(ROUND(AVG(rate),1),99) AS rating,
                     ((6-(IFNULL(AVG(rate),3))-3)*5)+(IFNULL(views,0)/(((UNIX_TIMESTAMP()-pt.mkdate)/604800)+1)) AS score,
                     IF(object_user.object_id!='',1,0) AS fav,
                     IF (:allow_anonymous, anonymous, 0) AS anonymous
              FROM px_topics AS pt
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN object_views ON (object_views.object_id = topic_id)
              LEFT JOIN object_rate ON (object_rate.object_id = topic_id)
              LEFT OUTER JOIN object_user
                ON (object_user.object_id = topic_id AND object_user.user_id = '$user->id' AND flag = 'fav')
              WHERE parent_id = '$topic_id'
                AND (pt.chdate >= pt.mkdate OR pt.user_id = '$user->id' OR pt.author='unbekannt')
              GROUP BY topic_id
              ORDER by pt.mkdate";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        ':user_id'         => $user->id,
        ':topic_id'        => $topic_id,
        ':allow_anonymous' => get_config('FORUM_ANONYMOUS_POSTINGS') ? 1 : 0
    ));
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    $forumposting['lines'][$level] = count($rows) + $new_topic_here;
    foreach ($rows as $row) {
        foreach ($row as $key => $val) {
            $forumposting[$key] = $val;
        }

        $forumposting["level"] = $level;

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
function forum_search_field ()
{
    $template = $GLOBALS['template_factory']->open('forum/search');
    return $template->render();
}

/////////////////////

/**
* Builds the HTML for the move-navigation
*
* @param    string  topic_id the id of the original posting to be moved
*
**/
function forum_move_navi ($topic_id)
{
    global $perm, $user;

    $mutter = suche_kinder($topic_id);
    $mutter = explode(';', $mutter);
    $count = count($mutter) - 2;
    $check_modules = new Modules;

    // Wohin darf ich schieben? Abfragen je nach Rechten
    
    // Erst die Veranstaltungen
    $seminars = array();
    if ($perm->have_perm('root')) {
        $query = "SELECT Seminar_id, Name FROM seminare ORDER BY Name";
        $seminars = DBManager::get()->query($query)->fetchGrouped(PDO::FETCH_COLUMN);
    } else if ($perm->have_perm('admin')) {
        $query = "SELECT seminare.Seminar_id, seminare.Name
                  FROM user_inst
                  LEFT JOIN Institute USING (Institut_id)
                  LEFT JOIN seminare USING (Institut_id)
                  LEFT OUTER JOIN seminar_user USING (Seminar_id)
                  WHERE user_inst.inst_perms = 'admin' AND user_inst.user_id = ?
                    AND seminare.Institut_id IS NOT NULL
                  GROUP BY seminare.Seminar_id
                  ORDER BY seminare.Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id));
        $seminars = $statement->fetchGrouped(PDO::FETCH_COLUMN);
    } else if ($perm->have_perm('autor')) {
        $query = "SELECT DISTINCT seminare.Seminar_id, seminare.Name
                  FROM seminar_user
                  LEFT JOIN seminare USING (Seminar_id)
                  WHERE user_id = ? AND seminar_user.status IN ('tutor', 'dozent')
                  ORDER BY Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id));
        $seminars = $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }

    // Prüfen, in welchem der Seminare das Forum aktiviert ist
    foreach ($seminars as $id => $name) {
        if (!$check_modules->checkLocal('forum', $id, 'sem')) {
            unset($seminars[$id]);
        }
    }

    // Nun die Einrichtungen
    $institutes = array();
    if ($perm->have_perm('root')) {
        $query = "SELECT Institut_id, Name FROM Institute ORDER BY Name";
        $institutes = DBManager::get()->query($query)->fetchGrouped(PDO::FETCH_COLUMN);
    } else if ($perm->have_perm('tutor') || $perm->have_perm('dozent') || $perm->have_perm('admin')) {
        $query = "SELECT Institute.Institut_id, Name
                  FROM user_inst
                  LEFT JOIN Institute USING (Institut_id)
                  WHERE user_id = '$user->id' AND inst_perms IN ('tutor', 'dozent', 'admin')
                  ORDER BY Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id));
        $institutes = $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }
    
    // Prüfen, in welcher der Einrichtungen das Forum aktiviert ist
    foreach ($institutes as $id => $name) {
        if (!$check_modules->checkLocal('forum', $id, 'inst')) {
            unset($institutes[$id]);
        }
    }

    $template = $GLOBALS['template_factory']->open('forum/move_navigation');
    $template->topic_id        = $topic_id;
    $template->count           = $count;
    $template->seminars        = $seminars;
    $template->current_seminar = $GLOBALS['SessSemName'][1];
    $template->institutes      = $institutes;
    echo $template->render();
}

function forum_count($parent_id, $seminar_id = '')
{
    global $SessSemName;

    if ($seminar_id == '') {
        $seminar_id = $SessSemName[1];
    }

    $query = "SELECT COUNT(*)
              FROM px_topics
              WHERE Seminar_id = ? AND parent_id != '0' AND root_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id, $parent_id));
    return $statement->fetchColumn() ?: false;
}
