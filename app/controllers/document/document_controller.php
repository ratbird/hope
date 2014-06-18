<?php
/**
 * 
 */

require_once 'app/controllers/authenticated_controller.php';

class DocumentController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Lock context to user id
        $this->context_id = $GLOBALS['user']->id;

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;charset=windows-1252');
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        $this->userConfig = DocUsergroupConfig::getUserConfig($GLOBALS['user']->id);
        if($this->userConfig['area_close'] == 1){
            $this->redirect('document/closed/index');
        }
    }

    protected function setDialogLayout($icon = false)
    {
        $layout = $this->get_template_factory()->open('document/dialog-layout.php');
        $layout->icon = $icon;

        if (!Request::isXhr()) {
            $layout->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }

        $this->set_layout($layout);
    }
}