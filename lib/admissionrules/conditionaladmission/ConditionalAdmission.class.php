<?php

/**
 * ConditionalAdmission.class.php
 *
 * An admission rule that specifies conditions for course admission, like
 * degree, study course or semester.
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
require_once('lib/classes/admission/UserFilter.class.php');

class ConditionalAdmission extends AdmissionRule
{
    // --- ATTRIBUTES ---

    /**
     * All conditions that must be fulfilled for successful admission.
     */
    public $conditions = array();

    public $allowed_combinations = array('ParticipantRestrictedAdmission', 'LimitedAdmission','ConditionalAdmission','TimedAdmission','PasswordAdmission','CourseMemberAdmission');

    // --- OPERATIONS ---

    /**
     * Standard constructor.
     *
     * @param  String ruleId If this rule has been saved previously, it
     *      will be loaded from database.
     * @return AdmissionRule the current object (this).
     */
    public function __construct($ruleId='', $courseSetId = '')
    {
        parent::__construct($ruleId, $courseSetId);
        $this->default_message = _("Sie erfüllen nicht die Bedingung: %s");
        if ($ruleId) {
            $this->load();
        } else {
            $this->id = $this->generateId('conditionaladmissions');
        }
        return $this;
    }

    /**
     * Adds a new UserFilter to this rule.
     *
     * @param  UserFilter condition
     * @return ConditionalAdmission
     */
    public function addCondition($condition)
    {
        $this->conditions[$condition->getId()] = $condition;
        return $this;
    }

    /**
     * Deletes the admission rule and all associated data.
     */
    public function delete() {
        parent::delete();
        // Delete rule data.
        $stmt = DBManager::get()->prepare("DELETE FROM `conditionaladmissions`
            WHERE `rule_id`=?");
        $stmt->execute(array($this->id));
        // Delete all associated conditions...
        foreach ($this->conditions as $condition) {
            $condition->delete();
        }
        // ... and their connection to this rule.
        $stmt = DBManager::get()->prepare("DELETE FROM `admission_condition`
            WHERE `rule_id`=?");
        $stmt->execute(array($this->id));
    }

    /**
     * Gets all users that are matched by thís rule.
     *
     * @return Array An array containing IDs of users who are matched by
     *      this rule.
     */
    public function getAffectedUsers()
    {
        $users = array();
        foreach ($this->condition as $condition) {
            $users = array_intersect($users, $condition->getAffectedUsers());
        }
        return $users;
    }

    /**
     * Gets all defined conditions.
     *
     * @return Array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Gets some text that describes what this AdmissionRule (or respective
     * subclass) does.
     */
    public static function getDescription() {
        return _("Über eine Menge von Bedingungen kann festgelegt werden, ".
            "wer zur Anmeldung zu den Veranstaltungen des Anmeldesets ".
            "zugelassen ist. Es muss nur eine der Bedingungen erfüllt sein, ".
            "innerhalb einer Bedingung müssen aber alle Datenfelder ".
            "zutreffen.");
    }

    /**
     * Return this rule's name.
     */
    public static function getName() {
        return _("Bedingte Anmeldung");
    }

    /**
     * Gets the template that provides a configuration GUI for this rule.
     *
     * @return String
     */
    public function getTemplate() {
        // Open generic admission rule template.
        $tpl = $GLOBALS['template_factory']->open('admission/rules/configure');
        $tpl->set_attribute('rule', $this);
        $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates/');
        // Now open specific template for this rule and insert base template.
        $tpl2 = $factory->open('configure');
        $tpl2->set_attribute('rule', $this);
        $tpl2->set_attribute('tpl', $tpl->render());
        return $tpl2->render();
    }

    /**
     * Helper function for loading data from DB. Generic AdmissionRule data is
     * loaded with the parent load() method.
     */
    public function load() {
        // Load basic data.
        $stmt = DBManager::get()->prepare("SELECT *
            FROM `conditionaladmissions` WHERE `rule_id`=? LIMIT 1");
        $stmt->execute(array($this->id));
        if ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->message = $current['message'];
            $this->startTime = $current['start_time'];
            $this->endTime = $current['end_time'];
            // Retrieve conditions.
            $stmt = DBManager::get()->prepare("SELECT *
                FROM `admission_condition` WHERE `rule_id`=?");
            $stmt->execute(array($this->id));
            $conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($conditions as $condition) {
                $currentCondition = new UserFilter($condition['filter_id']);
                $this->conditions[$condition['filter_id']] = $currentCondition;
            }
        }
    }

    /**
     * Removes the condition with the given ID from the rule.
     *
     * @param  String conditionId
     * @return ConditionalAdmission
     */
    public function removeCondition($conditionId)
    {
        $this->conditions[$conditionId]->delete();
        unset($this->conditions[$conditionId]);
        return $this;
    }

    /**
     * Checks whether the given user fulfills the configured
     * admission conditions. Only one of the conditions needs to be
     * fulfilled (logical operator OR). The fields in a condition are
     * in conjunction (logical operator AND).
     *
     * @param String $userId
     * @param String $courseId
     * @return Array Array with conditions that have failed. If array
     *               is empty, everything's all right.
     */
    function ruleApplies($userId, $courseId) {
        $failed = array();
        // Check for rule validity time frame.
        if ($this->checkTimeFrame()) {
            // Check all configured conditions.
            foreach ($this->conditions as $condition) {
                if (!$condition->isFulfilled($userId)) {
                    $failed[] = $this->getMessage($condition->toString());
                } else {
                    $failed = array();
                    break;
                }
            }
        } else {
            $failed = array();
        }
        return $failed;
    }

    /**
     * Uses the given data to fill the object values. This can be used
     * as a generic function for storing data if the concrete rule type
     * isn't known in advance.
     *
     * @param Array $data
     * @return AdmissionRule This object.
     */
    public function setAllData($data) {
        UserFilterField::getAvailableFilterFields();
        parent::setAllData($data);
        $this->conditions = array();
        foreach ($data['conditions'] as $condition) {
            $this->addCondition(unserialize($condition));
        }
        return $this;
    }

    /**
     * Helper function for storing data to DB.
     */
    public function store() {
        // Store rule data.
        $stmt = DBManager::get()->prepare("INSERT INTO `conditionaladmissions`
            (`rule_id`, `message`, `start_time`, `end_time`, `mkdate`, `chdate`)
            VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
            `message`=VALUES(`message`),
            `start_time`=VALUES(`start_time`),
            `end_time`=VALUES(`end_time`),
            `chdate`=VALUES(`chdate`)");
        $stmt->execute(array($this->id, $this->message, (int)$this->startTime,
            (int)$this->endTime, time(), time()));
        // Delete removed conditions from DB.
        $stmt = DBManager::get()->prepare("SELECT `filter_id` FROM
            `admission_condition` WHERE `rule_id`=? AND `filter_id` NOT IN ('".
            implode("', '", array_keys($this->conditions))."')");
        $stmt->execute(array($this->id));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entry) {
            $current = new UserFilter($entry['filter_id']);
            $current->delete();
        }
        DBManager::get()->exec("DELETE FROM `admission_condition`
            WHERE `rule_id`='".$this->id."' AND `filter_id` NOT IN ('".
            implode("', '", array_keys($this->conditions))."')");
        // Store all conditions.
        $queries = array();
        $parameters = array();
        foreach ($this->conditions as $condition) {
            // Store each condition...
            $condition->store();
            $queries[] = "(?, ?, ?)";
            $parameters[] = $this->id;
            $parameters[] = $condition->getId();
            $parameters[] = time();
        }
        // Store all assignments between rule and condition.
        $stmt = DBManager::get()->prepare("INSERT INTO `admission_condition`
            (`rule_id`, `filter_id`, `mkdate`)
            VALUES ".implode(",", $queries)." ON DUPLICATE KEY UPDATE
            `filter_id`=VALUES(`filter_id`)");
        $stmt->execute($parameters);
        return $this;
    }

    /**
     * A textual description of the current rule.
     *
     * @return String
     */
    public function toString() {
        $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates/');
        $tpl = $factory->open('info');
        $tpl->set_attribute('rule', $this);
        return $tpl->render();
    }

    /**
     * Validates if the given request data is sufficient to configure this rule
     * (e.g. if required values are present).
     *
     * @param  Array Request data
     * @return Array Error messages.
     */
    public function validate($data)
    {
        $errors = parent::validate($data);
        if (!$data['conditions']) {
            $errors[] = _('Es muss mindestens eine Auswahlbedingung angegeben werden.');
        }
        return $errors;
    }

    public function getMessage($condition = null)
    {
        $message = parent::getMessage();
        if ($condition) {
            return sprintf($message, $condition);
        } else {
            return $message;
        }
    }

    public function __clone()
    {
        $this->id = md5(uniqid(get_class($this)));
        $this->courseSetId = null;
        $cloned_conditions = array();
        foreach ($this->conditions as $condition) {
            $dolly = clone $condition;
            $cloned_conditions[$dolly->id] = $dolly;
        }
        $this->conditions = $cloned_conditions;
    }

} /* end of class ConditionalAdmission */

?>