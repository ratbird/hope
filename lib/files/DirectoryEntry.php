<?php
/**
 * File.php
 *
 * Class to represent file and directory entries inside a directory.
 * This should probably use SimpleORMap.
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class DirectoryEntry extends SimpleORMap
{
    /**
     * Configures this model with additional fields for the file
     * and directory reference as well as a complete notification map.
     */
    protected static function configure($config = array())
    {
        $config['db_table'] = 'file_refs';
        $config['belongs_to']['directory'] = array(
            'class_name'        => 'DirectoryEntry',
            'foreign_key'       => 'parent_id',
            'assoc_foreign_key' => 'file_id',
        );
        

        $config['additional_fields']['file'] = array(
            'get' => function ($record, $field) {
                return File::get($record->file_id);
            }
        );

#        $config['additional_fields']['directory'] = array(
#            'get' => function ($record, $field) {
#                return File::get($record->parent_id);
#            }
#        );

        $config['notification_map'] = array(
            'before_create' => 'FileWillCreate',
            'after_create'  => 'FileDidCreate',
            'before_update' => 'FileWillChange',
            'after_update'  => 'FileDidChange',
            'before_delete' => 'FileWillDelete',
            'after_delete'  => 'FileDidDelete',
        );
        
        parent::configure($config);
    }
    
    /**
     * Initialize a new directory entry object for the given id.
     *
     * @param string $id  directory entry id
     * @throws InvalidArgumentException if id of directory entry is invalid
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        
        if ($id !== null && $this->isNew()) {
            throw new InvalidArgumentException('directory entry not found');
        }
    }

    /**
     * Set the new parent_id.
     *
     * @param String $parent_id Directory id of the new parent
     * @todo  Prevent impossible situations (move a folder inside itself)
     */
    public function move($parent_id)
    {
        $entries = DirectoryEntry::findByFile_id($this->file_id);
        if(count($entries) > 0) {
            $entries[0]->parent_id = $parent_id;
            $entries[0]->store();
        }
    }

    /**
    * Returns the Parent from an Entry.
    *
    * @return DirectoryEntry Parent entry
    * @throws Exception if no valid parent is found
    */
    public function getParent()
    {
        $entries = DirectoryEntry::findByFile_id($this->parent_id);
        if (count($entries) === 0) {
            throw new Exception('No parent found');
        }
        return $entries[0];
    }
    
    public function isDirectory()
    {
        return $this->file instanceof StudipDirectory;
    }
    
    public function getSize()
    {
        if ($this->isDirectory()) {
            $size = 0;
            foreach ($this->file->listFiles() as $file) {
                $size += $file->getSize();
            }
        } else {
            $size = $this->file->size;
        }
        return $size;
    }
    
    public function getDownloadLink($inline = false, $absolute = false)
    {
        if ($absolute) {
            $old_base_url = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        }

        $url = $inline
             ? URLHelper::getURL('dispatch.php/document/download/' . $this->id . '/inline')
             : URLHelper::getURL('dispatch.php/document/download/' . $this->id);

        if ($absolute) {
            URLHelper::setBaseURL($old_base_url);
        }

        return $url;
    }
}
