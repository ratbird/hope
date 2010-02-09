<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * migration.php - abstract base class for migrations
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * This class serves as the abstract base class for all migrations.
 *
 * @package migrations
 */

class Migration {

  /**
   * use verbose output
   *
   * @access private
   * @var boolean
   */
  var $verbose;


  /**
   * Initalize a Migration object (optionally using verbose output).
   *
   * @param boolean $verbose verbose output (default FALSE)
   */
  function Migration($verbose = FALSE) {
    $this->verbose = (bool) $verbose;
  }


  /**
   * Abstract method describing this migration step.
   * This method should be implemented in a migration subclass.
   *
   * @return string migration description
   */
  function description() {
  }

  /**
   * Abstract method performing this migration step.
   * This method should be implemented in a migration subclass.
   *
   * @access protected
   */
  function up() {
  }


  /**
   * Abstract method reverting this migration step.
   * This method should be implemented in a migration subclass.
   *
   * @access protected
   */
  function down() {
  }


  /**
   * Perform or revert this migration, depending on the indicated direction.
   *
   * @access protected
   * @param string $direction migration direction (either 'up' or 'down')
   */
  function migrate($direction) {

    switch ($direction) {
      case 'up':   $this->announce('migrating'); break;
      case 'down': $this->announce('reverting'); break;
      default:     return;
    }

    $result = $this->$direction();

    $action = $direction == 'up' ? 'migrated' : 'reverted';
    $this->announce($action);

    $this->write();

    return $result;
  }


  /**
   * Print the given string (if verbose output is enabled).
   *
   * @access private
   * @param string $text text to print
   */
  function write($text = "") {
    if ($this->verbose) {
      echo $text . "\n";
    }
  }


  /**
   * Print the given formatted string (if verbose output is enabled).
   * Output always includes the migration's class name.
   *
   * @param string $format,... printf-style format string and parameters
   */
  function announce($format /* , ... */) {

    # format message
    $args = func_get_args();
    $message = vsprintf(array_shift($args), $args);
    $text = sprintf('== %s: %s ', get_class($this), $message);

    return $this->write($text . ((strlen($text)) < 79 ? str_repeat('=', 79 - strlen($text)) : ''));
  }
}
