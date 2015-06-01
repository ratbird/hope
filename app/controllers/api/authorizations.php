<?php

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/bootstrap-api.php';

/**
 *
 **/
class Api_AuthorizationsController extends AuthenticatedController
{
    /**
     *
     **/
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $GLOBALS['perm']->check('autor');

        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        Navigation::activateItem('/links/settings/api');
        PageLayout::setTabNavigation('/links/settings');
        PageLayout::setTitle(_('Applikationen'));

        $this->types = array(
            'website' => _('Website'),
            'program' => _('Herkömmliches Desktopprogramm'),
            'app'     => _('Mobile App')
        );
    }

    /**
     *
     **/
    public function index_action()
    {
        $this->consumers = RESTAPI\UserPermissions::get($GLOBALS['user']->id)->getConsumers();
        $this->types = array(
            'website' => _('Website'),
            'program' => _('Herkömmliches Desktopprogramm'),
            'app'     => _('Mobile App')
        );

        $widget = new SidebarWidget();
        $widget->setTitle(_('Informationen'));
        $widget->addElement(new WidgetElement(_('Dies sind die Apps, die Zugriff auf Ihren Account haben.')));
        Sidebar::Get()->addWidget($widget);
    }

    /**
     *
     **/
    public function revoke_action($id)
    {
        RESTAPI\Consumer\Base::find($id)->revokeAccess($GLOBALS['user']->id);
        PageLayout::postMessage(MessageBox::success(_('Der Applikation wurde der Zugriff auf Ihre Daten untersagt.')));
        $this->redirect('api/authorizations');
    }
}