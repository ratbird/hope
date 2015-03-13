<?php
/**
 * File.php
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * Class to represent files on disk (the only supported backend for now).
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/datei.inc.php';       // get_upload_file_path()

class DiskFileStorage implements FileStorage
{
    protected $storage_id;      // backend id
    protected $file_path;       // path on disk

    /**
     * Initialize a new DiskFileStorage object for the given id.
     * A new (empty) file is created of storage_id is NULL.
     *
     * @param string $storage_id  file id or NULL
     */
    public function __construct($storage_id = NULL, $user_id = null)
    {
        $user_id  = $user_id ?: $GLOBALS['user']->id;
        $user_dir = $GLOBALS['USER_DOC_PATH'] . DIRECTORY_SEPARATOR . $user_id;

        if (!file_exists($user_dir)) {
            mkdir($user_dir, 0755, true);
        }

        if (isset($storage_id)) {
            $this->storage_id = $storage_id;
        } else {
            $this->storage_id = md5(uniqid(__CLASS__, true));
        }

        $this->file_path  = $user_dir . DIRECTORY_SEPARATOR . $this->storage_id;
    }

    /**
     * Delete this file from disk.
     */
    public function delete()
    {
        if ($this->exists()) {
            unlink($this->file_path);
        }
    }

    public function getPath()
    {
        return $this->file_path;
    }
    /**
     * Check whether the file exists on disk.
     *
     * @return boolean  TRUE or FALSE
     */
    public function exists()
    {
        return file_exists($this->file_path);
    }

    /**
     * Return the file creation time.
     *
     * @return int  timestamp
     */
    public function getCreationTime()
    {
        return filectime($this->file_path);
    }

    /**
     * Return the backend id of this file.
     *
     * @return string  backend id
     */
    public function getId()
    {
        return $this->storage_id;
    }

    /**
     * Return the file's mime type, if known.
     *
     * @param  string  $filename Optional filename if storage_id's do not
     *                           provide enough information (like file
     *                           extension)
     * @return string  mime type (NULL if unknown)
     */
    public function getMimeType($filename = null)
    {
        return get_mime_type($filename ?: $this->file_path);
    }

    /**
     * Return the file modification time.
     *
     * @return int  timestamp
     */
    public function getModificationTime()
    {
        return filemtime($this->file_path);
    }

    /**
     * Return the file's size in bytes.
     *
     * @return int  file size
     */
    public function getSize()
    {
        return filesize($this->file_path);
    }

    /**
     * Check if this backend allows reading of files.
     *
     * @return boolean  TRUE
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * Check if this backend allows writing of files.
     *
     * @return boolean  TRUE
     */
    public function isWritable()
    {
        return true;
    }

    /**
     * Open a PHP stream resource for this file.
     * Access mode parameter works just like fopen.
     *
     * @param string $mode  access mode (see fopen)
     *
     * @return resource  file handle
     */
    public function open($mode)
    {
        return fopen($this->file_path, $mode);
    }

    public static function getQuotaUsage($user_id)
    {
        $statement = DBManager::get()->prepare('SELECT SUM(size) FROM files WHERE user_id = :user_id');
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();
        return 0 + $statement->fetchColumn();
    }
}
