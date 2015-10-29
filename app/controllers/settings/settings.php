<?php
/**
 * SettingsController - Base controller for all setting related pages
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

require_once 'lib/messaging.inc.php';

class Settings_SettingsController extends AuthenticatedController
{
    // Stores message which shall be send to the user via email
    protected $private_messages = array();

    /**
     * Sets up the controller
     *
     * @param String $action Which action shall be invoked
     * @param Array $args Arguments passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        // Abw�rtskompatibilit�t, erst ab 1.1 bekannt
        if (!isset($GLOBALS['ALLOW_CHANGE_NAME'])) {
            $GLOBALS['ALLOW_CHANGE_NAME'] = TRUE;
        }

        parent::before_filter($action, $args);

        // Ensure user is logged in
        $GLOBALS['auth']->login_if(($action !== 'logout') && ($GLOBALS['auth']->auth['uid'] === 'nobody'));

        // extract username
        $username = Request::username('username', $GLOBALS['user']->username);
        $user     = User::findByUsername($username);

        if (!$GLOBALS['perm']->have_profile_perm('user', $user->user_id)) {
            $username = $GLOBALS['user']->username;
        } else {
            $username = $user->username;
            URLHelper::addLinkParam('username', $username);
        }

        $this->about = new about($username, null);
        $this->about->get_user_details();

        if (!$this->about->check) {
            $this->reportErrorWithDetails(_('Zugriff verweigert.'), array(
                _("Wahrscheinlich ist Ihre Session abgelaufen. Bitte "
                 ."nutzen Sie in diesem Fall den untenstehenden Link, "
                 ."um zur�ck zur Anmeldung zu gelangen.\n\n"
                 ."Eine andere Ursache kann der Versuch des Zugriffs "
                 ."auf Userdaten, die Sie nicht bearbeiten d�rfen, sein. "
                 ."Nutzen Sie den untenstehenden Link, um zur�ck auf "
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

        // Show info message if user is not on his own profile
        if ($username != $GLOBALS['user']->username) {
            $message = sprintf(_('Daten von: %s %s (%s), Status: %s'),
                               htmlReady($this->user->Vorname),
                               htmlReady($this->user->Nachname),
                               $username,
                               $this->user->perms);
            $this->reportInfo($message);
        }

        Sidebar::get()->setImage('sidebar/person-sidebar.png');

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
    }

    /**
     * Generic ticket check
     *
     * @throws AccessDeniedException if ticket is missing or invalid
     */
    protected function check_ticket()
    {
        $ticket = Request::get('studipticket');
        if (!$ticket || !check_ticket($ticket)) {
            throw new InvalidSecurityTokenException();
        }
    }

    /**
     * Switch layout if an infobox is used
     */
    protected function populateInfobox()
    {
        if (!isset($this->infobox)) {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        parent::populateInfobox();
    }

    /**
     * Adjust url_for so it imitates the parameters behaviour of URLHelper.
     * This way you can add parameters by adding an associative array as last
     * argument.
     *
     * @param mixed $to Path segments of the url (String) or url parameters
     *                  (Array)
     * @return String Generated url
     */
    public function url_for($to/*, ...*/)
    {
        $arguments  = func_get_args();
        $parameters = is_array(end($arguments)) ? array_pop($arguments) : array();
        $url        = call_user_func_array('parent::url_for', $arguments);
        return URLHelper::getURL($url, $parameters);
    }

    /**
     * Gets the default template for an action.
     *
     * @param String $action Which action was invoked
     * @return String File name of the template
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
     * Render nothing but with a layout
     *
     * @param String $text Optional nothing text
     * @return String Rendered output
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
     * Determines whether a user is permitted to change a certain value
     * and if provided, whether the value has actually changed.
     *
     * @param String $field Which db field shall change
     * @param mixed $attribute Which attribute is related (optional,
     *                         automatically guessedif missing)
     * @param mixed $value Optional new value of the field (used to determine
     *                     whether the value has actually changed)
     * @return bool Indicates whether the value shall actually change
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
     * Generic verififcation dialog
     *
     * @param String $message  Message to be displayed to the user
     * @param mixed  $approved Arguments to pass to url_for if the user
     *                         approves the question
     * @param mixed  $rejected Arguments to pass to url_for if the user
     *                         disapproves the question
     * @return String Rendered output of the verification dialog.
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
     * Enables methods like reportError, reportInfo or reportSuccess as
     * a shortcut to post messages to the layout.
     *
     * @param String $method    Name of the called method
     * @param Array  $arguments Arguments passed to the method
     * @return Object Returns $this to allow chaining
     * @throws BadMethodCallException when an unhandled method was called
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
     * Add to the private messages
     *
     * @param String $message Message to store
     * @return Object Returns $this to allow chaining
     */
    protected function postPrivateMessage($message/*, $args */)
    {
        $message = vsprintf($message, array_slice(func_get_args(), 1));

        $this->private_messages[] = trim($message);
        return $this;
    }

    /**
     * The after filter handles the sending of private messages via email, if
     * present. Also, if an action requires the user to be logged out, this is
     * accomplished here.
     *
     * @param String $action Name of the action that has been invoked
     * @param Array  $args   Arguments of the action
     */
    public function after_filter($action, $args)
    {
        if ($this->restricted && count($this->private_messages) > 0) {
            setTempLanguage($this->user->user_id);

            $message = _("Ihre pers�nliche Seite wurde von Admin ver�ndert.\n "
                        ."Folgende Ver�nderungen wurden vorgenommen:\n \n")
                        . '- ' . implode("\n- ", $this->private_messages);
            $subject = _('Systemnachricht:') . ' ' . _('Profil ver�ndert');

            restoreLanguage();

            $messaging = new messaging;
            $messaging->insert_message($message, $this->user->username, '____%system%____', null, null, true, '', $subject);
        }

        // Check whether the user should be logged out, the token is
        // neccessary since the user could reload the page and will be logged
        // out immediately after, resulting in a login/logout-loop.
        $should_logout = $action === 'logout' && $this->flash['logout-token'] === Request::get('token');

        if ($should_logout) {
            $GLOBALS['sess']->delete();
            $GLOBALS['auth']->logout();
        }

        parent::after_filter($action, $args);

        if ($should_logout) {
            $GLOBALS['user']->set_last_action(time() - 15 * 60);
        }
    }
}
