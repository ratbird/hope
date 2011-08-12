<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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

    function restoreIssue($issue_id) {
        $db = new DB_Seminar();
        $db->query("SELECT themen.*, folder.range_id, folder.folder_id, px_topics.topic_id FROM themen LEFT JOIN folder ON (range_id = issue_id) LEFT JOIN px_topics ON (px_topics.topic_id = issue_id) WHERE issue_id = '$issue_id'");
        $db->next_record();
        return $db->Record;
    }

    function storeIssue(&$issue) {
        global $user;
        $db = new DB_Seminar();
        if ($issue->file) {
            $db->query("SELECT folder_id FROM folder WHERE range_id = '{$issue->issue_id}'");
            if ($db->num_rows() == 0) {
                $db->query("INSERT INTO folder (folder_id, range_id, user_id, name, description, mkdate, chdate) VALUES ('".md5(uniqid('folder'))."', '{$issue->issue_id}', '{$user->id}', '".mysql_escape_string($issue->toString())."', '"._("Themenbezogener Dateiordner")."', '".time()."', '".time()."')");
            } else {
                $db->query("UPDATE folder SET name = '".mysql_escape_string($issue->toString())."' WHERE range_id = '{$issue->issue_id}'");
            }
        } else {
            //$db->query("DELETE FROM folder WHERE range_id = '{$issue->issue_id}'");
        }

        if ($issue->forum) {
            $db->query("SELECT topic_id FROM px_topics WHERE topic_id = '{$issue->issue_id}'");
            if ($db->num_rows() == 0) {
                $db->query("INSERT INTO px_topics (topic_id, root_id, parent_id, name, description, mkdate, chdate, author, Seminar_id, user_id) VALUES ('{$issue->issue_id}', '{$issue->issue_id}', '0', '".mysql_escape_string($issue->toString())."', '"._("Themenbezogene Diskussionen")."', '".time()."', '".time()."', '".get_fullname($user->id)."', '{$issue->seminar_id}' , '{$user->id}')");
            } else {
                $db->query("UPDATE px_topics SET name = '".mysql_escape_string($issue->toString())."' WHERE topic_id = '{$issue->issue_id}'");
            }
        } else {
            //$db->query("DELETE FROM px_topics WHERE topic_id = '{$issue->issue_id}'");
        }
        
        if ($issue->new) {
            $db->query("INSERT INTO themen (issue_id, seminar_id, author_id, title, description, mkdate, chdate, priority) VALUES ('{$issue->issue_id}', '{$issue->seminar_id}', '{$issue->author_id}', '".mysql_escape_string($issue->title)."', '".mysql_escape_string($issue->description)."', '{$issue->mkdate}', '{$issue->chdate}', '{$issue->priority}')");
        } else {
            $db->query("UPDATE themen SET seminar_id = '{$issue->seminar_id}', author_id = '{$issue->author_id}', title = '".mysql_escape_string($issue->title)."', description = '".mysql_escape_string($issue->description)."', mkdate = '{$issue->mkdate}', priority = '{$issue->priority}' WHERE issue_id = '{$issue->issue_id}'");

            if ($db->affected_rows()) {
                $db->query("UPDATE themen SET chdate = ".time()." WHERE issue_id = '{$issue->issue_id}'");
                $db->query("SELECT termin_id FROM themen_termine WHERE issue_id = '{$issue->issue_id}'");
                $db2 = new DB_Seminar();
                while ($db->next_record()) {
                    $db2->query("UPDATE termine SET chdate = ".time()." WHERE termin_id = '".$db->f('termin_id')."'");
                }
            }

        }
        return TRUE;
    }

    function deleteIssue($issue_id, $seminar_id, $title = '', $description = '') {
        $db = new DB_Seminar();
        if ($title) {
            $new_id = md5($seminar_id . 'top_folder');
            $db->query("UPDATE folder SET name = '".mysql_escape_string($title)."', description= '".mysql_escape_string($description)."', range_id = '$new_id' WHERE range_id = '{$issue_id}'");
        }
        $db->query("DELETE FROM themen WHERE issue_id = '$issue_id'");
        $db->query("DELETE FROM themen_termine WHERE issue_id = '$issue_id'");
    }

    function isIssue($issue_id) {
        $db = new DB_Seminar();
        $db->query("SELECT * FROM themen WHERE issue_id = '$issue_id'");
        return $db->num_rows() ? true : false;
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
    
    function getDatesforIssue($issue_id){
        $ret = array();
        $db = new DB_Seminar("SELECT termine.* FROM themen_termine INNER JOIN termine USING(termin_id) WHERE issue_id='$issue_id' ORDER BY date ASC");
        while($db->next_record()) $ret[$db->f('termin_id')] = $db->Record;
        return $ret;
    }
    
    static function deleteAllIssues($course_id) {
        $db = DBManager::get();
        $themen = $db->query("SELECT issue_id FROM themen WHERE seminar_id = " . $db->quote($course_id))->fetchAll(PDO::FETCH_COLUMN);
        foreach ($themen as $issue_id) {
            self::deleteIssue($issue_id, $course_id);
        }
        return count($themen);
    }
}
?>
