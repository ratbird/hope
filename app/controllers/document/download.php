<?php
/**
 * download.php
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-3.0
 * @copyright   Stud.IP Core-Group
 * @since       3.1
 */

require_once 'app/controllers/authenticated_controller.php';

class Document_DownloadController extends AuthenticatedController
{
    public function __construct($dispatcher)
    {
        if (Request::get('cid')) {
            Request::set('cid', null);
        }

        parent::__construct($dispatcher);
    }

    protected $allow_nobody = true;
    
    protected $download_handle = null;
    protected $download_remove = null;

    public function before_filter(&$action, &$args)
    {
        if ($action !== 'index') {
            array_unshift($args, $action);
            $action = 'index';
        }

        parent::before_filter($action, $args);
    }

    public function index_action($entry_id, $inline = false)
    {
        $download_files = array();
        $download_type  = 'single';
        $filename       = null;

        // Download files from bulk-action?
        if ($entry_id === 'flashed') {
            foreach ($this->flash['ids'] as $id) {
                $entry   = new DirectoryEntry($id);
                if ($entry->isDirectory() && $entry->file->user_id !== $GLOBALS['user']->id) {
                    throw new AccessDeniedException(_('Sie dürfen keinen fremden Ordner herunterladen.'));
                }
                $download_files[] = $entry;
            }
            $download_type = 'multiple';
        } else {
            try {
                // Get directory entry
                $entry = new DirectoryEntry($entry_id);

                // Determine download type
                $filename         = $entry->file->filename;
                $download_files[] = $entry;
                $download_type    = $entry->isDirectory()
                                  ? 'multiple'
                                  : 'single';

                if ($entry->file->user_id !== $GLOBALS['user']->id) {
                    if ($entry->isDirectory()) {
                        throw new AccessDeniedException(_('Sie dürfen keinen fremden Ordner herunterladen.'));
                    } elseif (false) {
                        // TODO: Permission check inactive for now
                        throw new AccessDeniedException(_('Sie dürfen keine fremde Datei herunterladen.'));
                    }
                }
            } catch (InvalidArgumentException $e) {
                // Entry id is not a valid directory entry,
                // so we assume that it is a foreign folder
                if ($entry_id !== $GLOBALS['user']->id) {
                    if (User::exists($entry_id)) {
                        throw new AccessDeniedException(_('Sie dürfen keinen fremden Ordner herunterladen.'));
                    } else {
                        throw new InvalidArgumentException(_('404 - File not found'));
                    }
                }

                $download_files[] = new RootDirectory($entry_id);
                $download_type    = 'multiple';
            }
        }

        // Download either a zip file or single file
        if ($download_type === 'multiple') {
            $this->download_files($download_files, $filename ?: 'Stud-IP');
        } else {
            $this->download_file(reset($download_files), $inline);
        }
    }

    protected function download_file(DirectoryEntry $entry, $inline)
    {
        $file = $entry->file;

        $storage = $file->getStorageObject();
        if (!$storage->exists() || !$storage->isReadable()) {
            throw new Exception('Cannot access file "' . $storage->getPath() . '"');
        }

        $entry->downloads += 1;
        $entry->store();

        $this->initiateDownload($inline, $file->filename, $file->mime_type, $file->size, $storage->open('r'));
    }
    
    protected function download_files($files, $filename = 'Stud-IP.zip')
    {
        $files = (array)$files;
        
        $tmp_file = tempnam($GLOBALS['TMP_PATH'], 'doc');
        $zip = new ZipArchive();
        $open_result = $zip->open($tmp_file, ZipArchive::CREATE);
        if (true !== $open_result) {
            throw new Exception('Could not create zip file (' . $open_result . ')');
        }

        foreach ($files as $file) {
            $this->addToZip($zip, $file, '', $remove);
        }
        if (true !== ($close_result = $zip->close())) {
            throw new Exception('Could not close zip file (' . $close_result . ')');
        }

        array_map('unlink', $remove);

        // TODO: swap "Stud-IP.zip" with a more appropriate name
        $filename = basename($filename, '.zip') . '.zip';
        $this->initiateDownload(false, $filename, 'application/zip', filesize($tmp_file), fopen($tmp_file, 'r'));
        $this->download_remove = $tmp_file;
        
    }

    protected function addToZip(&$zip, $entry, $path = '', &$remove = array())
    {
        if ($entry instanceof DirectoryEntry) {
            $entry->downloads += 1;
            $entry->store();
            
            $entry = $entry->file;
        }
        
        $path = rtrim($path, '/');
        if ($entry instanceof StudipDirectory) {
            if ($entry->countFiles(true, false) > 0) {
                $path = ltrim($path . '/' . $entry->filename, '/');
                if ($path && true !== $zip->addEmptyDir($path)) {
                    throw new Exception('Can not add dir "' . $path . '"');
                }
                foreach ($entry->listFiles() as $file) {
                    $this->addToZip($zip, $file, $path, $remove);
                }
            }
        } else {
            $tmp_file = tempnam($GLOBALS['TMP_PATH'], 'zip');
            $source      = $entry->open('r');
            $destination = fopen($tmp_file, 'w+');

            stream_copy_to_stream($source, $destination);
            fclose($source);
            fclose($destination);

            if (!file_exists($tmp_file) || !is_readable($tmp_file) || filesize($tmp_file) === 0) {
                throw new Exception('Empty file');
            }
            if (true !== $zip->addFile($tmp_file, $path . '/' . $entry->filename)) {
                throw new Exception('Could not add file');
            }
            $remove[] = $tmp_file;
        }
    }

    protected function sanitizeFilename($filename)
    {
        // Remove umlauts, seems hackish but works great
        // Taken from http://stackoverflow.com/a/5950598
        $filename = htmlentities($filename, ENT_QUOTES, 'ISO-8859-1');
        $filename = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $filename), ENT_QUOTES, 'ISO-8859-1');

        // Combine dashes and whitespace
        $filename = preg_replace('/[\s-]+/', '-', $filename);
        
        // Remove all characters that are not dashes, dots or alphanumeric
        $filename = preg_replace('/[^0-9a-z_\-\.]/i', '', $filename);

        return $filename;
    }

    protected function initiateDownload($inline, $filename, $mime_type, $size, $handle)
    {
        $response = $this->response;

        if ($_SERVER['HTTPS'] === 'on') {
            $response->add_header('Pragma', 'public');
            $response->add_header('Cache-Control', 'private');
        } else {
            $response->add_header('Pragma', 'no-cache');
            $response->add_header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        $dispositon = sprintf('%s;filename="%s"',
                              $inline ? 'inline' : 'attachment',
                              urlencode($this->sanitizeFilename($filename)));
        $response->add_header('Content-Disposition', $dispositon);
        $response->add_header('Content-Description', 'File Transfer');
        $response->add_header('Content-Transfer-Encoding' , 'binary');
        $response->add_header('Content-Type', $mime_type);
        $response->add_header('Content-Length', $size);

        $this->render_nothing();

        $this->download_handle = $handle;
    }

    public function after_filter($action, $args)
    {
        parent::after_filter($action, $args);

        if ($this->download_handle !== null && is_resource($this->download_handle)) {
            fpassthru($this->download_handle);
            fclose($this->download_handle);
        }
        if ($this->download_remove !== null && ($this->download_remove) && file_exists($this->download_remove)) {
            unlink($this->download_remove);
        }
    }
}
