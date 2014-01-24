<?php

require_once('app/controllers/authenticated_controller.php');
require_once('lib/classes/admission/UserFilter.class.php');
require_once('lib/classes/admission/UserFilterField.class.php');

class Userfilter_FieldController extends AuthenticatedController {

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
            PageLayout::setTitle(_('Bedingung'));
            Navigation::activateItem('/tools/coursesets');
        }
        PageLayout::addSqueezePackage('userfilter');
        $this->set_content_type('text/html;charset=windows-1252');
    }

    public function configure_action() {
        $this->conditionFields = UserFilterField::getAvailableFilterFields();
        if ($fieldType = Request::option('fieldtype')) {
            $this->className = $fieldType;
            $this->field = new $fieldType();
        }
    }

}

?>