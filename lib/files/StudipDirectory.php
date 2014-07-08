<?php
/**
 * StudipDirectory.php
 *
 * Class to represent directories in the database. Every
 * directory is also a file, so this a subclass of File.
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class StudipDirectory extends File
{
    protected static function configure($config = array())
    {
        $config['has_many']['files'] = array(
            'class_name'  => 'DirectoryEntry',
            'foreign_key' => 'file_id',
            'assoc_foreign_key' => 'parent_id',
        );

        parent::configure($config);
    }
    
    /**
     * Get a root directory object for the given context id.
     * Root directories are not represented in the database.
     * TODO Is this function really needed?
     *
     * @param string $context_id  context (course id etc.)
     *
     * @return StudipDirectory  directory object
     */
    public static function getRootDirectory($context_id)
    {
        return parent::get($context_id);
    }

    /**
     * Create a new empty file in this directory under the
     * given name and returns the directory entry.
     *
     * @param string $name  file name
     *
     * @return DirectoryEntry  created DirectoryEntry object
     */
    public function createFile($name, $description = '')
    {
        $reflection = new ReflectionClass($this->storage);
        $storage_object = $reflection->newInstance();

        $file = new File();
        $file->user_id    = $GLOBALS['user']->id;
        $file->filename   = $name;
        $file->mime_type  = 'text/plain';
        $file->size       = 0;
        $file->restricted = false;
        $file->storage    = $this->storage;
        $file->storage_id = $storage_object->getId();
        $file->store();

        return $this->link($file, $name, $description);
    }

    /**
     * Create a new file by copying the contents of an existing
     * file into this directory under the given name. Unlike the
     * link() method, this creates a separate file. Returns the
     * directory entry.
     *
     * @param File $source  source file to copy
     * @param string $name  destination file name
     *
     * @return DirectoryEntry  created DirectoryEntry object
     */
    public function copy(File $source, $name, $description = '')
    {
        // Copy single file?
        if ($source->storage_id != '') {
            $new_entry = $this->createFile($name, $description);
            
            $new_file = $new_entry->file;

            // copy contents
            $source_fp = $source->open('rb');
            $dest_fp   = $new_file->open('wb');
            stream_copy_to_stream($source_fp, $dest_fp);
            fclose($dest_fp);
            fclose($source_fp);

            // copy attributes
            $new_file->filename  = $source->filename;
            $new_file->restricted = $source->restricted;
            $new_file->mime_type = $source->mime_type;
            $new_file->size = $source->size;
            $new_file->update();

            return $new_entry;
        }
        
        //COPY directory
        $newFolder = $this->mkdir($name, $description);
        // Todo: This probably could be more sormy
        $folder = StudipDirectory::get($newFolder->file_id);
        $folder->filename = $newFolder->name;
        $folder->store();

        foreach ($source->listFiles() as $entry){
            $folder->copy($entry->file, $entry->name, $entry->description);
        }
        return $folder;
    }


    /**
     * Return the entry with the given name in this directory,
     * if one exists (returns NULL otherwise).
     *
     * @param string $name  file name
     *
     * @return DirectoryEntry  DirectoryEntry object or NULL
     */
    public function getEntry($name)
    {
        return DirectoryEntry::findOneBySQL('parent_id = ? AND name = ?', array($this->file_id, $name));
    }
    
    /**
     * Get access permissions for this directory. Access
     * permissions are not implemented at this time.
     */
    public function getPermissions()
    {
        // TODO not yet implemented
        return NULL;
    }

    /**
     * Returns number of linked files or folder in this directory.
     * 
     * @return int Number of linked files or folders
     */
    public function countFiles()
    {
        return count($this->files);
    }

    /**
     * Check whether this directory is empty.
     *
     * @return boolean  TRUE or FALSE
     */
    public function isEmpty()
    {
        return $this->countFiles() == 0;
    }

    /**
     * Check whether this directory is a root directory,
     * i.e. an instance of the RootDirectory class.
     *
     * @return boolean  TRUE or FALSE
     */
    public function isRootDirectory()
    {
        return $this instanceof RootDirectory;
    }

    /**
     * Create a new entry in this directory for the given file
     * under the given name. This will increase the link count
     * of the file by one.
     *
     * @param File $file    file to link
     * @param string $name  new file name
     * @param string $description optional description
     *
     * @return DirectoryEntry  created DirectoryEntry object
     */
    public function link(File $file, $name, $description = '')
    {
        $name = FileHelper::CompressFilename($name);
        $name = $this->ensureUniqueFilename($name, $file);

        $entry = new DirectoryEntry();
        $entry->file_id     = $file->id;
        $entry->parent_id   = $this->file_id;
        $entry->name        = $name;
        $entry->description = $description;
        $entry->store();
        
        return $entry;
    }

    /**
     * Ensure a unique filename.
     *
     * @param String $name Base file name that is supposed to be unique
     * @param File|null $file Optional associated file
     * @return String Unique filename
     */
    public function ensureUniqueFilename($name, File $file = null)
    {
        $changed = false;
        while (($temp = $this->getEntry($name)) && ($file === null || $temp->file->id !== $file->id)) {
            $changed = true;
            $name = FileHelper::AdjustFilename($name);
        }

        if ($changed && $file !== null) {
            $file->filename = $name;
            $file->store();
        }

        return $name;
    }

    /**
     * Return a list of all entries in this directory.
     * Each entry is returned as a DirectoryEntry object.
     *
     * @return array  array of DirectoryEntry objects
     */
    public function listFiles()
    {
        return $this->files->orderBy('name asc');
    }
    
    /**
     * Return a list of all child directories in this
     * directory.
     *
     * @return array array of DirectoryEntry objects
     */
    public function listDirectories()
    {
        return $this->listFiles()->filter(function ($file) {
            return $file->isDirectory();
        });
    }

    /**
     * Create a new sub directory with the given name in this
     * directory. It inherits the backend storage of its parent.
     *
     * @param string $name  directory name
     * @param int $parent_id place in folder hierarchy
     *
     */
    public function mkdir($name, $description = '')
    {
        $dir = new StudipDirectory();
        $dir->user_id    = $GLOBALS['user']->id;
        $dir->filename   = $name;
        $dir->mime_type  = '';
        $dir->size       = 0;
        $dir->restricted = false;
        $dir->storage    = $this->storage;
        $dir->storage_id = '';
        $dir->store();

        return $this->link($dir, $name, $description);
    }

    /**
     * Delete the contents of this directory (recursively).
     */
    public function delete()
    {
        foreach ($this->listFiles() as $entry) {
            $entry->file->delete();
        }

        parent::delete();
    }

    /**
     * Check if the directory's backend allows reading of files.
     *
     * @return boolean  TRUE or FALSE
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * Check if the directory's backend allows writing of files.
     *
     * @return boolean  TRUE or FALSE
     */
    public function isWritable()
    {
        return true;
    }

    /**
     * Opening a directory is not allowed.
     */
    public function open($mode)
    {
        throw new Exception('cannot open directory');
    }

    /**
     * Search this directory (recursively) for the given text.
     * Searching is not implemented at this time.
     */
    public function search($text)
    {
        // TODO not yet implemented
    }

    /**
     * Set access permissions for this directory. Access
     * permissions are not implemented at this time.
     */
    public function setPermissions($permissions)
    {
        // TODO not yet implemented
    }

    /**
     * Remove the entry with the given name from this directory.
     * If the file's link count drops to zero, it is deleted.
     *
     * @param string $name  file name
     */
    public function unlink($name)
    {
        $db = DBManager::get();

        $stmt = $db->prepare('SELECT file_id FROM file_refs WHERE name = ? AND parent_id = ?');
        $stmt->execute(array($name, $this->file_id));
        $file_id = $stmt->fetchColumn();

        if ($file_id) {
            $stmt = $db->prepare("DELETE FROM file_refs WHERE file_id = ? AND parent_id = ?");
            $stmt->execute(array($file_id, $this->file_id));

            // count links and delete storage if link count == 0
            $stmt = $db->prepare("SELECT COUNT(id) FROM file_refs WHERE file_id = ?");
            $stmt->execute(array($file_id));
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                $file = File::get($file_id);
                $file->delete();
            }
        }
    }

    /**
     * Updating a directory is not allowed (and not needed).
     */
    public function update()
    {
        throw new Exception('cannot update directory');
    }
}
