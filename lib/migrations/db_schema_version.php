<?php
# Lifter002: DONE - not applicable
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - not applicable
/*
 * db_schema_version.php - database backed schema versions
 * Copyright (C) 2007  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'schema_version.php';

/**
 * Implementation of SchemaVersion interface using a database table.
 *
 * @package migrations
 */
class DBSchemaVersion extends SchemaVersion
{
    /**
     * domain name of schema version
     *
     * @access private
     * @var string
     */
    private $domain;

    /**
     * current schema version number
     *
     * @access private
     * @var int
     */
    private $version;

    /**
     * Initialize a new DBSchemaVersion for a given domain.
     * The default domain name is 'studip'.
     *
     * @param string $domain domain name (optional)
     */
    function DBSchemaVersion ($domain = 'studip')
    {
        $this->domain = $domain;
        $this->version = 0;
        $this->init_schema_info();
    }

    /**
     * Retrieve the domain name of this schema.
     *
     * @return string domain name
     */
    function get_domain ()
    {
        return $this->domain;
    }

    /**
     * Initialize the current schema version.
     *
     * @access private
     */
    private function init_schema_info ()
    {
        $query = "SELECT version FROM schema_version WHERE domain = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->domain
        ));
        $this->version = (int)$statement->fetchColumn();
    }

    /**
     * Retrieve the current schema version.
     *
     * @return int schema version
     */
    function get ()
    {
        return $this->version;
    }

    /**
     * Set the current schema version.
     *
     * @param int $version new schema version
     */
    function set ($version)
    {
        $this->version = (int) $version;

        $query = "INSERT INTO schema_version (domain, version)
                  VALUES (?, ?)
                  ON DUPLICATE KEY UPDATE version = VALUES(version)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->domain,
            $this->version
        ));
    }
}
