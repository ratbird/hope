<?php
/**
 * download.php
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-3.0
 * @copyright   Stud.IP Core-Group
 * @since       3.1
 */

require_once 'app/controllers/studip_controller.php';

class Document_DownloadController extends StudipController
{
    // This is duplicated in app/controllers/document/document_controller.php
    // TODO: Convert this into a trait as soon as Stud.IP supports PHP 5.4
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
        $entry = new DirectoryEntry($entry_id);
        $file  = $entry->file;

        if ($file instanceof StudipDirectory) {
            throw new Exception('Cannot download directory');
        }

        $storage = $file->getStorageObject();
        if (!$storage->exists() || !$storage->isReadable()) {
            throw new Exception('Cannot access file "' . $storage->getPath() . '"');
        }

        $entry->downloads += 1;
        $entry->store();

        $this->initiateDownload($inline, $file->filename, $file->mime_type, $file->size, $storage->open('r'));
    }

    // This is duplicated in app/controllers/document/document_controller.php
    // TODO: Convert this into a trait as soon as Stud.IP supports PHP 5.4
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
                              urlencode($filename));
        $response->add_header('Content-Disposition', $dispositon);
        $response->add_header('Content-Description', 'File Transfer');
        $response->add_header('Content-Transfer-Encoding' , 'binary');
        $response->add_header('Content-Type', $mime_type);
        $response->add_header('Content-Length', $size);

        $this->render_nothing();

        $this->download_handle = $handle;
    }

    // This is duplicated in app/controllers/document/document_controller.php
    // TODO: Convert this into a trait as soon as Stud.IP supports PHP 5.4
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
