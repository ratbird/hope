<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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
    var $domain;

    /**
     * database connection object
     *
     * @access private
     * @var DB_Seminar
     */
    var $db;

    /**
     * current schema version number
     *
     * @access private
     * @var int
     */
    var $version;

    /**
     * use UPDATE when updating version number
     *
     * @access private
     * @var boolean
     */
    var $update;

    /**
     * Initialize a new DBSchemaVersion for a given domain.
     * The default domain name is 'studip'.
     *
     * @param string $domain domain name (optional)
     */
    function DBSchemaVersion ($domain = 'studip')
    {
        $this->domain = $domain;
        $this->db = new DB_Seminar();
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
    function init_schema_info ()
    {
        $this->db->Halt_On_Error = 'no';
        $this->db->query('SELECT version FROM schema_version'.
                         " WHERE domain = '".$this->domain."'");

        if ($this->db->next_record()) {
            $this->version = (int) $this->db->f('version');
            $this->update = true;
        }
        $this->db->Halt_On_Error = 'yes';
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

        if ($this->update) {
            $this->db->query('UPDATE schema_version SET version = '.$this->version.
                             " WHERE domain = '".$this->domain."'");
        } else {
            $this->db->query('INSERT INTO schema_version (domain, version)'.
                             " VALUES ('".$this->domain."', ".$this->version.')');
            $this->update = true;
        }
    }
}
