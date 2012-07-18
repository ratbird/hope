<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// IssueDB.class.php
//
// Datenbank-Abfragen für Issue.class.php
//
// +--------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +--------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +--------------------------------------------------------------------------+


/**
 * IssueDB.class.php
 *
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @version     19. Oktober 2005
 * @access      protected
 * @package     raumzeit
 */

class IssueDB {

    function restoreIssue($issue_id)
    {
        $query = "SELECT themen.*, folder.range_id, folder.folder_id, px_topics.topic_id
                  FROM themen
                  LEFT JOIN folder ON (range_id = issue_id)
                  LEFT JOIN px_topics ON (px_topics.topic_id = issue_id)
                  WHERE issue_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($issue_id));
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    function storeIssue(&$issue)
    {
        global $user;
        if ($issue->file) {
            $query = "SELECT 1 FROM folder WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($issue->issue_id));
            $check = $statement->fetchColumn();

            if ($check) {
                $query = "UPDATE folder SET name = ? WHERE range_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $issue->toString(),
                    $issue->issue_id
                ));
            } else {
                $query = "INSERT INTO folder (folder_id, range_id, user_id, name, description, mkdate, chdate)
                          VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    md5(uniqid('folder', true)),
                    $issue->issue_id,
                    $user->id,
                    $issue->toString(),
                    _('Themenbezogener Dateiordner')
                ));
            }
        } else {
            //$db->query("DELETE FROM folder WHERE range_id = '{$issue->issue_id}'");
        }

        if ($issue->forum) {
            $query = "SELECT 1 FROM px_topics WHERE topic_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($issue->issue_id));
            $check = $statement->fetchColumn();

            if ($check) {
                $query = "UPDATE px_topics SET name = ? WHERE topic_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $issue->toString(),
                    $issue->issue_id
                ));
            } else {
                $query = "INSERT INTO px_topics
                            (topic_id, root_id, parent_id, name, description, mkdate, chdate, author, Seminar_id, user_id)
                          VALUES (?, ?, '0', ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, ?, ?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $issue->issue_id,
                    $issue->issue_id,
                    $issue->toString(),
                    _('Themenbezogene Diskussionen'),
                    get_fullname($user->id),
                    $issue->seminar_id,
                    $user->id
                ));
            }
        } else {
            //$db->query("DELETE FROM px_topics WHERE topic_id = '{$issue->issue_id}'");
        }
        
        if ($issue->new) {
            $query = "INSERT INTO themen
                        (issue_id, seminar_id, author_id, title, description, mkdate, chdate, priority)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $issue->issue_id,
                $issue->seminar_id,
                $issue->author_id,
                $issue->title,
                $issue->description,
                $issue->mkdate,
                $issue->chdate,
                $issue->priority
            ));
        } else {
            $query = "UPDATE themen
                      SET seminar_id = ?, author_id = ?, title = ?, description = ?, mkdate = ?, priority = ?
                      WHERE issue_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $issue->seminar_id,
                $issue->author_id,
                $issue->title,
                $issue->description,
                $issue->mkdate,
                $issue->priority,
                $issue->issue_id
            ));

            if ($statement->rowCount()) {
                $query = "UPDATE themen SET chdate = UNIX_TIMESTAMP() WHERE issue_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($issue->issue_id));

                $query = "SELECT termin_id FROM themen_termine WHERE issue_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($issue->issue_id));
                $termin_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

                if (count($termin_ids) > 0) {
                    $query = "UPDATE termine SET chdate = UNIX_TIMESTAMP() WHERE termin_id IN (?)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($termin_ids));
                }
            }

        }
        return TRUE;
    }

    function deleteIssue($issue_id, $seminar_id, $title = '', $description = '')
    {
        if ($title) {
            $query = "UPDATE folder
                      SET name = ?, description= ?, range_id = ?
                      WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $title,
                $description,
                md5($seminar_id . 'top_folder'),
                $issue_id
            ));
        }

        $query = "DELETE FROM themen WHERE issue_id = ?";
        $statement = DBManager::get()->prepare($query);$
        $statement->execute(array($issue_id));

        $query = "DELETE FROM themen_termine WHERE issue_id = ?";
        $statement = DBManager::get()->prepare($query);$
        $statement->execute(array($issue_id));
    }

    function isIssue($issue_id)
    {
        $query = "SELECT 1 FROM themen WHERE issue_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($issue_id));
        return (bool)$statement->fetchColumn();
    }

    /*function checkFile($issue_id) {
        $db = new DB_Seminar();
        $db->query("SELECT range_id FROM folder WHERE range_id = '$issue_id'");
        if ($db->num_rows() > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }*/
    
    function getDatesforIssue($issue_id)
    {
        $query = "SELECT termine.*
                  FROM themen_termine
                  INNER JOIN termine USING (termin_id)
                  WHERE issue_id = ?
                  ORDER BY `date` ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($issue_id));

        $ret = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $ret[$row['termin_id']] = $row;
        }
        return $ret;
    }
    
    static function deleteAllIssues($course_id)
    {
        $query = "SELECT issue_id FROM themen WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($course_id));
        $themen = $statement->fetchAll(PDO::FETCH_COLUMN);

        foreach ($themen as $issue_id) {
            self::deleteIssue($issue_id, $course_id);
        }

        return count($themen);
    }
}
?>
