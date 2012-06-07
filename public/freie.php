<?php
# Lifter002: TEST
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: DONE - not applicable
/**
* freie.php
*
* Show all courses readable by everyone
*
*
* @author       Stefan Suchi <suchi@data-quest.de>, Ralf Stockmann <rstockm@gwdg.de>
* @access       public
* @module       freie.php
* @modulegroup  views
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// freie.php
// Show all courses readable by everyone
// Copyright (C) 2000 Stefan Suchi <suchi@data-quest.de>, Ralf Stockmann <rstockm@gwdg.de>
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


require '../lib/bootstrap.php';
unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));


function get_my_sem_values(&$my_sem) {
    if (empty($my_sem)) {
        return;
    }

    $my_semids = array_keys($my_sem);
// Postings
    $query = "SELECT Seminar_id, COUNT(*) AS count
              FROM px_topics
              WHERE Seminar_id IN (?)
              GROUP BY Seminar_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($my_semids));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $nav = new Navigation('forum', 'forum.php?view=reset');
        $nav->setImage('icons/16/grey/forum.png', array('title' => sprintf(_('%s Postings'), $row['count'])));
        $my_sem[$row['Seminar_id']]['forum'] = $nav;
    }
//dokumente
    $query = "SELECT seminar_id, COUNT(*) AS count
              FROM dokumente
              WHERE seminar_id IN (?)
              GROUP BY seminar_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($my_semids));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $nav = new Navigation('files', 'folder.php?cmd=tree');
        $nav->setImage('icons/16/grey/files.png', array('title' => sprintf(_('%s Dokumente'), $row['count'])));
        $my_sem[$row['seminar_id']]['files'] = $nav;
    }
//Ankündigungen
    $query = "SELECT range_id, COUNT(*) AS count
              FROM news_range
              LEFT JOIN news USING (news_id)
              WHERE range_id IN (?)
              GROUP BY range_id
              HAVING count > 0";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($my_semids));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $nav = new Navigation('news', '');
        $nav->setImage('icons/16/grey/news.png', array('title' => sprintf(_('%s Ankündigungen'), $row['count'])));
        $my_sem[$row['range_id']]['news'] = $nav;
    }
// Freie Informationsseite
    $query = "SELECT range_id, COUNT(*) AS count
              FROM scm
              WHERE range_id IN (?)
              GROUP BY range_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($my_semids));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $nav = new Navigation('scm', 'scm.php');
        $nav->setImage('icons/16/grey/infopage.png', array('title' => sprintf(_('%s Einträge'), $row['count'])));
        $my_sem[$row['range_id']]['scm'] = $nav;
    }
// Literatur?
    $query = "SELECT range_id, COUNT(list_id) AS count
              FROM lit_list
              WHERE range_id IN (?) AND visibility = 1
              GROUP BY range_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($my_semids));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $nav = new Navigation('literature', 'literatur.php');
        $nav->setImage('icons/16/grey/literature.png', array('title' => sprintf(_('%s Literaturlisten'), $row['count'])));
        $my_sem[$row['range_id']]['literature'] = $nav;
    }
//termine
    $query = "SELECT range_id, COUNT(*) AS count
              FROM termine
              WHERE range_id IN (?)
              GROUP BY range_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($my_semids));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $nav = new Navigation('schedule', 'dates.php');
        $nav->setImage('icons/16/grey/schedule.png', array('title' => sprintf(_('%s Termine'), $row['count'])));
        $my_sem[$row['range_id']]['schedule'] = $nav;
    }
// Wiki
    if (get_config('WIKI_ENABLE')) {
        $query = "SELECT range_id, COUNT(DISTINCT keyword) AS count
                  FROM wiki
                  WHERE range_id IN (?)
                  GROUP BY range_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($my_semids));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $nav = new Navigation('wiki', 'wiki.php');
            $nav->setImage('icons/16/grey/wiki.png', array('title' => sprintf(_('%s WikiSeiten'), $row['count'])));
            $my_sem[$row['range_id']]['wiki'] = $nav;
        }
    }
// Votes
    if (get_config('VOTE_ENABLE')) {
        $query = "SELECT range_id, COUNT(vote_id) AS count
                  FROM vote
                  WHERE state IN ('active','stopvis') AND range_id IN (?)
                  GROUP BY range_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($my_semids));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $nav = new Navigation('vote', '#vote');
            $nav->setImage('icons/16/grey/vote.png', array('title' => sprintf(_('%s Umfrage(n)'), $row['count'])));
            $my_sem[$row['range_id']]['vote'] = $nav;
        }
    }

}  // Ende function get_my_sem_values


function print_seminar_content($semid,$my_sem_values) {

    foreach (words('forum files news scm schedule wiki vote literature') as $key) {
        $navigation[$key] = $my_sem_values[$key];
    }

    foreach (PluginEngine::getPlugins('StandardPlugin', $semid) as $plugin) {
        $navigation[] = $plugin->getIconNavigation($semid, time());
    }

    foreach ($navigation as $key => $nav) {
        if (isset($nav) && $nav->isVisible(true)) {
            // need to use strtr() here to deal with seminar_main craziness
            $url = 'seminar_main.php?auswahl='.$semid.'&redirect_to='.strtr($nav->getURL(), '?', '&');
            printf(' <a href="%s"><img ', htmlspecialchars($url));
            foreach ($nav->getImage() as $key => $value) {
                printf('%s="%s" ', $key, htmlReady($value));
            }
            echo '></a>';
        } else if (is_string($key)) {
            echo ' '.Assets::img('blank.gif', array('width' => 16, 'height' => 16));
        }
    }
    echo "&nbsp;";

} // Ende function print_seminar_content

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

PageLayout::setHelpKeyword("Basis.SymboleFreieVeranstaltungen");
PageLayout::setTitle(_("Öffentliche Veranstaltungen"));

if (get_config('ENABLE_FREE_ACCESS')) {
    Navigation::activateItem('/browse');
}


require_once('config.inc.php');
require_once('lib/msg.inc.php');
require_once('lib/visual.inc.php');

// we are definitely not in an lexture or institute
closeObject();

$my_sem = array();
if(get_config('ENABLE_FREE_ACCESS')){
    $query = "SELECT Seminar_id, seminare.Name AS name, seminare.status, seminare.Schreibzugriff,
                     Institute.Name AS Institut, Institut_id AS id
              FROM seminare
              LEFT JOIN Institute USING (Institut_id)
              WHERE Lesezugriff = '0' AND seminare.visible = '1'
              ORDER BY :order";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':order', Request::option('sortby', 'Name'), StudipPDO::PARAM_COLUMN);
    $statement->execute();
    
    $my_sem = $statement->fetchGrouped(PDO::FETCH_ASSOC);
    
    if (!empty($my_sem)) {
        get_my_sem_values($my_sem);
    }
}

$template = $GLOBALS['template_factory']->open('freie');
$template->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
$template->seminars = $my_sem;
echo $template->render();

// Save data back to database.
page_close();
