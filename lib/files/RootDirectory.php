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
     * Wrapper "find" method in compliance with the other SimpleORMap methods.
     * Since the root directory is abstract and does not have a corresponding
     * db entry, find would always return null otherwise.
     *
     * @param String $context_id
     * @return RootDirectory
     */
    public static function find($context_id)
    {
        return new self($context_id);
    }

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

    /**
     * Checks whether a user has access to the current root directory.
     *
     * @param mixed $user_id Id of the user or null for current user (default)
     * @param bool $throw_exception Throw an AccessDeniedException instead of
     *                              returning false
     * @return bool indicating whether the user may access this root directory
     * @throws AccessDeniedException if $throw_exception is true and the user
     *                               may not access the root directory
     */
    public function checkAccess($user_id = null, $throw_exception = true)
    {
        $user_id = $user_id ?: $GLOBALS['user']->id;

        $valid = $GLOBALS['perm']->have_perm('root')
              || $this->file_id === $user_id;

        if (!$valid && $throw_exception) {
            throw new AccessDeniedException(_('Sie dürfen auf dieses Objekt nicht zugreifen.'));
        }

        return $valid;
    }
}
