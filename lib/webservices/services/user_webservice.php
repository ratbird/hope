<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * user_webservice.php - User webservice for Stud.IP
 *
 * Copyright (C) 2006 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once 'lib/webservices/api/studip_user.php';


/**
 * Kind of Mock for Stud.IP Permission Class.
 *
 * @package   studip
 * @package   webservice
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class MockPermission extends Seminar_Perm {
  function have_perm($perm) { return TRUE; }
  function is_fak_admin()   { return TRUE; }
}


/**
 * Service definition regarding Stud.IP users.
 *
 * @package   studip
 * @package   webservice
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class UserService extends Studip_Ws_Service {

  /**
   * This method is called before every other service method and tries to
   * authenticate an incoming request using the first argument as an so
   * called "api key". If the "api key" matches a valid one, the request will
   * be authorized, otherwise a fault is sent back to the caller.
   *
   * @param string the function's name.
   * @param array an array of arguments that will be delivered to the function.
   *
   * @return mixed if this method returns a "soap_fault" or "FALSE", further
   *               processing will be aborted and a "soap_fault" delivered.
   */
  function before_filter($name, &$args) {

    # get api_key
    $api_key = current($args);
    
    if ($api_key != $GLOBALS['STUDIP_API_KEY'])
      return new Studip_Ws_Fault('Could not authenticate client.');
    
    page_open(array('sess' => 'Seminar_Session',
                    'auth' => 'Seminar_Default_Auth',
                    'perm' => 'MockPermission',
                    'user' => 'Seminar_User'));
  }


  /**
   * Create a User using a User struct as defined in the service's WSDL.
   *
   * @param string the api key.
   * @param string a partially filled User struct.
   *
   * @return string the updated User struct. At least the user's id is filled
   *                in. If the user cannot be created, a fault containing a
   *                textual representation of the error will be delivered
   *                instead.
   */
  function create_user_action($api_key, $user) {

    $user = new Studip_User($user);

    if (!$user->save())
      return new soap_fault('Server', '', $user->error);

    return $user;
  }


  /**
   * Searches for a user using the user's user name.
   *
   * @param string the api key.
   * @param string the user's username.
   *
   * @return mixed the found User struct or a fault if the user could not be
   *               found.
   */
  function find_user_by_user_name_action($api_key, $user_name) {
    $user =& Studip_User::find_by_user_name($user_name);

    if (!$user)
      return new soap_fault('Server', '', 'No such user.');

    return $user;
  }


  /**
   * Updates a user.
   *
   * @param string the api key.
   * @param array an array representation of an user
   *
   * @return mixed the user's User struct or a fault if the user could not be
   *               updated.
   */
  function update_user_action($api_key, $user) {

    $user = new Studip_User($user);

    if (!$user->id)
      return new soap_fault('Server', '', 'You have to give the user\'s id.');

    if (!$user->save())
      return new soap_fault('Server', '', $user->error);

    return $user;
  }


  /**
   * Deletes a user.
   *
   * @param string the api key.
   * @param string the user's username.
   *
   * @return boolean returns TRUE if deletion was successful or a fault
   *                 otherwise.
   */
  function delete_user_action($api_key, $user_name) {

    $user =& Studip_User::find_by_user_name($user_name);

    if (!$user)
      return new soap_fault('Server', '', 'No such user.');
  
    if (!$user->destroy())
      return new soap_fault('Server', '', $user->error);

    return TRUE;
  }
}
