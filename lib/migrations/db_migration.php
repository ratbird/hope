<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
 * db_migration.php - database schema migration
 * Copyright (C) 2007  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'migration.php';

/**
 * Abstract base class for database schema migrations.
 *
 * @package migrations
 */
class DBMigration extends Migration
{
    /**
     * database connection object
     *
     * @access protected
     * @var DB_Seminar
     */
    var $db;

    /**
     * Initalize database connection for migration.
     *
     * @param boolean $verbose verbose output (optional)
     */
    function DBMigration ($verbose = TRUE)
    {
        parent::Migration($verbose);
        $this->db = new DB_Seminar();
    }
}
