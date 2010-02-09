<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * schema_version.php - schema version interface for migrations
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * This interface provides an abstract way to retrieve and set the current
 * version of a schema. Implementations of this interface need to define
 * where the version information is actually stored (e.g. in a file).
 *
 * @package migrations
 */

class SchemaVersion {


  /**
   * Returns current schema version.
   *
   * @return int schema version
   */
  function get() {
    trigger_error(sprintf('%s#%s() must be overridden.',
                          __CLASS__, __FUNCTION__),
                  E_USER_ERROR);
  }


  /**
   * Sets the new schema version.
   *
   * @param int $version new schema version
   */
  function set($version) {
    trigger_error(sprintf('%s#%s() must be overridden.',
                          __CLASS__, __FUNCTION__),
                  E_USER_ERROR);
  }
}

