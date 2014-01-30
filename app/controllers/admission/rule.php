<?php

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/AdmissionRule.class.php');

class Admission_RuleController extends AuthenticatedController {

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

    public function configure_action($ruleType='', $ruleId='') {
        $this->ruleTypes = AdmissionRule::getAvailableAdmissionRules();
        $this->ruleType = $ruleType;
        $this->rule = new $ruleType($ruleId);
        $this->ruleTemplate = $this->rule->getTemplate();
    }

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

    public function delete_action($ruleType, $ruleId) {
        $rules = AdmissionRule::getAvailableAdmissionRules();
        $rule = new $ruleType($ruleId);
        $rule->delete();
    }

    public function validate_action($ruleType) {
        $rules = AdmissionRule::getAvailableAdmissionRules();
        $rule = new $ruleType($ruleId);
        $this->errors = $rule->validate(Request::getInstance());
    }

}

?>