<?php
/**
 * File.php
 *
 * Class to represent (virtual) root directory.
 * Root directories are not represented in the database.
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class RootDirectory extends StudipDirectory
{
    /**
     * Initialize a new root directory object for the given id.
     *
     * @param string $id  context id
     *
     * @return RootDirectory  directory object
     */
    public function __construct($id = null)
    {
        parent::__construct($id);

        // default is to use DiskFileStorage
        $this->storage = 'DiskFileStorage';
    }

    /**
     * Delete the contents of this directory (recursively).
     * The root directory itself cannot (and need not) be deleted.
     */
    public function delete()
    {
        foreach ($this->listFiles() as $entry) {
            $entry->file->delete();
        }
    }
}
