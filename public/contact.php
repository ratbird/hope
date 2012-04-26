<?
# Lifter002: TODO
# Lifter005: TEST - overlib
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head


echo "\n" . $cssSw->GetHoverJSFunction() . "\n";
$cssSw->switchClass();
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


$size_of_book = GetSizeofBook();
$size_of_book_by_filter = array_merge(GetSizeOfBookByLetter(), GetSizeOfBookByGroup());

?>
<form action="<? echo $PHP_SELF ?>?cmd=search#anker" method="post">
<?= CSRFProtection::tokenTag() ?>
<table width = "100%" cellspacing="0" border="0" cellpadding="0">
<?
if (Request::get('edit_id') && Request::submitted('uebernehmen')) {
    echo '<tr><td class="blank" colspan="2">';
    echo MessageBox::success(_("Die Änderungen wurden übernommen."));
    echo '</td></tr>';
}
?>
    <tr>
        <td class="blank" align="left">
<?
if ($size_of_book > 0) {
    $link = URLHelper::getLink('',array('view'=>$view, 'open'=>'all', 'filter'=>$filter));
    
    if (Request::get('filter', 'all') == 'all' || Request::get('filter') == '') {
       $current_size = $size_of_book;
    } else {
       $current_size = $size_of_book_by_filter[$filter];
    }
    
    echo "&nbsp; <a href=\"$link\">";
    
    
    if ($open != 'all') {
    echo Assets::img('icons/16/blue/arr_1down.png');
    echo _("Alle aufklappen (");
    } else {
     echo Assets::img('icons/16/blue/arr_1up.png');
     echo _("Alle zuklappen (");
    }
    
    echo ($current_size == 1 ? _("1 Eintrag") : sprintf(_("%d Eintr&auml;ge"), $current_size)) . ")</a></td>";
}

echo "<td class=\"blank\" align=\"right\">";
$search_exp = Request::quoted('search_exp');
if ($search_exp) {
    $search_exp = str_replace("%", "\%", $search_exp);
    $search_exp = str_replace("_", "\_", $search_exp);
    if (strlen(trim($search_exp))<3) {
        echo "&nbsp; <font size=\"-1\">"._("Ihr Suchbegriff muss mindestens 3 Zeichen umfassen! ");
        printf ("<a href=\"".URLHelper::getLink()."\"><img src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\" value=\"" . _("neue Suche") . "\" %s>", tooltip(_("neue Suche")));
    } else {
        $search_exp = trim($search_exp);
        if (SearchResults($search_exp)) {
            printf ("<input type=\"IMAGE\" name=\"addsearch\" src=\"" . Assets::image_path('icons/16/yellow/arr_2down.png') . "\" value=\"" . _("In Adressbuch eintragen") . "\" %s>     ", tooltip(_("In Adressbuch eintragen")));
            echo SearchResults($search_exp);
        } else {
            echo "<font size=\"2\">"._("keine Treffer zum Suchbegriff:")."</font><b>&nbsp; " . htmlReady($search_exp) . "&nbsp; </b>";
        }
        printf ("<a href=\"".URLHelper::getLink()."\"><img src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\"  value=\"" . _("neue Suche") . "\" %s>", tooltip(_("neue Suche")));
    }
} else {
    echo "<font size=\"2\" color=\"#555555\">". _("Person zum Eintrag in das Adressbuch suchen:")."</font>&nbsp; <input type=\"text\" name=\"search_exp\" value=\"\">";
    printf ("<input type=\"IMAGE\" name=\"search\" src=\"" . Assets::image_path('icons/16/blue/search.png') . "\"  border=\"0\" value=\"" . _("Personen suchen") . "\" %s>&nbsp;  ", tooltip(_("Person suchen")));
}
echo "</td></tr>";

if ($_SESSION['sms_msg'])   {
    parse_msg ($_SESSION['sms_msg']);
    $_SESSION['sms_msg'] = '';
    $sess->unregister('sms_msg');
    }
?>
</table>
</form>
<?


echo "<table align=\"center\" class=\"grey\" border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td align=\"middle\" class=\"lightgrey\">";


if (($contact["view"])=="alpha") {
    echo "<table align=\"center\" width=\"70%\"><tr>";
    if (!$filter) {
        $cssSw->switchClass();
    }
    echo "<td width=\"8%\" align=\"center\" valign=\"center\" " . $cssSw->getHover() . " class=\"" . $cssSw->getClass() . "\" "
        . tooltip(($size_of_book == 1) ? _("1 Eintrag") : sprintf(_("%d Einträge"), $size_of_book), false)
        . "><a href=\"" . URLHelper::getLink('', array('filter'=>'all')) . "\">a-z</a>"
        . "&nbsp; <a href=\"" . URLHelper::getLink('contact_export.php', array('groupid'=>'all')) . "\">"
        .  Assets::img('icons/16/blue/vcard.png', array('class' => 'text-top', 'title' => _("Alle Einträge als vCard exportieren")))
        . "</a></td>";
    if (!$filter) {
        $cssSw->switchClass();
    }
    
    for ($i=97;$i<123;$i++) {
        if ($filter==chr($i)) {
            $cssSw->switchClass();
        }
        if ($size_of_book_by_filter[chr($i)]==0) {
            $character = "<font color=\"#999999\">".chr($i)."</font>";
        } elseif($filter==chr($i)) {
            $character = "<font color=\"#FF0000\">".chr($i)."</font>";
        } else {
            $character = chr($i);
        }
        echo "<td width=\"3%\"  align=\"center\" valign=\"center\" ".$cssSw->getHover()." class=\"".$cssSw->getClass()."\""
        . tooltip(($size_of_book_by_filter[chr($i)] == 1) ? _("1 Eintrag") : (($size_of_book_by_filter[chr($i)] > 1 ) ? 
                                sprintf(_("%d Einträge"),$size_of_book_by_filter[chr($i)]) : _("keine Einträge")),false)
        ."><a href=\"".URLHelper::getLink('',array('view'=>$view, 'filter'=>chr($i)))."\" "
        . ">".$character."</a>"
        ."</td>";
        if ($filter==chr($i)) {
            $cssSw->switchClass();
        }
    }
    echo "</tr></table>";
}

if (($contact["view"])=="gruppen") {
    echo "<table align=\"center\" ><tr>";
    if (!$filter) {
        $cssSw->switchClass();
    }
    echo '<td nowrap ' . $cssSw->getHover() . ' class="' . $cssSw->getClass() . '">&nbsp; '
    . '<a href="' . URLHelper::getLink('', array('filter' => 'all', 'view' => 'gruppen')) . '" '
                . tooltip(($size_of_book == 1) ? _('1 Eintrag') : sprintf(_('%d Einträge'), $size_of_book), false) .  '>' . _("Alle Gruppen") . '</a>'
    . '&nbsp; <a href="' . URLHelper::getLink('', array('groupid' => 'all')) . '"><img style="vertical-align:middle;"'
    . Assets::img('icons/16/blue/vcard.png', array('class' => 'text-top', 'title' => _("Alle Einträge als vCard exportieren")))
    . '</a>&nbsp; </td>';
    if (!$filter) {
        $cssSw->switchClass();
    }
    
    $query = "SELECT name, statusgruppe_id FROM statusgruppen WHERE range_id = ? ORDER BY position ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if ($filter == $row['statusgruppe_id']) {
            $cssSw->switchClass();
            $color = "color=\"#FF0000\"";
            $smslink = URLHelper::getLink('sms_send.php',array('sms_source_page'=>'contact.php', 'group_id' => $filter));
            $exportlink = URLHelper::getLink('contact_export.php',array('groupid' => $row['statusgruppe_id']));
            $maillink = "&nbsp; <a href=\"$smslink\">" .  Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' => _("Nachricht an alle Personen dieser Gruppe schicken"))) . "</a>";
            $maillink .= "&nbsp; <a href=\"$exportlink\">" .  Assets::img('icons/16/blue/vcard.png', array('class' => 'text-top', 'title' => _("Diese Gruppe als vCard exportieren"))) . " </a>";
        } else {
            $color = "";
            $maillink ="";
        }
        
        $link = URLHelper::getLink('',array('view'=>$view, 'filter' => $row['statusgruppe_id']));
        echo "<td " . $cssSw->getHover() . " class=\"" . $cssSw->getClass() . "\">&nbsp; "
        . "<a href=\"$link\" " . tooltip(($size_of_book_by_filter[$row['statusgruppe_id']] == 1) ? _("1 Eintrag") : 
                                (($size_of_book_by_filter[$row['statusgruppe_id']] > 1 ) ? sprintf(_("%d Einträge"), $size_of_book_by_filter[$row['statusgruppe_id']]) : 
                                _("keine Einträge")), false) . " ><font size=\"2\" $color>" . htmlready($row['name']) . "</font></a>$maillink" . "&nbsp; </td>";
        if ($filter == $row['statusgruppe_id']) {
            $cssSw->switchClass();
        }
    }
    echo "</tr></table>";
}



// Anzeige Treffer
if ($edit_id) {
    PrintEditContact($edit_id);
} else {
    PrintAllContact($filter);
}





if (!$edit_id) {

    if ($size_of_book>0)
        $hints .= "&nbsp; |&nbsp; <img src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/mail.png\">&nbsp; "._("Nachricht an Kontakt");
    if ($open && $size_of_book>0)
        $hints .= "&nbsp; |&nbsp; <img src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1up.png\">&nbsp; "._("Kontakt zuklappen");
    if ((!$open) && $size_of_book>0)
        $hints .= "&nbsp; |&nbsp; <img src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1down.png\">&nbsp; "._("Kontakt aufklappen");
    if ($open && $size_of_book>0) {
        $hints .= "&nbsp; |&nbsp; <img src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/person.png\">&nbsp; "._("Buddystatus");
        $hints .= "&nbsp; |&nbsp; <img src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/edit.png\">&nbsp; "._("Eigene Rubriken");
        $hints .= "&nbsp; |&nbsp; <img src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/trash.png\">&nbsp; "._("Kontakt löschen");
    }
    if (($open || $contact["view"]=="gruppen") && $size_of_book>0) {
        $hints .= "&nbsp; |&nbsp; <img style=\"vertical-align:middle;\" src= \"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/vcard.png\">&nbsp; "._("als vCard exportieren");
    }
    echo    "<br><font size=\"2\" color=\"#555555\">"._("Bedienung:").$hints;
}
echo "<br></td></tr></table>";

    include ('lib/include/html_end.inc.php');
    page_close();
?>
