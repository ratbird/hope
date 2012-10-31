<?php
/**
* PHPLib Sessions using PHP 4 build-in sessions and PHPLib storage container
*
* @copyright  (c) 1998,1999 NetUSE GmbH Boris Erdmann, Kristian Koehntopp,
*             2000 Maxim Derkachev <kot@books.ru>,
*             2000 Teodor Cimpoesu <teo@digiro.net>
* @author     André Noack <noack@data-quest.de> Maxim Derkachev <kot@books.ru>,
*               Teodor Cimpoesu <teo@digiro.net>,Ulf Wendel <uw@netuse.de>
* @package    PHPLib
* @access     public
*/

class Session {


  /**
  * Session name
  *
  */
  var $classname = "Session";


  /**
  * Current session id.
  *
  * @var  string
  * @see  id(), Session()
  */
  var $id = "";


  /**
  * [Current] Session name.
  *
  * @var  string
  * @see  name(), Session()
  */
  var $name = "";

  /**
  *
  * @var  string
  */
  var $cookie_path = '/';


  /**
  *
  * @var  strings
  */
  var $cookiename;


  /**
  *
  * @var  int
  */
  var $lifetime = 0;


  /**
  * If set, the domain for which the session cookie is set.
  *
  * @var  string
  */
  var $cookie_domain = '';


  /**
  * Propagation mode is by default set to cookie
  * The other parameter, fallback_mode, decides wether
  * we accept ONLY cookies, or cookies and eventually get params
  * in php4 parlance, these variables cause a setting of either
  * the php.ini directive session.use_cookie or session.use_only_cookie
  * The session.use_only_cookie possibility was introdiced in PHP 4.2.2, and
  * has no effect on previous versions
  *
  * @var    string
  */
  var $mode = "cookie";               ## We propagate session IDs with cookies

  /**
  * If fallback_mode is set to 'cookie', php4 will impose a cookie-only
  * propagation policy, which is a safer  propagation method that get mode
  *
  * @var    string
  */
  var $fallback_mode;                 ## if fallback_mode is also 'ccokie'
                                      ## we enforce session.use_only_cookie


  /**
  * Was the PHP compiled using --enable-trans-sid?
  *
  * PHP 4 can automatically rewrite all URLs to append the session ID
  * as a get parameter if you enable the feature. If you've done so,
  * the old session3.inc method url() is no more needed, but as your
  * application might still call it you can disable it by setting this
  * flag to false.
  *
  * @var  boolean
  */
  var $trans_id_enabled = true;


  /**
  * See the session_cache_limit() options
  *
  * @var  string
  */
  var $allowcache = 'nocache';

  /**
  * session storage module - user, files or mm
  *
  * @var  string
  */
  var $module = 'user';


  /**
  * where to save session files if module == files
  *
  * @var string
  */
  var $save_path;


  /**
  * Name of data storage container
  *
  * var string
  */
  var $that_class = '';


  /**
  *
  * @var  object CT_*
  */
  var $that;


  /**
  * Purge all session data older than 1440 minutes.
  *
  * @var int
  */
  var $gc_time = 1440;


  /**
  * Garbaga collection probability
  *
  * Set this in php.ini or httpd.conf (.htaccess)
  *
  * @var  int
  */
  var $gc_probability;

    /**
  * Sets the session name before the session starts.
  *
  * Make sure that all derived classes call the constructor
  *
  * @see  name()
  */
  function Session() {
      if (!$this->classname){
          $this->classname = strtolower(get_class($this));
      }
      $this->name($this->name);
  } // end constructor


  /**
  * Start a new session or recovers from an existing session
  *
  * @return boolean   session_start() return value
  * @access public
  */
  function start() {

    if ( $this->mode=="cookie"
        && $this->fallback_mode=="cookie")  {
    ini_set ("session.use_trans_sid", "0");
    ini_set ("session.use_only_cookies", "1");
    ini_set ("url_rewriter.tags" , "");
    }

    $this->set_container();
    $this->set_tokenname();
    $this->put_headers();

    //check for illegal cookiename
    if (isset($_COOKIE[$this->name])){
        if (strlen($_COOKIE[$this->name]) != 32 || preg_match('/[^0-9a-f]+/', $_COOKIE[$this->name])){
            session_id(md5(uniqid($this->name,1)));
        }
    } else {
        session_id(md5(uniqid($this->name,1)));
    }

    $ok = session_start();
    $this->id = session_id();

    return $ok;
  } // end func start


  /**
  * Sets or returns the name of the current session
  *
  * @param  string  If given, sets the session name
  * @return string  session_name() return value
  * @access public
  */
  function name($name = '') {

    if ($name = (string)$name) {

      $this->name = $name;
      $ok = session_name($name);

    } else {

      $ok = session_name();

    }

    return $ok;
  } // end func name

  /**
  * Delete the cookie holding the session id.
  *
  * RFC: is this really needed? can we prune this function?
  *          the only reason to keep it is if one wants to also
  *      unset the cookie when session_destroy()ing,which PHP
  *      doesn't seem to do (looking @ the session.c:940)
  * uw: yes we should keep it to remain the same interface, but deprec.
  *
  * @access public
  * @global $_COOKIE
  */
  function put_id() {
    if (ini_get('session.use_cookies')){
      $cookie_params = session_get_cookie_params();
      setCookie($this->name, '', 0, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
      $_COOKIE[$this->name] = "";
    }

  } // end func put_id

  /**
  * Delete the current session destroying all registered data.
  *
  * Note that it does more but the PHP 4 session_destroy it also
  * throws away a cookie is there's one.
  *
  * @return boolean session_destroy return value
  * @access public
  */
  function delete() {

    $this->put_id();
    $_SESSION = array();
    return session_destroy();
  } // end func delete

  /**
  * ?
  *
  */
  function set_tokenname(){

      $this->name = ("" == $this->cookiename) ? $this->classname : $this->cookiename;
      session_name ($this->name);
      if (!$this->cookie_domain) {
        $this->cookie_domain = ini_get ("session.cookie_domain");
      }

      if (!$this->cookie_path && ini_get('session.cookie_path')) {
        $this->cookie_path = ini_get('session.cookie_path');
      } elseif (!$this->cookie_path) {
        $this->cookie_path = "/";
      }

      if ($this->lifetime > 0) {
        $lifetime = time()+$this->lifetime*60;
      } else {
        $lifetime = 0;
      }

      session_set_cookie_params($lifetime, $this->cookie_path, $this->cookie_domain, $this->cookie_secure, $this->cookie_httponly);
  } // end func set_tokenname


  /**
  * ?
  *
  */
  function put_headers() {
    # set session.cache_limiter corresponding to $this->allowcache.

    switch ($this->allowcache) {

      case "passive":
      case "public":
        session_cache_limiter ("public");
        break;

      case "private":
        session_cache_limiter ("private");
        break;

      default:
        session_cache_limiter ("nocache");
        break;
    }
  } // end func put_headers


  // the following functions used in session_set_save_handler

  /**
  * Open callback
  *
  * abstract
  */
  function open() {
    return true;
  } // end func open


  /**
  * Close callback
  *
  * @abstract
  */
  function close() {
    return true;
  } // end func close


  /**
  * Delete callback
  */
  function del() {

    if ($this->module == 'user') {
      $this->that->ac_delete($this->id, $this->name);
      $this->put_id();
    }

    return true;
  } // end func del


  /*
  * Write callback.
  *
  */
  function freeze($id = NULL, $sess_data = NULL)
  {
    if ($this->module == 'user') {
      if (!isset($sess_data)) {
        $sess_data = session_encode();
      }
      $r = $this->that->ac_store($this->id, $this->name, $sess_data);
      if (!$r) {
        $this->that->ac_halt("Session: freeze() failed.");
      }
    }

    return $r;
  } // end func freeze


  /**
  * Read callback.
  */
  function thaw() {

      if ($this->module == 'user') {
        return $this->that->ac_get_value(session_id(), $this->name);
      }

      return true;
  }

  /**
  * gc callback.
  *
  * Destroy all session data older than $this->gc_time
  *
  */
  function gc() {

    if ($this->module == 'user') {

      if (empty($this->gc_time))
        $this->gc_time = ini_get("session.gc_maxlifetime");

      return $this->that->ac_gc($this->gc_time, $this->name);
    }

    return true;
  } // end func gc


  // helper functions used in initialization

  /**
  * ?
  *
  */
  function set_container(){

    switch ($this->module) {
      case "user" :

        session_module_name('user');

        $name = $this->that_class;
        $this->that = new $name;
        $this->that->ac_start();

        // set custom session handlers
        session_set_save_handler(array (&$this, 'open'),
            array (&$this, 'close'),
            array (&$this, 'thaw'),
            array (&$this, 'freeze'),
            array (&$this, 'del'),
            array (&$this, 'gc')
        );
        break;

      case "mm":
        session_module_name('mm');
        break;

      case "files" :
      default:
        if ($this->save_path)
          session_save_path($this->save_path);

        session_module_name('files');
        break;
    }

  } // end func set_container

  function regenerate_session_id($keep_session_vars = array())
  {
      if (is_array($_SESSION)) {
          foreach(array_keys($_SESSION) as $k) {
              if (in_array($k, $keep_session_vars)) {
                  $keep[$k] = $_SESSION[$k];
              }
          }
          $_SESSION = array();
      }
      session_destroy();
      $this->start();
      foreach($keep_session_vars as $k) {
          $_SESSION[$k] = $keep[$k];
      }
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
} // end class Session4_Custom
?>
