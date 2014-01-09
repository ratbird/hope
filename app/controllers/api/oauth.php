<?php

require_once 'app/controllers/studip_controller.php';
require_once 'lib/bootstrap-api.php';

/**
 *
 **/
class Api_OauthController extends StudipController
{
    /**
     *
     **/
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        # initialize Stud.IP-Session
        page_open(array('sess' => 'Seminar_Session',
                        'auth' => 'Seminar_Default_Auth',
                        'perm' => 'Seminar_Perm',
                        'user' => 'Seminar_User'));

        $this->set_layout(null);
    }

    /**
     *
     **/
    public function index_action()
    {
        $this->render_text('TODO');
    }

    /**
     *
     **/
    public function request_token_action()
    {
        try {
            $server = new OAuthServer();
            $token = $server->requestToken();
            $this->render_nothing();
        } catch (Exception $e) {
            $this->render_text($e->getMessage());
        }
    }

    /**
     *
     **/
    public function authorize_action()
    {
        global $user, $auth;

        $auth_plugin = Config::get()->OAUTH_AUTH_PLUGIN;
        if ($GLOBALS['user']->id === 'nobody' && $auth_plugin !== 'Standard' && !Request::option('sso')) {
            $params = $_GET;
            $params['sso'] = $auth_plugin;
            $this->redirect($this->url_for('api/oauth/authorize?' . http_build_query($params)));
            return;
        } else {
            $auth->login_if($user->id === 'nobody');
        }

        try {
            $consumer = RESTAPI\Consumer\Base::detectConsumer('oauth');
            if (isset($_POST['allow'])) {
                $consumer->grantAccess($GLOBALS['user']->id);

                // No oauth_callback, show the user the result of the authorization
                // ** your code here **
                PageLayout::postMessage(MessageBox::success(_('Sie haben der Applikation Zugriff auf Ihre Daten gewährt.')));
                $this->redirect('api/authorizations#' . $consumer->auth_key);
           }
        } catch (OAuthException $e) {
            // No token to be verified in the request, show a page where the user can enter the token to be verified
            // **your code here**
            die('invalid');
        }

        PageLayout::disableHeader();
        PageLayout::setTitle(sprintf(_('"%s" bittet um Zugriff'), $consumer->title));
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        $this->consumer = $consumer;
        $this->token    = Request::option('oauth_token');
    }

    /**
     *
     **/
    public function access_token_action()
    {
        $server = new OAuthServer();
        $server->accessToken();

        $this->render_nothing();
    }
}
