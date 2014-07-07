<?php
/**
 * my_courses.php - Model for user and seminar related
 * pages under "Meine Veranstaltungen"
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @author      David Siegfried <david@ds-labs.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2 or later
 * @category    Stud.IP
 * @since       3.1
 */

require_once('lib/meine_seminare_func.inc.php');
require_once('lib/object.inc.php');
require_once('lib/functions.php');

class MyRealmModel
{

    /**
     * Check for changes in folders for a course
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkDocuments(&$my_obj, $user_id, $object_id)
    {
        if ($my_obj["modules"]["documents"]) {
            if (!$GLOBALS['perm']->have_perm('admin')) {
                if ($my_obj['modules']['documents_folder_permissions'] || ($my_obj['obj_type'] == 'sem' && StudipDocumentTree::ExistsGroupFolders($object_id))) {
                    $must_have_perm = $my_obj['obj_type'] == 'sem' ? 'tutor' : 'autor';
                    if ($GLOBALS['perm']->permissions[$my_obj['status']] < $GLOBALS['perm']->permissions[$must_have_perm]) {
                        $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree',
                            array('range_id'    => $object_id,
                                  'entity_type' => $my_obj['obj_type']));

                        $unreadable_folders = (array)$folder_tree->getUnReadableFolders($user_id);
                    }
                }
            }
            $result = self::getDocumentsVisitDate($object_id, $user_id, $unreadable_folders);
            if (!is_null($result['last_modified'])) {
                if ($my_obj['last_modified'] < $result['last_modified']) {
                    $my_obj['last_modified'] = $result['last_modified'];
                }
                $nav = new Navigation('files');
                if ($result['last_modified']) {
                    $nav->setURL(sprintf('folder.php?cmd=all'));
                    $nav->setImage('icons/20/red/new/files.png', array('title' => _('Es sind neue Dokumente vorhanden')));
                    $nav->setBadgeNumber($result['neue']);
                } else {
                    $nav->setURL('folder.php?cmd=tree');
                    $nav->setImage('icons/20/grey/files.png', array('title' => _('Dokumente')));
                }
                return $nav;
            }
        }
        return null;
    }


    /**
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkLiterature(&$my_obj, $user_id, $object_id)
    {
        if ($my_obj["modules"]["literature"]) {
            $sql       = "SELECT a.range_id, COUNT(list_id) as count,
                MAX(IF((chdate > IFNULL(b . visitdate, 0) AND a . user_id != :user_id), chdate, 0)) AS last_modified
                FROM
                lit_list a
                LEFT JOIN object_user_visits b ON (b.object_id = a.range_id AND b.user_id = :user_id AND b.type ='literature')
                WHERE a.range_id = :course_id  AND a. visibility = 1
                GROUP BY a.range_id";
            $statement = DBManager::get()->prepare($sql);
            $statement->bindValue(':user_id', $user_id);
            $statement->bindValue(':course_id', $object_id);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if (!empty($result)) {
                if (!is_null($result['last_modified']) && (int)$result['last_modified'] != 0) {
                    if ($my_obj['last_modified'] < $result['last_modified']) {
                        $my_obj['last_modified'] = $result['last_modified'];
                    }
                }
                $nav = new Navigation('literature', 'dispatch.php/course/literature');
                if ((int)$result['last_modified']) {
                    $nav->setImage('icons/20/red/new/literature.png', array('title' => _('Es sind neue Literaturlisten/ -einträge vorhanden')));
                } elseif ((int)$result['count']) {
                    $nav->setImage('icons/20/grey/literature.png', array('title' => _('Literaturlisten')));
                }
                return $nav;
            }
        }
        return null;
    }


    /**
     * Check for new news
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkOverview(&$my_obj, $user_id, $object_id)
    {

        $sql = "SELECT
            COUNT(nw . news_id) as count,
            MAX(IF ((chdate > IFNULL(b . visitdate, 0) AND nw . user_id !=:user_id), chdate, 0)) AS last_modified
            FROM news_range a
            LEFT JOIN news nw ON(a . news_id = nw . news_id AND UNIX_TIMESTAMP() BETWEEN date AND (date + expire))
            LEFT JOIN object_user_visits b ON(b . object_id = a . news_id AND b . user_id = :user_id AND b . type = 'news')
            WHERE a . range_id = :course_id
            GROUP BY a.range_id";

        $statement = DBManager::get()->prepare($sql);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':course_id', $object_id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!empty($result)) {
            if ($my_obj['last_modified'] < $result['last_modified']) {
                $my_obj['last_modified'] = $result['last_modified'];
            }
            $nav = new Navigation('news', '');
            if ($result['last_modified']) {
                $nav->setURL('?new_news=true');
                $nav->setImage('icons/20/red/new/news.png', array('title' => _('Es sind neue Ankündigungen vorhanden')));
                $nav->setBadgeNumber($result['neue']);
            } elseif ($result['count']) {
                $nav->setImage('icons/20/grey/news.png', array('title' => _('Ankündigungen')));
            }
            return $nav;
        }
        return null;
    }


    /**
     * Check SCM for news
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkScm(&$my_obj, $user_id, $object_id)
    {

        if ($my_obj["modules"]["scm"]) {
            $sql = "SELECT tab_name,  ouv.object_id,
                  COUNT(IF(content !='',1,0)) as count,
                  COUNT(IF((chdate > IFNULL(ouv.visitdate,0) AND scm.user_id !=:user_id), IF(content !='',1,0), NULL)) AS neue,
                  MAX(IF((chdate > IFNULL(ouv.visitdate,0) AND scm.user_id !=:user_id), chdate, 0)) AS last_modified
                FROM
                  scm
                LEFT JOIN
                  object_user_visits ouv ON(ouv.object_id = scm.range_id AND ouv.user_id = :user_id and ouv . type = 'scm')
                WHERE
                  scm.range_id = :course_id
                GROUP BY
                  scm.range_id";

            $statement = DBManager::get()->prepare($sql);
            $statement->bindValue(':user_id', $user_id);
            $statement->bindValue(':course_id', $object_id);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);


            if (!empty($result)) {
                if ($my_obj['last_modified'] < $result['last_modified']) {
                    $my_obj['last_modified'] = $result['last_modified'];
                }

                $nav = new Navigation('scm', 'dispatch.php/course/scm');
                if ($result['count']) {

                    if ($result['neue']) {
                        $image = 'icons/20/red/new/infopage.png';
                        $nav->setBadgeNumber($result['neue']);
                        if ($result['count'] == 1) {
                            $title = $result['tab_name'] . _(' (geändert)');
                        } else {
                            $title = sprintf(_('%s Einträge'), $result['count']);
                        };
                    } else {
                        $image = 'icons/20/grey/infopage.png';
                        if ($result['count'] == 1) {
                            $title = $result['tab_name'] . _(' (geändert)');
                        } else {
                            $title = sprintf(_('%s Einträge'), $result['count']);
                        }
                    }
                    $nav->setImage($image, array('title' => $title));
                }

                return $nav;
            }
        }
        return null;
    }

    /**
     * Check for new dates
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkSchedule(&$my_obj, $user_id, $object_id)
    {
        if ($my_obj["modules"]["schedule"]) {
            $modified = false;
            $count    = 0;
            // check for extern dates
            $sql       = "SELECT  COUNT(term . termin_id) as count,
                  MAX(IF ((term . chdate > IFNULL(ouv . visitdate, 0) AND term . autor_id != :user_id), term . chdate, 0)) AS last_modified
                FROM
                  ex_termine term
                LEFT JOIN
                  object_user_visits ouv ON(ouv . object_id = term . range_id AND ouv . user_id = :user_id AND ouv . type = 'schedule')
                WHERE term . range_id = :course_id
                GROUP BY term.range_id";
            $statement = DBManager::get()->prepare($sql);
            $statement->bindValue(':user_id', $user_id);
            $statement->bindValue(':course_id', $object_id);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if (!empty($result)) {
                $count = $result['count'];
                if ($my_obj['last_modified'] < $result['last_modified'] && (int)$result['last_modified'] != 0) {
                    $my_obj['last_modified'] = $result['last_modified'];
                    $modified                = true;
                }
            }


            // check for normal dates
            $sql = "SELECT  COUNT(term . termin_id) as count,
                  MAX(IF ((term . chdate > IFNULL(ouv . visitdate, 0) AND term . autor_id != :user_id), term . chdate, 0)) AS last_modified
                FROM
                  termine term
                LEFT JOIN
                  object_user_visits ouv ON(ouv . object_id = term . range_id AND ouv . user_id = :user_id AND ouv . type = 'schedule')
                WHERE term . range_id = :course_id
                GROUP BY term.range_id";

            $statement = DBManager::get()->prepare($sql);
            $statement->bindValue(':user_id', $user_id);
            $statement->bindValue(':course_id', $object_id);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);


            if (!empty($result)) {
                $count += $result['count'];
                if ($my_obj['last_modified'] < $result['last_modified'] && (int)$result['last_modified'] != 0) {
                    $my_obj['last_modified'] = $result['last_modified'];
                    $modified                = true;
                }
            }

            if ($modified || $count > 0) {
                $nav = new Navigation('schedule', 'dispatch.php/course/dates');
                if ($modified) {
                    $nav->setImage('icons/20/red/new/schedule.png', array('title' => _('Es sind neue Termine vorhanden')));
                } elseif ($count) {
                    $nav->setImage('icons/20/grey/schedule.png', array('title' => _('Termine')));
                }
                return $nav;
            }
        }
        return null;
    }

    /**
     * Check for new entries in wiki
     * @param $my_obj
     * @param $user_id
     * @param $modules
     */
    public static function checkWiki(&$my_obj, $user_id, $object_id)
    {
        if ($my_obj["modules"]["wiki"]) {
            $sql       = "SELECT COUNT(DISTINCT keyword) as count_d,
                COUNT(IF((chdate > IFNULL(ouv.visitdate,0) AND wiki.user_id !=:user_id), keyword, NULL)) AS neue,
                MAX(IF((chdate > IFNULL(ouv.visitdate,0) AND wiki.user_id !=:user_id), chdate, 0)) AS last_modified,
              COUNT(keyword) as count
            FROM
              wiki
            LEFT JOIN
              object_user_visits ouv ON(ouv . object_id = wiki . range_id AND ouv . user_id = :user_id and ouv . type = 'wiki')
            WHERE
              wiki . range_id = :course_id
            GROUP BY
              wiki.range_id";
            $statement = DBManager::get()->prepare($sql);
            $statement->bindValue(':user_id', $user_id);
            $statement->bindValue(':course_id', $object_id);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if (!empty($result)) {
                if (!is_null($result['last_modified']) && (int)$result['last_modified'] != 0) {
                    if ($my_obj['last_modified'] < $result['last_modified']) {
                        $my_obj['last_modified'] = $result['last_modified'];
                    }
                }
                $nav = new Navigation('wiki');
                if ((int)$result['neue']) {
                    $nav->setURL('wiki.php?view=listnew');
                    $nav->setImage('icons/20/red/new/wiki.png', array('title' => sprintf(_('%s WikiSeiten, %s Änderungen'), $result['count_d'], $result['neue'])));
                    $nav->setBadgeNumber($result['neue']);
                } elseif ((int)$result['count']) {
                    $nav->setURL('wiki.php');
                    $nav->setImage('icons/20/grey/wiki.png', array('title' => sprintf(_('%s WikiSeiten'), $result['count_d'])));
                }
                return $nav;
            }
        }

        return null;
    }

    /**
     * TODO: prepared statement
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkElearning_interface(&$my_obj, $user_id, $object_id)
    {
        if ($my_obj["modules"]["elearning_interface"]) {
            $sql = "SELECT a.object_id, COUNT(module_id) as count,
                MAX(IF((chdate > IFNULL(b.visitdate, 0) AND a.module_type != 'crs'), chdate, 0)) AS last_modified
                FROM
                object_contentmodules a
                LEFT JOIN object_user_visits b ON (b.object_id = a.object_id AND b.user_id = :user_id AND b.type ='elearning_interface')
                WHERE a.object_id = :course_id  AND a.module_type != 'crs'
                GROUP BY a.object_id";

            $statement = DBManager::get()->prepare($sql);
            $statement->bindValue(':user_id', $user_id);
            $statement->bindValue(':course_id', $object_id);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if (!empty($result)) {
                if (!is_null($result['last_modified']) && (int)$result['last_modified'] != 0) {
                    if ($my_obj['last_modified'] < $result['last_modified']) {
                        $my_obj['last_modified'] = $result['last_modified'];
                    }
                }
                $nav = new Navigation('elearning', 'dispatch/course/elearning/show');
                if ((int)$result['last_modified']) {
                    $nav->setImage('icons/20/red/new/learnmodule.png', array('title' => _('Es sind neue Lernmodule vorhanden')));
                } elseif ((int)$result['count']) {
                    $nav->setImage('icons/20/grey/learnmodule.png', array('title' => _('Lernmodule')));
                }
                return $nav;

            }
        }
        return null;
    }

    /**
     * Check the voting system
     * @param      $my_obj
     * @param      $user_id
     * @param null $modules
     */
    public static function checkVote(&$my_obj, $user_id, $object_id)
    {
        $count    = 0;
        $modified = false;

        $sql = "SELECT  COUNT(vote.vote_id) as count,
              MAX(IF((chdate > IFNULL(ouv.visitdate,0) AND vote.author_id !=:user_id AND vote.state != 'stopvis'), chdate, 0)) AS last_modified
            FROM vote
            LEFT JOIN object_user_visits ouv ON(ouv.object_id = vote.vote_id AND ouv.user_id = :user_id AND ouv.type = 'vote')
            WHERE vote.range_id = :course_id AND vote.state IN('active','stopvis')
            GROUP BY vote.range_id";

        $statement = DBManager::get()->prepare($sql);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':course_id', $object_id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!empty($result)) {
            $count = $result['count'];
            if (!is_null($result['last_modified']) && (int)$result['last_modified'] != 0) {
                if ($my_obj['last_modified'] < $result['last_modified']) {
                    $my_obj['last_modified'] = $result['last_modified'];
                    $modified                = true;
                }
            }
        }

        $sql = "SELECT
                COUNT(a.eval_id) as count,
                MAX(IF ((chdate > IFNULL(b.visitdate, 0) AND d.author_id != :user_id), chdate, 0)) AS last_modified
            FROM
                eval_range a
            INNER JOIN
              eval d
            ON
              (a.eval_id = d.eval_id AND d.startdate < UNIX_TIMESTAMP() AND (d.stopdate > UNIX_TIMESTAMP() OR d.startdate + d.timespan > UNIX_TIMESTAMP() OR (d.stopdate IS NULL AND d.timespan IS NULL)))
            LEFT JOIN
                object_user_visits b
            ON
              (b.object_id = a.eval_id AND b.user_id = :user_id AND b . type = 'eval')
            WHERE
              a.range_id = :course_id
            GROUP BY
              a.range_id";

        $statement = DBManager::get()->prepare($sql);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':course_id', $object_id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            $count += $result['count'];
            if (!is_null($result['last_modified']) && (int)$result['last_modified'] != 0) {
                if ($my_obj['last_modified'] < $result['last_modified']) {
                    $my_obj['last_modified'] = $result['last_modified'];
                    $modified                = true;
                }
            }
        }


        if ($modified || $count > 0) {
            $nav = new Navigation('vote', '#vote');
            if ($modified) {
                $nav->setImage('icons/20/red/new/vote.png', array('title' => _('Es sind neue Umfragen vorhanden')));
                $nav->setBadgeNumber($my_obj['neuevotes']);
            } else if ($count) {
                $nav->setImage('icons/20/grey/vote.png', array('title' => _('Umfragen')));
            }
            return $nav;
        }


        return null;
    }


    /**
     * @param $seminar_id
     * @param $visitdate
     * @return mixed
     */
    public static function getPluginNavigationForSeminar($seminar_id, $visitdate)
    {
        $plugin_navigation[$seminar_id] = array();
        $plugins                        = PluginEngine::getPlugins('StandardPlugin', $seminar_id);

        if (empty($plugins)) return $plugin_navigation[$seminar_id];

        foreach ($plugins as $plugin) {
            $nav = $plugin->getIconNavigation($seminar_id, $visitdate, $GLOBALS['user']->id);
            if ($nav instanceof Navigation) $plugin_navigation[$seminar_id][get_class($plugin)] = $nav;
        }
        return $plugin_navigation[$seminar_id];
    }


    /**
     * Get all courses vor given user in selected semesters
     *
     */
    public static function getCourses($min_sem_key, $max_sem_key, $params = array())
    {
        // init
        $order_by          = $params['order_by'];
        $order             = $params['order'];
        $deputies_enabled  = $params['deputies_enabled'];
        $sem_data          = SemesterData::GetSemesterArray();
        $min_sem           = $sem_data[$min_sem_key];
        $max_sem           = $sem_data[$max_sem_key];
        $studygroup_filter = !$params['studygroups_enabled'] ? false : true;
        $ordering          = '';
        // create ordering
        if (!$order_by) {
            $ordering .= 'name asc';
        } else {
            $ordering .= $order_by . ' ' . $order;
        }

        // search for your own courses
        // Filtering by Semester
        $courses = Course::findThru($GLOBALS['user']->id, array(
            'thru_table'        => 'seminar_user',
            'thru_key'          => 'user_id',
            'thru_assoc_key'    => 'seminar_id',
            'assoc_foreign_key' => 'seminar_id'
        ));

        if ($deputies_enabled) {
            $datas = self::getDeputies($GLOBALS['user']->id);
            if(!empty($datas)) {
                foreach ($datas as $data) {
                    $deputies[] = Course::import($data);
                }
                $courses = array_merge($courses, $deputies);
            }
        }
        // create a new collection for more functionality
        $courses = new SimpleCollection($courses);
        if ($studygroup_filter) {
            $courses = $courses->filter(function ($a) {
                return (int)$a['status'] != 99;
            });
        }

        $courses = $courses->filter(function ($a) use ($min_sem, $max_sem) {
            return (($a->end_semester->beginn >= $min_sem['beginn'])
                || ($a->start_semester->beginn >= $min_sem['beginn'] && $a->end_semester->beginn <= $max_sem['beginn'])
                || (int)$a->duration_time == -1);
        });


        $courses->orderBy($ordering);

        return $courses;
    }


    public static function getDeputies($user_id)
    {
        $query     = "SELECT DISTINCT range_id as seminar_id FROM deputies WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $data = $statement->fetchALL(PDO::FETCH_ASSOC);
        return $data;
    }


    public static function getDeputieGroup($range_id)
    {
        $query     = "SELECT gruppe FROM deputies WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->fetch(PDO::FETCH_COLUMN);
    }


    /**
     * Get the start_ and end_sem_number for a given course
     * @param $course
     * @return array
     */
    public static function getCourseSemNumbers($course)
    {
        $sem_data = SemesterData::GetSemesterArray();
        $result   = array();

        foreach ($sem_data as $key => $sem) {
            if (!empty($result['sem_number']) && !empty($result['sem_number_end'])) return $result;

            if ($sem['semester_id'] == $course->start_semester->semester_id) $result['sem_number'] = $key;

            if ($sem['semester_id'] == $course->end_semester->semester_id) $result['sem_number_end'] = $key;
        }

        // add the last existiting semester to unlimited courses / studygroup
        if ((int)$course->duration_time == -1) {
            $result['sem_number_end'] = max(array_keys($sem_data));
        }

        return $result;
    }

    public static function getSelectedSemesters($sem = 'all')
    {
        $sem_data  = SemesterData::GetSemesterArray();
        $semesters = array();
        foreach ($sem_data as $sem_key => $one_sem) {
            $current_sem = $sem_key;
            if (!$one_sem['past']) break;
        }

        if (isset($sem_data[$current_sem + 1])) {
            $max_sem = $current_sem + 1;
        } else {
            $max_sem = $current_sem;
        }

        // Get the needed semester
        if ($sem != 'all' && $sem != 'current' && $sem != 'future' && $sem != 'last') {
            $semesters[] = SemesterData::GetSemesterIndexById($sem);
        } else {
            switch ($sem) {
                case 'current':
                    $semesters[] = $current_sem;
                    break;
                case 'future':
                    $semesters[] = $current_sem;
                    $semesters[] = $max_sem;
                    break;
                case 'last':
                    $semesters[] = $current_sem - 1;
                    $semesters[] = $current_sem;
                    break;
                default:
                    $semesters = array_keys($sem_data);;
                    break;
            }
        }

        return $semesters;
    }

    public static function getPreparedCourses($sem = "all", $params = array())
    {
        $semesters   = self::getSelectedSemesters($sem);
        $min_sem_key = min($semesters);
        $max_sem_key = max($semesters);
        $group_field = $params['group_field'];
        $courses     = self::getCourses($min_sem_key, $max_sem_key, $params);


        $param_array = 'name seminar_id visible veranstaltungsnummer start_time duration_time status visible ';
        $param_array .= 'chdate admission_binding modules admission_prelim';

        if (!empty($courses)) {
            // filtering courses
            $modules = new Modules();

            foreach ($courses as $index => $course) {
                // remove invisible courses if no rights avaible
                if ((int)$course->visible != 1 && !$GLOBALS['perm']->have_studip_perm('dozent', $course->seminar_id)) {
                    continue;
                }

                // export object to array for simple handling
                $_course                   = $course->toArray($param_array);
                $_course['start_semester'] = $course->start_semester->name;
                $_course['end_semester']   = $course->end_semester->name;
                $_course['obj_type']       = 'sem';

                if ($group_field == 'sem_tree_id') {
                    $_course['sem_tree'] = $course->study_areas->toArray();
                }

                // the sem numbers for a given course
                $sem_nrs     = self::getCourseSemNumbers($course);
                $member_ship = User::findCurrent()->course_memberships->findOneBy('seminar_id', $course->id);

                // get teachers only if grouping selected (for better performance)
                if ($group_field == 'dozent_id') {
                    $teachers = new SimpleCollection($course->getMembersWithStatus('dozent'));
                    $teachers->filter(function ($a) use (&$_course) {
                        return $_course['teachers'][] = $a->user->getFullName('no_title_rev');
                    });
                }

                $_course['last_visitdate'] = object_get_visit($course->id, 'sem', 'last');
                $_course['visitdate']      = object_get_visit($course->id, 'sem', '');
                $_course['user_status']    = $member_ship->status;
                $_course['gruppe']         = !empty($member_ship->gruppe) ? $member_ship->gruppe : self::getDeputieGroup($course->id);
                $_course['sem_number_end'] = $sem_nrs['sem_number_end'];
                $_course['sem_number']     = $sem_nrs['sem_number'];
                $_course['modules']        = $modules->getLocalModules($course->id, 'sem', $course->modules, $course->status);
                $_course['name']           = $course->name;
                $_course['temp_name']      = $course->name;

                // add the the course to the correct semester
                for ($i = $min_sem_key; $i <= $max_sem_key; $i++) {
                    if ((int)$course->duration_time == -1) {
                        self::getObjectValues($_course);
                        $sem_courses[$max_sem_key][$course->id] = $_course;
                        unset($course[$index]);
                        break;
                    }

                    if ($i >= $sem_nrs['sem_number'] && $i <= $sem_nrs['sem_number_end']) {
                        self::getObjectValues($_course);
                        $sem_courses[$i][$course->id] = $_course;
                        unset($course[$index]);
                        break;
                    }
                }

            }
        } else {
            return null;
        }

        if (empty($sem_courses)) {
            return null;
        }

        if ($params['main_navigation']) {
            return $sem_courses;
        }
        krsort($sem_courses);

        // grouping
        if ($group_field == 'sem_number' && !$params['order_by']) {
            foreach ($sem_courses as $index => $courses) {
                uasort($courses, function ($a, $b) {
                     return $a['gruppe'] == $b['gruppe'] ? strcmp($a['temp_name'], $b['temp_name']) : $a['gruppe'] - $b['gruppe'];
                });
                $sem_courses[$index] = $courses;
            }
        }
        // Group by teacher
        if ($group_field == 'dozent_id') {
            self::groupByTeacher($sem_courses);
        }

        // Group by Sem Status
        if ($group_field == 'sem_status') {
            self::groupBySemStatus($sem_courses);
        }

        // Group by colors
        if ($group_field == 'gruppe') {
            self::groupByGruppe($sem_courses);
        }

        // Group by sem_tree
        if ($group_field == 'sem_tree_id') {
            self::groupBySemTree($sem_courses);
        }

        return !empty($sem_courses) ? $sem_courses : false;
    }

    public static function  checkParticipants(&$my_obj, $user_id, $object_id, $is_admission)
    {
        if ($my_obj["modules"]["participants"]) {
            if ($is_admission) {
                //vorlï¿½ufige Teilnahme
                //            $sql     = MyCoursesModel::get_obj_clause('admission_seminar_user a', 'seminar_id', 'a.user_id', "(mkdate > IFNULL(b . visitdate, 0) AND a . user_id != '$user_id')",
                //                'participants', false, " AND a . status = 'accepted' ", false, $user_id, 'mkdate');
                //            $results = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                //            if (!empty($results)) {
                //                foreach ($results as $result) {
                //                    if ($my_obj["modules"]["participants"]) {
                //                        $my_obj["new_accepted_participants"]   = $result['neue'];
                //                        $my_obj["count_accepted_participants"] = $result['count'];
                //                        if ($my_obj['last_modified'] < $result['last_modified']) {
                //                            $my_obj['last_modified'] = $result['last_modified'];
                //                        }
                //                    }
                //                }
                //            }
            }

            if (SeminarCategories::GetByTypeId($my_obj['status'])->studygroup_mode) {
                $nav = new Navigation('participants', 'dispatch.php/course/studygroup/members/' . $object_id);
            } else {
                $nav = new Navigation('participants', 'dispatch.php/course/members/index');
            }

            if ($GLOBALS['perm']->have_perm('admin', $user_id) || in_array($my_obj['user_status'], words('dozent tutor'))) {
                $all_auto_inserts = AutoInsert::getAllSeminars(true);
                $auto_insert_perm = Config::get()->AUTO_INSERT_SEM_PARTICIPANTS_VIEW_PERM;

                $sql       = "SELECT
                    COUNT(a . user_id) as count,
                    MAX(IF ((mkdate > IFNULL(b . visitdate, 0) AND a . user_id != :user_id), mkdate, 0)) AS last_modified
                    FROM seminar_user a
                    LEFT JOIN object_user_visits b ON(b . object_id = a . seminar_id AND b . user_id = :user_id AND b . type = 'participants')
                    WHERE seminar_id = :course_id";
                $statement = DBManager::get()->prepare($sql);
                $statement->bindValue(':user_id', $user_id);
                $statement->bindValue(':course_id', $object_id);
                $statement->execute();
                $result = $statement->fetch(PDO::FETCH_ASSOC);

                if (!empty($result)) {
                    // show the participants-icon only if the module is activated and it is not an auto-insert-sem

                    if (in_array($object_id, $all_auto_inserts)) {
                        if ($GLOBALS['perm']->have_perm('admin', $user_id) && !$GLOBALS['perm']->have_perm($auto_insert_perm, $user_id)) {
                            return null;
                        } else if ($GLOBALS['perm']->permissions[$auto_insert_perm] > $GLOBALS['perm']->permissions[$my_obj['user_status']]) {
                            return null;
                        }
                    }
                    $my_obj["countparticipants"] = $result['count'];
                    if ($GLOBALS['perm']->have_perm('admin', $user_id) || in_array($my_obj['user_status'], words('dozent tutor'))) {
                        if ($my_obj['last_modified'] < $result['last_modified']) {
                            $my_obj['last_modified'] = $result['last_modified'];
                        }
                    }

                    $count = $my_obj["countparticipants"] + $my_obj["count_accepted_participants"];

                    if ($result['last_modified']) {
                        $nav->setImage('icons/20/red/new/persons.png', array('title' => _('Es sind neue TeilnehmerInnen vorhanden')));
                    } else if ($count) {
                        $nav->setImage('icons/20/grey/persons.png', array('title' => _('TeilnehmerInnen')));
                    }
                }
            } else {
                $nav->setImage('icons/20/grey/persons.png', array('title' => _('TeilnehmerInnen')));
            }
            return $nav;
        }
        return null;
    }


    public static function getDocumentsVisitDate($object_id, $user_id, $unreadable_folders = array())
    {
        $sql = "SELECT MAX(IF ((d . chdate > IFNULL(ouv . visitdate, 0) AND d . user_id !=:user_id), d . chdate, 0)) AS last_modified
            FROM
              dokumente d
            LEFT JOIN object_user_visits ouv
              ON(ouv . object_id = d . Seminar_id AND ouv . user_id = :user_id AND ouv . type = 'documents')
            WHERE d . Seminar_id = :course_id";
        $sql .= (count($unreadable_folders) ? " AND d . range_id NOT IN('" . join("', '", $unreadable_folders) . "')" : "");
        $sql .= " GROUP BY d.Seminar_id";

        $statement = DBManager::get()->prepare($sql);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':course_id', $object_id);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }


    public static function getAdditionalNavigations($object_id, &$my_obj_values, $sem_class = null, $user_id)
    {

        $plugin_navigation = MyRealmModel::getPluginNavigationForSeminar($object_id, $my_obj_values['visitdate']);
        $available_modules = 'CoreForum participants documents overview scm schedule wiki vote literature elearning_interface';

        foreach (words($available_modules) as $key) {

            // Go to next module if current module is not available and not voting-module
            if (!$my_obj_values['modules'][$key] && strcmp('vote', $key) !== 0) {
                $navigation[$key] = null;
                continue;
            }
            if (!Config::get()->VOTE_ENABLE && strcmp($key, 'vote') === 0) {
                continue;
            }

            if (!Config::get()->WIKI_ENABLE && strcmp($key, 'wiki') === 0) {
                continue;
            }

            if (!Config::get()->ELEARNING_INTERFACE_ENABLE && strcmp($key, 'elearning_interface') === 0) {
                continue;
            }

            if (!Config::get()->LITERATURE_ENABLE && strcmp($key, 'literature') === 0) {
                continue;
            }

            $function = 'check' . ucfirst($key);
            $main_nav = true;

            if (method_exists(__CLASS__, $function)) {
                $params = array(&$my_obj_values,
                    $user_id,
                    $object_id);
                if (strcmp($key, 'participants') === 0) {
                    array_push($params, false);
                }
                $nav = call_user_func_array(array(self,
                    $function), $params);

            }


            if ($sem_class && !empty($plugin_navigation)) {
                $module = $sem_class->getModule($key);
                if (!is_null($module)) {
                    if (property_exists($module, 'plugin_info')) {
                        $navigation[$key] = $plugin_navigation[get_class($module)];
                        // Workaround to get the badge-information on overview
                        // if still badge number exists something new in blubber or forum exists
                        if ($navigation[$key] && ((int) $navigation[$key]->getBadgeNumber() > 0)) {
                            $my_obj_values['last_modified'] = time();
                        }
                        $main_nav = false;
                        unset($plugin_navigation[get_class($module)]);
                    }
                }
            }

            // add the main navigation item to resultset
            if ($main_nav) {
                $navigation[$key] = $nav;
                unset($nav);
            }
        }

        $navigation = array_merge($navigation, $plugin_navigation);
        unset($plugin_navigation);
        return $navigation;
    }


    /**
     * @param $course_id
     * @return array
     */
    public static function getSemTree($course_id, $depth = false)
    {
        $the_tree        = TreeAbstract::GetInstance("StudipSemTree");
        $view            = new DbView();
        $ret             = null;
        $view->params[0] = $course_id;
        $rs              = $view->get_query("view:SEMINAR_SEM_TREE_GET_IDS");
        while ($rs->next_record()) {
            $ret[$rs->f('sem_tree_id')]['name'] = $the_tree->getShortPath($rs->f('sem_tree_id'), null, ">", $depth ? $depth - 1 : 0);
            $ret[$rs->f('sem_tree_id')]['info'] = $the_tree->getValue($rs->f('sem_tree_id'), 'info');
        }

        return $ret;
    }


    /**
     * Returns the id for the studygroup name
     * @return Interger
     */
    public static function getStudygroupId()
    {
        $statement = DBManager::get()->prepare(
            "SELECT id FROM sem_classes WHERE name = :name"
        );
        $statement->execute(array('name' => 'Studiengruppen'));
        $result = $statement->fetch(PDO::FETCH_COLUMN);
        return $result;
    }


    /**
     * This function reset all visits on every available modules
     * @param $object
     * @param $object_id
     * @param $user_id
     * @return bool
     */
    public static function setObjectVisits(&$object, $object_id, $user_id, $timestamp = null)
    {
        // load plugins, so they have a chance to register themselves as observers
        PluginEngine::getPlugins('StandardPlugin');

        NotificationCenter::postNotification('OverviewWillClear', $user_id);

        $query     = "INSERT INTO object_user_visits
                    (object_id, user_id, type, visitdate, last_visitdate)
                  (
                    SELECT news_id, :user_id, 'news', :timestamp, 0
                    FROM news_range
                    WHERE range_id = :id
                  ) UNION (
                    SELECT vote_id, :user_id, 'vote', :timestamp, 0
                    FROM vote
                    WHERE range_id = :id
                  ) UNION (
                    SELECT eval_id, :user_id, 'eval', :timestamp, 0
                    FROM eval_range
                    WHERE range_id = :id
                  )
                  ON DUPLICATE KEY UPDATE last_visitdate = IFNULL(visitdate, 0), visitdate = :timestamp";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':timestamp', $timestamp ?: time());
        // Update all activated modules
        foreach (words('forum documents schedule participants literature wiki scm elearning_interface') as $type) {
            if ($object['modules'][$type]) {
                object_set_visit($object_id, $type);
            }
        }

        // Update news, votes and evaluations
        $statement->bindValue('id', $object_id);
        $statement->execute();

        // Update object itself
        object_set_visit($object_id, $object['obj_type']);

        NotificationCenter::postNotification('OverviewDidClear', $GLOBALS['user']->id);

        return true;
    }

    /**
     * This functions check all modules for changes (new documents,...) and adds a new icon-navigation item to given course
     * This function will only add something if nothing exists to get better performance
     * @param $course
     * @param $call (debug)
     */
    public static function getObjectValues(&$course)
    {

        if (!isset($course['sem_class'])) {
            $sem_class           = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$course['status']]['class']];
            $course['sem_class'] = $sem_class;
        }

        if (!isset($course['navigation'])) {
            // get additional navigation items
            $course['navigation'] = self::getAdditionalNavigations($course['seminar_id'], $course, $sem_class, $GLOBALS['user']->id);
        }
    }


    public static function getWaitingList($user_id)
    {
        $claiming = DBManager::get()->fetchAll(
            "SELECT set_id, priorities.seminar_id,'claiming' as status, seminare.Name, seminare.Ort
            FROM priorities
            LEFT JOIN seminare USING(seminar_id)
            WHERE user_id = ?", array($user_id));
        $csets    = array();
        foreach ($claiming as $k => $claim) {
            if (!$csets[$claim['set_id']]) {
                $csets[$claim['set_id']] = new CourseSet($claim['set_id']);
            }
            $cs = $csets[$claim['set_id']];
            if (!$cs->hasAlgorithmRun()) {
                $claiming[$k]['admission_endtime'] = $cs->getSeatDistributionTime();
                $num_claiming                      = count(AdmissionPriority::getPrioritiesByCourse($claim['set_id'], $claim['seminar_id']));
                $free                              = Course::find($claim['seminar_id'])->getFreeSeats();
                if ($free <= 0) {
                    $claiming[$k]['admission_chance'] = 0;
                } else if ($free >= $num_claiming) {
                    $claiming[$k]['admission_chance'] = 100;
                } else {
                    $claiming[$k]['admission_chance'] = round(($free / $num_claiming) * 100);
                }

            } else {
                unset($claiming[$k]);
            }
        }

        $stmt = DBManager::get()->prepare(
            "SELECT admission_seminar_user.*, seminare.status as sem_status, " .
            "seminare.Name, seminare.Ort " .
            "FROM admission_seminar_user " .
            "LEFT JOIN seminare USING(seminar_id) " .
            "WHERE user_id = ? " .
            "ORDER BY admission_seminar_user.status, name");
        $stmt->execute(array($user_id));

        $waitlists = array_merge($claiming, $stmt->fetchAll());

        return $waitlists;
    }


    /**
     * Get all user assigned institutes based on simple or map
     * @return array
     */
    public static function getMyInstitutes()
    {
        $memberShips = InstituteMember::findByUser($GLOBALS['user']->id);

        if(empty($memberShips)) {
            return null;
        }
        $insts      = new SimpleCollection($memberShips);
        $institutes = array();
        $insts->filter(function ($a) use (&$institutes) {
            $array                   = $a->institute->toArray();
            $array['perms']          = $a->inst_perms;
            $array['visitdate']      = object_get_visit($a->institut_id, 'inst', '');
            $array['last_visitdate'] = object_get_visit($a->institut_id, 'inst', 'last');

            $institutes[] = $array;

            return true;
        });


        if (!empty($institutes)) {
            $Modules = new Modules();
            foreach ($institutes as $index => $inst) {
                $institutes[$index]['modules']    = $Modules->getLocalModules($inst['institut_id'], 'inst', $inst['modules'], $inst['type'] ? : 1);
                $institutes[$index]['obj_type']   = 'inst';
                $institutes[$index]['navigation'] = MyRealmModel::getAdditionalNavigations($inst['institut_id'], $institutes[$index], null, $GLOBALS['user']->id);
            }
            unset($Modules);
        }

        return $institutes;
    }

    public static function groupBySemTree(&$sem_courses)
    {
        foreach ($sem_courses as $sem_key => $collection) {
            foreach ($collection as $course) {
                if (!empty($course['sem_tree'])) {
                    foreach ($course['sem_tree'] as $tree) {
                        $_tmp_courses[$sem_key][$tree['name']][$course['seminar_id']] = $course;
                    }
                } else {
                    $_tmp_courses[$sem_key][""][$course['seminar_id']] = $course;
                }
            }
        }

        $sem_courses = $_tmp_courses;
    }

    public static function groupByGruppe(&$sem_courses)
    {
        foreach ($sem_courses as $sem_key => $collection) {
            foreach ($collection as $course) {
                $_tmp_courses[$sem_key][$course['gruppe']][$course['seminar_id']] = $course;
                ksort($_tmp_courses[$sem_key]);
            }
        }
        $sem_courses = $_tmp_courses;
    }

    public static function groupBySemStatus(&$sem_courses)
    {
        foreach ($sem_courses as $sem_key => $collection) {
            foreach ($collection as $course) {

                $sem_status = $GLOBALS['SEM_TYPE'][$course['status']]["name"]
                    . " (" . $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$course['status']]["class"]]["name"] . ")";

                $_tmp_courses[$sem_key][$sem_status][$course['seminar_id']] = $course;
            }
            // reorder array
            uksort($_tmp_courses[$sem_key], function ($a, $b) {
                if (ucfirst($a) == ucfirst($b)) return 0;
                return ucfirst($a) < ucfirst($b) ? -1 : 1;
            });
        }
        $sem_courses = $_tmp_courses;
    }

    public static function groupByTeacher(&$sem_courses)
    {
        foreach ($sem_courses as $sem_key => $collection) {
            foreach ($collection as $course) {
                if (!empty($course['teachers'])) {
                    foreach ($course['teachers'] as $fullname) {
                        $_tmp_courses[$sem_key][$fullname][$course['seminar_id']] = $course;
                    }
                } else {
                    $_tmp_courses[$sem_key][""][$course['seminar_id']] = $course;
                }
                ksort($_tmp_courses[$sem_key]);
            }
        }
        $sem_courses = $_tmp_courses;
    }


    public static function getStudygroups()
    {
        $courses = array();
        $modules = new Modules();

        $studygroups = User::findCurrent()
            ->course_memberships
            ->filter(function ($c) {
                return $c->course->getSemClass()->offsetGet('studygroup_mode');
            })->toGroupedArray('seminar_id');


        $param_array = 'name seminar_id visible veranstaltungsnummer start_time duration_time status visible ';
        $param_array .= 'chdate admission_binding modules admission_prelim';
        $courses = Course::findAndMapMany(function ($course) use ($param_array, $studygroups, $modules) {
            $ret = $course->toArray($param_array);
            $ret['start_semester'] = $course->start_semester->name;
            $ret['end_semester'] = $course->end_semester->name;
            $ret['obj_type'] = 'sem';
            $ret['last_visitdate'] = object_get_visit($course->id, 'sem', 'last');
            $ret['visitdate'] = object_get_visit($course->id, 'sem', '');
            $ret['user_status'] = $studygroups[$course->id]['status'];
            $ret['gruppe'] = $studygroups[$course->id]['gruppe'];
            $ret['modules'] = $modules->getLocalModules($course->id, 'sem', $course->modules, $course->status);
            MyRealmModel::getObjectValues($ret);

            return $ret;
        }, array_keys($studygroups));

        return $courses;
    }


    public static function checkAdmissionParticipation($course_id)
    {
        $query     = "SELECT 1 FROM admission_seminar_user WHERE user_id = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($GLOBALS['user']->id,
            $course_id));
        $present = $statement->fetchColumn();
        return $present;
    }
} 
