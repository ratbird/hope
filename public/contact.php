<?
# Lifter002: TEST
# Lifter003: TEST
# Lifter005: TEST - overlib
# Lifter007: TODO
# Lifter010: DONE - not applicable
/*
contact.php - 0.8
Bindet Adressbuch ein.
Copyright (C) 2003 Ralf Stockmann <rstockm@uni-goettingen.de>

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

// Default_Auth

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

require_once 'lib/functions.php';
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/user_visible.inc.php');
require_once ('lib/contact.inc.php');
require_once ('lib/visual.inc.php');

$cssSw = new cssClassSwitcher;                                  // Klasse für Zebra-Design
$cssSw->enableHover();
PageLayout::setTitle(_("Mein Adressbuch"));
Navigation::activateItem('/community/contacts/' . Request::get('view', 'alpha'));
// add skip links
SkipLinks::addIndex(Navigation::getItem('/community/contacts/' . Request::get('view'))->getTitle(), 'main_content', 100);


$filter = Request::option('filter');
$contact = Request::quotedArray('contact');
$view = Request::option('view');
$contact_id = Request::option('contact_id');
$open = Request::option('open');
$edit_id = Request::option('edit_id');
if (!$contact["filter"])
    $contact["filter"]="all";
if ($view) {
    $contact["view"]=$view;
}
if (!$contact["view"])
    $contact["view"]="alpha";

if ($filter) {
    $contact["filter"]=$filter;
}
$filter = $contact["filter"];

if ($filter == "all")
    $filter="";
if ($contact["view"]=="alpha" && strlen($filter) > 3)
    $filter="";
if ($contact["view"]=="gruppen" && strlen($filter) < 4)
    $filter="";

// Aktionen //////////////////////////////////////

// adding a contact via search

if (Request::get('Freesearch')) {
    $open = AddNewContact(get_userid(Request::get('Freesearch')));
}

// deletel a contact

if (Request::option('cmd') == "delete") {
    DeleteContact ($contact_id);
}

// remove from buddylist

if (Request::option('cmd') == "changebuddy") {
    changebuddy($contact_id);
    if (!$open) {
        $open = $contact_id;
    }
}

// change calendar permissions
if (!is_null(Request::get('calperm')))  {
    if (Config::get()->getValue('CALENDAR_GROUP_ENABLE')) {
        switch_member_cal(Request::get('user_id'), Request::get('calperm', Calendar::PERMISSION_FORBIDDEN));
    }
}

// delete a single userinfo

if (Request::option('deluserinfo')) {
    DeleteUserinfo (Request::option('deluserinfo'));
}

if (Request::option('move')) {
    MoveUserinfo (Request::option('move'));
}

// add a new userinfo
$owninfocontent = Request::quotedArray('owninfocontent');
$owninfolabel =  Request::quotedArray('owninfolabel');
if ($owninfolabel AND ($owninfocontent[0]!=_("Inhalt"))){
    AddNewUserinfo ($edit_id, $owninfolabel[0], $owninfocontent[0]);
}
$existingowninfolabel = Request::quotedArray('existingowninfolabel');
$userinfo_id = Request::optionArray('userinfo_id');
$existingowninfocontent = Request::quotedArray('existingowninfocontent');
if ($existingowninfolabel) {
    for ($i=0; $i<sizeof($existingowninfolabel); $i++) {
      UpdateUserinfo ($existingowninfolabel[$i], $existingowninfocontent[$i], $userinfo_id[$i]);
    }
}


if (Request::get('edit_id') && Request::submitted('uebernehmen')) {
    PageLayout::postMessage(Messagebox::success(_('Die Änderungen wurden übernommen.')));
}

$search_exp = Request::quoted('search_exp');
$search_results = false;
if ($search_exp) {
    $search_exp = str_replace("%", "\%", $search_exp);
    $search_exp = str_replace("_", "\_", $search_exp);
    $search_exp = trim($search_exp);
    
    if (strlen($search_exp) < 3) {
        PageLayout::postMessage(Messagebox::error(_('Ihr Suchbegriff muss mindestens 3 Zeichen umfassen!')));
    } else {
        $search_results = SearchResults($search_exp);
        if (!$search_results) {
            $message = sprintf(_('Keine Treffer zum Suchbegriff: %s'), $search_exp);
            PageLayout::postMessage(Messagebox::info($message));
        }
    }
}

if (($filter ?: 'all') == 'all') {
    $size_of_book = GetSizeofBook();
} else {
    $sizes = array_merge(GetSizeOfBookByLetter(), GetSizeOfBookByGroup());
    $size_of_book = $sizes[$filter];
}

$template = $GLOBALS['template_factory']->open('contact/index');
$template->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
$template->size_of_book           = $size_of_book;
$template->sizes                  = array_merge(GetSizeOfBookByLetter(), GetSizeOfBookByGroup());
$template->open                   = $open;
$template->view                   = $view;
$template->filter                 = $filter;
$template->search_exp             = $search_exp;
$template->search_results         = $search_results;
$template->edit_id                = $edit_id;
$template->contact                = $contact;

if ($contact['view'] == 'gruppen') {
    $query = "SELECT statusgruppe_id, name FROM statusgruppen WHERE range_id = ? ORDER BY position ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $template->groups = $statement->fetchGrouped(PDO::FETCH_COLUMN);
}

echo $template->render();

page_close();
