<?php

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/StudipCondition.class.php');
require_once('lib/classes/admission/ConditionField.class.php');

class Conditions_FieldController extends AuthenticatedController {

    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Bedingung'));
            Navigation::activateItem('/tools/coursesets');
        }
        PageLayout::addSqueezePackage('conditions');
    }

    public function configure_action() {
        $this->conditionFields = ConditionField::getAvailableConditionFields();
        if ($fieldType = Request::option('fieldtype')) {
            $this->className = $fieldType;
            $this->field = new $fieldType();
        }
    }

}

?>