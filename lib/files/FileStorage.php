<?php
/**
 * File.php
 *
 * Common interface for all backends to store files. At the
 * moment, there is only one backend: DiskFileStorage.
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

interface FileStorage
{
    /**
     * Delete this file from disk.
     */
    public function delete();

    /**
     * Check whether the file exists on disk.
     *
     * @return boolean  TRUE or FALSE
     */
    public function exists();

    /**
     * Return the file creation time.
     *
     * @return int  timestamp
     */
    public function getCreationTime();

    /**
     * Return the backend id of this file.
     *
     * @return string  backend id
     */
    public function getId();

    /**
     * Return the file's mime type, if known.
     *
     * @return string  mime type (NULL if unknown)
     */
    public function getMimeType();

    /**
     * Return the file modification time.
     *
     * @return int  timestamp
     */
    public function getModificationTime();

    /**
     * Return the file's size in bytes.
     *
     * @return int  file size
     */
    public function getSize();

    /**
     * Check if this backend allows reading of files.
     *
     * @return boolean  TRUE
     */
    public function isReadable();

    /**
     * Check if this backend allows writing of files.
     *
     * @return boolean  TRUE
     */
    public function isWritable();

    /**
     * Open a PHP stream resource for this file.
     * Access mode parameter works just like fopen.
     *
     * @param string $mode  access mode (see fopen)
     *
     * @return resource  file handle
     */
    public function open($mode);
}
