<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
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

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

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

?>
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td class="blank" width="99%"><br>
<?
    print("<blockquote>");
    print( _("Die folgenden Veranstaltungen k&ouml;nnen Sie betreten, ohne sich im System registriert zu haben."));
    print("<br></blockquote>");
    print("<blockquote>");
    printf( _("In den %s blau markierten Veranstaltungen d&uuml;rfen Sie nur lesen und Dokumente herunterladen."), "<span class=\"gruppe6\">&nbsp;&nbsp;</span>");
    print("<br>");
    printf( _("In den %s orange markierten Veranstaltungen k&ouml;nnen Sie sich zus&auml;tzlich mit eigenen Beitr&auml;gen im Forum beteiligen."), "<span class=\"gruppe2\">&nbsp;&nbsp;</span>");
    print("</blockquote>");
    print("<blockquote>");
    print( _("In der rechten Spalte k&ouml;nnen Sie sehen, was in den einzelnen Veranstaltungen an Inhalten vorhanden ist."));
    print("</blockquote>");

    if (empty($my_sem)) {
        echo MessageBox::info('Es gibt keine Veranstaltungen, die einen freien Zugriff erlauben!');
    }
?>
    </td>
    <td class="blank"  width="1%" align="right" valign="top"><?=Assets::img('infobox/board1.jpg') ?></td>
</tr>

<? if (!empty($my_sem)): ?>
    <tr><td colspan="2">
    <table class="default">
    <tr>
        <th></th>
        <th><a href="<?= URLHelper::getLink('?sortby=Name') ?>"><?= _("Name") ?></a></th>
        <th><a href="<?= URLHelper::getLink('?sortby=status') ?>"><?=_ ("Veranstaltungstyp") ?></a></th>
        <th><a href="<?= URLHelper::getLink('?sortby=Institut') ?>"><?= _("Einrichtung") ?></a></th>
        <th><? echo _("Inhalt") ?></th>
    </tr>
    <? foreach ($my_sem as $semid => $values): ?>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td class="<?= $values["Schreibzugriff"] ? 'gruppe6' : 'gruppe2' ?>">&nbsp;</td>
            <td><a href="<?= URLHelper::getLink('seminar_main.php?auswahl='.$semid) ?>"><?= htmlReady($values["name"]) ?></a></td>
            <td><?= htmlReady($SEM_TYPE[$values["status"]]["name"]) ?></td>
            <td><a href="<?= URLHelper::getLink('institut_main.php?auswahl='.$values["id"]) ?>"><?= htmlReady($values["Institut"]) ?></a></td>
            <td style="white-space: nowrap;"><? print_seminar_content($semid, $values) ?></td>
        </tr>
    <? endforeach ?>
    </table>
    </td></tr>
<? endif ?>

</table>
<?php
include ('lib/include/html_end.inc.php');
  // Save data back to database.
  page_close()
?>
