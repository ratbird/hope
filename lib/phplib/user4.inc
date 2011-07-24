<?php
/**
* Session Management for PHP3
*
* @copyright  1998,1999 NetUSE GmbH Boris Erdmann, Kristian Koehntopp
*             2001, Maxim Derkachev <kot@books.ru>
* @package    PHPLib
* @access     public
*/
class PhpLibUser {

  /**
  *
  */
  var $classname = "PhpLibUser";


  /**
  * AC storage name
  *
  * @var  string
  */
  var $name = "";


  /**
  * AC storage  ID
  *
  * @var  string
  */
  var $id = "";


  /**
  * An array where references to registered user vars are stored.
  *
  * @var  string
  */
  var $user_vars = null;


  /**
  * Do we need to push user vars into global namespace?
  *
  *
  * @var  boolean
  */
  var $register_globals = true;


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
  *
  * @param  string
  */
  function start($sid = '') {
    $this->get_id($sid);
    if ("" == $this->name)
      $this->name = $this->classname;
    $name = $this->that_class;
    $this->that = new $name;
    $this->that->ac_start();
    $this->thaw();
  } // end func start


  /**
  * registers a global as user variables
  *
  * @param  array
  */
  function register($things) {
      if ($this->register_globals){
          foreach (explode(',',$things) as $thing) {
              $thing = trim($thing);
              if (!isset($GLOBALS[$thing])){
                  $GLOBALS[$thing] = null;
              }
              $this->user_vars[$thing] =& $GLOBALS[$thing];
          }
      } else {
          return false;
      }
  } // end func register


  /**
  * find out if a var is registered user variable
  *
  * @param  string
 */
  function is_registered($name) {
    return (isset($this->user_vars[trim($name)]));
  } // end func is_registered


  /**
  * cancel the registration of a registered user variables
  *
  */
  function unregister($things) {

    foreach (explode(',',$things) as $thing) {
        $thing = trim($thing);
        unset($this->user_vars[$thing]);
    }

  } // end func unregister

  /**
  *
  * @param string
  */
  function get_id($id = "") {
    $this->id = $id;
  } // end func get_id


  /**
  * Delete the current user record
  */
  function delete() {
      $this->that->ac_delete($this->id, $this->name);
  } // end func delete


  /**
  * serializes user data
  */
  function serialize() {
    return serialize($this->user_vars);
  } // end func serialize


  /**
  * prepare serialized user data and store it in a storage container
  *
  */
  function freeze() {
      if ($this->id == 'nobody'){
          return;
      }
      $vals = $this->serialize();

      if($this->checksum != hash('md5', $vals)){
          if(!$this->that->ac_store($this->id, $this->name, $vals)){
            $this->that->ac_halt("User: freeze() failed.");
          }
      } else {
            $this->that->ac_set_changed($this->id, $this->name, time());
      }
      return true;
  } // end func freeze


  /*
  * restore saved registered user variables
  **/
  function thaw() {
    if ($this->id == 'nobody'){
          return;
    }
    $vals = $this->that->ac_get_value($this->id, $this->name);

    $this->checksum = hash('md5', $vals);

    $vars = @unserialize($vals);

    if (is_array ($vars)) {
        if($this->register_globals){
            foreach($vars as $key => $value){
                $GLOBALS[$key] = $value;
                $this->user_vars[$key] =& $GLOBALS[$key];
            }
        } else {
            $this->user_vars = $vars;
        }
    }
  } // end func thaw
} // end class User
?>
