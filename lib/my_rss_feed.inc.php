<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* my_rss_feed.inc.php
*
* RSSFeed configuration
*
*
* @author               Jan Kulmann <jankul@tzi.de>
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// RSSFeed.class.php
// Copyright (C) 2005 Jan Kulmann <jankul@tzi.de>
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

require_once "lib/classes/RSSFeed.class.php";

function print_rss($username) {

    global $view,$PHP_SELF,$auth;

    $cssSw=new cssClassSwitcher;

    $cssSw->switchClass();

    $query = "SELECT feed_id, name, fetch_title, url, hidden
              FROM auth_user_md5
              JOIN rss_feeds USING (user_id)
              WHERE username = ?
              ORDER BY priority";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($username));
    $feeds = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<tr><td id=\"main_content\" align=\"left\" valign=\"top\" class=\"blank\"><p class=\"info\"><br>";
    echo _("Hier können Sie beliebige eigene RSS-Feeds einbinden. Diese RSS-Feeds erscheinen auf Ihrer pers&ouml;nlichen Startseite. Mit den Pfeilsymbolen k&ouml;nnen Sie die Reihenfolge, in der die RSS-Feeds angezeigt werden, ver&auml;ndern.");
    echo "<br>\n";
    echo _("<b>Achtung:</b> Je mehr RSS-Feeds Sie definieren, desto l&auml;nger ist die Ladezeit der Startseite f&uuml;r Sie!");
    echo "<br>\n";

    echo "\n<br></p></td></tr>\n<tr><td class=blank><table width=100% class=blank border=0 cellpadding=0 cellspacing=0>";
    echo "<form action=\"$PHP_SELF?rss=update_rss&username=$username&view=$view&show_rss_bsp=$show_rss_bsp\" method=\"POST\" name=\"edit_rss\">";
    echo CSRFProtection::tokenTag();
    if (empty($feeds)) {
        echo "<tr><td class=\"".$cssSw->getClass()."\"><b><p class=\"info\">" . _("Es existieren zur Zeit keine eigenen RSS-Feeds.") . "</p></b></td></tr>\n";
    }
    echo "<tr><td class=\"".$cssSw->getClass()."\"><p class=\"info\">" . _("RSS-Feed") . "&nbsp; " . LinkButton::create(_("Neuanlegen"), 
                URLHelper::getURL('', array('rss' => 'create_rss', 'view' => $view, 'username' => $username, 'show_rss_bsp' => $show_rss_bsp))) . "</a></p></td></tr>";

    foreach ($feeds as $index => $feed) {
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass()."\">";
        if ($index) {
            echo "<br>";
        }
        echo "<input type=\"hidden\" name=\"rss_id[]\" value=\"".$feed['feed_id']."\">\n";
        echo "<div style=\"padding: 10px; margin: 0px;\"><label>"._("Name:")."<BR><input type=\"text\" name=\"rss_name[]\" id=\"rss_name_$index\" style=\"width: 50%\" value='".htmlReady($feed['name'])."' size=40></label>";
        echo "&nbsp; &nbsp; &nbsp; <label><input type=checkbox name=\"rss_fetch_title[$index]\" value=\"1\"";
        if ($feed['fetch_title']=='1') echo " checked";
        echo ">" . _("Name des Feeds holen") . "</label>&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;";
        if ($index){
            echo "\n<a href=\"$PHP_SELF?rss=order_rss&direction=up&username=$username&view=$view&cat_id=" . $feed['feed_id']
            . "&show_rss_bsp=$show_rss_bsp\">" . Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-top', 'title' =>_('RSS-Feed nach oben verschieben')))
            . "</a>";
        }
        if ($index != count($feeds) - 1) {
            echo "\n<a href=\"$PHP_SELF?rss=order_rss&direction=down&username=$username&view=$view&cat_id=" . $feed['feed_id']
            . "&show_rss_bsp=$show_rss_bsp\">" .  Assets::img('icons/16/yellow/arr_2down.png', array('class' => 'text-top', 'title' =>_('RSS-Feed nach unten verschieben')))
            . "</a>";
          }
        echo "<br>&nbsp;</div></td></tr>";
        echo "<tr><td class=\"".$cssSw->getClass()."\"><div style=\"padding: 10px; margin: 0px;\"><label>"._("URL:")."<BR><input type='text' name='rss_url[]' style=\"width: 50%\" value='".htmlReady($feed['url'])."' size=40></label>";
        echo "&nbsp; &nbsp; &nbsp; <label><input type=checkbox name='rss_secret[$index]' value='1'";
        if ($feed['hidden']=='1') echo " checked";
        echo ">" . _("unsichtbar") . "</label>&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<br><br>" . Button::create(_("Übernehmen"), array('title' => _("verändern")));
        echo LinkButton::create(_("Löschen"), URLHelper::getURL('', array('rss' => 'delete_rss', 'rss_id' => $feed['feed_id'], 'view' => $view, 'username' => $username, 'show_rss_bsp' => $show_rss_bsp))) . "<br>&nbsp; </div></td></tr>";
    }
    echo "</form></td></tr></table></td></tr>";
}

function create_rss() {
    global $username;

    $user    = User::findByUsername($username);
    $feed_id = md5(uniqid('blablubburegds4', true));

    $query = "UPDATE rss_feeds SET priority = priority + 1 WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));

    $query = "INSERT INTO rss_feeds (feed_id, name, url, user_id, priority, fetch_title, hidden, mkdate, chdate)
              VALUES (?, ?, ?, ?, 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($feed_id, _('neuer Feed'), _('URL'), $user->user_id));

    if ($statement->rowCount() == 0) {
        parse_msg ("info§" . _("Anlegen fehlgeschlagen"));
        die;
    }
}

function delete_rss($rss_id) {
    global $username;

    $query = "SELECT 1
              FROM rss_feeds
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE username = ? AND feed_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($username, $rss_id));
    $check = $statement->fetchColumn();

    if (!$check) { //hier wollte jemand schummeln
        parse_msg ("info§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion."));
        die;
    }

    $query = "DELETE FROM rss_feeds WHERE feed_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($rss_id));

    if ($statement->rowCount() == 1) {
        parse_msg ("msg§" . _("RSS-Feed gel&ouml;scht!"));
    }
}

function update_rss() {
    global $rss_id,$rss_name,$rss_url, $rss_secret, $rss_fetch_title;
    check_rss();
    $max = sizeof($rss_id);

    $query = "UPDATE rss_feeds
              SET name = ?, url = ?, hidden = ?, fetch_title = ?, chdate = UNIX_TIMESTAMP()
              WHERE feed_id = ?";
    $update = DBManager::get()->prepare($query);

    FOR ($i=0;$i<$max;$i++) {
        if (trim($rss_name[$i])!="" && trim($rss_url[$i])) {
            $name = $rss_name[$i];
            $url = $rss_url[$i];
            $secret=$rss_secret[$i];
            $id = $rss_id[$i];
            $fetch_title = $rss_fetch_title[$i];
            
            $update->execute(array($name, $url, $secret, $fetch_title, $id));
        }
    }
    $msg[] = array('msg', _("RSS-Feeds ge&auml;ndert!"));
    parse_msg_array ($msg,'blank',2,0,1);
}

function check_rss() {
    global $rss_id,$rss_name,$rss_url, $rss_secret, $rss_fetch_title;
    define('MAGPIE_CACHE_AGE',1);
    $max = sizeof($rss_id);
    $msg = array();
    FOR ($i=0;$i<$max;$i++) {
        if (trim($rss_url[$i])) {
            $feed = new RSSFeed($rss_url[$i]);
            if ($feed->ausgabe->feed_type){
                if($rss_fetch_title[$i] && $feed->ausgabe->channel['title']) $rss_name[$i] = addslashes($feed->ausgabe->channel['title']);
                $msg[] = array('msg', sprintf(_("Feed: <b>%s</b> (Typ: %s) erreicht."), htmlReady($rss_url[$i]), htmlReady($feed->ausgabe->feed_type)));
            } else {
                $rss_secret[$i] = 1;
                $msg[] = array('error', sprintf(_("Feed: <b>%s</b> nicht erreicht, oder Typ nicht erkannt."), htmlReady($rss_url[$i])));
            }
        }
    }
    parse_msg_array ($msg,'blank',2,0,1);
}

function order_rss($cat_id,$direction,$username){
    $user = User::findByUsername($username);
    
    $query = "SELECT feed_id FROM rss_feeds WHERE user_id = ? ORDER BY priority";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->user_id));
    $items_to_order = $statement->fetchAll(PDO::FETCH_COLUMN);

    for ($i = 0; $i < count($items_to_order); ++$i) {
        if ($cat_id == $items_to_order[$i])
            break;
    }
    if ($direction == "up" && isset($items_to_order[$i-1])) {
        $items_to_order[$i] = $items_to_order[$i-1];
        $items_to_order[$i-1] = $cat_id;
    } elseif (isset($items_to_order[$i+1])) {
        $items_to_order[$i] = $items_to_order[$i+1];
        $items_to_order[$i+1] = $cat_id;
    }

    $query = "UPDATE rss_feeds SET priority = ? WHERE feed_id = ?";
    $update = DBManager::get()->prepare($query);

    for ($i = 0; $i < count($items_to_order); ++$i) {
        $update->execute(array($i, $items_to_order[$i]));
    }
    $msg[] = array('msg', _("RSS-Feeds wurden neu geordnet"));
    parse_msg_array ($msg,'blank',2,0,1);
}

?>
