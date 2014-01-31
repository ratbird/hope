<?php
/**
 * restricted_courses.php - administration of admission restrictions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Andr� Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/admission/CourseSet.class.php';

class Admission_RestrictedCoursesController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        
        parent::before_filter($action, $args);
        PageLayout::setTitle(_('Anmeldesets'));
        Navigation::activateItem('/tools/coursesets/restricted_courses');
    }
    
    function index_action()
    {
        $this->setInfoboxImage(Assets::image_path('infobox/hoersaal.jpg'));
        $this->addToInfobox(_('Information'), _("Sie k�nnen hier alle Veranstaltungen mit eingeschr�nkter Teilnehmerzahl einsehen."), 'icons/16/black/info');
        $this->addToInfobox(_('Aktionen'), '<a href="'.$this->link_for('admission/restricted_courses', array('csv' => 1)).'">' . _("Export") . '</a>', 'icons/16/blue/file-xls');
        
        $sem_condition = "AND EXISTS (SELECT * FROM seminar_courseset INNER JOIN courseset_rule USING(set_id) WHERE type='ParticipantRestrictedAdmission' AND seminar_courseset.seminar_id=seminare.seminar_id) ";
        if (Request::isPost()) {
            if (Request::submitted('choose_institut')) {
                $this->current_institut_id = Request::option('choose_institut_id');
                $this->current_semester_id = Request::option('select_semester_id');
                $this->sem_name_prefix = trim(Request::get('sem_name_prefix'));
            }
        }
        if (!$this->current_institut_id) {
            $this->current_institut_id = 'all';
        }
        if (!$this->current_semester_id) {
            $this->current_semester_id = $_SESSION['_default_sem'];
        } else {
            $_SESSION['_default_sem'] = $this->current_semester_id;
        }
        $semester = Semester::find($this->current_semester_id);
        $sem_condition .= "AND seminare.start_time <=" . (int)$semester["beginn"]." AND (" . (int)$semester["beginn"] . " <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
        if ($this->sem_name_prefix) {
            $sem_condition .= sprintf('AND (seminare.Name LIKE %1$s OR seminare.VeranstaltungsNummer LIKE %1$s) ', DBManager::get()->quote($this->sem_name_prefix . '%'));
        }
        if ($GLOBALS['perm']->have_perm('dozent')) {
            $this->my_inst = $this->get_institutes($sem_condition);
        }
        $this->courses = $this->get_courses($sem_condition);
        if (Request::get('csv')) {
            $captions = array(_("Anmeldeset"),
                    _("Nummer"),
                    _("Name"),
                    _("max. Teilnehmer"),
                    _("Teilnehmer aktuell"),
                    _("Anzahl Anmeldungen"),
                    _("Anzahl vorl. Anmeldungen"),
                    _("Anzahl Warteliste"),
                    _("Platzverteilung"),
                    _("Startzeitpunkt"),
                    _("Endzeitpunkt"));
            $data = array();
            foreach ($this->courses as $course) {
                $row = array();
                $row[] = $course['cs_name'];
                $row[] = $course['course_number'];
                $row[] = $course['course_name'];
                $row[] = (int)$course['admission_turnout'];
                $row[] = $course['count_teilnehmer'] + $course['count_prelim'];
                $row[] = (int)$course['count_prelim'];
                $row[] = (int)$course['count_waiting'];
                $row[] = (int)$course['count_claiming'];
                $row[] = $course['distribution_time'] ? strftime('%x %R', $course['distribution_time']) : '';
                $row[] = $course['start_time'] ? strftime('%x %R', $course['start_time']) : '';
                $row[] = $course['end_time'] ? strftime('%x %R', $course['end_time']) : '';
                $data[] = $row;
            }
            $tmpname = md5(uniqid('tmp'));
            if (array_to_csv($data, $GLOBALS['TMP_PATH'].'/'.$tmpname, $captions)) {
                $this->redirect(GetDownloadLink($tmpname, 'teilnahmebeschraenkteVeranstaltungen.csv', 4, 'force'));
                return;
            }
        }
    }

    function get_courses($seminare_condition)
    {
        global $perm, $user;
        
        list($institut_id, $all) = explode('_', $this->current_institut_id);
        // Prepare count statements
        $query = "SELECT count(*)
                  FROM seminar_user
                  WHERE seminar_id = ? AND status IN ('user', 'autor')";
        $count0_statement = DBManager::get()->prepare($query);
        
        $query = "SELECT SUM(status = 'accepted') AS count2,
                     SUM(status = 'awaiting') AS count3
                  FROM admission_seminar_user
                  WHERE seminar_id = ?
                  GROUP BY seminar_id";
        $count1_statement = DBManager::get()->prepare($query);
        
        $parameters = array();
        
        $sql = "SELECT seminare.seminar_id,seminare.Name as course_name,seminare.VeranstaltungsNummer as course_number,
                admission_prelim, admission_turnout,seminar_courseset.set_id
                FROM seminar_courseset 
                INNER JOIN courseset_rule csr ON csr.set_id=seminar_courseset.set_id AND csr.type='ParticipantRestrictedAdmission'
                INNER JOIN seminare ON seminar_courseset.seminar_id=seminare.seminar_id
                ";
        if ($institut_id == 'all'  && $perm->have_perm('root')) {
            $sql .= "WHERE 1 {$seminare_condition} ";
        } elseif ($all == 'all') {
            $sql .= "INNER JOIN Institute USING (Institut_id)
                    WHERE Institute.fakultaets_id = ? {$seminare_condition}
                    ";
            $parameters[] = $institut_id;
        } else {
            $sql .= "WHERE seminare.Institut_id = ? {$seminare_condition}
                    ";
            $parameters[] = $institut_id;
        }
        $sql .= "GROUP BY seminare.Seminar_id ORDER BY seminar_courseset.set_id, seminare.Name";
        
        $statement = DBManager::get()->prepare($sql);
        $statement->execute($parameters);
        
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $seminar_id = $row['seminar_id'];
            $ret[$seminar_id] = $row;

            $count0_statement->execute(array($seminar_id));
            $count = $count0_statement->fetchColumn();

            $ret[$seminar_id]['count_teilnehmer']     = $count;

            $count1_statement->execute(array($seminar_id));
            $counts = $count1_statement->fetch(PDO::FETCH_ASSOC);

            $ret[$seminar_id]['count_prelim'] = (int)$counts['count2'];
            $ret[$seminar_id]['count_waiting']  = (int)$counts['count3'];
            $cs = new CourseSet($row['set_id']);
            $ret[$seminar_id]['cs_name'] = $cs->getName();
            $ret[$seminar_id]['distribution_time'] = $cs->getSeatDistributionTime();
            if ($ta = $cs->getAdmissionRule('TimedAdmission')) {
                $ret[$seminar_id]['start_time'] = $ta->getStartTime();
                $ret[$seminar_id]['end_time'] = $ta->getEndTime();
            }
            if (!$cs->hasAlgorithmRun()) {
                $ret[$seminar_id]['count_claiming'] = $cs->getNumApplicants();
            }
        }
        return $ret;
    }

    function get_institutes($seminare_condition) 
    {
        global $perm, $user;

        // Prepare institute statement
        $query = "SELECT a.Institut_id, a.Name, COUNT(seminar_id) AS num_sem
        FROM Institute AS a
        LEFT JOIN seminare ON (seminare.Institut_id = a.Institut_id {$seminare_condition})
        WHERE fakultaets_id = ? AND a.Institut_id != fakultaets_id
        GROUP BY a.Institut_id
        ORDER BY a.Name, num_sem DESC";
        $institute_statement = DBManager::get()->prepare($query);

        $parameters = array();
        if ($perm->have_perm('root')) {
            $query = "SELECT COUNT(*) FROM seminare WHERE 1 {$seminare_condition}";
            $statement = DBManager::get()->query($query);
            $num_sem = $statement->fetchColumn();

            $_my_inst['all'] = array(
                    'name'    => _('alle'),
                    'num_sem' => $num_sem
            );
            $query = "SELECT a.Institut_id, a.Name, 1 AS is_fak, COUNT(seminar_id) AS num_sem
            FROM Institute AS a
            LEFT JOIN seminare ON (seminare.Institut_id = a.Institut_id {$seminare_condition})
            WHERE a.Institut_id = fakultaets_id
            GROUP BY a.Institut_id
            ORDER BY is_fak, Name, num_sem DESC";
        } else {
            $query = "SELECT s.inst_perms,b.Institut_id, b.Name, b.Institut_id = b.fakultaets_id AS is_fak, COUNT( seminar_id ) AS num_sem
            FROM user_inst AS s
            LEFT JOIN Institute AS b USING ( Institut_id )
            LEFT JOIN seminare ON ( seminare.Institut_id = b.Institut_id {$seminare_condition})
            WHERE s.user_id = ? AND s.inst_perms IN ('admin', 'dozent')
            GROUP BY b.Institut_id
            ORDER BY is_fak, Name, num_sem DESC";
            $parameters[] = $user->id;
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($temp as $row) {
            $_my_inst[$row['Institut_id']] = array(
                    'name'    => $row['Name'],
                    'is_fak'  => $row['is_fak'],
                    'num_sem' => $row['num_sem']
            );
            if ($row["is_fak"] && $row["inst_perms"] != 'dozent') {
                $institute_statement->execute(array($row['Institut_id']));
                $alle = $institute_statement->fetchAll();
                if (count($alle)) {
                    $_my_inst[$row['Institut_id'] . '_all'] = array(
                            'name'    => sprintf(_('[Alle unter %s]'), $row['Name']),
                            'is_fak'  => 'all',
                            'num_sem' => $row['num_sem']
                    );

                    $num_inst = 0;
                    $num_sem_alle = $row['num_sem'];


                    foreach ($alle as $institute) {
                        if(!$_my_inst[$institute['Institut_id']]) {
                            $num_inst += 1;
                            $num_sem_alle += $institute['num_sem'];
                        }
                        $_my_inst[$institute['Institut_id']] = array(
                                'name'    => $institute['Name'],
                                'is_fak'  => 0,
                                'num_sem' => $institute["num_sem"]
                        );
                    }
                    $_my_inst[$row['Institut_id']]['num_inst']          = $num_inst;
                    $_my_inst[$row['Institut_id'] . '_all']['num_inst'] = $num_inst;
                    $_my_inst[$row['Institut_id'] . '_all']['num_sem']  = $num_sem_alle;
                }
            }
        }
        return $_my_inst;
    }
}