<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * Copyright (C) 2009 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'studip_controller.php';

abstract class AuthenticatedController extends StudipController {

  /**
   * Callback function being called before an action is executed. If this
   * function does not return FALSE, the action will be called, otherwise
   * an error will be generated and processing will be aborted. If this function
   * already #rendered or #redirected, further processing of the action is
   * withheld.
   *
   * @param string  Name of the action to perform.
   * @param array   An array of arguments to the action.
   *
   * @return bool
   */
  function before_filter(&$action, &$args) {

    # open session
    page_open(array('sess' => 'Seminar_Session',
                    'auth' => 'Seminar_Auth',
                    'perm' => 'Seminar_Perm',
                    'user' => 'Seminar_User'));

    // show login-screen, if authentication is "nobody"
    $GLOBALS['auth']->login_if($auth->auth["uid"] == "nobody");

    $this->flash = Trails_Flash::instance();

    // set up user session
    include 'lib/seminar_open.php';

    // allow only "word" characters in arguments
    $this->validate_args($args);

    # Set base layout
    #
    # If your controller needs another layout, overwrite your controller's
    # before filter:
    #
    #   class YourController extends AuthenticatedController {
    #     function before_filter(&$action, &$args) {
    #       parent::before_filter($action, $args);
    #       $this->set_layout("your_layout");
    #     }
    #   }
    #
    # or unset layout by sending:
    #
    #   $this->set_layout(NULL)
    #
    $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
  }

  /**
   * Callback function being called after an action is executed.
   *
   * @param string Name of the action to perform.
   * @param array  An array of arguments to the action.
   *
   * @return void
   */
  function after_filter($action, $args) {
    page_close();
  }
}
