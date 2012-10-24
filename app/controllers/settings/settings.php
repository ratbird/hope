<?php
/*
 * SettingsController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/messaging.inc.php';

class Settings_SettingsController extends AuthenticatedController
{
    protected $private_messages = array();

    /**
     *
     */
    public function before_filter(&$action, &$args)
    {
        // Abwärtskompatibilität, erst ab 1.1 bekannt
        if (!isset($GLOBALS['ALLOW_CHANGE_NAME'])) {
            $GLOBALS['ALLOW_CHANGE_NAME'] = TRUE;
        }

        parent::before_filter($action, $args);

        $GLOBALS['auth']->login_if(($action !== 'logout') && ($GLOBALS['auth']->auth['uid'] === 'nobody'));

        // extract username
        if (!$GLOBALS['perm']->have_perm('root') || !($username = Request::get('username'))) {
            $username = $GLOBALS['user']->username;
        } else {
            URLHelper::addLinkParam('username', $username);
        }
        $this->about = new about($username, null);
        $this->about->get_user_details();

        if (!$this->about->check) {
            $this->reportErrorWithDetails(_('Zugriff verweigert.'), array(
                _("Wahrscheinlich ist Ihre Session abgelaufen. Bitte "
                 ."nutzen Sie in diesem Fall den untenstehenden Link, "
                 ."um zurück zur Anmeldung zu gelangen.\n\n"
                 ."Eine andere Ursache kann der Versuch des Zugriffs "
                 ."auf Userdaten, die Sie nicht bearbeiten dürfen, sein. "
                 ."Nutzen Sie den untenstehenden Link, um zurück auf "
                 ."die Startseite zu gelangen."),
                sprintf(_('%s Hier%s geht es wieder zur Anmeldung beziehungsweise Startseite.'),
                        '<a href="index.php">', '</a>')
            ));
            $this->render_nothing();
            return;
        }

        $this->user       = User::findByUsername($username);
        $this->restricted = ($GLOBALS['perm']->get_profile_perm($this->user->user_id) !== 'user')
                            && ($username !== $GLOBALS['user']->username);
        $this->config     = UserConfig::get($this->user->user_id);
        $this->validator  = new email_validation_class; # Klasse zum Ueberpruefen der Eingaben
        $this->validator->timeout = 10;
        
        // Default auth plugin to standard
        if (!$this->user->auth_plugin) {
            $this->user->auth_plugin = 'standard';
        }

        PageLayout::addSqueezePackage('settings');

        if ($username != $GLOBALS['user']->username) {
            $message = sprintf(_('Daten von: %s %s (%s), Status: %s'),
                               htmlReady($this->user->Vorname),
                               htmlReady($this->user->Nachname),
                               $username,
                               $this->user->perms);
            $this->reportInfo($message);
        }

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
    }

    /**
     *
     */
    protected function check_ticket()
    {
        $ticket = Request::get('studipticket');
        if (!$ticket || !check_ticket($ticket)) {
            throw new AccessDeniedException(_('Fehler beim Zugriff. Bitte versuchen Sie es erneut.'));
        }
    }

    /**
     *
     */
    protected function populateInfobox()
    {
        if (!isset($this->infobox)) {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        parent::populateInfobox();
    }

    /**
     *
     */
    public function url_for($to/*, ...*/)
    {
        $arguments  = func_get_args();
        $parameters = is_array(end($arguments)) ? array_pop($arguments) : array();
        $url        = call_user_func_array('parent::url_for', $arguments);
        return URLHelper::getURL($url, $parameters);
    }

    /**
     *
     */
    public function get_default_template($action)
    {
        $class = get_class($this);
        $controller_name = Trails_Inflector::underscore(substr($class, 0, -10));
        return file_exists($this->dispatcher->trails_root . '/views/' . $controller_name . '.php')
            ? $controller_name
            : $controller_name . '/' . $action;
    }

    /**
     * 
     */
    public function render_nothing($text = '')
    {
        if ($this->layout) {
            $factory = $this->get_template_factory();
            $layout = $factory->open($this->layout);
            $layout->content_for_layout = $text;
            $text = $layout->render();
        }

        return parent::render_text($text);
    }

    /**
     *
     */
    public function shallChange($field, $attribute = null, $value = null)
    {
        $column = end(explode('.', $field));
        $attribute = $attribute ?: strtolower($column);

        $global_mapping = array(
            'email'    => 'ALLOW_CHANGE_EMAIL',
            'name'     => 'ALLOW_CHANGE_NAME',
            'title'    => 'ALLOW_CHANGE_TITLE',
            'username' => 'ALLOW_CHANGE_USERNAME',
        );
        
        if (isset($global_mapping[$attribute]) and !$GLOBALS[$global_mapping[$attribute]]) {
            return false;
        }

        return !($field && StudipAuthAbstract::CheckField($field, $this->user->auth_plugin))
            && !LockRules::check($this->user->user_id, $attribute)
            && (($value === null) || ($this->user->$column != $value));
    }

    /**
     *
     */
    public function verifyDialog($message, $approved, $rejected)
    {
        $template = $GLOBALS['template_factory']->open('shared/question');

        // inject tickets into arguments
        $arguments = is_array(end($approved)) ? array_pop($approved) : array();
        $arguments['studipticket'] = get_ticket();
        $approved[] = $arguments;

        $template->approvalLink    = call_user_func_array(array($this, 'url_for'), $approved);
        $template->disapprovalLink = call_user_func_array(array($this, 'url_for'), $rejected);
        $template->question        = $message;

        return $template->render();
    }

    /**
     *
     */
    public function __call($method, $arguments)
    {
        if (preg_match('/^report(Error|Info|Success)(WithDetails)?$/', $method, $match)) {
            $hash    = md5($method . serialize($arguments));
            $type    = strtolower($match[1]);
            $details = empty($match[2]) ? false : array_pop($arguments);

            $message = array_shift($arguments);
            $message = vsprintf($message, $arguments);

            $box     = $details
                     ? MessageBox::$type($message, $details)
                     : MessageBox::$type($message);

            PageLayout::postMessage($box, $hash);
            return $this;
        }
        
        throw new BadMethodCallException('Method "' . $method . '" does not exist.');
    }

    /**
     *
     */
    protected function postPrivateMessage($message)
    {
        $message = vsprintf($message, array_slice(func_get_args(), 1));

        $this->private_messages[] = trim($message);
        return $this;
    }

    /**
     *
     */
    public function after_filter($action, $args)
    {
        if ($this->restricted && count($this->private_messages) > 0) {
            setTempLanguage($this->user->user_id);

            $message = _("Ihre persönliche Seite wurde von einer Administratorin oder einem Administrator verändert.\n "
                        ."Folgende Veränderungen wurden vorgenommen:\n \n")
                        . '- ' . implode("\n- ", $this->private_messages);
            $subject = _('Systemnachricht:') . ' ' . _('Profil verändert');

            restoreLanguage();

            $messaging = new messaging;
            $messaging->insert_message($message, $this->user->username, '____%system%____', null, null, true, '', $subject);
        }

        if ($action === 'logout') {
            $GLOBALS['sess']->delete();  
            $GLOBALS['auth']->logout();
        }
        
        parent::after_filter($action, $args);
        
        if ($action === 'logout') {
            $GLOBALS['user']->set_last_action(time() - 15 * 60);
        }
    }
}
