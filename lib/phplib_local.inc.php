<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// phplib_local.inc.php
// This file contains several phplib classes extended for use with Stud.IP
//
// Copyright (c) 2003 Andr� Noack <noack@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


namespace Studip {
    const ENV = 'development';
}

// use default namespace for the remaining lines
namespace {

require_once 'lib/deputies_functions.inc.php';
require_once 'lib/functions.php';
require_once 'lib/navigation/Navigation.php';
require_once 'lib/navigation/AutoNavigation.php';

//setup default logger
Log::get()->setHandler($GLOBALS['TMP_PATH'] . '/studip.log');
if (Studip\ENV == 'development') {
    Log::get()->setLogLevel(Log::DEBUG);
} else {
    Log::get()->setLogLevel(Log::ERROR);
}

// set default time zone
date_default_timezone_set(@date_default_timezone_get());

// set assets url
Assets::set_assets_url($GLOBALS['ASSETS_URL']);

// globale template factory anlegen
require_once 'vendor/flexi/lib/flexi.php';
$GLOBALS['template_factory'] =
    new Flexi_TemplateFactory($STUDIP_BASE_PATH . '/templates');

// set default exception handler
function studip_default_exception_handler($exception) {
    require_once('lib/visual.inc.php');

    if ($exception instanceof AccessDeniedException) {
        $status = 403;
        $template = 'access_denied_exception';
    } else if ($exception instanceof CheckObjectException) {
        $status = 403;
        $template = 'check_object_exception';
    } else {
        $status = 500;
        error_log($exception->__toString());
        $template = 'unhandled_exception';
    }

    header('HTTP/1.1 ' . $status . ' ' . $exception->getMessage());

    // ajax requests return JSON instead
    // re-use the http status code determined above
    if (!strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest')) {
        header('Content-Type: application/json; charset=UTF-8');
        $template = 'json_exception';
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    try {
        $args = compact('exception', 'status');
        ob_start();
        echo $GLOBALS['template_factory']->render($template, $args);
    } catch (Exception $e) {
        ob_end_clean();
        echo 'Error: ' . htmlReady($e->getMessage());
    }
    exit;
}

// command line or http request?
if (isset($_SERVER['REQUEST_METHOD'])) {
    set_exception_handler('studip_default_exception_handler');
}

// set default pdo connection
DBManager::getInstance()
  ->setConnection('studip',
                  'mysql:host='.$GLOBALS['DB_STUDIP_HOST'].
                  ';dbname='.$GLOBALS['DB_STUDIP_DATABASE'],
                  $GLOBALS['DB_STUDIP_USER'],
                  $GLOBALS['DB_STUDIP_PASSWORD']);

// set slave connection
if (isset($GLOBALS['DB_STUDIP_SLAVE_HOST'])) {
    try {
        DBManager::getInstance()
            ->setConnection('studip-slave',
                            'mysql:host='.$GLOBALS['DB_STUDIP_SLAVE_HOST'].
                            ';dbname='.$GLOBALS['DB_STUDIP_SLAVE_DATABASE'],
                            $GLOBALS['DB_STUDIP_SLAVE_USER'],
                            $GLOBALS['DB_STUDIP_SLAVE_PASSWORD']);
    } catch (PDOException $exception) {
        // if connection to slave fails, fall back to master instead
        DBManager::getInstance()->aliasConnection('studip', 'studip-slave');
    }
} else {
    DBManager::getInstance()->aliasConnection('studip', 'studip-slave');
}
/**
 * @deprecated
 */
class DB_Seminar extends DB_Sql {
    function DB_Seminar($query = false){
        $this->Host = $GLOBALS['DB_STUDIP_HOST'];
        $this->Database = $GLOBALS['DB_STUDIP_DATABASE'];
        $this->User = $GLOBALS['DB_STUDIP_USER'];
        $this->Password = $GLOBALS['DB_STUDIP_PASSWORD'];
        parent::DB_Sql($query);
    }
}

//software version - please leave it as it is!
$SOFTWARE_VERSION = '2.4.alpha-svn';

// set dummy navigation until db is ready
Navigation::setRootNavigation(new Navigation(''));

// set up default page layout
PageLayout::initialize();



require_once 'lib/msg.inc.php';
require_once('lib/language.inc.php');
require_once('lib/classes/auth_plugins/StudipAuthAbstract.class.php');

//Besser hier globale Variablen definieren...
$GLOBALS['_fullname_sql'] = array();
$GLOBALS['_fullname_sql']['full'] = "TRIM(CONCAT(title_front,' ',Vorname,' ',Nachname,IF(title_rear!='',CONCAT(', ',title_rear),'')))";
$GLOBALS['_fullname_sql']['full_rev'] = "TRIM(CONCAT(Nachname,', ',Vorname,IF(title_front!='',CONCAT(', ',title_front),''),IF(title_rear!='',CONCAT(', ',title_rear),'')))";
$GLOBALS['_fullname_sql']['no_title'] = "CONCAT(Vorname ,' ', Nachname)";
$GLOBALS['_fullname_sql']['no_title_rev'] = "CONCAT(Nachname ,', ', Vorname)";
$GLOBALS['_fullname_sql']['no_title_short'] = "CONCAT(Nachname,', ',UCASE(LEFT(TRIM(Vorname),1)),'.')";
$GLOBALS['_fullname_sql']['no_title_motto'] = "CONCAT(Vorname ,' ', Nachname,IF(motto!='',CONCAT(', ',motto),''))";
$GLOBALS['_fullname_sql']['full_rev_username'] = "TRIM(CONCAT(Nachname,', ',Vorname,IF(title_front!='',CONCAT(', ',title_front),''),IF(title_rear!='',CONCAT(', ',title_rear),''),' (',username,')'))";

//Initialize $SEM_TYPE and $SEM_CLASS arrays
require_once 'lib/classes/SemClass.class.php';
require_once 'lib/classes/SemType.class.php';
$GLOBALS['SEM_CLASS'] = SemClass::getClasses();
$GLOBALS['SEM_TYPE'] = SemType::getTypes();

// set up global navigation
require_once 'lib/navigation/StudipNavigation.php';

Navigation::setRootNavigation(new StudipNavigation(''));

/*class for config; load config in globals (should be deprecated in future)
----------------------------------------------------------------*/
Config::GetInstance()->extractAllGlobal(FALSE);

/* set default umask to a sane value */
umask(022);

/*mail settings
----------------------------------------------------------------*/
if($GLOBALS['MAIL_TRANSPORT']){
    $mail_transporter_name = strtolower($GLOBALS['MAIL_TRANSPORT']) .'_message';
} else {
    $mail_transporter_name = 'smtp_message';
}
include 'vendor/email_message/email_message.php';
include 'vendor/email_message/' . $mail_transporter_name . '.php';
$mail_transporter_class = $mail_transporter_name . '_class';
$mail_transporter = new $mail_transporter_class;
if($mail_transporter_name == 'smtp_message'){
    include 'vendor/email_message/smtp.php';
    $mail_transporter->localhost = ($GLOBALS['MAIL_LOCALHOST'] == "") ? $_SERVER["SERVER_NAME"] : $GLOBALS['MAIL_LOCALHOST'];
    $mail_transporter->smtp_host = ($GLOBALS['MAIL_HOST_NAME'] == "") ? $_SERVER["SERVER_NAME"] : $GLOBALS['MAIL_HOST_NAME'];
}
$mail_transporter->default_charset = 'WINDOWS-1252';
$mail_transporter->SetBulkMail((int)$GLOBALS['MAIL_BULK_DELIVERY']);
StudipMail::setDefaultTransporter($mail_transporter);
unset($mail_transporter);

class Seminar_CT_Sql extends CT_Sql {
    var $database_table = PHPLIB_SESSIONDATA_TABLE; // and find our session data in this table.
}


class Seminar_Session extends Session {
    var $classname = "Seminar_Session";

    var $cookiename     = "Seminar_Session"; // defaults to classname
    var $magic    = "sdfghjdfdf";      // ID seed
    var $mode      = "cookie";    // We propagate session IDs with cookies
    var $fallback_mode  = "cookie";
    var $lifetime       = 0;         // 0 = do session cookies, else minutes
    var $that_class     = "Seminar_CT_Sql"; // name of data storage container
    var $gc_probability = 2;
    var $allowcache = "nocache";
    var $cookie_secure = false;
    var $cookie_httponly = true;

    /**
     * Returns true, if the current session is valid and belongs to an
     * authenticated user. Does not start a session.
     *
     * @static
     * @return bool
     */
    function is_current_session_authenticated(){
        return Seminar_Session::get_current_session_state() == 'authenticated';
    }

    /**
     * Returns the state of the current session. Does not start a session.
     * possible return values:
     * 'authenticated' - session is valid and user is authenticated
     * 'nobody' - session is valid, but user is not authenticated
     * false - no valid session
     *
     * @static
     * @return string|false
     */
    function get_current_session_state(){
        static $current_session_state = null;
        if(!is_null($current_session_state)){
            return $current_session_state;
        }
        $state = false;
        if(is_object($GLOBALS['user'])) {
            $state = in_array($GLOBALS['user']->id, array('nobody','form')) ? 'nobody' : 'authenticated';
        } else {
            $sess = $GLOBALS['sess'];
            if(!is_object($sess)){
                $sess = new Seminar_Session();
            }
            $sid = $_COOKIE[$sess->cookiename];
            if($sid){
                $session_vars = Seminar_Session::get_session_vars($sid);
                $session_auth = $session_vars['auth']->auth;
                if($session_auth['uid'] && !in_array($session_auth['uid'], array('nobody','form'))) {
                    $state = 'authenticated';
                } else {
                    $state = in_array($session_auth['uid'], array('nobody','form')) ? 'nobody' : false;
                }
            }
        }
        return ($current_session_state = $state);
    }

    /**
     * returns a SessionDecoder object containing the session variables
     * for the given session id
     *
     * @static
     * @param string $sid a session id
     * @return SessionDecoder
     */
    function get_session_vars($sid){
        $sess = $GLOBALS['sess'];
        if(!is_object($sess)){
            $sess = new Seminar_Session();
        }
        $storage_class = $sess->that_class;
        $storage = new $storage_class();
        $storage->ac_start();
        return new SessionDecoder($storage->ac_get_value($sid));
    }

    /**
     * returns a random string token for XSRF prevention
     * the string is stored in the session
     *
     * @static
     * @return string
     */
    function get_ticket(){
        static $studipticket;
        if (!$studipticket){
            $studipticket = $_SESSION['last_ticket'] = md5(uniqid('studipticket',1));
        }
        return $studipticket;
    }

    /**
     * checks the given string token against the one stored
     * in the session
     *
     * @static
     * @param string $studipticket
     * @return bool
     */
    function check_ticket($studipticket){
        $check = (isset($_SESSION['last_ticket']) && $_SESSION['last_ticket'] == $studipticket);
        $_SESSION['last_ticket'] = null;
        return $check;
    }


    function Seminar_Session(){
        $this->cookie_path = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'];
        if (method_exists($this, 'Session')){
            $this->Session();
        }
    }

    //erweiterter Garbage Collector
    function gc() {
        //bail out if cronjob activated and not called in cli context
        if (Config::getInstance()->getValue('CRONJOBS_ENABLE')
                && ($task = array_pop(CronjobTask::findByClass('SessionGcJob')))
                && count($task->schedules->findBy('active', 1))
                && PHP_SAPI !== 'cli') {
            return false;
        }    //weiter mit gc() in der Super Klasse
        parent::gc();
    }
}

class Seminar_User {
    public $cfg = null; //UserConfig object
    private $user = null; //User object

    function __construct($uid = null)
    {
        if ($uid) {
            $this->user = User::find($uid);
            $this->cfg = UserConfig::get($uid);
            if (!isset($this->user)) {
                $this->user = new User();
                $this->user->user_id = 'nobody';
            }
        }
    }

    function get_last_action()
    {
        if ($this->id && $this->id != 'nobody') {
            $stmt = DBManager::get()->prepare("SELECT last_lifesign FROM user_online WHERE user_id = ?");
            $stmt->execute(array($this->id));
            return $stmt->fetchColumn();
        }
    }

    function set_last_action($timestamp = 0)
    {
        if ($this->id && $this->id != 'nobody') {
            if ($timestamp <= 0) {
                $timestamp = time();
            }
            try {
                $stmt = DBManager::get()->prepare("REPLACE INTO user_online (user_id,last_lifesign) VALUES (?,?)");
                $stmt->execute(array($this->id, $timestamp));
            } catch (PDOException $e) {
                require_once 'lib/migrations/db_schema_version.php';
                $version = new DBSchemaVersion('studip');
                if ($version->get() < 98) {
                    Log::ALERT('Seminar_User::set_last_action() failed. Check migration no. 98!');
                } else {
                    throw $e;
                }
            }
            return $stmt->rowCount();
        }
    }

    function delete()
    {
        if ($this->id && $this->id != 'nobody') {
            $stmt = DBManager::get()->prepare("DELETE FROM user_online WHERE user_id = ?");
            $stmt->execute(array($this->id));
            return $stmt->rowCount();
        }
    }

    function __get($field)
    {
        if ($field == 'id') {
            return $this->user->user_id;
        }
        return $this->user->$field;
    }

    function __set($field, $value)
    {
        return null;
    }

    function __isset($field)
    {
        return isset($this->user->$field);
    }

    function getFullName($format = 'full')
    {
        return $this->user->getFullName($format);
    }
    /**
     *
     * @deprecated
     */
    function register()
    {
        trigger_error('deprecated use of ' . __METHOD__, E_USER_NOTICE);
    }

    /**
     *
     * @deprecated
     */
    function is_registered()
    {
        trigger_error('deprecated use of ' . __METHOD__, E_USER_NOTICE);
    }
    /**
     *
     * @deprecated
     */
    function unregister()
    {
        trigger_error('deprecated use of ' . __METHOD__, E_USER_NOTICE);
    }
}


//
// Seminar_Challenge_Crypt_Auth: Keep passwords in md5 hashes rather
//             than cleartext in database
// Author: Jim Zajkowski <jim@jimz.com>

class Seminar_Auth extends Auth {
    var $classname      = "Seminar_Auth";

    var $lifetime       =  0;

    var $magic    = "Fdfglkdfsg";  // Challenge seed
    var $error_msg = "";

    //constructor
    function Seminar_Auth() {
    }

    function login_if($ok){
        if ($ok){
            parent::login_if($ok);
            if (is_object($GLOBALS['user'])){
                $GLOBALS['user'] = new Seminar_User($this->auth['uid']);
            }
        }
        return true;
    }

    function is_authenticated(){

        $cfg = Config::GetInstance();
        //check if the user got kicked meanwhile, or if user is locked out
        if ($this->auth['uid'] && !in_array($this->auth['uid'], array('form','nobody'))) {
            $user = $GLOBALS['user']->id == $this->auth['uid'] ? $GLOBALS['user'] : User::find($this->auth['uid']);
            if (!$user->username || $user->locked) {
                $this->unauth();
            }
        } elseif ($cfg->getValue('MAINTENANCE_MODE_ENABLE') && Request::username('loginname')) {
            $user = User::findByUsername(Request::username('loginname'));
        }
        if ($cfg->getValue('MAINTENANCE_MODE_ENABLE') && $user->perms != 'root') {
            $this->unauth();
            throw new AccessDeniedException(_("Das System befindet sich im Wartungsmodus. Zur Zeit ist kein Zugriff m�glich."));
        }
        return parent::is_authenticated();
    }

    function auth_preauth() {
        // is Single Sign On activated?
        if ( ($provider = Request::option('sso')) ) {
            // then do login
            if ( ($authplugin = StudipAuthAbstract::GetInstance($provider)) ) {
                $authplugin->authenticateUser("","","");
                if ($authplugin->getUser()){
                    $uid = $authplugin->getStudipUserid($authplugin->getUser());
                    $user = User::find($uid);
                    $this->auth["jscript"] = true;
                    $this->auth["perm"]  = $user->perms;
                    $this->auth["uname"] = $user->username;
                    $this->auth["auth_plugin"]  = $user->auth_plugin;
                    $this->auth_set_user_settings($user);
                    return $uid;
                }
            } else {
                return false;
            }
        }
        // end of single sign on
    }

    function auth_loginform() {
        // first of all init I18N because seminar_open is not called here...
        require_once('lib/visual.inc.php');
        require_once('config.inc.php');

        global $_language_path;

        // set up dummy user environment
        if($GLOBALS['user']->id !== 'nobody') {
            $GLOBALS['user'] = new Seminar_User('nobody');
            $GLOBALS['perm'] = new Seminar_Perm();
            $GLOBALS['auth'] = $this;
        }

        if (!($_SESSION['_language'])) {
            $_SESSION['_language'] = get_accepted_languages();
        }
        if (!$_SESSION['_language']) {
            $_SESSION['_language'] = $GLOBALS['DEFAULT_LANGUAGE'];
        }
        // init of output via I18N
        $_language_path = init_i18n($_SESSION['_language']);

        // load the default set of plugins
        PluginEngine::loadPlugins();

        if (Request::get('loginname') && !$_COOKIE[$GLOBALS['sess']->name]) {
            $login_template = $GLOBALS['template_factory']->open('nocookies');
        } else if (isset($this->need_email_activation)) {
            $login_template = $GLOBALS['template_factory']->open('login_emailactivation');
            $login_template->set_attribute('uid', $this->need_email_activation);
        } else {
            unset($_SESSION['semi_logged_in']); // used by email activation
            $login_template = $GLOBALS['template_factory']->open('loginform');
            $login_template->set_attribute('loginerror', (isset($this->auth["uname"]) && $this->error_msg));
            $login_template->set_attribute('error_msg', $this->error_msg);
            $login_template->set_attribute('uname', (isset($this->auth["uname"]) ? $this->auth["uname"] : Request::username('loginname')));
            $login_template->set_attribute('self_registration_activated', $GLOBALS['ENABLE_SELF_REGISTRATION']);
        }
        PageLayout::setHelpKeyword('Basis.AnmeldungLogin');
        $header_template = $GLOBALS['template_factory']->open('header');
        $header_template->current_page = _('Login');
        $header_template->link_params = array('cancel_login' => 1);

        include 'lib/include/html_head.inc.php';
        echo $header_template->render();
        echo $login_template->render();
        include 'lib/include/html_end.inc.php';
        page_close();
    }

    function auth_validatelogin() {
        global $_language_path;

        //prevent replay attack
        if (!Seminar_Session::check_ticket(Request::option('login_ticket'))){
            return false;
        }

        // check for direct link
        if (!($_SESSION['_language']) || $_SESSION['_language'] == "") {
            $_SESSION['_language'] = get_accepted_languages();
        }

        $_language_path = init_i18n($_SESSION['_language']);


        $this->auth["uname"] = Request::get('loginname');   // This provides access for "loginform.ihtml"
        $this->auth["jscript"] = Request::get('resolution') != "";
        $this->auth['devicePixelRatio'] = Request::int('device_pixel_ratio');

        $check_auth = StudipAuthAbstract::CheckAuthentication(Request::get('loginname'),Request::get('password'),$this->auth['jscript']);

        if ($check_auth['uid']) {
            $uid = $check_auth['uid'];
            if ($check_auth['need_email_activation'] == $uid) {
                $this->need_email_activation = $uid;
                $_SESSION['semi_logged_in'] = $uid;
                return false;
            }
            $user = User::find($uid);
            $this->auth["perm"]  = $user->perms;
            $this->auth["uname"] = $user->username;
            $this->auth["auth_plugin"]  = $user->auth_plugin;
            $this->auth_set_user_settings($user);
            return $uid;
        } else {
            $this->error_msg = $check_auth['error'];
            return false;
        }
    }

    function auth_set_user_settings($user) {
        $divided = explode("x", Request::get('resolution'));
        $this->auth["xres"] = ($divided[0] != 0) ? (int)$divided[0] : 1024; //default
        $this->auth["yres"] = ($divided[1] != 0) ? (int)$divided[1] : 768; //default
        // Change X-Resulotion on Multi-Screen Systems (as Matrox Graphic-Adapters are)
        if (($this ->auth["xres"] / $this ->auth["yres"]) > 2){
            $this->auth["xres"] = $this->auth["xres"] /2;
        }
        $user = User::toObject($user);
        //restore user-specific language preference
        if ($user->preferred_language) {
            // we found a stored setting for preferred language
            $_SESSION['_language'] = $user->preferred_language;
        }
    }
}

class Seminar_Default_Auth extends Seminar_Auth {
    var $classname = "Seminar_Default_Auth";

    var $nobody    = true;

    function Seminar_Default_Auth(){
        Seminar_Auth::Seminar_Auth();
    }
}


class Seminar_Register_Auth extends Seminar_Auth {
    var $classname = "Seminar_Register_Auth";
    var $magic     = "dsdfjhgretha";  // Challenge seed

    var $mode      = "reg";
    var $error_msg = ""; // Was l�uft falsch bei der Registrierung ?

    function auth_registerform() {

        require_once('lib/visual.inc.php');

        // set up dummy user environment
        if($GLOBALS['user']->id !== 'nobody') {
            $GLOBALS['user'] = new Seminar_User('nobody');
            $GLOBALS['perm'] = new Seminar_Perm();
            $GLOBALS['auth'] = $this;
        }
        // set up user session
        include 'lib/seminar_open.php';

        if (!$_COOKIE[$GLOBALS['sess']->name]) {
            $register_template = $GLOBALS['template_factory']->open('nocookies');
        } else {
            $register_template = $GLOBALS['template_factory']->open('registerform');
            $register_template->set_attribute('validator',  new email_validation_class());
            $register_template->set_attribute('error_msg', $this->error_msg);
            $register_template->set_attribute('username', Request::get('username'));
            $register_template->set_attribute('Vorname', Request::get('Vorname'));
            $register_template->set_attribute('Nachname', Request::get('Nachname'));
            $register_template->set_attribute('Email', Request::get('Email'));
            $register_template->set_attribute('title_front', Request::get('title_front'));
            $register_template->set_attribute('title_rear', Request::get('title_rear'));
            $register_template->set_attribute('geschlecht', Request::int('geschlecht', 0));
        }
        PageLayout::setHelpKeyword('Basis.AnmeldungRegistrierung');
        $header_template = $GLOBALS['template_factory']->open('header');
        $header_template->current_page = _('Registrierung');

        include 'lib/include/html_head.inc.php';
        echo $header_template->render();
        echo $register_template->render();
        include 'lib/include/html_end.inc.php';
    }

    function auth_doregister() {
        global $_language_path;

        $this->error_msg = "";

        // check for direct link to register2.php
        if (!$_SESSION['_language'] || $_SESSION['_language'] == "") {
            $_SESSION['_language'] = get_accepted_languages();
        }

        $_language_path = init_i18n($_SESSION['_language']);

        $this->auth["uname"] = Request::username('username');                 // This provides access for "crcregister.ihtml"

        $validator=new email_validation_class;  // Klasse zum Ueberpruefen der Eingaben
        $validator->timeout=10;                                 // Wie lange warten wir auf eine Antwort des Mailservers?

        if (!Seminar_Session::check_ticket(Request::option('login_ticket'))){
            return false;
        }

        $username = trim(Request::get('username'));
        $Vorname = trim(Request::get('Vorname'));
        $Nachname = trim(Request::get('Nachname'));

        // accept only registered domains if set
        $cfg = Config::GetInstance();
        $email_restriction = $cfg->getValue('EMAIL_DOMAIN_RESTRICTION');
        if ($email_restriction) {
            $Email = trim(Request::get('Email')) . '@' . trim(Request::get('emaildomain'));
        } else {
            $Email = trim(Request::get('Email'));
        }

        if (!$validator->ValidateUsername($username))
        {
            $this->error_msg=$this->error_msg. _("Der gew�hlte Benutzername ist zu kurz!") . "<br>";
            return false;
        }                                                       // username syntaktisch falsch oder zu kurz
        // auf doppelte Vergabe wird weiter unten getestet.

        if (!$validator->ValidatePassword(Request::quoted('password')))
        {
            $this->error_msg=$this->error_msg. _("Das Passwort ist zu kurz!") . "<br>";
            return false;
        }

        if (!$validator->ValidateName($Vorname))
        {
            $this->error_msg=$this->error_msg. _("Der Vorname fehlt oder ist unsinnig!") . "<br>";
            return false;
        }              // Vorname nicht korrekt oder fehlend
        if (!$validator->ValidateName($Nachname))
        {
            $this->error_msg=$this->error_msg. _("Der Nachname fehlt oder ist unsinnig!") . "<br>";
            return false;              // Nachname nicht korrekt oder fehlend
        }
        if (!$validator->ValidateEmailAddress($Email))
        {
            $this->error_msg=$this->error_msg. _("Die E-Mail-Adresse fehlt oder ist falsch geschrieben!") . "<br>";
            return false;
        }              // E-Mail syntaktisch nicht korrekt oder fehlend

        $REMOTE_ADDR=$_SERVER["REMOTE_ADDR"];
        $Zeit=date("H:i:s, d.m.Y",time());

        if (!$validator->ValidateEmailHost($Email)) {     // Mailserver nicht erreichbar, ablehnen
            $this->error_msg=$this->error_msg. _("Der Mailserver ist nicht erreichbar, bitte �berpr�fen Sie, ob Sie E-Mails mit der angegebenen Adresse verschicken und empfangen k�nnen!") . "<br>";
            return false;
        } else {                      // Server ereichbar
            if (!$validator->ValidateEmailBox($Email)) {    // aber user unbekannt. Mail an abuse!
                StudipMail::sendAbuseMessage("Register", "Emailbox unbekannt\n\nUser: $username\nEmail: $Email\n\nIP: $REMOTE_ADDR\nZeit: $Zeit\n");
                $this->error_msg=$this->error_msg. _("Die angegebene E-Mail-Adresse ist nicht erreichbar, bitte �berpr�fen Sie Ihre Angaben!") . "<br>";
                return false;
            } else {
                ;                        // Alles paletti, jetzt kommen die Checks gegen die Datenbank...
            }
        }

        $check_uname = StudipAuthAbstract::CheckUsername($username);

        if ($check_uname['found']){
            //   error_log("username schon vorhanden", 0);
            $this->error_msg = $this->error_msg. _("Der gew�hlte Benutzername ist bereits vorhanden!") . "<br>";
            return false;                  // username schon vorhanden
        }

        if (count(User::findBySQL("Email LIKE " . DbManager::get()->quote($Email)))) {
            $this->error_msg=$this->error_msg. _("Die angegebene E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet. Sie m�ssen eine andere E-Mail-Adresse angeben!") . "<br>";
            return false;                  // Email schon vorhanden
        }

        // alle Checks ok, Benutzer registrieren...
        $new_user = new User();
        $new_user->username = $username;
        $new_user->perms = 'user';
        $new_user->password = md5(Request::get('password'));
        $new_user->vorname = $Vorname;
        $new_user->nachname = $Nachname;
        $new_user->email = $Email;
        $new_user->geschlecht = Request::int('geschlecht');
        $new_user->title_front = trim(Request::get('title_front', Request::get('title_front_chooser')));
        $new_user->title_rear = trim(Request::get('title_rear', Request::get('title_rear_chooser')));
        $new_user->store();
        if ($new_user->user_id) {
            // Abschicken der Bestaetigungsmail
            $to = $Email;
            $secret= md5("$new_user->user_id:$this->magic");
            $url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . "email_validation.php?secret=" . $secret;
            $mail = new StudipMail();
            $abuse = $mail->getReplyToEmail();
            // include language-specific subject and mailbody
            include_once("locale/$_language_path/LC_MAILS/register_mail.inc.php");
            $mail->setSubject($subject)
            ->addRecipient($to)
            ->setBodyText($mailbody)
            ->send();
            $this->auth["perm"] = $new_user->perms;
            return $new_user->user_id;
        }
    }
}



class Seminar_Perm extends Perm {
    var $classname = "Seminar_Perm";

    var $permissions = array(
    "user"       => 1,
    "autor"      => 3,
    "tutor"      => 7,
    "dozent"     => 15,
    "admin"      => 31,
    "root"       => 63
    );
    var $studip_perms = array();
    var $fak_admins = array();

    function perm_invalid($does_have, $must_have) {
        if ($GLOBALS['user']->id == 'nobody') {
            $message = _('Sie sind nicht im System angemeldet und k�nnen daher nicht auf diesen Teil des Systems zugreifen. Um den vollen Funktionsumfang des Systems benutzen zu k�nnen, m�ssen Sie sich mit Ihrem Nutzernamen und Passwort anmelden.');
        } else {
            $message = _('Sie haben keine ausreichende Berechtigung, um auf diesen Teil des Systems zuzugreifen.');
        }
        throw new AccessDeniedException($message);
    }

    function get_perm($user_id = false) {
        global $user;
        if (!$user_id) $user_id = $user->id;
        if ($user_id && $user_id == $user->id) {
            return $user->perms;
        } else if ($user_id && isset($this->studip_perms['studip'][$user_id])) {
            return $this->studip_perms['studip'][$user_id];
        } else if ($user_id && $user_id !== 'nobody') {
            $db = DbManager::get();
            $perms = $db->query("SELECT perms FROM auth_user_md5 WHERE user_id = " . $db->quote($user_id))->fetchColumn();
            return $this->studip_perms['studip'][$user_id] = $perms;
        }
    }

    function have_perm($perm, $user_id = false) {

        $pageperm = explode(",", $perm);
        $userperm = explode(",", $this->get_perm($user_id));

        list($ok0, $pagebits) = $this->permsum($pageperm);
        list($ok1, $userbits) = $this->permsum($userperm);

        $has_all = (($userbits & $pagebits) == $pagebits);
        if (!($has_all && $ok0 && $ok1) ) {
            return false;
        } else {
            return true;
        }
    }


    function get_studip_perm($range_id, $user_id = false) {

        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }

        if (!isset($this->studip_perms[$range_id][$user_id])) {
            $this->studip_perms[$range_id][$user_id] = $this->get_uncached_studip_perm($range_id, $user_id);
        }
        return $this->studip_perms[$range_id][$user_id];
    }

    function get_uncached_studip_perm($range_id, $user_id) {
        global $user;
        $db = DBManager::get();
        $status = false;
        if ($user_id && $user_id == $user->id) {
            $user_perm = $user->perms;
        } else {
            $user_perm = $this->get_perm($user_id);
            if (!$user_perm) {
                return false;
            }
        }
        if ($user_perm == "root") {
            return "root";
        } elseif ($user_perm == "admin") {
            $st = $db->prepare("SELECT seminare.Seminar_id
                          FROM user_inst
                          LEFT JOIN seminare USING (Institut_id)
                          WHERE inst_perms='admin' AND user_id = ? AND seminare.Seminar_id = ? LIMIT 1");
            $st->execute(array($user_id, $range_id));
            if ($st->fetchColumn()) {
                $status = "admin";
            } else {
               $st = $db->prepare("SELECT Seminar_id FROM user_inst a
                            LEFT JOIN Institute b ON(a.Institut_id=b.Institut_id AND b.Institut_id=b.fakultaets_id)
                            LEFT JOIN Institute c ON (b.Institut_id=c.fakultaets_id)
                            LEFT JOIN seminare d ON (d.Institut_id=c.Institut_id)
                            WHERE a.user_id = ? AND a.inst_perms='admin' AND d.Seminar_id = ? LIMIT 1");
               $st->execute(array($user_id, $range_id));
                if ($st->fetchColumn()) {
                    $status = "admin";
                } else {
                    $st = $db->prepare("SELECT a.Institut_id FROM user_inst a
                                LEFT JOIN Institute b ON(a.Institut_id=b.fakultaets_id)
                                WHERE user_id = ? AND a.inst_perms='admin'
                                AND b.Institut_id = ? LIMIT 1");
                    $st->execute(array($user_id, $range_id));
                    if ($st->fetchColumn()) {
                        $status = "admin";
                    }
                }
            }
        }

        if ($status) {
            return $status;
        }

        if (get_config('DEPUTIES_ENABLE') && isDeputy($user_id, $range_id)) {
            if ($_SESSION['seminar_change_view_'.$range_id]) {
                $status = $_SESSION['seminar_change_view_'.$range_id];
            } else {
                $status = 'dozent';
            }
        } else {
            $st = $db->prepare("SELECT status FROM seminar_user
                          WHERE user_id = ? AND Seminar_id = ?");
            $st->execute(array($user_id, $range_id));
            if ($status = $st->fetchColumn()) {
                if (in_array($status, words('dozent tutor')) && isset($_SESSION['seminar_change_view_'.$range_id])) {
                    $status = $_SESSION['seminar_change_view_'.$range_id];
                }
            } else {
                $st = $db->prepare("SELECT inst_perms FROM user_inst
                              WHERE user_id = ? AND Institut_id = ?");
                $st->execute(array($user_id, $range_id));
                $status = $st->fetchColumn();
            }
        }
        return $status;
    }

    function have_studip_perm($perm, $range_id, $user_id = false) {

        $pageperm = explode(",", $perm);
        $userperm = explode(",", $this->get_studip_perm($range_id, $user_id));

        list ($ok0, $pagebits) = $this->permsum($pageperm);
        list ($ok1, $userbits) = $this->permsum($userperm);

        $has_all = (($userbits & $pagebits) == $pagebits);

        if (!($has_all && $ok0 && $ok1) ) {
            return false;
        } else {
            return true;
        }
    }

    function get_profile_perm($range_id, $user_id = false) {

        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }

        if (!isset($this->studip_perms[$range_id][$user_id])) {
            $this->studip_perms[$range_id][$user_id] = $this->get_uncached_profile_perm($range_id, $user_id);
        }
        return $this->studip_perms[$range_id][$user_id];
    }

    function get_uncached_profile_perm($range_id, $user_id) {

        $status = false;

        if ($range_id == $user_id && $this->have_perm('autor', $user_id)) {
            // user on his own profile
            $status = 'user';
        } else if (isDeputyEditAboutActivated() && isDeputy($user_id, $range_id, true)) {
            // user is an assigned deputy
            $status = 'user';
        } else if ($this->have_perm('root', $user_id)) {
            // respect root's authority
            $status = 'admin';
        } else if ($this->have_perm('admin', $user_id)) {
            // institute admin may have permission
            $db = DBManager::get();
            $stmt = $db->prepare("SELECT a.inst_perms FROM user_inst AS a " .
                                 "LEFT JOIN user_inst AS b USING (Institut_id) " .
                                 "WHERE a.user_id = ? AND a.inst_perms = 'admin' " .
                                 "  AND b.user_id = ? AND b.inst_perms IN ('autor', 'tutor', 'dozent')");
            $stmt->execute(array($user_id, $range_id));

            if ($stmt->fetchColumn()) {
                $status = 'admin';
            } else if ($this->is_fak_admin($user_id)) {
                $stmt = $db->prepare("SELECT a.inst_perms FROM user_inst a " .
                                     "LEFT JOIN Institute i ON a.Institut_id = i.fakultaets_id " .
                                     "LEFT JOIN user_inst b ON b.Institut_id = i.Institut_id " .
                                     "WHERE a.user_id = ? AND a.inst_perms = 'admin' " .
                                     "  AND b.user_id = ? AND b.inst_perms != 'user'");
                $stmt->execute(array($user_id, $range_id));

                if ($stmt->fetchColumn()) {
                    $status = 'admin';
                }
            }
        }

        return $status;
    }

    function have_profile_perm($perm, $range_id, $user_id = false) {

        $pageperm = explode(",", $perm);
        $userperm = explode(",", $this->get_profile_perm($range_id, $user_id));

        list ($ok0, $pagebits) = $this->permsum($pageperm);
        list ($ok1, $userbits) = $this->permsum($userperm);

        $has_all = (($userbits & $pagebits) == $pagebits);

        if (!($has_all && $ok0 && $ok1) ) {
            return false;
        } else {
            return true;
        }
    }

    function is_fak_admin($user_id = false){
        global $user;
        if (!$user_id) $user_id = $user->id;
        $user_perm = $this->get_perm($user_id);
        if ($user_perm == "root") {
            return true;
        }
        if ($user_perm != "admin"){
            return false;
        }
        if (isset($this->fak_admins[$user_id])) {
            return $this->fak_admins[$user_id];
        } else {
            $db = DBManager::get();
            $st = $db->prepare("SELECT a.Institut_id FROM user_inst a
                          LEFT JOIN Institute b ON(a.Institut_id=b.Institut_id AND b.Institut_id=b.fakultaets_id)
                          WHERE a.user_id = ? AND a.inst_perms='admin' AND NOT ISNULL(b.Institut_id) LIMIT 1");
            $st->execute(array($user_id));
            return $this->fak_admins[$user_id] = (bool)$st->fetchColumn();
        }
    }

    function is_staff_member($user_id = false) {
        global $user;
        if (!$user_id) $user_id = $user->id;
        $user_perm = $this->get_perm($user_id);
        if ($user_perm == "root") {
            return true;
        }
        if (!$this->have_perm('autor', $user_id)) {
            return false;
        }
        $db = DBManager::get();
        $st = $db->prepare("SELECT 1 FROM user_inst
                            WHERE user_id = ? AND inst_perms <> 'user' LIMIT 1");
        $st->execute(array($user_id));
        return (bool)$st->fetchColumn();
    }
}

require_once 'lib/plugins/plugins.inc.php';

}
