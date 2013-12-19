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

        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        Navigation::activateItem('/admin/config/api');
        PageLayout::setTitle(_('API Verwaltung'));
        
#        $this->store = new OAuthConsumer;
#        $this->types = array(
#            'website' => _('Website'),
#            'program' => _('Herkömmliches Desktopprogramm'),
#            'app'     => _('Mobile App')
#        );

        // Infobox
        $this->setInfoboxImage('infobox/administration.jpg');

        if ($action !== 'index') {
            $back = sprintf('<a href="%s">%s</a>',
                           $this->url_for('admin/api'),
                           _('Zurück zur Übersicht'));
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
#        $this->consumers = $this->store->getList();
        $this->routes = API\Router::getInstance()->getRoutes(true);
    }

    /**
     *
     **/
    public function render_keys($key, $consumer = null)
    {
        if ($consumer === null) {
            $consumer = $this->store->load($key);
        }

        return array(
            'Consumer Key = ' . $consumer['consumer_key'],
            'Consumer Secret = ' . $consumer['consumer_secret'],
        );
    }

    /**
     *
     **/
    public function keys_action($key)
    {
        $details = $this->render_keys($key);

        if (Request::isXhr()) {
            $this->render_text(implode('<br>', $details));
        } else {
            PageLayout::postMessage(MessageBox::info(_('Die Schlüssel in den Details dieser Meldung sollten vertraulich behandelt werden!'), $details, true));
            $this->redirect('admin/api/#' . $key);
        }
    }

    /**
     *
     **/
    public function edit_action($key = null)
    {
        $this->consumer = $this->store->extractConsumerFromRequest($key);

        if (Request::submitted('store')) {
            $errors = $this->store->validate($this->consumer);

            if (!empty($errors)) {
                $message = MessageBox::error(_('Folgende Fehler sind aufgetreten:'), $errors);
                PageLayout::postMessage($message);
                return;
            }

            $consumer = $this->store->store($this->consumer, Request::int('enabled', 0));

            if ($key) {
                $message = MessageBox::success(_('Die Applikation wurde erfolgreich gespeichert.'));
            } else {
                $details  = $this->render_keys($key, $consumer);
                $message = MessageBox::success(_('Die Applikation wurde erfolgreich erstellt, die Schlüssel finden Sie in den Details dieser Meldung.'), $details, true);
            }
            PageLayout::postMessage($message);
            $this->redirect('admin/api/index#' . $consumer['consumer_key']);
            return;
        }

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));

        $this->id = $id;
    }

    /**
     *
     **/
    public function toggle_action($key, $state = null)
    {
        $consumer = $this->store->extractConsumerFromRequest($key);

        $state = $state === null
               ? !$consumer['enabled']
               : $state === 'on';

        $consumer = $this->store->store($consumer, $state);

        $message = $state
                 ? _('Die Applikation wurde erfolgreich aktiviert.')
                 : _('Die Applikation wurde erfolgreich deaktiviert.');

        PageLayout::postMessage(MessageBox::success($message));
        $this->redirect('admin/api/#' . $consumer['consumer_key']);
    }

    /**
     *
     **/
    public function delete_action($key)
    {
        $this->store->delete($key);
        PageLayout::postMessage(MessageBox::success(_('Die Applikation wurde erfolgreich gelöscht.')));
        $this->redirect('admin/api');
    }

    /**
     *
     **/
    public function permissions_action($consumer_id = null)
    {
        if (Request::submitted('store')) {
            $perms       = Request::getArray('permission');
            $permissions = Api\Permissions::get($consumer_id ?: 'global');

            foreach ($perms as $route => $methods) {
                foreach ($methods as $method => $granted) {
                    $permissions->set(urldecode($route), urldecode($method), (bool)$granted, true);
                }
            }
            
            $permissions->store();

            PageLayout::postMessage(MessageBox::success(_('Die Zugriffsberechtigungen wurden erfolgreich gespeichert')));
            $this->redirect($consumer_key ? 'admin/api' : 'admin/api/permissions');
            return;
        }

        $title = $consumer_id ? _('Zugriffsberechtigungen') : _('Globale Zugriffsberechtigungen');
        $title .= ' - ' . PageLayout::getTitle();
        PageLayout::setTitle($title);

        $this->consumer_id = $consumer_id;
        $this->router       = Api\Router::getInstance();
        $this->routes       = $this->router->getRoutes(true, false);
        $this->permissions  = Api\Permissions::get($consumer_id ?: 'global');
        $this->global       = $consumer_id ? Api\Permission::get('global') : false;
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