<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * This class provides a singleton instance that is used to manage PDO database
 * connections.
 *
 * Example of use:
 *
 *   # getting a PDO connection
 *   $key = 'studip';
 *   $db = DBManager::get($key);
 *   $db->query('SELECT * FROM user_info');
 *
 *   # setting a PDO connection
 *   $manager = DBManager::getInstance();
 *   $manager->setConnection('example', 'mysql:host=localhost;dbname=example',
 *                           'root', '');
 *
 *
 * @package     studip
 * @subpackage  lib
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class DBManager {


  /**
   * the singleton instance
   *
   * @access  private
   * @var     DBManager
   */
  static private $instance;


  /**
   * an array of connections of the singleton instance
   *
   * @access  private
   * @var     array
   */
  private $connections;


  /**
   * @access private
   *
   * @return void
   */
  private function __construct() {
    $this->connections = array();
  }


  /**
   * This method returns the singleton instance of this class.
   *
   * @return DBManager  the singleton instance
   */
  static public function getInstance() {
    if (is_null(DBManager::$instance)) {
      DBManager::$instance = new DBManager();
    }
    return DBManager::$instance;
  }


  /**
   * This method returns the database connection to the given key. Throws a
   * DBManagerException if there is no such connection.
   *
   * @param  string  the database connection's key
   *
   * @throw DBManagerException
   *
   * @return PDO     the database connection
   */
  public function getConnection($database) {

    if (!isset($this->connections[$database])) {
      throw new DBManagerException('Database connection: "'.$database.
                                   '" does not exist.');
    }

    return $this->connections[$database];
  }


    /**
     * This method maps the specified key to the specified database connection.
     *
     * You can either use an instance of class PDO or specify a DSN (optionally
     * with username/password).
     *
     * Examples:
     * @code
     *    $dbManager = DBManager::getInstance();
     *
     *    // using an existing PDO connection
     *    $existingPdo = new LoggingPDO($dsn);
     *    $dbManager->setConnection('studip', $pdo);
     *
     *    // using a DSN with username and password
     *    $dbManager->setConnection('studip', $dsn , $username, $password);
     * @endcode
     *
     * @param  string      the key
     * @param  string|PDO  either a DSN or an existing PDO connection
     * @param  string      (optional) the connection's username
     * @param  array       (optional) the connection's password
     *
     * @return DBManager this instance, useful for cascading method calls
     */
    public function setConnection($database, $dsnOrConnection, $user = NULL, $pass = NULL)
    {
        $connection = $dsnOrConnection instanceof PDO
            ? $dsnOrConnection
            : new PDO($dsnOrConnection, $user, $pass);

        $this->configureConnection($connection);
        $this->connections[$database] = $connection;

        return $this;
    }


    // PDO connection should throw exceptions and use buffered queries
    private function configureConnection($connection)
    {
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
            $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

  /**
   * This method creates an alias for a database connection.
   *
   * @param  string    the new key of the database connection
   * @param  string    the old key of the database connection
   *
   * @return DBManager this instance, useful for cascading method calls
   */
  public function aliasConnection($new, $old) {

    if (!isset($this->connections[$old])) {
      throw new DBManagerException('No database found using key: ' . $old);
    }

    $this->connections[$new] = $this->connections[$old];

    return $this;
  }


  /**
   * Shortcut static method to retrieve the database connection for a given key.
   *
   * @param  string  the database connection's key
   *
   * @return PDO     the database connection
   */
  static public function get($database = 'studip') {
    $manager = DBManager::getInstance();
    return $manager->getConnection($database);
  }
}


/**
 * @package     studip
 * @subpackage  lib
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class DBManagerException extends Exception {


  /**
   * @param  string   the message of this exception
   *
   * @return void
   */
  public function __construct($message) {
    parent::__construct($message);
  }
}
