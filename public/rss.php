<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
rss.php - Ausgabe der persšnlcihen News als rss-Feed
Copyright (C) 2005  Philipp HŸgelmeyer <phuegelm@uni-osnabrueck.de>

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
$auth->login_if(!$logout && ($auth->auth["uid"] == "nobody"));

require_once('lib/my_rss_feed.inc.php');
require_once 'lib/functions.php';
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/visual.inc.php');
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

if ($view == "rss") {
	
	
	if (!$username) $username = $auth->auth["uname"];
	Navigation::activateItem('/tools/rss');
    PageLayout::setHelpKeyword("Basis.MyStudIPRSS");
    PageLayout::setTitle(_("Einstellungen der RSS-Anzeige anpassen"));
    SkipLinks::addIndex(_("Einstellungen der RSS-Anzeige anpassen"), 'main_content', 100);
	
	include ('lib/include/html_head.inc.php'); // Output of html head
	include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

	
    if ($rss == "create_rss") create_rss();
    if ($rss == "delete_rss") delete_rss($rss_id);
    if ($rss == "update_rss") update_rss();
    if ($rss == "order_rss") order_rss($cat_id,$direction,$username);
    print_rss($username);

} else {

    ob_end_clean();
    ob_start();
    require_once('lib/showNews.inc.php');
    if (get_config('NEWS_RSS_EXPORT_ENABLE')){
        $range = StudipNews::GetRangeFromRssID($_REQUEST['id']);
        if (is_array($range)){
            show_rss_news($range['range_id'], $range['range_type']);
        } else {
            header("Content-type: text/xml; charset=utf-8");
            echo "<?xml version=\"1.0\"?>\n<rss version=\"2.0\">\n</rss>\n";
        }
    }   
    ob_end_flush();
}
?>
