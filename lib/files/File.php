<?php
/**
 * File.php
 *
 * Class to represent files and directories in the database.
 * Should probably use SimpleORMap. Does this work for factory
 * classes like this?.
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class File extends SimpleORMap
{
    protected $storage_object;  // backend object

    protected static $object_cache = array();

    /**
     * Get a file object for the given id. May be file or directory.
     * If the file does not exist, a new (virtual) RootDirectory is
     * created for this id. TODO Is this a good idea?
     *
     * @param string $id  file id
     *
     * @return File  File object
     */
    public static function get($id)
    {
        if (!isset(self::$object_cache[$id])) {
            $entry = self::find($id);
            if (empty($entry)) {
                $file = new RootDirectory($id);
            } else {
                if ($entry['storage_id']) {
                    $file = $entry;
                    $file->storage_object = new $file->storage($file->storage_id);
                } else {
                    $file = new StudipDirectory($id);
                }
            }
            self::$object_cache[$id] = $file;
        }
        return self::$object_cache[$id];
    }

    /**
     * Configures this model
     */
    protected static function configure($config = array())
    {
        $config['db_table'] = 'files';
        $config['belongs_to']['owner'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id',
        );
        $config['has_many']['refs'] = array(
            'class_name'  => 'DirectoryEntry',
            'foreign_key' => 'file_id',
        );
        $config['default_values']['storage'] = 'DiskFileStorage';
            
        parent::configure($config);
    }

    /**
     * Delete all the links to this file and the file itself.
     */
    public function delete()
    {
        $db = DBManager::get();

        if (isset($this->storage_object)) {
            $this->storage_object->delete();
        }

        $stmt = $db->prepare('DELETE FROM file_refs WHERE file_id = ?');
        $stmt->execute(array($this->file_id));

        parent::delete();
    }

    /**
     * Return the links to this file (directory entries). Each file can
     * be linked into mutiple directories, like on a POSIX file system.
     * The file is deleted when the link count drops to zero.
     *
     * @return array  array of DirectoryEntry objects
     */
    public function getLinks()
    {
        $db = DBManager::get();
        $result = array();

        $stmt = $db->prepare('SELECT id FROM file_refs WHERE parent_id = ?');
        $stmt->execute(array($this->file_id));

        foreach ($stmt as $row) {
            $result[] = new DirectoryEntry($row[0]);
        }

        return $result;
    }

    /**
     * Return the file's storage path.
     *
     * @return string storage path
     */

    public function getStoragePath()
    {
        $path = $this->storage_object->getPath();
        return  $path;
    }

    /**
     * Return the Storage Opject from File.
     *
     * @return Storage Object
     */
    public function getStorageObject(){
        return $this->storage_object;
    }

    /**
     * Check if the file's backend allows reading of files.
     *
     * @return boolean  TRUE or FALSE
     */
    public function isReadable()
    {
        return $this->storage_object->isReadable();
    }

    /**
     * Check if the file's backend allows writing of files.
     *
     * @return boolean  TRUE or FALSE
     */
    public function isWritable()
    {
        return $this->storage_object->isWritable();
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
        return $this->storage_object->open($mode);
    }

    /**
     * Update this file's metadata if the content has changed.
     * Note: This needs to be called after each update of the file.
     */
    public function update() 
    {
        $this->mime_type = $this->storage_object->getMimeType() ?: $this->mime_type;
        $this->mkdate    = $this->storage_object->getCreationTime();
        $this->chdate    = $this->storage_object->getModificationTime();
        $this->size      = $this->storage_object->getSize();

        $this->store();
    }
}