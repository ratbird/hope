<?php

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/AdmissionRule.class.php');

class Admission_RuleController extends AuthenticatedController {

    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Anmeldesets'));
            Navigation::activateItem('/tools/coursesets');
        }
    }

    public function configure_action($ruleType='', $ruleId='') {
        $this->ruleTypes = AdmissionRule::getAvailableAdmissionRules();
        $this->ruleType = $ruleType;
        $this->rule = new $ruleType($ruleId);
        $this->ruleTemplate = ($this->via_ajax ? 
            studip_utf8encode($this->rule->getTemplate()) : 
            $this->rule->getTemplate());
    }

    public function select_type_action() {
        $this->ruleTypes = AdmissionRule::getAvailableAdmissionRules();
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
        if ($this->via_ajax) {
            $decoded = array();
            foreach ($requestData as $name => $entry) {
                $decoded[$name] = is_array($entry) ? array_map('studip_utf8decode', $entry) : studip_utf8decode($entry);
            }
            $this->rule->setAllData($decoded);
        } else {
            $this->rule->setAllData($requestData);
        }
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