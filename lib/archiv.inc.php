<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/*

archiv.inc.php - Funktionen zur Archivierung in Stud.IP
Copyright (C) 2000 Cornelis Kater <ckater@gwdg.de>, Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

require_once 'config.inc.php';
require_once 'lib/dates.inc.php';
require_once 'lib/datei.inc.php';
require_once 'lib/wiki.inc.php'; // getAllWikiPages for dump
require_once 'lib/visual.inc.php';
require_once 'lib/functions.php';
require_once 'lib/language.inc.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/classes/Modules.class.php';
require_once 'lib/classes/StudipLitList.class.php';
require_once 'lib/classes/SemesterData.class.php';
require_once 'lib/classes/StudipScmEntry.class.php';
require_once 'lib/classes/StudipDocumentTree.class.php';
require_once 'lib/user_visible.inc.php';
require_once 'forum.inc.php';

// Liefert den dump des Seminars
function dump_sem($sem_id, $print_view = false)
{
    global $TERMIN_TYP, $SEM_TYPE, $SEM_CLASS, $_fullname_sql, $AUTO_INSERT_SEM;

    $Modules = new Modules;
    $Modules = $Modules->getLocalModules($sem_id);

    $query = "SELECT status, Name, Untertitel, art, VeranstaltungsNummer,
                     ects, Beschreibung, teilnehmer, vorrausetzungen,
                     lernorga, leistungsnachweis, Sonstiges, Institut_id,
                     admission_turnout
              FROM seminare
              WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $seminar = $statement->fetch(PDO::FETCH_ASSOC);

    $sem_type = $seminar['status'];

    $sem = Seminar::getInstance($sem_id);

    $dump  = '<table width="100%" border="1" cellpadding="2" cellspacing="0">';
    $dump .= '<tr><td colspan="2" align="left" class="table_header_bold">';
    $dump .= '<h1 class="table_header_bold">&nbsp;' . htmlReady($seminar['Name'], 1, 1) . '</h1>';
    $dump .= '</td></tr>' . "\n";

    // Helper function that dumps into a single table row
    $dumpRow = function ($title, $content, $escape = false) use (&$dump) {
        $content = trim($content);
        if ($content) {
            if ($escape) {
                $content = htmlReady($content, 1, 1);
            }
            $dump .= sprintf('<tr><td width="15%%"><b>%s</b></td><td>%s</td></tr>' . "\n",
                             htmlReady($title), $content);
        }
    };

    //Grunddaten des Seminars, wie in den seminar_main
    $dumpRow(_('Untertitel:'), $seminar['Untertitel'], true);

    if ($data = $sem->getDatesExport()) {
        $dumpRow(_('Zeit:'), nl2br($data));
    }

    $dumpRow(_('Semester:'), get_semester($sem_id));
    $dumpRow(_('Erster Termin:'), veranstaltung_beginn($sem_id, 'export'));

    if ($temp = vorbesprechung($sem_id, 'export')) {
        $dumpRow(_('Vorbesprechung:'), htmlReady($temp));
    }

    if ($data = $sem->getDatesTemplate('dates/seminar_export_location')) {
        $dumpRow(_('Ort:'), nl2br($data));
    }

    //wer macht den Dozenten?
    $query = "SELECT {$_fullname_sql['full']} AS fullname
              FROM seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE Seminar_id = ? AND status = 'dozent'
              ORDER BY position, Nachname";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $teachers = $statement->fetchAll(PDO::FETCH_COLUMN);
    if (count($teachers) > 0) {
        $title = get_title_for_status('dozent', count($teachers), $sem_type);
        $dumpRow($title, implode('<br>', array_map('htmlReady', $teachers)));
    }

    //und wer ist Tutor?
    $query = "SELECT {$_fullname_sql['full']} AS fullname
              FROM seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE Seminar_id = ? AND status = 'tutor'
              ORDER BY position, Nachname";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $tutors = $statement->fetchAll(PDO::FETCH_COLUMN);
    if (count($tutors) > 0) {
        $title = get_title_for_status('tutor', count($tutors), $sem_type);
        $dumpRow($title, implode('<br>', array_map('htmlReady', $tutors)));
    } 

    if ($seminar['status'] != '') {
        $content  = $SEM_TYPE[$seminar['status']]['name'];
        $content .= ' ' . _('in der Kategorie') . ' ';
        $content .= '<b>' . $SEM_CLASS[$SEM_TYPE[$seminar['status']]['class']]['name'] . '</b>';
        $dumpRow(_('Typ der Veranstaltung'), $content);
    }

    $dumpRow(_('Art der Veranstaltung:'), $seminar['art'], true);
    $dumpRow(_('VeranstaltungsNummer:'), htmlReady($seminar['VeranstaltungsNummer']));
    $dumpRow(_('ECTS-Punkte:'), htmlReady($seminar['ects']));
    $dumpRow(_('Beschreibung:'), $seminar['Beschreibung'], true);
    $dumpRow(_('TeilnehmerInnen:'), $seminar['teilnehmer'], true);
    $dumpRow(_('Voraussetzungen:'), $seminar['vorrausetzungen'], true);
    $dumpRow(_('Lernorganisation:'), $seminar['lernorga'], true);
    $dumpRow(_('Leistungsnachweis:'), $seminar['leistungsnachweis'], true);

    //add the free adminstrable datafields
    $localEntries = DataFieldEntry::getDataFieldEntries($sem_id);
    foreach ($localEntries as $entry) {
        $dumpRow($entry->getName, $entry->getDisplayValue());
    }

    $dumpRow(_('Sonstiges:'), $seminar['Sonstiges'], true);

    // Fakultaeten...
    $query = "SELECT DISTINCT c.Name
              FROM seminar_inst AS a
              LEFT JOIN Institute AS b USING (Institut_id)
              LEFT JOIN Institute AS c ON (c.Institut_id = b.fakultaets_id)
              WHERE a.seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $faculties = $statement->fetchAll(PDO::FETCH_COLUMN);
    if (count($faculties) > 0) {
        $dumpRow(_('Fakult&auml;t(en):'), implode('<br>', array_map('htmlReady', $faculties)));
    }

    //Studienbereiche
    if ($SEM_CLASS[$SEM_TYPE[$seminar['status']]['class']]['bereiche']) {
        $sem_path = get_sem_tree_path($sem_id) ?: array();
        $dumpRow(_('Studienbereich(e):'), implode('<br>', array_map('htmlReady', $sem_path)));
    }

    $iid = $seminar['Institut_id'];
    $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($iid));
    $inst_name = $statement->fetchColumn();
    $dumpRow(_('Heimat-Einrichtung:'), $inst_name, true);

    $query = "SELECT Name
              FROM seminar_inst
              LEFT JOIN Institute USING (institut_id)
              WHERE seminar_id = ? AND Institute.institut_id != ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id, $iid));
    $other_institutes = $statement->fetchAll(PDO::FETCH_COLUMN);
    if (count($other_institutes) > 0) {
        $title = (count($other_institutes) == 1)
               ? _('Beteiligte Einrichtung:')
               : _('Beteiligte Einrichtungen:');
        $dumpRow($title, implode(', ', array_map('htmlReady', $other_institutes)));
    }

    //Teilnehmeranzahl
    $dumpRow(_('max. TeilnehmerInnenanzahl:'), $seminar['admission_turnout']);

    //Statistikfunktionen
    $query = "SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $count = $statement->fetchColumn();
    $dumpRow(_('Anzahl der angemeldeten TeilnehmerInnen:'), $count);

    $query = "SELECT COUNT(*) FROM px_topics WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $count = $statement->fetchColumn();
    $dumpRow(_('Forenbeiträge:'), $count);

    if ($Modules['documents']) {
        //do not show hidden documents
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $sem_id)) {
            $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $sem_id,'entity_type' => 'sem'));
            $unreadable_folders = $folder_tree->getUnReadableFolders($GLOBALS['user']->id);
        } else {
            $unreadable_folders = array();
        }
        $query = "SELECT COUNT(*) FROM dokumente WHERE seminar_id = ?";
        $parameters = array($sem_id);

        if (count($unreadable_folders) > 0) {
            $query .= " AND range_id NOT IN(?)";
            $parameters[] = $unreadable_folders;
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $docs = $statement->fetchColumn();
    }
    $dumpRow(_('Dokumente:'), $docs ?: 0);

    $dump.= '</table>' . "\n";

    // Ablaufplan
    if ($Modules['schedule']) {
        $dump.= dumpRegularDatesSchedule($sem_id);
        $dump.= dumpExtraDatesSchedule($sem_id);
    }

    //SCM
    if ($Modules['scm']) {
        foreach(StudipScmEntry::GetSCMEntriesForRange($sem_id) as $scm) {
            if (!empty($scm['content'])) {
                $dump .= '<br>';
                $dump .= '<table width="100%" border="1" cellpadding="2" cellspacing="0">';
                $dump .= ' <tr><td align="left" class="table_header_bold">';
                $dump .= '<h2 class="table_header_bold">&nbsp;' . htmlReady($scm['tab_name']) . '</h2>';
                $dump .= '</td></tr>' . "\n";
                $dump .= '<tr><td align="left" width="100%"><br>'. formatReady($scm['content'], 1, 1) .'<br></td></tr>' . "\n";
                $dump .= '</table>' . "\n";
            }
        }
    }

    if ($Modules['literature']) {
        $lit = StudipLitList::GetFormattedListsByRange($sem_id, false, false);
        if ($lit) {
            $dump .= '<br>';
            $dump .= '<table width="100%" border="1" cellpadding="2" cellspacing="0">';
            $dump .= '<tr><td align="left" class="table_header_bold">';
            $dump .= '<h2 class="table_header_bold">&nbsp;' . _('Literaturlisten') . '</h2>';
            $dump .= '</td></tr>' . "\n";
            $dump .= '<tr><td align="left" width="100%"><br>'. $lit .'<br></td></tr>' . "\n";
            $dump .= '</table>' . "\n";
        }
    }

    // Dateien anzeigen
    if ($Modules['documents']) {
        //do not show hidden documents
        $unreadable_folders = array();
        if ($print_view) {
            if ($Modules['documents_folder_permissions'] || StudipDocumentTree::ExistsGroupFolders($sem_id)) {
                if (!$GLOBALS['perm']->have_studip_perm('tutor', $sem_id)) {
                    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $sem_id,'entity_type' => 'sem'));
                    $unreadable_folders = $folder_tree->getUnReadableFolders($GLOBALS['user']->id);
                }
            }
        }

        $link_text = _('Hinweis: Diese Datei wurde nicht archiviert, da sie lediglich verlinkt wurde.');
        $query = "SELECT name, filename, mkdate, filesize, Nachname AS nachname,
                         IF(url != '', CONCAT('{$link_text}', ' / ', description), description) AS description 
                  FROM dokumente
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE seminar_id = ?";
        $parameters = array($sem_id);

        if (count($unreadable_folders) > 0) {
            $query .= " AND range_id NOT IN (?)";
            $parameters[] = $unreadable_folders;
        }
        
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $dbresult = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (count($dbresult) > 0) {
            $dump .= '<br>';
            $dump .= '<table width="100%" border="1" cellpadding="2" cellspacing="0">';
            $dump .= '<tr><td align="left" colspan="3" class="table_header_bold">';
            $dump .= '<h2 class="table_header_bold">&nbsp;' . _('Dateien:') . '</h2>';
            $dump .= '</td></tr>' . "\n";

            foreach ($dbresult as $row) {
                $name = ($row['name'] && $row['name'] != $row['filename'])
                      ? $row['name'] . ' (' . $row['filename'] . ')'
                      : $row['filename'];
                $dump .= sprintf('<tr><td width="100%%"><b>%s</b><br>%s (%u KB)</td><td>%s</td><td>%s</td></tr>' . "\n", 
                                 htmlReady($name),
                                 htmlReady($row['description']),
                                 round($row['filesize'] / 1024),
                                 htmlReady($row['Nachname']),
                                 date('d.m.Y', $row['mkdate']));
            }

            $dump .= '</table>' . "\n";
        }
    }

    // Teilnehmer
    if ($Modules['participants']
        && ($GLOBALS['AUTO_INSERT_SEM_PARTICIPANTS_VIEW_PERM'] || !in_array($sem_id, AutoInsert::getAllSeminars(true))))
    {
        $dump .= '<br>';

        // Prepare statement that obtains the number of document a specific
        // user has uploaded into a specific seminar
        $query = "SELECT COUNT(*) FROM dokumente WHERE Seminar_id = ? AND user_id = ?";
        $documents_statement = DBManager::get()->prepare($query);

        // Prepare statement that obtains all participants of a specific
        // seminar with a specific status
        $ext_vis_query = get_ext_vis_query('seminar_user');
        $query = "SELECT user_id, {$_fullname_sql['full']} AS fullname,
                         COUNT(topic_id) AS doll, {$ext_vis_query} AS user_is_visible
                    FROM seminar_user
                    LEFT JOIN px_topics USING (user_id,Seminar_id)
                    LEFT JOIN auth_user_md5 USING (user_id)
                    LEFT JOIN user_info USING (user_id)
                    WHERE Seminar_id = ? AND status = ?
                    GROUP by user_id
                    ORDER BY Nachname, Vorname";
        $user_statement = DBManager::get()->prepare($query);

        foreach (words('dozent tutor autor user') as $key) {
            // die eigentliche Teil-Tabelle
            
            $user_statement->execute(array($sem_id, $key));
            $users = $user_statement->fetchAll(PDO::FETCH_ASSOC);
            $user_statement->closeCursor();

            //haben wir in der Personengattung ueberhaupt einen Eintrag?
            if (count($users) > 0) {
                $dump .= '<table width="100%" border="1" cellpadding="2" cellspacing="0">';
                $dump .= '<tr><td align="left" colspan="3" class="table_header_bold">';
                $dump .= '<h2 class="table_header_bold">&nbsp;' . get_title_for_status($key, count($users), $sem_type) . '</h2>';
                $dump .= '</td></tr>' . "\n";
                $dump .= '<th width="30%">' . _('Name') . '</th>';
                $dump .= '<th width="10%">' . _('Forenbeiträge') . '</th>';
                $dump .= '<th width="10%">' . _('Dokumente') . '</th></tr>' . "\n";

                foreach ($users as $user) {
                    $documents_statement->execute(array($sem_id, $user['user_id']));
                    $count = $documents_statement->fetchColumn() ?: 0;
                    $documents_statement->closeCursor();

                    $dump .= sprintf('<tr><td>%s</td><td align="center">%u</td><td align="center">%u</td></tr>' . "\n",
                                     $user['user_is_visible'] ? htmlReady($user['fullname']) : _('(unsichtbareR NutzerIn)'),
                                     $user['doll'],
                                     $count);
                } // eine Zeile zuende

                $dump.= '</table>' . "\n";
            }
        } // eine Gruppe zuende
    }

    return $dump;
} // end function dump_sem($sem_id)


/**
 * Returns the regular dates for one seminar.
 * @param  $sem_id the id of the seminar
 * @return the HTML for the schedule table
 */
function dumpRegularDatesSchedule($sem_id)
{
    $presence_type_clause = getPresenceTypeClause();
    $query = "SELECT termine.*, themen.title AS th_title, themen.description AS th_desc
              FROM termine
              LEFT JOIN themen_termine USING (termin_id)
              LEFT JOIN themen USING (issue_id)
              WHERE range_id = ? AND date_typ IN {$presence_type_clause}
              ORDER BY date";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);

    return dumpScheduleTable($data, _('Ablaufplan'));
}

/**
 * Returns the extra dates for one seminar
 * @param  $sem_id the id of the seminar
 * @return the HTML for the schedule table for the extra dates
 */
function dumpExtraDatesSchedule($sem_id)
{
    $presence_type_clause = getPresenceTypeClause();
    $query = "SELECT termine.*, themen.title AS th_title, themen.description AS th_desc
              FROM termine
              LEFT JOIN themen_termine USING (termin_id)
              LEFT JOIN themen USING (issue_id)
              WHERE range_id = ? AND date_typ NOT IN {$presence_type_clause}
              ORDER BY date";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);

    return dumpScheduleTable($data, _('zus&auml;tzliche Termine'));
}

/**
 * Returns the schedule table for one query as HTML.
 * The query has to start like this:
 * SELECT termine.*, themen.title as th_title, themen.description as th_desc FROM termine LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen USING (issue_id)
 * @param  $data the result of an query for date entries
 * @param  $title the title for the table header
 * @return the HTML for the schedule table
 */
function dumpScheduleTable($data, $title)
{
    if (count($data) > 0) {
        $dump  = '<br>';
        $dump .= '<table width="100%" border="1" cellpadding="2" cellspacing="0">';
        $dump .= dumpDateTableHeader($title);
        $dump .= dumpDateTableRows($data);
        $dump .= '</table>\n';
    }

    return $dump;
}

/**
 * Returns the first row (the header row) for the tables listing dates.
 * @param  $title title to show in first table row
 * @return the HTML for the first table row
 */
function dumpDateTableHeader($title)
{
    $dump  = '<tr><td colspan="2" align="left" class="table_header_bold">';
    $dump .= '<h2 class="table_header_bold">&nbsp;' . htmlReady($title) . '</h2>';
    $dump .= '</td></tr>' . "\n";

    return $dump;
}

/**
 * Returns the HTML table rows for the date entries in $data.
 * The query has to start like this:
 * SELECT termine.*, themen.title as th_title, themen.description as th_desc FROM termine LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen USING (issue_id)
 * @param  $data the result of an query for date entries
 * @return the HTML for the table rows
 */
function dumpDateTableRows($data)
{
    global $TERMIN_TYP;

    $dump = '';
    $lastTerminId = NULL;

    foreach ($data as $row) {
        $currentTerminId = $row['termin_id'];
        if ($lastTerminId != $currentTerminId) {
            $dump .= '<tr align="center"> ';
            $dump .= '<td width="25%" align="left" valign="top">';
            $dump .= strftime('%d. %b. %Y, %H:%M', $row['date']);
            $dump .= ' - ' . strftime('%H:%M', $row['end_time']);
            $dump .= '&nbsp;(' . $TERMIN_TYP[$row['date_typ']]['name'] . ')';
            $dump .= '</td>';
        } else {
            $dump .= '<tr><td width="25%"></td>';
        }

        $dump .= '<td width="75%" align="left"> ';
        $dump .= htmlReady($row['th_title'], 1, 1);
        if ($row['th_desc']) {
            $dump .= '<br/>';
            $dump .= formatReady($row['th_desc'], 1, 1);
        }
        $dump .= '&nbsp;</td></tr>' . "\n";

        $lastTerminId = $currentTerminId;
    }

    return $dump;
}


/////// die beiden Funktionen um das Forum zu exportieren

function Export_Kids ($topic_id = 0, $level = 0)
{
    // stellt im Treeview alle Postings dar, die NICHT Thema sind
    $dump = '';

    $query = "SELECT topic_id, name, author, mkdate, description, anonymous
              FROM px_topics
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE parent_id = ?
              ORDER BY mkdate";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($topic_id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if ($row['topic_id'] != $topic_id) {
            $author = (get_config('FORUM_ANONYMOUS_POSTINGS') && $row['anonymous'])
                    ? _('anonym')
                    : $row['author'];
            $dump .= sprintf('<tr><td class="blank"><hr><b>%s</b> %s %s %s %s</td></tr>'
                            .'<tr><td class="blank">%s</td></tr>' . "\n",
                             htmlReady($row['name']),
                             _('von'),
                             $author,
                             _('am'),
                             date('d.m.Y - H:i', $row['mkdate']),
                             formatReady($row['description']));
        }
        $dump .= Export_Kids($row['topic_id'], $level + 1);
    }
    return $dump;
}

function Export_Topic ($sem_id)
{
    $query = "SELECT DISTINCT t.topic_id, t.name, t.description, t.author, t.anonymous,
                     COUNT(*) AS count, MAX(s.chdate) AS last
              FROM px_topics AS t
              LEFT JOIN px_topics AS s USING (root_id)
              WHERE t.topic_id = t.root_id AND t.Seminar_id = ?
              GROUP by t.root_id
              ORDER by t.mkdate";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));

    $dump = '';

    $count = 0;
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $author = (get_config('FORUM_ANONYMOUS_POSTINGS') && $row['anonymous'])
                ? _('anonym')
                : $row['author'];

        $dump .= sprintf('<table class="blank" width="100%%" border="0" cellpadding="5" cellspacing="0">'
                        .'<tr><td><h3>%s</h3> %s %s / <b>%u</b> / %s</td></tr>'
                        .'<tr><td class="blank">%s</td></tr>',
                         htmlReady($row['name']),
                         _('von'),
                         htmlReady($author),
                         $row['count'] - 1,
                         date("d.m.Y - H:i", $row['last']),
                         formatReady(forum_parse_edit($row['description'], $row['anonymous'])));
        $dump .= Export_Kids($row['topic_id']);
        $dump .= '</table><br><br>';
        
        $count += 1;
    }

    if ($count == 0) {  // Das Forum ist leer
        $text = _('Das Forum ist leer');
        $dump ="<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>".$text."</table>";
    }

    return $dump;
}



//Funktion zum archivieren eines Seminars, sollte in der Regel vor dem Loeschen ausgfuehrt werden.
function in_archiv ($sem_id)
{
    global $SEM_CLASS,$SEM_TYPE, $ARCHIV_PATH, $TMP_PATH, $ZIP_PATH, $ZIP_OPTIONS, $_fullname_sql;

    //Besorgen der Grunddaten des Seminars
    $query = "SELECT Seminar_id, Name, Untertitel, Beschreibung,
                     start_time, Institut_id, status
              FROM seminare
              WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    $seminar_id     = $row['Seminar_id'];
    $name           = $row['Name'];
    $untertitel     = $row['Untertitel'];
    $beschreibung   = $row['Beschreibung'];
    $start_time     = $row['start_time'];
    $heimat_inst_id = $row['Institut_id'];

    //Besorgen von einzelnen Daten zu dem Seminar
    $semester = new SemesterData;
    $all_semester = $semester->getAllSemesterData();
    foreach ($all_semester as $sem) {
        if (($start_time >= $sem['beginn']) && ($start_time <= $sem['ende'])) {
            $semester_tmp = $sem['name'];
        } 
    }

    //Studienbereiche
    if ($SEM_CLASS[$SEM_TYPE[$row['status']]['class']]['bereiche']) {
        $sem_path = get_sem_tree_path($seminar_id);
        if (is_array($sem_path)) {
            $studienbereiche = join(', ', $sem_path);
        }
    }

    // das Heimatinstitut als erstes
    $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($heimat_inst_id));
    $institute = $statement->fetchColumn();

    // jetzt den Rest
    $query = "SELECT Name
              FROM Institute
              LEFT JOIN seminar_inst USING (institut_id)
              WHERE seminar_id = ? AND Institute.Institut_id != ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id, $heimat_inst_id));
    while ($temp = $statement->fetchColumn()) {
        $institute .= ', ' . $temp;
    }

    $query = "SELECT GROUP_CONCAT({$_fullname_sql['full']} SEPARATOR ', ')
              FROM seminar_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE seminar_id = ? AND seminar_user.status = 'dozent'";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $dozenten = $statement->fetchColumn();

    $query = "SELECT fakultaets_id
              FROM seminare
              LEFT JOIN Institute USING (Institut_id)
              WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $fakultaet_id = $statement->fetchColumn();

    $query = "SELECT GROUP_CONCAT(DISTINCT c.Name SEPARATOR ' | ')
              FROM seminar_inst AS a
              LEFT JOIN Institute AS b USING (Institut_id)
              LEFT JOIN Institute AS c ON (c.Institut_id = b.fakultaets_id)
              WHERE a.seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $fakultaet = $statement->fetchColumn();

    setTempLanguage();  // use $DEFAULT_LANGUAGE for archiv-dumps
    
    //Dump holen
    $dump = dump_sem($sem_id);

    //Forumdump holen
    $forumdump = export_topic($sem_id);

    // Wikidump holen
    $wikidump = getAllWikiPages($sem_id, $name, FALSE);

    restoreLanguage();
    
    //OK, naechster Schritt: Kopieren der Personendaten aus seminar_user in archiv_user
    $query = "INSERT INTO archiv_user (seminar_id, user_id, status)
              SELECT Seminar_id, user_id, status FROM seminar_user WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));

    // Eventuelle Vertretungen in der Veranstaltung haben weiterhin Zugriff mit Dozentenrechten
    if (get_config('DEPUTIES_ENABLE')) {
        $deputies = getDeputies($seminar_id);
        // Eintragen ins Archiv mit Zugriffsberechtigung "dozent"
        $query = "INSERT INTO archiv_user SET seminar_id = ?, user_id = ?, status = 'dozent'";
        $statement = DBManager::get()->prepare($query);
        foreach ($deputies as $deputy) {
            $statement->execute(array($seminar_id, $deputy['user_id']));
        }
    }

    //OK, letzter Schritt: ZIPpen der Dateien des Seminars und Verschieben in eigenes Verzeichnis
    $query = "SELECT COUNT(dokument_id) FROM dokumente WHERE seminar_id = ? AND url = ''";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $count = $statement->fetchColumn();
    if ($count) {
        $hash_secret = "frauen";
        $archiv_file_id = md5(uniqid($hash_secret,1));

        //temporaeres Verzeichnis anlegen
        $tmp_full_path = "$TMP_PATH/$archiv_file_id";
        mkdir($tmp_full_path, 0700);

        $folder_tree =& TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $seminar_id));
        if($folder_tree->getNumKids('root')) {
            $list = $folder_tree->getKids('root');
        }
        if (is_array($list) && count($list) > 0) {
            //copy documents in the temporary folder-system
            $query = "SELECT folder_id, name
                      FROM folder WHERE range_id IN (?)
                      ORDER BY name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($list));

            $folder = 0;
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $folder += 1;
                $temp_folder = $tmp_full_path . "/[$folder]_" . prepareFilename($row['name'], FALSE);
                mkdir($temp_folder, 0700);
                createTempFolder($row['folder_id'], $temp_folder, FALSE);
            }

            //zip all the stuff
            $archiv_full_path = "$ARCHIV_PATH/$archiv_file_id";
            create_zip_from_directory($tmp_full_path, $tmp_full_path);
            @rename($tmp_full_path . '.zip', $archiv_full_path);
        }
        rmdirr($tmp_full_path);
    } else {
        $archiv_file_id = '';
    }

    //Reinschreiben von diversem Klumpatsch in die Datenbank
    $query = "INSERT INTO archiv
                (seminar_id, name, untertitel, beschreibung, start_time,
                 semester, heimat_inst_id, institute, dozenten, fakultaet,
                 dump, archiv_file_id, forumdump, wikidump, studienbereiche,
                 mkdate)
              VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $seminar_id,
        $name,
        $untertitel,
        $beschreibung,
        $start_time,
        $semester_tmp ?: '',
        $heimat_inst_id,
        $institute,
        $dozenten,
        $fakultaet,
        $dump,
        $archiv_file_id, 
        $forumdump,
        $wikidump,
        $studienbereiche,
    ));
}
