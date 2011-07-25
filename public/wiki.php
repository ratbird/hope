<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
wiki.php - (No longer so) Simple WikiWikiWeb in Stud.IP

@module wiki
@author Tobias Thelen <tthelen@uos.de>

Copyright (C) 2003 Tobias Thelen <tthelen@uni-osnabrueck.de>
Contains code (regex for WikiWord detection) from Blast Wiki http://www.roboticboy.com/wiki/ (GPL'd)
Contains code (diff routine) from PukiWiki http://www.pukiwiki.org (GPL'd)

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($again && ($auth->auth["uid"] == "nobody"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/wiki.inc.php';
require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';

// -- Load Wiki Plugins -------------------
// $WIKI_PLUGINS is defined in local.inc
//
$wiki_plugin_messages=array();

if (is_array($WIKI_PLUGINS)) {
    foreach ($WIKI_PLUGINS as $plugin) {
        require_once('lib/'.$plugin);
    }
}


if ($view=="wikiprint") {
    printWikiPage($keyword, $version);
    page_close();
    die();
} elseif ($view=="wikiprintall") {
    printAllWikiPages($SessSemName[1], $SessSemName[header_line]);
    page_close();
    die();
} elseif ($view=="export_pdf") {
    include_once 'lib/classes/exportdocument/ExportPDF.class.php';
    exportWikiPagePDF($keyword, $version);
}

checkObject(); // do we have an open object?
checkObjectModule("wiki"); //are we allowed to use this module here?
object_set_visit_module("wiki");

PageLayout::setHelpKeyword("Basis.Wiki"); // Hilfeseite im Hilfewiki
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Wiki"));

if (in_array(Request::get('view'), words('listnew listall export'))) {
    Navigation::activateItem('/course/wiki/' . Request::get('view'));
} else {
    Navigation::activateItem('/course/wiki/show');
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if ($wiki_comments=="all") {         // show all comments
    $show_wiki_comments="all";
} elseif ($wiki_comments=="none") {  // don't show comments
    $show_wiki_comments="none";
} else {                             // show comments as icons
    $show_wiki_comments="icon";
}

URLHelper::addLinkParam('wiki_comments', $show_wiki_comments);


// echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>\n";

$db = new DB_Seminar();
$user_id=$auth->auth['uid'];

wikiSeminarHeader();

// ---------- Start of main WikiLogic

if (is_array($wiki_plugin_messages)) { // print ay messages produced by plugins
    foreach ($wiki_plugin_messages as $msg) {
        begin_blank_table();
        parse_msg($msg);
        end_blank_table();
    }
}

if ($view=="listall") {
    //
    // list all pages, default sorting = alphabetically
    //
    SkipLinks::addIndex(_("Alle Seiten"), 'main_content', 100);
    listPages("all", $sortby);

} else if ($view=="listnew") {
    //
    // list new pages, default sorting = newest first
    //
    SkipLinks::addIndex(_("Neue Seiten"), 'main_content', 100);
    listPages("new", $sortby);

} else if ($view=="diff") {
    //
    // show one large diff-file containing all changes
    //
    SkipLinks::addIndex(_("Seite mit Änderungen"), 'main_content', 100);
    showDiffs($keyword, $versionssince);

} else if ($view=="combodiff") {
    //
    // show one large diff-file containing all changes
    //
    SkipLinks::addIndex(_("Seite mit Änderungen"), 'main_content', 100);
    showComboDiff($keyword);

} else if ($view=="diffselect") {
    //
    // show only last changes in a diff
    //
    SkipLinks::addIndex(_("Seite mit Änderungen"), 'main_content', 100);
    showDiffs($keyword, $diffmode);

} else if ($view=="export") {
    //
    // show export dialog
    //
    SkipLinks::addIndex(_("Seiten exportieren"), 'wiki_export', 100);
    exportWiki();

} else if ($view=="search") {
    searchWiki(stripslashes($_REQUEST["searchfor"]), $_REQUEST["searchcurrentversions"], $_REQUEST["keyword"], $_REQUEST["localsearch"]);

} else if ($view=="edit") {
    //
    // show page for editing
    //
    if (!$perm->have_studip_perm("autor", $SessSemName[1])) {
        begin_blank_table();
        parse_msg("error§" . _("Sie haben keine Berechtigung, Seiten zu editieren!"));
        end_blank_table();
        echo '</td></tr></table>';
        include ('lib/include/html_end.inc.php');
        die;
    }

    // prevent malformed urls: keword must be set
    if (!$keyword) {
        begin_blank_table();
        parse_msg("error§" . _("Es wurde keine zu editierende Seite übergeben!"));
        end_blank_table();
        echo '</td></tr></table>';
        include ('lib/include/html_end.inc.php');
        die;
    }
    SkipLinks::addIndex(_("Seite bearbeiten"), 'main_content', 100);

    $wikiData=getWikiPage($keyword,0); // always get newest page

    // set lock
    setWikiLock($db, $user->id, $SessSemName[1], $keyword);

    //show form
    wikiEdit($keyword, $wikiData, $user->id);

} else if ($view=='editnew') { // edit a new page

    if (!$perm->have_studip_perm("autor", $SessSemName[1])) {
        begin_blank_table();
        parse_msg("error§" . _("Sie haben keine Berechtigung, Seiten zu editieren!"));
        end_blank_table();
        echo '</td></tr></table>';
        include ('lib/include/html_end.inc.php');
        die;
    }
    // set lock
    setWikiLock($db, $user->id, $SessSemName[1], $keyword);
    wikiEdit($keyword, NULL, $user->id, $lastpage);

} else {
    // Default action: Display WikiPage (+ logic for submission)
    //
    if (empty($keyword)) {
        $keyword='WikiWikiWeb'; // display Start page as default
    }
    releaseLocks($keyword); // kill old locks
    $special="";

    if ($submit) {
        //
        // Page was edited and submitted
        //
        $special=submitWikiPage($keyword, $version, $body, $user->id, $SessSemName[1]);
        $version=""; // $version="" means: get latest

    } else if ($cmd == "abortedit") { // Editieren abgebrochen
        //
        // Editing page was aborted
        //
        releasePageLocks($keyword); // kill lock (set when starting to edit)
        if ($lastpage) { // if editing new page was aborted, display last page again
            $keyword=$lastpage;
        }

    } else if ($cmd == "delete") {
        //
        // Delete request sent -> confirmdialog and current page
        //
        $special="delete";

    } else if ($cmd == "really_delete") {
        //
        // Delete was confirmed -> really delete
        //

        $keyword=deleteWikiPage($keyword, $version, $SessSemName[1]);
        $version=""; // show latest version

    } else if ($cmd == "delete_all") {
        //
        // Delete all request sent -> confirmdialog and current page
        //
        $special="delete_all";

    } else if ($cmd == "really_delete_all") {
        //
        // Delete all was confirmed -> delete entire page
        //
        $keyword=deleteAllWikiPage($keyword, $SessSemName[1]);
        $version=""; // show latest version
    }

    //
    // Show Page
    //
    SkipLinks::addIndex(_("Aktuelle Seite"), 'main_content', 100);
    showWikiPage($keyword, $version, $special, $show_wiki_comments, stripslashes($_REQUEST["hilight"]));

} // end default action

include 'lib/include/html_end.inc.php';

// Save data back to database.
page_close()
?>
