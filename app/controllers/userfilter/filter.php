<?php

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/UserFilter.class.php');
require_once('lib/classes/admission/UserFilterField.class.php');

class Userfilter_FilterController extends AuthenticatedController {

    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        $this->conditionFields = UserFilterField::getAvailableFilterFields();
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Auswahlbedingungen'));
            Navigation::activateItem('/tools/coursesets');
        }
        PageLayout::addSqueezePackage('userfilter');
    }

    public function configure_action($containerId, $conditionId='') {
        $this->containerId = $containerId;
        if ($conditionId) {
            $this->condition = new UserFilter($conditionId);
        }
    }

    /**
     * Adds a condition.
     * 
     * @param  String $containerElementId HTML element to which the condition
     *                entry should be appended.
     */
    public function add_action($containerElementId) {
        $condition = new UserFilter();
        $fields = Request::getArray('field');
        $compareOps = Request::getArray('compare_operator');
        $values = Request::getArray('value');
        $data = array();
        for ($i=0 ; $i<sizeof($fields) ; $i++) {
            $current = $fields[$i];
            if ($this->conditionFields[$current]) {
                $field = new $current();
                $field->setCompareOperator($compareOps[$i]);
                $field->setValue($values[$i]);
                $condition->addField($field);
            }
        }
        $this->containerId = $containerElementId;
        $this->condition = $condition;
    }

    public function delete_action($conditionId) {
        $condition = new UserFilter($conditionId);
        $condition->delete();
        $this->render_nothing();
    }

}

?>