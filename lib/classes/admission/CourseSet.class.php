<?php

/**
 * CourseSet.class.php
 *
 * Represents groups of Stud.IP courses that have common rules for admission.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once('lib/classes/admission/AdmissionRule.class.php');
require_once('lib/classes/admission/UserFilterField.class.php');
require_once('lib/classes/admission/RandomAlgorithm.class.php');
require_once('lib/classes/admission/AdmissionPriority.class.php');
require_once('lib/classes/admission/AdmissionUserList.class.php');

class CourseSet
{
    // --- ATTRIBUTES ---

    /**
     * Admission rules that are applied to the courses belonging to this set.
     */
    protected $admissionRules = array();

    /**
     * Seat distribution algorithm.
     */
    protected $algorithm = null;

    /**
     * IDs of courses that are aggregated into this set. The array is in the
     * form ($courseId1 => true, $courseId2 => true).
     */
    protected $courses = array();

    /**
     * Has the seat distribution algorithm already been executed?
     */
    protected $hasAlgorithmRun = false;

    /**
     * Unique identifier for this set.
     */
    protected $id = '';

    /**
     * Some extensive descriptional text for informing confused students.
     */
    protected $infoText = '';

    /**
     * Which Stud.IP institute does the course set belong to?
     */
    protected $institutes = array();

    /**
     * Some display name for this course set.
     */
    protected $name = '';

    /**
     * Is the course set only visible for the creator?
     */
    protected $private = false;

    /**
     * Semester ID.
     */
    protected $semester = '';

    /**
     * Who owns this course set?
     */
    protected $user_id = false;

    /*
     * Lists of users who are treated differently on seat distribution
     */
    protected $userlists = array();

    // --- OPERATIONS ---

    public function __construct($setId='') {
        $this->id = $setId;
        $this->name = _("Anmeldeset");
        // Define autoload function for admission rules.
        spl_autoload_register(array('AdmissionRule', 'getAvailableAdmissionRules'));
        // Define autoload function for admission rules.
        spl_autoload_register(array('UserFilterField', 'getAvailableFilterFields'));
        if ($setId) {
            $this->load();
        }
    }

    /**
     * Adds the given admission rule to the list of rules for the course set.
     *
     * @param  AdmissionRule rule
     * @return CourseSet
     */
    public function addAdmissionRule($rule)
    {
        $this->admissionRules[$rule->getId()] = $rule;
        return $this;
    }

    /**
     * Adds the course with the given ID to the course set.
     *
     * @param  String courseId
     * @return CourseSet
     */
    public function addCourse($courseId)
    {
        $this->courses[$courseId] = true;
        return $this;
    }

    /**
     * Adds a new institute ID.
     *
     * @param  String newId
     * @return CourseSet
     */
    public function addInstitute($newId) {
        $this->institutes[$newId] = true;
    }

    /**
     * Adds several institute IDs to the existing institute assignments.
     *
     * @param  Array newIds
     * @return CourseSet
     */
    public function addInstitutes($newIds) {
        foreach ($newIds as $newId) {
            $this->addInstitute($newId);
        }
        return $this;
    }

    /**
     * Adds a UserList to the course set. The list contains several users and a
     * factor that changes seat distribution chances for these users;
     *
     * @param  String listId
     * @return CourseSet
     */
    public function addUserList($listId)
    {
        $this->userlists[$listId] = true;
        return $this;
    }

    /**
     * Is the given user allowed to register as participant in the given
     * course according to the rules of this course set?
     *
     * @param  String userId
     * @param  String courseId
     * @return Array Optional error messages from rules if something went wrong.
     */
    public function checkAdmission($userId, $courseId) {
        $errors = array();
        foreach ($this->admissionRules as $rule) {
            // All rules must be fulfilled.
            $ruleCheck = $rule->ruleApplies($userId, $courseId);
            if ($ruleCheck) {
                $errors = array_merge($errors, $ruleCheck);
            }
        }
        return $errors;
    }

    /**
     * Removes all admission rules at once.
     *
     * @return CourseSet
     */
    public function clearAdmissionRules() {
        $this->admissionRules = array();
        return $this;
    }

    /**
     * Deletes the course set and all associated data.
     */
    public function delete() {
        // Delete institute associations.
        $stmt = DBManager::get()->prepare("DELETE FROM `courseset_institute`
            WHERE `set_id`=?");
        $stmt->execute(array($this->id));
        // Delete course associations.
        $stmt = DBManager::get()->prepare("DELETE FROM `seminar_courseset`
            WHERE `set_id`=?");
        $stmt->execute(array($this->id));
        // Delete all rules...
        foreach ($this->rules as $rule) {
            $rule->delete();
        }
        // ... and their association to the current course set.
        $stmt = DBManager::get()->prepare("DELETE FROM `courseset_rule`
            WHERE `set_id`=?");
        $stmt->execute(array($this->id));
        // Delete associations to user lists.
        $stmt = DBManager::get()->prepare("DELETE FROM `courseset_factorlist`
            WHERE `set_id`=?");
        $stmt->execute(array($this->id));
        // Delete course set data.
        $stmt = DBManager::get()->prepare("DELETE FROM `coursesets`
            WHERE `set_id`=?");
        $stmt->execute(array($this->id));
        /*
         * Delete waiting lists (users are moved to corresponding course as
         * participants).
         */
        foreach ($this->courses as $id => $assigned) {
            // Get waiting list for each course.
            $list = AdmissionApplication::findBySeminar_id($id);
            // Move each user into course.
            foreach ($list as $entry) {
                $new = new CourseMember(array($id, $entry->user_id));
                if ($new->isNew()) {
                    $new->status = 'autor';
                    $new->admission_studiengang_id = 'all';
                }
                $new->store();
            }
            AdmissionApplication::deleteBySQL("seminar_id=?", array($id));
        }
    }

    /**
     * Starts the seat distribution algorithm.
     *
     * @return CourseSet
     */
    public function distributeSeats() {
        if ($this->algorithm) {
            // Call pre-distribution hooks on all assigned rules.
            foreach ($this->admissionRules as &$rule) {
                $rule->beforeSeatDistribution();
            }
            $this->algorithm->run($this);
            // Mark as "seats distributed".
            $this->setAlgorithmRun(true);
            // Call post-distribution hooks on all assigned rules.
            foreach ($this->admissionRules as &$rule) {
                $rule->afterSeatDistribution();
            }
            AdmissionPriority::unsetAllPriorities($this->getId());
        }
    }

    public function setAlgorithmRun($state)
    {
        $this->hasAlgorithmRun = (bool)$state;
        $db = DbManager::get();
        return $db->execute("UPDATE coursesets SET algorithm_run = ? WHERE set_id = ?", array($this->hasAlgorithmRun, $this->getId()));
    }

    public function isSeatDistributionEnabled()
    {
        return $this->getSeatDistributionTime() !== null;
    }

    public function getSeatDistributionTime()
    {
        $pr_admission = $this->getAdmissionRule('ParticipantRestrictedAdmission');
        if ($pr_admission) {
            return $pr_admission->getDistributionTime();
        }
    }

    /**
     * Get all admission rules belonging to the course set.
     *
     * @return Array
     */
    public function getAdmissionRules()
    {
        return $this->admissionRules;
    }

    public function getAdmissionRule($class_name)
    {
        return array_pop(array_filter($this->getAdmissionRules(), function($r) use ($class_name) {
            return $r instanceof $class_name;}));
    }
    /**
     * check if course set has given admission rule
     *
     * @param string $rule name of AdmissionRule class
     * @return boolean
     */
    public function hasAdmissionRule($rule)
    {
        return is_object($this->getAdmissionRule($rule));
    }

    /**
     * Get the currently used distribution algorithm.
     *
     * @return AdmissionAlgorithm
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * How many users will be allowed to register according to the defined
     * rules? This can help in estimating whether the combination of the
     * defined rules makes sense.
     *
     * @return int
     */
    public function getAllowedUserCount()
    {
        $users = array();
        foreach ($this->admissionRules as $rule) {
            $users = array_merge($users, $rule->getAffectedUsers());
        }
        return $sizeof($users);
    }

    /**
     * Gets the course IDs belonging to the course set.
     *
     * @return Array
     */
    public function getCourses()
    {
        return array_keys($this->courses);
    }

    /**
     * Gets all courses belonging to the given course set ID.
     *
     * @param String $courseSetId
     * @return Array
     */
    public static function getCoursesByCourseSetId($courseSetId) {
        $stmt = DBManager::get()->prepare("SELECT seminar_id FROM `seminar_courseset`
            WHERE courseset_id=?");
        $stmt->execute(array($courseSetId));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all course sets belonging to the given institute ID.
     *
     * @param String $instituteId
     * @return Array
     */
    public static function getCoursesetsByInstituteId($instituteId, $filter = array()) {
        $query = "SELECT DISTINCT ci.*
            FROM `courseset_institute` ci
            JOIN `coursesets` c ON (ci.`set_id`=c.`set_id`)
            LEFT JOIN courseset_rule cr ON cr.set_id=ci.set_id
            LEFT JOIN seminar_courseset sc ON c.set_id = sc.set_id
            LEFT JOIN seminare s ON s.seminar_id = sc.seminar_id
            WHERE ci.`institute_id`=?";
        $parameters = array($instituteId);
        if (!$GLOBALS['perm']->have_perm('root')) {
            $query .= " AND (c.`private`=0 OR c.`user_id`=?)";
            $parameters[] = $GLOBALS['user']->id;
        }
        if ($filter['course_set_name']) {
            $query .= " AND c.name LIKE ?";
            $parameters[] = $filter['course_set_name'] . '%';
        }
        if (is_array($filter['rule_types']) && count($filter['rule_types'])) {
            $query .= " AND cr.type IN (?)";
            $parameters[] = $filter['rule_types'];
        }
        if ($filter['semester_id']) {
            $query .= " AND s.start_time = ?";
            $parameters[] = Semester::find($filter['semester_id'])->beginn;
        }
        $query .= " ORDER BY c.name";
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute($parameters);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the identifier of the course set.
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the course set's info text.
     *
     * @return String
     */
    public function getInfoText()
    {
        return $this->infoText;
    }

    /**
     * Which institutes does the rule belong to?
     *
     * @return Array
     */
    public function getInstituteIds() {
        return $this->institutes;
    }

    /**
     * Gets this course set's display name.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Retrieves the priorities given to the courses in this set.
     *
     * @return Array
     */
    public function getPriorities()
    {
        return AdmissionPriority::getPriorities($this->id);
    }

    public function getNumApplicants()
    {
        return AdmissionPriority::getPrioritiesCount($this->id);
    }

    /**
     * Is the current courseset private?
     *
     * @return bool
     */
    public function getPrivate() {
        return $this->private;
    }
    

    public function getSemester() {
        return $this->semester;
    }

    /**
     * Gets the owner of this course set.
     */
    public function getUserId() {
        return $this->user_id;
    }
    
    public function setUserId($user_id) {
        return $this->user_id = $user_id;
    }

    /**
     * Gets the course sets the given course belongs to.
     *
     * @param  String courseId
     * @return CourseSet
     */
    public static function getSetForCourse($courseId)
    {
        $stmt = DBManager::get()->prepare("SELECT `set_id`
            FROM `seminar_courseset` WHERE `seminar_id`=?");
        $stmt->execute(array($courseId));
        $set_id = $stmt->fetchColumn();
        if ($set_id) {
            return new CourseSet($set_id);
        }
        return null;
    }
    
    /**
     * Gets the course sets the given rule belongs to.
     *
     * @param  String $rule_id
     * @return CourseSet
     */
    public static function getSetForRule($rule_id)
    {
        $set_id = DBManager::get()->fetchColumn("SELECT `set_id`
            FROM `courseset_rule` WHERE `rule_id`=?", array($rule_id));
        if ($set_id) {
            return new CourseSet($set_id);
        }
        return null;
    }
    
    /**
     * Retrieves the lists of users that are considered specially in
     * seat distribution.
     *
     * @return Array
     */
    public function getUserLists()
    {
        return array_keys($this->userlists);
    }

    public function getUserFactorList()
    {
        $factored_users = array();
        foreach ($this->getUserLists() as $ul_id) {
            $user_list = new AdmissionUserList($ul_id);
            $factored_users = array_merge($factored_users,
                                 array_combine(array_keys($user_list->getUsers()),
                                         array_fill(0, count($user_list->getUsers()), $user_list->getFactor())
                                         )
                    );
        }
        return $factored_users;
    }

    /**
     * Evaluates whether the seat distribution algorithm has already been
     * executed on this course set.
     *
     * @return boolean True if algorithm has already run, otherwise false.
     */
    public function hasAlgorithmRun() {
        return $this->hasAlgorithmRun;
    }

    /**
     * Helper function for loading data from DB.
     */
    public function load() {
        // Load basic data.
        $stmt = DBManager::get()->prepare(
            "SELECT * FROM `coursesets` WHERE set_id=? LIMIT 1");
        $stmt->execute(array($this->id));
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->name = $data['name'];
            $this->infoText = $data['infotext'];
            $this->hasAlgorithmRun = (bool)$data['algorithm_run'];
            if ($data['algorithm']) {
                if (class_exists($data['algorithm'])) {
                    $this->algorithm = new $data['algorithm']();
                }
            }
            $this->private = (bool) $data['private'];
            $this->semester = $data['semester'];
            $this->user_id = $data['user_id'];
        }
        // Load institute assigments.
        $stmt = DBManager::get()->prepare(
            "SELECT * FROM `courseset_institute` WHERE set_id=?");
        $stmt->execute(array($this->id));
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->institutes[$data['institute_id']] = true;
        }
        // Load courses.
        $stmt = DBManager::get()->prepare(
            "SELECT * FROM `seminar_courseset` WHERE set_id=?");
        $stmt->execute(array($this->id));
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->courses[$data['seminar_id']] = true;
        }
        // Load admission rules.
        $stmt = DBManager::get()->prepare(
            "SELECT * FROM `courseset_rule` WHERE set_id=?");
        $stmt->execute(array($this->id));
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (class_exists($data['type'])) {
                $this->admissionRules[$data['rule_id']] =
                    new $data['type']($data['rule_id'], $this->id);
            }
        }
        // Load assigned user lists.
        $stmt = DBManager::get()->prepare("SELECT `factorlist_id`
            FROM `courseset_factorlist` WHERE `set_id`=?");
        $stmt->execute(array($this->id));
        while ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->userlists[$current['factorlist_id']] = true;
        }
        return $this;
    }

    /**
     * Removes the course with the given ID from the set.
     *
     * @param  String courseId
     * @return CourseSet
     */
    public function removeCourse($courseId)
    {
        unset($this->courses[$courseId]);
        return $this;
    }

    /**
     * Removes the rule with the given ID from the set.
     *
     * @param  String ruleId
     * @return CourseSet
     */
    public function removeAdmissionRule($ruleId)
    {
        unset($this->admissionRules[$ruleId]);
        return $this;
    }

    /**
     * Removes the institute with the given ID from the set.
     *
     * @param  String instituteId
     * @return CourseSet
     */
    public function removeInstitute($instituteId)
    {
        unset($this->institutes[$instituteId]);
        return $this;
    }

    /**
     * Removes the user list with the given ID from the set.
     *
     * @param  String listId
     * @return CourseSet
     */
    public function removeUserlist($listId)
    {
        unset($this->userlists[$listId]);
        return $this;
    }

    /**
     * Adds several admission rules after clearing the existing rule
     * assignments.
     *
     * @param  Array newIds
     * @return CourseSet
     */
    public function setAdmissionRules($newRules) {
        $this->admissionRules = array();
        foreach ($newRules as $newRule) {
            $this->addAdmissionRule(unserialize(html_entity_decode($newRule)));
        }
        return $this;
    }

    /**
     * Sets a seat distribution algorithm for this course set. This will only
     * have an effect in conjunction with a TimedAdmission, as the algorithm
     * needs a defined point in time where it will start.
     *
     * @param  String newAlgorithm
     * @return CourseSet
     */
    public function setAlgorithm($newAlgorithm)
    {
        try {
            $this->algorithm = new $newAlgorithm();
        } catch (Exception $e) {
        }
        return $this;
    }

    /**
     * Adds several course IDs after clearing the existing course
     * assignments.
     *
     * @param  Array newIds
     * @return CourseSet
     */
    public function setCourses($newIds) {
        $this->courses = array_fill_keys($newIds, true);
        return $this;
    }

    /**
     * Adds several institute IDs after clearing the existing institute
     * assignments.
     *
     * @param  Array newIds
     * @return CourseSet
     */
    public function setInstitutes($newIds) {
        $this->institutes = array();
        $this->addInstitutes($newIds);
        return $this;
    }

    /**
     * Set the course set's info text.
     *
     * @return CourseSet
     */
    public function setInfoText($newText)
    {
        $this->infoText = $newText;
        return $this;
    }

    /* Sets a new name for this course set.
     *
     * @param  String newName
     * @return CourseSet
     */
    public function setName($newName) {
        $this->name = $newName;
        return $this;
    }

    /**
     * Set a new value for courseset privacy.
     *
     * @param  bool $newPrivate
     * @return CourseSet
     */
    public function setPrivate($newPrivate) {
        $this->private = $newPrivate;
        return $this;
    }

    public function setSemester($newSemester) {
        $this->semester = $newSemester;
        return $this;
    }

    /**
     * Adds several user list IDs after clearing the existing user list
     * assignments.
     *
     * @param  Array newIds
     * @return CourseSet
     */
    public function setUserlists($newIds) {
        $this->userlists = array();
        foreach ($newIds as $newId) {
            $this->addUserlist($newId);
        }
        return $this;
    }

    public function store() {
        global $user;
        // Generate new ID if course set doesn't exist in DB yet.
        if (!$this->id) {
            do {
                $newid = md5(uniqid(get_class($this), true));
                $db = DBManager::get()->query("SELECT `set_id`
                    FROM `coursesets` WHERE `set_id`='.$newid.'");
            } while ($db->fetch());
            $this->id = $newid;
        }
        if ($this->isSeatDistributionEnabled() && !$this->getAlgorithm()) {
            $algorithm = new RandomAlgorithm();
            $this->setAlgorithm($algorithm);
            if (!$this->getSeatDistributionTime()) {
                $this->setAlgorithmRun($state);
            }
        }
        // Store basic data.
        $stmt = DBManager::get()->prepare("INSERT INTO `coursesets`
            (`set_id`, `user_id`, `name`, `semester`, `infotext`, `algorithm`, `algorithm_run`,
            `private`, `mkdate`, `chdate`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
            `name`=VALUES(`name`), `semester`=VALUES(`semester`), `infotext`=VALUES(`infotext`),
            `algorithm`=VALUES(`algorithm`), `algorithm_run`=VALUES(`algorithm_run`), `private`=VALUES(`private`), 
            `chdate`=VALUES(`chdate`)");
        $stmt->execute(array($this->id, $user->id, $this->name, $this->semester, $this->infoText,
            get_class($this->algorithm), $this->hasAlgorithmRun(), intval($this->private), time(), time()));
        // Delete removed institute assignments from database.
        DBManager::get()->exec("DELETE FROM `courseset_institute`
            WHERE `set_id`='".$this->id."' AND `institute_id` NOT IN ('".
            implode("', '", array_keys($this->institutes))."')");
        // Store associated institute IDs.
        foreach ($this->institutes as $institute => $associated) {
            $stmt = DBManager::get()->prepare("INSERT INTO `courseset_institute`
                (`set_id`, `institute_id`, `mkdate`)
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                `set_id`=VALUES(`set_id`)");
            $stmt->execute(array($this->id, $institute, time()));
        }
        // Delete removed course assignments from database.
        DBManager::get()->exec("DELETE FROM `seminar_courseset`
            WHERE `set_id`='".$this->id."' AND `seminar_id` NOT IN ('".
            implode("', '", array_keys($this->courses))."')");
        // Store associated course IDs.
        foreach ($this->courses as $course => $associated) {
            $stmt = DBManager::get()->prepare("INSERT INTO `seminar_courseset`
                (`set_id`, `seminar_id`, `mkdate`)
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                `seminar_id`=VALUES(`seminar_id`)");
            $stmt->execute(array($this->id, $course, time()));
        }
        // Delete removed user list assignments from database.
        DBManager::get()->exec("DELETE FROM `courseset_factorlist`
            WHERE `set_id`='".$this->id."' AND `factorlist_id` NOT IN ('".
            implode("', '", array_keys($this->userlists))."')");
        // Store associated user list IDs.
        foreach ($this->userlists as $list => $associated) {
            $stmt = DBManager::get()->prepare("INSERT INTO `courseset_factorlist`
                (`set_id`, `factorlist_id`, `mkdate`)
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                `factorlist_id`=VALUES(`factorlist_id`)");
            $stmt->execute(array($this->id, $list, time()));
        }
        // Delete removed admission rules from database.
        $stmt = DBManager::get()->query("SELECT `rule_id`, `type` FROM `courseset_rule`
            WHERE `set_id`='".$this->id."' AND `rule_id` NOT IN ('".
            implode("', '", array_keys($this->admissionRules))."')");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as $ruleData) {
            $rule = new $ruleData['type']($ruleData['rule_id']);
            $rule->delete();
        }
        // Store all rules.
        foreach ($this->admissionRules as $rule) {
            // Store each rule...
            $rule->store();
            // ... and its connection to the current course set.
            $stmt = DBManager::get()->prepare("INSERT INTO `courseset_rule`
                (`set_id`, `rule_id`, `type`, `mkdate`)
                VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                `type`=VALUES(`type`)");
            $stmt->execute(array($this->id, $rule->getId(), get_class($rule), time()));
        }
    }

    /**
     * A textual description of the current rule.
     *
     * @param bool short Show only short info without overview of assigned
     *                   courses and institutes.
     * @return String
     */
    public function toString($short=false) {
        $tpl = $GLOBALS['template_factory']->open('admission/courseset/info');
        $tpl->set_attribute('courseset', $this);
        $institutes = array();
        if (!$short) {
            foreach ($this->institutes as $id => $assigned) {
                $current = new Institute($id);
                $institutes[$id] = $current['Name'];
            }
            $courses = array();
            foreach ($this->courses as $id => $assigned) {
                $current = new Seminar($id);
                $name = ($current->getNumber() ? $current->getNumber().' | '.$current->getName() : $current->getName());
                $name .= ' (' . $current->getStartSemesterName() . ')';
                $courses[$id] = $name;
            }
            $tpl->set_attribute('institutes', $institutes);
            $tpl->set_attribute('courses', $courses);
        }
        $tpl->set_attribute('short', $short);
        return $tpl->render();
    }

    public function __toString() {
        return $this->toString();
    }

    public function isUserAllowedToAssignCourse($user_id, $course_id)
    {
        global $perm;
        $is_dozent = $perm->have_studip_perm('dozent', $course_id, $user_id);
        $is_private = $this->getUserId() == $user_id && $this->getPrivate();
        $is_correct_institute = isset($this->institutes[Course::find($course_id)->institut_id]);
        return $is_dozent && ($is_private || $is_correct_institute);
    }
    
    public function isUserAllowedToEdit($user_id)
    {
        global $perm;
        $i_am_the_boss = $perm->have_perm('root', $user_id) || $this->getUserId() == $user_id;
        if (!$i_am_the_boss && ($perm->have_perm('admin') || ($perm->have_perm('dozent') && get_config('ALLOW_DOZENT_COURSESET_ADMIN')))) {
            foreach ($this->getInstituteIds() as $one) {
                if ($perm->have_studip_perm('dozent', $one, $user_id)) {
                    $i_am_the_boss = !$this->getPrivate();
                    break;
                }
            }
        }
        return $i_am_the_boss;
    }

} /* end of class CourseSet */

?>