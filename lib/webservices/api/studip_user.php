<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * studip_user.php - Basisklasse für Stud.IP User
 *
 * Copyright (C) 2006 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


# using UserManagement class for now
# TODO shall be removed afterwards
#require_once 'lib/classes/UserManagement.class.php';


/**
 * <ClassDescription>
 *
 * @package    studip
 * @subpackage base
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

require_once('lib/functions.php');

class Studip_User {
  # internal variables
  var $id;
  var $user_name;
  var $first_name;
  var $last_name;
  var $email;
	var $permission;
	var $fullname;
 

  #  Constructor
  function Studip_User($user) {
    $fields = Studip_User::get_fields();

    foreach ($fields as $field)
      if (isset($user[$field]))
				$this->$field = $user[$field];
		
		$this->fullname = get_fullname($this->id);

  }
  
  
  /**
   * <MethodDescription>
   *
   * @return type <description>
   */
  function save() {

    array_walk($user = Studip_User::get_fields(),
               create_function('&$v,$k,$user', '$v=$user->$v;'),
               $this);

    # no id, create
    if (!$this->id) {
      $user_management =& new UserManagement();
      if (!$user_management->createNewUser($user)) {
        $this->error = $user_management->msg; # TODO
        return FALSE;
      }
      
      # set id
      $this->id = $user_management->user_data['auth_user_md5.user_id'];
    }
    
    # update
    else {
      $user_management =& new UserManagement($this->id);
      if (!$user_management->changeUser($user)) {
        $this->error = $user_management->msg; # TODO
        return FALSE;
      }
    }

    return TRUE;
  }
  
  
  /**
   * <MethodDescription>
   *
   * @return type <description>
   */
  function destroy() {
    $user_management =& new UserManagement($this->id);
    if (!$user_management->deleteUser()) {
      $this->error = $user_management->msg; # TODO
      return FALSE;
    }
    
    return TRUE;
  }
  
  
  /**
   * <MethodDescription>
   *
   * @param type <description>
   *
   * @return mixed <description>
   */
  function &find_by_user_name($user_name) {
  
    $result = NULL;
  
    $db = new DB_Seminar;
    $db->queryf("SELECT * FROM auth_user_md5 WHERE username = '%s'",
                $user_name);
     
    if ($db->next_record()) {

      $user = array();
      foreach (Studip_User::get_fields() as $old => $new)
        $user[$new] = $db->f(array_pop(explode('.', $old)));
      $result =& new Studip_User($user);
    return $result;
    }
    
  }

  /**
   * <MethodDescription>
   *
   * @param type <description>
   *
   * @return mixed <description>
   */
  function &find_by_user_id($user_id) {
  
    $result = NULL;
  
    $db = new DB_Seminar;
    $db->queryf("SELECT * FROM auth_user_md5 WHERE user_id = '%s'",
                $user_id);
     
    if ($db->next_record()) {

      $user = array();
      foreach (Studip_User::get_fields() as $old => $new)
        $user[$new] = $db->f(array_pop(explode('.', $old)));
      $result =& new Studip_User($user);
    return $result;
    }
    
  }
  
  /**
   * <MethodDescription>
   *
   * @param type <description>
   *
   * @return mixed <description>
   */
  function &find_by_status($status) {
  
    $result = NULL;
  
    $db = new DB_Seminar;
    $db->queryf("SELECT username FROM auth_user_md5 WHERE perms = '%s'",
                $status);
    $userlist = array();
    while ($db->next_record()) {

      $userlist [] = $db->f("username");
    }
    
    return $userlist;     
  }
  
  /**
   * <MethodDescription>
   *
   * @return array <description>
   */
  function get_fields() {
    $fields = array('auth_user_md5.user_id'  => 'id',
                    'auth_user_md5.username' => 'user_name',
                    'auth_user_md5.Vorname'  => 'first_name',
                    'auth_user_md5.Nachname' => 'last_name',
                    'auth_user_md5.Email'    => 'email',
                    'auth_user_md5.perms'    => 'permission');
    return $fields;
  }
}
