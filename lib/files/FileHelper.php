<?php
class FileHelper
{
    public static function CompressFilename($filename)
    {
        $pathinfo = pathinfo($filename);

        if (preg_match_all('/\s\(' . _('Kopie') . '\s*(\d*)\)/', $pathinfo['filename'], $matches, PREG_SET_ORDER) <= 1) {
            return $filename;
        }
        $filename = $pathinfo['filename'];
        $number = 1;
            
        foreach ($matches as $match) {
            $number += $match[1] ? intval($match[1]) : 1;
            $filename = str_replace($match[0], '', $filename);
        }
        $filename = trim($filename);
        
        $filename .= sprintf(' (%s %u)', _('Kopie'), $number);

        if (!empty($pathinfo['extension'])) {
            $filename .= '.' . $pathinfo['extension'];
        }
        if (!empty($pathinfo['dirname']) && $pathinfo['dirname'] !== '.') {
            $filename = $pathinfo['dirname'] . '/' . $filename;
        }

        return $filename;
    }
    
    public static function AdjustFilename($filename)
    {
        $pathinfo = pathinfo($filename);

        if (preg_match('/\s?\((\d+)\)$/', $pathinfo['filename'], $match)) {
            $filename = str_replace($match[0], '', $pathinfo['filename']);
            $number = $match[1] + 1;
        } else {
            $filename = $pathinfo['filename'];
            $number = 1;
        }
        
        $filename .= sprintf(' (%u)', $number);

        if (!empty($pathinfo['extension'])) {
            $filename .= '.' . $pathinfo['extension'];
        }
        if (!empty($pathinfo['dirname']) && $pathinfo['dirname'] !== '.') {
            $filename = $pathinfo['dirname'] . '/' . $filename;
        }

        return $filename;
    }

    public static function getParentId($entry_id)
    {
        try {
            $entry  = new DirectoryEntry($entry_id);
            $parent = $entry->getParent();
            $parent_id = $parent->id;
        } catch (Exception $e) {
            $parent_id = null;
        }
        return $parent_id;
    }

    
    public static function getBreadCrumbs($entry_id)
    {
        $crumbs = array();

        do {
            try {
                $entry = new DirectoryEntry($entry_id);
                $crumbs[$entry->file->id] = array(
                    'id'   => $entry_id,
                    'name' => $entry->file->filename,
                    'description' => $entry->description,
                );
                $entry_id = self::getParentId($entry_id);
            } catch (Exception $e) {
                break; // No parent directory, so we are at root level
            }
        } while ($entry_id);

        $crumbs[$entry_id] = array(
            'id'   => $entry_id,
            'name' => _('Hauptverzeichnis'),
            'description' => '',
        );

        return array_reverse($crumbs);
    }

    public static function GetDirectoryTree($folder_id)
    {
        // TODO Top level?
        $result = array();
        
        $folder = new StudipDirectory($folder_id);
        
        foreach ($folder->listFiles() as $entry) {
            $file = $entry->file;
            if ($file instanceof StudipDirectory) {
                $result[$file->file_id] = array(
                    'ref_id'      => $entry->id,
                    'filename'    => $file->filename,
                    'description' => $entry->description,
                    'children'    => self::getDirectoryTree($file->file_id),
                );
            }
        }

        return $result;
    }
}