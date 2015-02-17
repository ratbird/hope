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

        // Try to extract previous number
        if (preg_match('/\s?\((\d+)\)$/', $pathinfo['filename'], $match)) {
            $number = $match[1] + 1;

            $replacement = str_replace($match[0], '', $pathinfo['filename']);
            $filename    = str_replace($pathinfo['filename'], $replacement, $filename);
        } else {
            $number = 1;
        }

        return self::ExtendFilename($filename, $number);
    }

    public static function ExtendFilename($filename, $extension, $wrap = array('(', ')'))
    {
        $pathinfo = pathinfo($filename);

        $filename  = $pathinfo['filename'];
        $filename .= ' ' . $wrap[0] . $extension . $wrap[1];

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

    /**
     * Checks whether the user may access a file or a bunch of files if you
     * pass an array.
     *
     * @param mixed $files Either a single directory entry id or an array of those
     *                     (DirectoryEntry or File object(s) are also valid)
     * @param mixed $user_id Id of the user or null for current user (default)
     * @param bool $throw_exception Throw an AccessDeniedException instead of
     *                              returning false
     * @return bool indicating whether the user may access this file/these files
     * @throws AccessDeniedException if $throw_exception is true and the user
     *                               may not access the file(s)
     */
    public static function CheckAccess($files, $user_id = null, $throw_exception = true)
    {
        if (!is_array($files)) {
            $files = array($files);
        }

        $user_id = $user_id ?: $GLOBALS['user']->id;

        foreach ($files as $file) {
            try {
                if (!is_object($file)) {
                    $file = DirectoryEntry::find($file);
                }
                if ($file instanceof DirectoryEntry) {
                    $file = $file->file;
                }
                if (!$file instanceof File || !$file->checkAccess()) {
                    throw new Exception();
                }
            } catch (Exception $e) {
                if (!is_object($file) && ($file === $GLOBALS['user']->id || $GLOBALS['perm']->have_perm('root'))) {
                    continue;
                }
                if ($throw_exception) {
                    throw new AccessDeniedException(_('Sie dürfen auf dieses Objekt nicht zugreifen.'));
                }
                return false;
            }
        }
        return true;
    }
}