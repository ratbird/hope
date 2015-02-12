<?php

/**
 * Admission_RuleController - Admission rules
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.0
 */

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/AdmissionRule.class.php');
require_once 'lib/classes/admission/CourseSet.class.php';

class Admission_RuleController extends AuthenticatedController {

    /**
     * @see AuthenticatedController::before_filter
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Anmeldesets'));
            Navigation::activateItem('/tools/coursesets');
        }
        $this->set_content_type('text/html;charset=windows-1252');
    }

    /**
     * Gets the template for the rule configuration form.
     *
     * @param String $ruleType Class name of the rule to configure.
     * @param String $ruleId   Optional ID of an existing rule.
     */
    public function configure_action($ruleType='', $ruleId='') {
        $this->ruleTypes = AdmissionRule::getAvailableAdmissionRules();
        $this->ruleType = $ruleType;
        $this->rule = new $ruleType($ruleId);
        $this->ruleTemplate = $this->rule->getTemplate();
    }

    /**
     * Shows a form for selecting which rule type to use.
     *
     * @param String $cs_id ID of a courseset the rule shall belong to.
     */
    public function select_type_action($cs_id = '') {
        $this->ruleTypes = AdmissionRule::getAvailableAdmissionRules();
        $this->courseset = new CourseSet($cs_id);
        $this->courseset->clearAdmissionRules();
        foreach (Request::getArray('rules') as $rule) {
            $rule = unserialize($rule);
            if ($rule instanceof AdmissionRule) {
                $this->courseset->addAdmissionRule($rule);
            }
        }
    }

    /**
     * Saves the given rule.
     *
     * @param String $ruleType The class name of the configured rule.
     * @param String $ruleId   ID of the rule to save, or empty if this is a new rule.
     */
    public function save_action($ruleType, $ruleId='') {
        CSRFProtection::verifyUnsafeRequest();
        $rules = AdmissionRule::getAvailableAdmissionRules();
        $this->rule = new $ruleType($ruleId);
        $requestData = Request::getInstance();
        // Check for start and end date and parse the String values to timestamps.
        if ($requestData['start_date'] ) {
            $parsed = date_parse($requestData['start_date'].' 00:00:00');
            $timestamp = mktime($parsed['hour'], $parsed['minute'], 0,
                $parsed['month'], $parsed['day'], $parsed['year']);
            $requestData['start_time'] = $timestamp;
        }
        if ($requestData['end_date'] ) {
            $parsed = date_parse($requestData['end_date'].' 23:59:59');
            $timestamp = mktime($parsed['hour'], $parsed['minute'], 0,
                $parsed['month'], $parsed['day'], $parsed['year']);
            $requestData['end_time'] = $timestamp;
        }
        $this->rule->setAllData($requestData);
        $this->rule->store();
    }

    /**
     * Deletes the given rule with all dependencies.
     *
     * @param String $ruleType Class name of the given rule.
     * @param String $ruleId   ID of the given rule.
     */
    public function delete_action($ruleType, $ruleId) {
        $rules = AdmissionRule::getAvailableAdmissionRules();
        $rule = new $ruleType($ruleId);
        $rule->delete();
    }

    /**
     * Validates if the values given in the current request are sufficient to
     * configure a rule of the given type.
     *
     * @param String $ruleType Class name of the rule to check.
     */
    public function validate_action($ruleType) {
        $rules = AdmissionRule::getAvailableAdmissionRules();
        $rule = new $ruleType($ruleId);
        $this->errors = $rule->validate(Request::getInstance());
    }

}

?>