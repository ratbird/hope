<?php

/**
 * files.php
 *
 * Der Controller stellt angemeldeten Benutzer/innen einen Dateimanager
 * fuer deren persoenlichen Dateibereich im Stud.IP zur Verfuegung.
 *
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author      Stefan Osterloh <s.osterloh@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0
 * @copyright   Stud.IP Core-Group
 * @since       3.1
 *
 * @todo        Remove user dir creation from this controller, it is storage type specific
 * @todo        Extends file extension black list to mime type black list?
 * @todo        Info page for # of downloads
 * @todo        Inline display of media
 * @todo        AJAX file upload
 * @todo        Admin/root handling needs to be improved
 * @todo        ZIP extract in local file space?
 * @todo        Test another storage type (DB? FTP?)
 * @todo        Drag and drop move operation
 * @todo        ?? Trash functionality (store deleted files in trash for X days)
 */

require_once 'document_controller.php';


class Document_FilesController extends DocumentController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        //Setup the user's sub-directory in $USER_DOC_PATH
        $userdir = $GLOBALS['USER_DOC_PATH'] . '/' . $this->context_id . '/';

        if (!file_exists($userdir)) {
            mkdir($userdir, 0755, true);
        }

        PageLayout::setTitle(_('Dateiverwaltung'));
        PageLayout::setHelpKeyword('Basis.Dateien');
        Navigation::activateItem('/profile/files');
        
        PageLayout::addSqueezePackage('document');
    }

    public function index_action($dir_id = null)
    {
        $dir_id = $dir_id ?: $this->context_id;
        $this->setupSidebar($dir_id);
        try {
            $directory = new DirectoryEntry($dir_id);
            $this->directory = $directory->file;
            $this->files     = $this->directory->listFiles();
            $this->folder_id = $directory->parent_id;
        } catch (Exception $e) {
            $this->directory = new RootDirectory($this->context_id);
            $this->files     = $this->directory->listFiles();
            $this->parent_id = null;
            $this->folder_id = $this->context_id;
        }

        if (isset($directory)) {
            $this->parent_id = $directory->directory->id ?: $this->context_id;
        }

        $this->dir_id = $dir_id;
        $this->marked = $this->flash['marked-ids'] ?: array();
        $this->breadcrumbs = FileHelper::getBreadCrumbs($dir_id);

        $config = DocUsergroupConfig::getUserConfig($GLOBALS['user']->id);
        $this->space_used  = DiskFileStorage::getQuotaUsage($GLOBALS['user']->id);
        $this->space_total = $config['quota'];
    }

    public function upload_action($folder_id)
    {
        $folder_id = $folder_id ?: $this->context_id;

        if (Request::isPost()) {
            if ($folder_id === $this->context_id) {
                $directory = new RootDirectory($this->context_id);
            } else {
                $dirEntry = new DirectoryEntry($folder_id);
                $directory = $dirEntry->file;
            }
            
            $title       = Request::get('title');
            $description = Request::get('description', '');
            $restricted  = Request::int('restricted', 0);

            $count = count($_FILES['file']['name']);

            $failed = array();
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['file']['error'][$i] !== 0) {
                    $failed[] = array($_FILES['file']['name'][$i], 'remote');
                    continue;
                }

                $filename = $_FILES['file']['name'][$i];
                $filesize = $_FILES['file']['size'][$i];
                $mimetype = $_FILES['file']['type'][$i];
                $tempname = $_FILES['file']['tmp_name'][$i];

                $fileExtension = explode('.', $filename);
                if(!empty($fileExtension) && !empty($this->userConfig['types'])){
                    foreach($this->userConfig['types'] as $typ){
                        if($typ['type']==$fileExtension[count($fileExtension)-1]){
                            $failed[] = array($_FILES['file']['name'][$i], 'forbidden_type');
                        }
                    }
                }

                $restQuota = ( (int)$this->userConfig['quota'] -
                        DiskFileStorage::getQuotaUsage($GLOBALS['user']->id));

                if ($filesize > $restQuota){
                    $failed[] = array($_FILES['file']['name'][$i], 'quota');

                } else if ($filesize > (int)$this->userConfig['upload_quota']){
                       $failed[] = array($_FILES['file']['name'][$i], 'upload_quota');

                } else {
                     while ($directory->getEntry($filename) !== null) {
                        $filename = FileHelper::AdjustFilename($filename);
                    }
                    $this_title = $title;
                    if ($this_title && $count > 1) {
                        $this_title .= ' ' . sprintf(_('(%u von %u)'), $i + 1, $count);
                    }

                    $new_file = $directory->createFile($filename);
                    $new_file->description = $description;
                    $new_file->name        = $this_title ?: $filename;
                    $new_file->store();

                    $handle = $new_file->file;
                    $handle->restricted = $restricted;
                    $handle->mime_type  = $mimetype;
                    //echo $filesize;die;
                    $handle->size = $filesize;
                        
                    try {
                        $handle->setContentFromFile($tempname);
                        $handle->update();
                    } catch (Exception $e) {
                        if (Studip\ENV === 'development') {
                            throw $e;
                        } else {
                            $failed[] = array($filename, 'local');
                            $handle->delete();
                        }
                    }
                }
            }
            if (!empty($failed)) {
                $remote = array_map('reset', array_filter($failed, function ($item) {
                    return $item[1] === 'remote';
                }));
                if (!empty($remote)) {
                    $message = MessageBox::error(_('Folgende Dateien wurden fehlerhaft hochgeladen:'),
                                                 $remote);
                    PageLayout::postMessage($message);
                }

                $forbidden = array_map('reset', array_filter($failed, function($item) {
                    return $item[1] === 'forbidden_type' ;
                }));
                if (!empty($forbidden)){
                    $message = MessageBox::error(_('Der Upload folgender Dateien ist verboten:'),
                                                 $forbidden);
                    PageLayout::postMessage($message);
                }

                $quota = array_map('reset', array_filter($failed, function($item) {
                    return $item[1] === 'quota' ;
                }));
                if (!empty($quota)){
                    $message = MessageBox::error(_('Für folgende Dateien ist der verbleibende Speicherplatz zu klein:'),
                                                 $quota);
                    PageLayout::postMessage($message);
                }

                $upload = array_map('reset', array_filter($failed, function($item) {
                    return $item[1] === 'upload_quota' ;
                }));
                if (!empty($upload)){
                    $message = MessageBox::error(_('Folgende Dateien sind zu groß für den Upload:'),
                                                 $upload);
                    PageLayout::postMessage($message);
                }

                $local = array_map('reset', array_filter($failed, function ($item) {
                    return $item[1] === 'local';
                }));
                if (!empty($local)) {
                    $message = MessageBox::error(_('Folgende Dateien konnten nicht gespeichert werden:'),
                                                 $local);
                    PageLayout::postMessage($message);
                }
            }
            if ($count - count($failed) > 0) {
                $message = sprintf(_('%u Dateien wurden erfolgreich hochgeladen.'), $count - count($failed));
                PageLayout::postMessage(MessageBox::success($message));
            }

            $this->redirect('document/files/index/' . $folder_id);
        }

        $this->folder_id = $folder_id;

        $this->setDialogLayout('icons/48/blue/upload.png');

        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Datei hochladen'));
            
        }
    }

    public function edit_action($entry_id)
    {
        $entry = new DirectoryEntry($entry_id);

        if (Request::isPost()) {
            $entry->file->filename   = Request::get('filename');
            $entry->file->restricted = Request::int('restricted', 0);
            $entry->file->store();

            $entry->name        = Request::get('name');
            $entry->description = Request::get('description');
            $entry->store();

            PageLayout::postMessage(MessageBox::success(_('Die Datei wurde bearbeitet.')));
            $this->redirect('document/files/index/' . FileHelper::getParentId($entry_id) ?: $this->context_id);
            return;
        }

        $this->setDialogLayout('icons/48/blue/edit.png');

        $this->entry = $entry;

        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Datei bearbeiten'));
        }
    }

    public function move_action($file_id, $source_id = null)
    {
        if (Request::isPost()) {
            $folder_id = Request::option('folder_id');

            if ($file_id === 'flashed') {
                $ids = Request::optionArray('file_id');
            } else {
                $ids = array($file_id);
            }

            foreach ($ids as $id) {
                $source_id = $source_id ?: FileHelper::getParentId($file_id) ?: $this->context_id;

                $entry = new DirectoryEntry($id);
                $entry->move($folder_id);
            }

            $message = ngettext('Die Datei wurde erfolgreich verschoben', 'Die Dateien wurden erfolgreich verschoben', count($ids));
            PageLayout::postMessage(MessageBox::success($message));

            $this->redirect('document/files/index/' . $source_id);
            return;
        }

        $this->file_id  = $file_id;
        $this->dir_tree = FileHelper::getDirectoryTree($this->context_id);

        if ($file_id === 'flashed') {
            $this->flashed = $this->flash['move-ids'];
            $this->parent_id = $source_id;
        } else {
            $this->parent_id = FileHelper::getParentId($file_id) ?: $this->context_id;
        }
        $this->active_folders = array_keys(FileHelper::getBreadCrumbs($this->parent_id));

        try {
            $parent = new DirectoryEntry($this->parent_id);
            $this->parent_file_id = $parent->file->id;
        } catch (Exception $e) {
            $this->parent_file_id = $this->context_id;
        }
    }

    public function copy_action($file_id, $source_id = null)
    {
         if (Request::isPost()) {
            $folder_id = Request::option('folder_id');
            $folder = StudipDirectory::get($folder_id);
            if ($file_id === 'flashed') {
                $ids = Request::optionArray('file_id');
            } else {
                $ids = array($file_id);
            }
            if ($this->checkCopyQuota($ids)) {
                foreach ($ids as $id) {
                    $source_id = $source_id ? : FileHelper::getParentId($file_id) ?: $this->context_id;
                    $entry = DirectoryEntry::find($id);
                    $folder->copy($entry->file, sprintf('%s (%s)', $entry->name, _('Kopie')), $entry->description);
                 }
                PageLayout::postMessage(MessageBox::success(_('Die ausgewählten Dateien wurden erfolgreich kopiert')));
            } else {
                PageLayout::postMessage(MessageBox::error(_('Der Kopiervorgang wurde abgebrochen, '.
                    'da Ihnen nicht genügend freier Speicherplatz zur Verfügung steht')));
            }
            
            $this->redirect('document/files/index/' . $source_id);
            return;
        }
        
        
        $this->file_id  = $file_id;
        $this->dir_tree = FileHelper::getDirectoryTree($this->context_id);

        if ($file_id === 'flashed') {
            $this->flashed   =  $this->flash['copy-ids'];
            $this->parent_id = $source_id;
        } else {
            $this->parent_id = FileHelper::getParentId($file_id) ?: $this->context_id;
        }
        $this->active_folders = array_keys(FileHelper::getBreadCrumbs($this->parent_id));

        try {
            $parent = new DirectoryEntry($this->parent_id);
            $this->parent_file_id = $parent->file->id;
        } catch (Exception $e) {
            $this->parent_file_id = $this->context_id;
        }
    }
    
    public function checkCopyQuota($ids)
    {
        $size = 0;
        foreach ($ids as $id) {
            $size += DirectoryEntry::find($id)->getSize();
        }

        $restQuota = $this->userConfig['quota'] -  DiskFileStorage::getQuotaUsage($GLOBALS['user']->id);

        return $restQuota > $copySize;
    }
    
    public function download_action($entry_id, $inline = false)
    {
        $entry = new DirectoryEntry($entry_id);
        $file  = $entry->file;

        if ($file instanceof StudipDirectory) {
            throw new Exception('Cannot download directory');
        }

        $storage = $file->getStorageObject();
        if (!$storage->exists() || !$storage->isReadable()) {
            throw new Exception('Cannot access file');
        }

        $entry->downloads += 1;
        $entry->store();

        $this->initiateDownload($inline, $file->filename, $file->mime_type, $file->size, $storage->open('r'));
    }

    public function delete_action($id)
    {
        $entry = DirectoryEntry::find($id);
        $parent_id = $entry->directory->id ?: $this->context_id;

        if (!Request::isPost()) {
            $question = createQuestion2(_('Soll die Datei wirklich gelöscht werden?'),
                                        array(), array(),
                                        $this->url_for('document/files/delete/' . $id));
            $this->flash['question'] = $question;
        } elseif (Request::isPost() && Request::submitted('yes')) {
            File::get($parent_id)->unlink($entry->name);
            PageLayout::postMessage(MessageBox::success(_('Die Datei wurde gelöscht.')));
        }
        $this->redirect('document/files/index/' . $parent_id);
    }

    public function bulk_action($folder_id)
    {
        $ids = Request::optionArray('ids');

        if (empty($ids)) {
            $this->redirect('document/files/index/' . $folder_id);
        } else if (Request::submitted('download')) {
            $this->flash['download-ids'] = $ids;
            $this->redirect('document/folder/download/flashed');
        } else if (Request::submitted('move')) {
            $this->flash['move-ids'] = $ids;
            $this->redirect('document/files/move/flashed/' . $folder_id);
        } else if (Request::submitted('copy')) {
            $this->flash['copy-ids'] = $ids;
            $this->redirect('document/files/copy/flashed/' . $folder_id);
        } else if (Request::submitted('delete')) {
            if (Request::submitted('yes')) {
                if ($folder_id === $this->context_id) {
                    $dir = new RootDirectory($this->context_id);
                } else {
                    $entry = new DirectoryEntry($folder_id);
                    $dir   = $entry->file;
                }
                foreach ($ids as $id) {
                    $entry = new DirectoryEntry($id);
                    $dir->unlink($entry->name);
                }
                PageLayout::postMessage(MessageBox::success(_('Die Dateien wurden erfolgreich gelöscht.')));
            } elseif (!Request::submitted('no')) {
                $question = createQuestion2(_('Sollen die markierten Dateien wirklich gelöscht werden?'),
                                            array('delete' => 'true', 'ids' => $ids), array(),
                                            $this->url_for('document/files/bulk/' . $folder_id));
                $this->flash['question']   = $question;

                $this->flash['marked-ids'] = $ids;
            }

            $this->redirect('document/files/index/' . $folder_id);
        }
    }

    private function setupSidebar($current_dir)
    {
        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/files-sidebar.png');

        $widget = new ActionsWidget();

        $widget->addLink(_('Datei hochladen'),
                         $this->url_for('document/files/upload/' . $current_dir),
                         'icons/16/blue/upload.png',
                         $this->userConfig['forbidden']
                             ? array('disabled' => '',
                                     'title' => _('Ihre Upload-Funktion wurde gesperrt.'))
                             : array())
               ->asDialog();

        $widget->addLink(_('Neuen Ordner erstellen'),
                         $this->url_for('document/folder/create/' . $current_dir),
                         'icons/16/blue/add/folder-empty.png')
               ->asDialog();

        $widget->addLink(_('Dateibereich leeren'),
                         $this->url_for('document/folder/delete/all'),
                         'icons/16/blue/trash.png');
        $sidebar->addWidget($widget);


        $widget = new ExportWidget();
        $widget->addLink(_('Inhalt dieses Ordners herunterladen'),
                         $this->url_for('document/folder/download/' . $current_dir),
                         'icons/16/blue/file-archive.png');
        $widget->addLink(_('Alle meine Dateien herunterladen'),
                         $this->url_for('document/folder/download/' . $this->context_id),
                         'icons/16/blue/download.png');
        $sidebar->addWidget($widget);
    }
}
