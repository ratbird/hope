<?php

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/bootstrap-api.php';

/**
 *
 **/
class Admin_ApiController extends AuthenticatedController
{
    /**
     *
     **/
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $GLOBALS['perm']->check('root');

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));

        Navigation::activateItem('/admin/config/api');
        PageLayout::setTitle(_('API Verwaltung'));
        
        $this->types = array(
            'website' => _('Website'),
            'desktop' => _('Herk�mmliches Desktopprogramm'),
            'app'     => _('Mobile App')
        );

        // Infobox
        $this->setInfoboxImage('infobox/administration.jpg');

        if ($action !== 'index') {
            $back = sprintf('<a href="%s">%s</a>',
                           $this->url_for('admin/api'),
                           _('Zur�ck zur �bersicht'));
            $this->addToInfobox(_('Aktionen'), $back, 'icons/16/black/arr_1left');
        }

        $new = sprintf('<a href="%s">%s</a>',
                       $this->url_for('admin/api/edit'),
                       _('Neue Applikation registrieren'));
        $this->addToInfobox(_('Aktionen'), $new, 'icons/16/black/add');

        $global = sprintf('<a href="%s">%s</a>',
                         $this->url_for('admin/api/permissions'),
                         _('Globale Zugriffseinstellungen'));
        $this->addToInfobox(_('Aktionen'), $global, 'icons/16/black/admin');

        $config = sprintf('<a href="%s">%s</a>',
                          $this->url_for('admin/api/config'),
                          _('Konfiguration'));
        $this->addToInfobox(_('Aktionen'), $config, 'icons/16/black/tools');
    }

    /**
     *
     **/
    public function index_action()
    {
        $this->consumers = API\Consumer\Base::findAll();
        $this->routes = API\Router::getInstance()->getRoutes(true);
    }

    /**
     *
     **/
    public function render_keys($id)
    {
        $consumer = API\Consumer\Base::find($id);

        return array(
            'Consumer Key = ' . $consumer->auth_key,
            'Consumer Secret = ' . $consumer->auth_secret,
        );
    }

    /**
     *
     **/
    public function keys_action($id)
    {
        $details = $this->render_keys($id);

        if (Request::isXhr()) {
            $this->render_text(implode('<br>', $details));
        } else {
            PageLayout::postMessage(MessageBox::info(_('Die Schl�ssel in den Details dieser Meldung sollten vertraulich behandelt werden!'), $details, true));
            $this->redirect('admin/api/#' . $id);
        }
    }

    /**
     *
     **/
    public function edit_action($id = null)
    {
        $consumer = $id
                  ? API\Consumer\Base::find($id)
                  : API\Consumer\Base::create(Request::option('consumer_type') ?: 'oauth');

        if (Request::submitted('store')) {
            $errors = array();

            $consumer->active = Request::int('active');
            $consumer->title  = Request::get('title');
            $consumer->contact = Request::get('contact');
            $consumer->email = Request::get('email');
            $consumer->callback = Request::get('callback');
            $consumer->url = Request::get('url');
            $consumer->type = Request::get('type');
            $consumer->commercial = Request::int('commercial');
            $consumer->notes = Request::get('notes');
            $consumer->description = Request::get('description');

            if (!empty($errors)) {
                $message = MessageBox::error(_('Folgende Fehler sind aufgetreten:'), $errors);
                PageLayout::postMessage($message);
                return;
            }

            $consumer->store();

            if ($id) {
                $message = MessageBox::success(_('Die Applikation wurde erfolgreich gespeichert.'));
            } else {
                $details  = $this->render_keys($consumer->id);
                $message = MessageBox::success(_('Die Applikation wurde erfolgreich erstellt, die Schl�ssel finden Sie in den Details dieser Meldung.'), $details, true);
            }
            PageLayout::postMessage($message);
            $this->redirect('admin/api/index#' . $consumer->id);
            return;
        }

        $this->consumer = $consumer;
        $this->id = $id;
    }

    /**
     *
     **/
    public function toggle_action($id, $state = null)
    {
        $consumer = API\Consumer\Base::find($id);

        $consumer->active = $state === null ? !$consumer->active : ($state === 'on');
        $consumer->store();

        $message = $state
                 ? _('Die Applikation wurde erfolgreich aktiviert.')
                 : _('Die Applikation wurde erfolgreich deaktiviert.');

        PageLayout::postMessage(MessageBox::success($message));
        $this->redirect('admin/api/#' . $consumer->id);
    }

    /**
     *
     **/
    public function delete_action($id)
    {
        $this->store->delete($id);
        PageLayout::postMessage(MessageBox::success(_('Die Applikation wurde erfolgreich gel�scht.')));
        $this->redirect('admin/api');
    }

    /**
     *
     **/
    public function permissions_action($consumer_id = null)
    {
        if (Request::submitted('store')) {
            $perms       = Request::getArray('permission');
            $permissions = Api\ConsumerPermissions::get($consumer_id ?: 'global');

            foreach ($perms as $route => $methods) {
                foreach ($methods as $method => $granted) {
                    $permissions->set(urldecode($route), urldecode($method), (bool)$granted, true);
                }
            }
            
            $permissions->store();

            PageLayout::postMessage(MessageBox::success(_('Die Zugriffsberechtigungen wurden erfolgreich gespeichert')));
            $this->redirect($consumer_id ? 'admin/api' : 'admin/api/permissions');
            return;
        }

        $title = $consumer_id ? _('Zugriffsberechtigungen') : _('Globale Zugriffsberechtigungen');
        $title .= ' - ' . PageLayout::getTitle();
        PageLayout::setTitle($title);

        $this->consumer_id = $consumer_id;
        $this->router      = Api\Router::getInstance();
        $this->routes      = $this->router->getRoutes(true, false);
        $this->permissions = Api\ConsumerPermissions::get($consumer_id ?: 'global');
        $this->global      = $consumer_id ? Api\ConsumerPermissions::get('global') : false;
    }

    public function config_action()
    {
        $this->config = Config::get();

        if (Request::isPost()) {
            $this->config->store('API_ENABLED', Request::int('active', 0));
            $this->config->store('API_OAUTH_AUTH_PLUGIN', Request::option('auth'));

            PageLayout::postMessage(MessageBox::success(_('Die Einstellungen wurden gespeichert.')));
            $this->redirect('admin/api/config');
        }
    }
}