<?php
# Lifter002: TEST
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - not applicable
/*
user_activities.php
Copyright (C) 2006 André Noack <noack@data-quest.de>
Suchi & Berg GmbH <info@data-quest.de>
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA   02111-1307, USA.
*/

require '../lib/bootstrap.php';

unregister_globals();
require_once 'lib/functions.php';
require_once 'lib/datei.inc.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");

include 'lib/seminar_open.php';       // initialise Stud.IP-Session

PageLayout::setTitle(_('Informationen zu einem Nutzer'));
Navigation::activateItem('/admin/config/user');

/**
 * Returns the posts of a certain user in a certain guestbook
 *
 * @param String $user_id   Id of the user in question
 * @param String $range_id  Range id of the guestbook in question
 * @return string List of posts as html, ready to be displayed
 */
function show_posts_guestbook($user_id, $range_id)
{
    $query = "SELECT user_id, mkdate, content, post_id
              FROM guestbook
              WHERE user_id = ? AND range_id = ?
              ORDER BY mkdate DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $range_id));
    $posts = $statement->fetchAll(PDO::FETCH_ASSOC);

    $template = $GLOBALS['template_factory']->open('user_activities/guestbook-posts');
    $template->posts = $posts;
    return $template->render();
}

/**
 * Returns an overview of certain documents
 *
 * @param Array $documents Ids of the documents in question
 * @param mixed $open      Array containing open states of documents
 * @return string Overview of documents as html, ready to be displayed
 */
function show_documents($documents, $open = null)
{
    if (!is_array($documents)) {
        return;
    }
    if (!is_null($open) && !is_array($open)) {
        $open = null;
    }
    if (is_array($open)) {
        reset($open);
        $ank = key($open);
    }

    if (!empty($documents)) {
        $query = "SELECT {$GLOBALS['_fullname_sql']['full']} AS fullname, username, user_id,
                         dokument_id, filename, filesize, downloads, protected, url, description,
                         IF(IFNULL(name, '') = '', filename, name) AS t_name,
                         GREATEST(a.chdate, a.mkdate) AS chdate
                  FROM dokumente AS a
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE dokument_id IN (?)
                  ORDER BY a.chdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($documents));
        $documents = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($documents as $index => $document) {
        $type      = empty($document['url']) ? 0 : 6;
        $is_open   = (is_null($open) || $open[$document['dokument_id']]) ? 'open' : 'close';
        $extension = getFileExtension($document['filename']);

        // Create icon
        $icon   = sprintf('<a href="%s">%s</a>',
                        GetDownloadLink($document['dokument_id'], $document['filename'], $type),
                        GetFileIcon($extension, true));

        // Create open/close link
        $link    = $is_open === 'open'
                 ? URLHelper::getLink('#dok_anker', array('close' => $document['dokument_id']))
                 : URLHelper::getLink('#dok_anker', array('open' => $document['dokument_id']));

        // Create title including filesize and number of downloads
        $size      = $document['filesize'] > 1024 * 1024
                   ? sprintf('%u MB', round($document['filesize'] / 1024 / 1024))
                   : sprintf('%u kB', round($document['filesize'] / 1024));
        $downloads = $document['downloads'] == 1
                   ? '1 ' . _('Download')
                   : $document['downloads'] . ' ' . _('Downloads');
        $title     = sprintf('<a href="%s"%s class="tree">%s</a> (%s / %s)',
                             $link,
                             $ank == $document['dokument_id'] ? ' name="dok_anker"' : '',
                             htmlReady(mila($document['t_name'])),
                             $size, $downloads);

        // Create additional information
        $addon = sprintf('<a href="%s">%s</a> %s',
                         URLHelper::getLink('about.php', array('username' => $document['username'])),
                         $document['fullname'],
                         date('d.m.Y H:i', $document['chdate']));
        if ($document['protected']) {
            $addon =  tooltipicon(_('Diese Datei ist urheberrechtlich geschützt!')) . ' ' . $addon;
        }
        if (!empty($document['url'])) {
            $addon .= ' ' . Assets::img('icons/16/blue/link-extern',
                                        tooltip2(_('Diese Datei wird von einem externen Server geladen!')));
        }

        // Attach created variables to document
        $documents[$index]['addon']     = $addon;
        $documents[$index]['extension'] = $extension;
        $documents[$index]['icon']      = $icon;
        $documents[$index]['is_open']   = $is_open;
        $documents[$index]['link']      = $link;
        $documents[$index]['title']     = $title;
        $documents[$index]['type']      = $type;
    }

    $template = $GLOBALS['template_factory']->open('user_activities/files-details');
    $template->documents = $documents;
    return $template->render();
}

/**
 * Returns a certain user's documents (in a certain course)
 *
 * @param string $user_id    Id of the user in question
 * @param mixed  $seminar_id Optional id of a seminar
 * @return Array List of the user's documents' ids
 */
function get_user_documents($user_id, $seminar_id = null)
{
    $query = "SELECT dokument_id FROM dokumente WHERE user_id = ?";
    $parameters = array($user_id);

    if ($seminar_id !== null) {
        $query .= " AND seminar_id = ?";
        $parameters[] = $seminar_id;
    }

    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

if (!is_array($_SESSION['_user_activities'])){
    $_SESSION['_user_activities']['open'] = array();
    $_SESSION['_user_activities']['details'] = 'files';
}

$queries = array();
$msg = array();

if (Request::quoted('username')){
    $_SESSION['_user_activities']['username'] = Request::quoted('username');
    $_SESSION['_user_activities']['open'] = array();
    $_SESSION['_user_activities']['details'] = 'files';
}
if (Request::get('details')) $_SESSION['_user_activities']['details'] = Request::quoted('details');
if (Request::get('open')) $_SESSION['_user_activities']['open'][Request::get('open')] = time();
if (Request::get('close')) unset($_SESSION['_user_activities']['open'][Request::get('close')]);
$user_id = get_userid($_SESSION['_user_activities']['username']);
arsort($_SESSION['_user_activities']['open'], SORT_NUMERIC);
if (Request::get('download_as_zip')) {
    $download_ids = Request::quoted('download_as_zip') == 'all' ? get_user_documents($user_id) : get_user_documents($user_id, Request::quoted('download_as_zip'));
    if (is_array($download_ids) && count($download_ids)) {
        $zip_file_id = createSelectedZip($download_ids, false);
        $zip_name = prepareFilename($_SESSION['_user_activities']['username'] . '-' . _("Dokumente") . '.zip');
        header('Location: ' . getDownloadLink( $zip_file_id, $zip_name, 4));
        page_close();
        die;
    }
}
if (Request::option('deletepost') && check_ticket(Request::option('ticket'))){
    $post_id = Request::option('deletepost');

    $query = "DELETE FROM guestbook WHERE post_id = ? LIMIT 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($post_id));
    if ($statement->rowCount()){
        PageLayout::postMessage(Messagebox::success(_('Ein Gästebucheintrag wurde gelöscht.')));
    }
}

reset($_SESSION['_user_activities']['open']);
$ank = key($_SESSION['_user_activities']['open']);

// Define structure of displayed information
$queries[] = array(
    'desc'    => _('Eingetragen in Veranstaltungen (dozent / tutor / autor / user)'),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(status = 'dozent'), SUM(status = 'tutor'),
                                          SUM(status = 'autor'), SUM(status = 'user'))
                  FROM seminar_user
                  WHERE user_id = ?
                  GROUP BY user_id",
    'details' => "details=seminar",
);
$queries[] = array(
    'desc'    => _('Eingetragen in geschlossenen Veranstaltungen (dozent / tutor / autor / user)'),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(su.status = 'dozent'), SUM(su.status = 'tutor'),
                                          SUM(su.status = 'autor'), SUM(su.status = 'user'))
                  FROM seminar_user AS su
                  INNER JOIN seminare USING (Seminar_id)
                  WHERE user_id = ? AND (Schreibzugriff > 2 OR Lesezugriff > 2)
                  GROUP BY user_id",
    'details' => "details=seminar_closed",
);
$queries[] = array(
    'desc'    => _("Eingetragen in Wartelisten (chronologisch / los / vorläufig akzeptiert)"),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(status = 'awaiting'), SUM(status = 'claiming'), SUM(status = 'accepted'))
                  FROM admission_seminar_user
                  WHERE user_id = ?
                  GROUP BY user_id",
    'details' => "details=seminar_wait",
);
$queries[] = array(
    'desc'    => _("Eingetragen in Einrichtungen (admin / dozent / tutor / autor)"),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(inst_perms = 'admin'), SUM(inst_perms = 'dozent'),
                                          SUM(inst_perms = 'tutor'), SUM(inst_perms = 'autor'))
                  FROM user_inst
                  WHERE user_id = ?
                  GROUP BY user_id",
);
$queries[] = array(
    'desc'    => _("Anzahl der Gästebucheinträge"),
    'query'   => "SELECT COUNT(*) FROM guestbook WHERE user_id = ? GROUP BY user_id",
    'details' => "details=guestbook",
);
$queries[] = array(
    'desc'  => _("Anzahl der Forenpostings"),
    'query' => "SELECT COUNT(*) FROM px_topics WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Ankündigungen"),
    'query' => "SELECT COUNT(*) FROM news WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Wikiseiten"),
    'query' => "SELECT COUNT(*) FROM wiki WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Umfragen"),
    'query' => "SELECT COUNT(*) FROM vote WHERE author_id = ? GROUP BY author_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Evaluationen"),
    'query' => "SELECT COUNT(*) FROM eval WHERE author_id = ? GROUP BY author_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Literatureinträge"),
    'query' => "SELECT COUNT(*) FROM lit_catalog WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Ressourcenobjekte"),
    'query' => "SELECT COUNT(*) FROM resources_objects WHERE owner_id = ? GROUP BY owner_id",
);
$queries[] = array(
    'desc'    => _("Anzahl der Dateien (hochgeladen / verlinkt)"),
    'query'   => "SELECT CONCAT_WS(' / ', COUNT(*) - COUNT(NULLIF(url,'')), COUNT(NULLIF(url,'')))
                  FROM dokumente
                  WHERE user_id = ?
                  GROUP BY user_id",
    'details' => "details=files",
);
$queries[] = array(
    'desc'    => _("Gesamtgröße der hochgeladenen Dateien (MB)"),
    'query'   => "SELECT FORMAT(SUM(filesize)/1024/1024,2)
                  FROM dokumente
                  WHERE user_id = ? AND (url IS NULL OR url = '')
                  GROUP BY user_id",
    'details' => "details=files",
);

// Evaluate queries
foreach ($queries as $index => $query) {
    $statement = DBManager::get()->prepare($query['query']);
    $statement->execute(array($user_id));
    $queries[$index]['value'] = $statement->fetchColumn() ?: 0;
}

// Create details if neccessary
$details = false;
if ($_SESSION['_user_activities']['details'] == 'files') {
    $files = array();

    // Seminar
    $query = "SELECT s.Seminar_id AS id, seminar_user.status, IF(s.visible = 0,CONCAT(s.Name, ' ', :hidden), s.Name) AS Name,
                     COUNT(dokument_id) AS numdok, sd1.name AS startsem, IF(s.duration_time = -1, :unlimited, sd2.name) AS endsem
            FROM dokumente d
            INNER JOIN seminare s USING(seminar_id)
            LEFT JOIN semester_data sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
            LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
            LEFT JOIN seminar_user ON (d.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = :user_id)
            WHERE d.user_id = :user_id
            GROUP BY s.Seminar_id
            ORDER BY sd1.beginn, numdok DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':hidden', _('(versteckt)'));
    $statement->bindParam(':unlimited', _('(unbegrenzt)'));
    $statement->bindParam(':user_id', $user_id);
    $statement->execute();
    $files['seminars'] = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($files['seminars'] as $index => $file) {
        $is_open = $_SESSION['_user_activities']['open'][$file['id']] ? 'open' : 'close';
        
        $title = sprintf('%s (%s%s)', $file['Name'], $file['startsem'],
                                      $file['startsem'] != $file['endsem'] ? ' - ' . $file['endsem'] : '');
        $title = sprintf('<a href="%s"%s class="tree">%s</a>',
                         URLHelper::getLink('?' . ($is_open == 'open' ? 'close' : 'open') . '=' . $file['id'] . '#dok_anker'),
                         $ank == $file['id'] ? ' name="dok_anker"' : '',
                         htmlReady($title));

        $files['seminars'][$index]['title']   = $title;
        $files['seminars'][$index]['is_open'] = $is_open;
        $files['seminars'][$index]['addon']   = $file['numdok'] . '&nbsp;' . _('Dokumente');
    }

    // Institute
    $query = "SELECT i.Institut_id AS id, inst_perms AS status, i.Name, COUNT(dokument_id) AS numdok
              FROM dokumente d
              INNER JOIN Institute i ON (i.Institut_id = d.seminar_id)
              LEFT JOIN user_inst  ON (d.seminar_id = user_inst.institut_id AND user_inst.user_id = :user_id)
              WHERE d.user_id = :user_id
              GROUP BY i.Institut_id
              ORDER BY numdok DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':user_id', $user_id);
    $statement->execute();
    $files['institutes'] = $statement->fetchAll(PDO::FETCH_ASSOC);
    

    foreach ($files['institutes'] as $index => $file) {
        $is_open = $_SESSION['_user_activities']['open'][$file['id']] ? 'open' : 'close';

        $title = sprintf('<a href="%s"%s class="tree">%s</a>',
                         URLHelper::getLink('?' . ($is_open == 'open' ? 'close' : 'open') . '=' . $file['id'] . '#dok_anker'),
                         $ank == $file['id'] ? ' name="dok_anker"' : '',
                         htmlReady($file['Name']));

        $files['institutes'][$index]['title']   = $title;
        $files['institutes'][$index]['is_open'] = $is_open;
        $files['institutes'][$index]['addon']   = $file['numdok'] . '&nbsp;' . _('Dokumente');
    }

    $template = $GLOBALS['template_factory']->open('user_activities/files');
    $template->files   = $files;
    $template->open    = $_SESSION['_user_activities']['open'];
    $template->user_id = $user_id;
    $details = $template->render();
} elseif ($_SESSION['_user_activities']['details'] == 'guestbook') {
    $query = "SELECT range_id, COUNT(post_id) AS count, MAX(mkdate) AS newest
              FROM guestbook
              WHERE user_id = ?
              GROUP BY range_id
              ORDER BY mkdate DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $posts = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($posts as $index => $post) {
        $other_user = User::find($post['range_id']);

        $is_open = $_SESSION['_user_activities']['open'][$post['range_id']] ? 'open' : 'close';        
        $title = sprintf('<a href="%s"%s class="tree">%s</a>',
                         URLHelper::getLink('?' . ($is_open == 'open' ? 'close' : 'open') . '=' . $post['range_id'] . '#guest_anker'),
                         $ank == $post['range_id'] ? ' name="guest_anker"' : '',
                         $other_user->getFullName());
        
        $posts[$index]['user']    = $other_user;
        $posts[$index]['title']   = $title;
        $posts[$index]['is_open'] = $is_open;
        $posts[$index]['addon']   = sprintf('(%s %d Letzter: %s)',
                                            _('Anzahl:'),
                                            $post['count'],
                                            date('d.m.Y H:i:s', $post['newest']));
    }
    
    $template = $GLOBALS['template_factory']->open('user_activities/guestbook');
    $template->posts   = $posts;
    $template->user_id = $user_id;
    $details = $template->render();
} elseif (in_array($_SESSION['_user_activities']['details'], words('seminar seminar_closed seminar_wait'))) {
    $table = $status = $desc = $where = '';

    switch ($_SESSION['_user_activities']['details']){
        case 'seminar':
            $table  = 'seminar_user';
            $status = 'seminar_user.status';
            $desc   = _('Übersicht Veranstaltungen');
            break;
        case 'seminar_closed':
            $table  = 'seminar_user';
            $status = 'seminar_user.status';
            $where  = " AND (Schreibzugriff > 2 OR Lesezugriff > 2) ";
            $desc   = _('Übersicht geschlossene Veranstaltungen');
            break;
        case 'seminar_wait':
            $table  = 'admission_seminar_user';
            $status = "IF(admission_seminar_user.status = 'awaiting', CONCAT('awaiting at ', admission_seminar_user.position), admission_seminar_user.status)";
            $desc   = _('Übersicht Wartelisten von Veranstaltungen');
        break;
    }

    $query = "SELECT s.Seminar_id, {$status} AS status, IF(s.visible = 0,CONCAT(s.Name, ' ', ?), s.Name) AS Name,
                     sd1.name AS startsem, IF(s.duration_time = -1, ?, sd2.name) AS endsem
            FROM {$table}
            LEFT JOIN seminare AS s USING(Seminar_id)
            LEFT JOIN semester_data AS sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
            LEFT JOIN semester_data AS sd2 ON (start_time + duration_time BETWEEN sd2.beginn AND sd2.ende)
            WHERE user_id = ? {$where}
            GROUP BY s.Seminar_id ORDER BY name DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        _('(versteckt)'), _('unbegrenzt'),
        $user_id,
    ));
    $courses = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($courses as $index => $course) {
        $title = sprintf('%s (%s%s)', $course['Name'], $course['startsem'],
                                      $course['startsem'] != $course['endsem'] ? ' - ' . $course['endsem'] : '');
        $title = sprintf('<a href="%s" class="tree">%s</a>',
                         URLHelper::getLink('seminar_main.php?redirect_to=teilnehmer.php#' . $_SESSION['_user_activities']['username'], 
                                            array('auswahl' => $course['Seminar_id'])),
                        htmlReady($title));

        $courses[$index]['title'] = $title;
        $courses[$index]['addon'] = sprintf('<b>%s: %s</b>', _('Status'), $course['status']);
    }

    $template = $GLOBALS['template_factory']->open('user_activities/courses');
    $template->courses     = $courses;
    $template->description = $desc;
    $details = $template->render();
}

// Create, populate and display template
$template = $GLOBALS['template_factory']->open('user_activities/index');
$template->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));

$template->user    = User::find($user_id);
$template->queries = $queries;
$template->details = $details;
echo $template->render();

page_close();
