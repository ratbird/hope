<?php

/**
 * files.php
 *
 * Der Controller stellt angemeldeten Benutzer/innen einen Dateimanager
 * fuer deren persönlichen Dateibereich im Stud.IP zur Verfügung. In
 * diesem Controller werden sämtliche Dateioperationen gekapselt.
 *
 * @author    Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author    Stefan Osterloh <s.osterloh@uni-oldenburg.de>
 * @license   GPL2 or any later version
 * @copyright Stud.IP Core-Group
 * @since     3.1
 *
 * @todo Remove user dir creation from this controller, it is storage type specific
 * @todo Extends file extension black list to mime type black list?
 * @todo Inline display of media
 * @todo AJAX file upload
 * @todo Admin/root handling needs to be improved
 * @todo Test another storage type (DB? FTP?)
 * @todo Drag and drop move operation
 * @todo ?? Trash functionality (store deleted files in trash for X days)
 */

require_once 'document_controller.php';


class Document_FilesController extends DocumentController
{
    protected static $possible_limits = array(20, 50, 100);

    /**
     * Before filter, basically initializes the controller by actvating the
     * according navigation entry and other settings.
     *
     * @param String $action Action to execute
     * @param Array $args    Arguments passed for the action (might be empty)
     */
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

    /**
     * Displays the files in a speicfic folder.
     *
     * @param mixed $dir_id Directory entry id of the folder (default to root)
     */
    public function index_action($dir_id = null, $page = 1)
    {
        PageLayout::addScript('jquery/jquery.tablesorter.js');

        $dir_id = $dir_id ?: $this->context_id;
        try {
            $directory       = new DirectoryEntry($dir_id);
            $this->directory = $directory->file;
            $this->parent_id = FileHelper::getParentId($directory->id) ?: $this->context_id;
            $this->folder_id = $directory->parent_id;
            $parent_index    = $directory->indexInParent();
        } catch (Exception $e) {
            $this->directory = new RootDirectory($GLOBALS['perm']->have_perm('root') ? $dir_id : $this->context_id);
            $this->parent_id = null;
            $this->folder_id = $this->context_id;
            $parent_index    = false;
        }

        $this->directory->checkAccess();

        $this->filecount   = $this->directory->countFiles();
        $this->maxpages    = ceil($this->filecount / $this->limit);
        $this->page        = min($page, $this->maxpages);
        $this->parent_page = $this->getPageForIndex($parent_index);

        $this->files = $this->directory->listFiles(($this->page - 1) * $this->limit, $this->limit);

        $this->dir_id = $dir_id;
        $this->marked = $this->flash['marked-ids'] ?: array();
        $this->breadcrumbs = FileHelper::getBreadCrumbs($dir_id);

        $config = DocUsergroupConfig::getUserConfig($GLOBALS['user']->id);
        $this->space_used  = DiskFileStorage::getQuotaUsage($GLOBALS['user']->id);
        $this->space_total = $config['quota'];

        $this->setupSidebar($dir_id, $this->directory->id, $this->page);
    }

    /**
     * Upload a new file.
     *
     * @param String $folder_id Directory entry id of the folder to upload to
     */
    public function upload_action($folder_id, $page = 1)
    {
        PageLayout::setTitle(_('Datei hochladen'));

        $folder_id = $folder_id ?: $this->context_id;

        if ($folder_id === $this->context_id) {
            $directory = new RootDirectory($this->context_id);
        } else {
            $dirEntry = new DirectoryEntry($folder_id);
            $directory = $dirEntry->file;
        }
        $directory->checkAccess();

        if (Request::isPost()) {
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
                    $filename = $directory->ensureUniqueFilename($filename);

                    $this_title = $title;
                    if ($this_title && $count > 1) {
                        $this_title .= ' ' . sprintf(_('(%u von %u)'), $i + 1, $count);
                    }

                    $new_file = $directory->createFile($filename);
                    $new_file->description = $description;
                    $new_file->name        = $filename;
                    $new_file->store();

                    $handle = $new_file->file;
                    $handle->restricted = $restricted;
                    $handle->mime_type  = $mimetype;
                    $handle->size       = $filesize;

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

            $this->redirect('document/files/index/' . $folder_id . '/' . $page);
        }

        $this->folder_id = $folder_id;
        $this->page      = $page;

        PageLayout::setTitle(_('Datei hochladen'));
    }

    /**
     * Edits a file.
     *
     * @param String $entry_id Directory entry id of the file
     */
    public function edit_action($entry_id)
    {
        PageLayout::setTitle(_('Datei bearbeiten'));

        $entry = new DirectoryEntry($entry_id);
        $entry->checkAccess();

        if (Request::isPost()) {
            $name = Request::get('filename');
            $name = $entry->directory->ensureUniqueFilename($name, $entry->file);

            $entry->file->filename   = $name;
            $entry->file->restricted = Request::int('restricted', 0);

            $entry->name        = $name;
            $entry->description = Request::get('description');
            
            if ($entry->file->isDirty() || $entry->isDirty()) {
                $entry->store();
                $entry->file->store();

                $message = sprintf(_('Die Datei "%s" wurde bearbeitet.'), $entry->name);
                PageLayout::postMessage(MessageBox::success($message));
            }

            $this->redirect($this->url_for_parent_directory($entry));
            return;
        }

        $this->setDialogLayout('icons/100/lightblue/' . get_icon_for_mimetype($entry->file->mime_type));

        $this->entry = $entry;
    }

    /**
     * Move a file to another folder.
     *
     * @param String $file_id   Direcory entry id of the file to move
     *                          (use 'flashed' to read ids from from flash
     *                          memory for a bulk operation)
     * @param mixed  $source_id Optional folder id to return to after
     *                          operation has succeeded
     */
    public function move_action($file_id, $source_id = null)
    {
        PageLayout::setTitle(_('Datei verschieben'));

        if (Request::isPost()) {
            $folder_id = Request::option('folder_id');

            if ($file_id === 'flashed') {
                $ids = Request::optionArray('file_id');
            } else {
                $ids = array($file_id);
            }
            FileHelper::checkAccess($ids);

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
            FileHelper::checkAccess($this->flashed);
        } else {
            $this->parent_id = FileHelper::getParentId($file_id) ?: $this->context_id;
            FileHelper::checkAccess($file_id);
        }
        $this->active_folders = array_keys(FileHelper::getBreadCrumbs($this->parent_id));

        try {
            $parent = new DirectoryEntry($this->parent_id);
            $this->parent_file_id = $parent->file->id;
        } catch (Exception $e) {
            $this->parent_file_id = $this->context_id;
        }
    }

    /**
     * Copy a file to another folder.
     *
     * @param String $file_id   Direcory entry id of the file to copy
     *                          (use 'flashed' to read ids from from flash
     *                          memory for a bulk operation)
     * @param mixed  $source_id Optional folder id to return to after
     *                          operation has succeeded
     */
    public function copy_action($file_id, $source_id = null)
    {
        PageLayout::setTitle(_('Datei kopieren'));

         if (Request::isPost()) {
            $folder_id = Request::option('folder_id');
            $folder = StudipDirectory::get($folder_id);
            if ($file_id === 'flashed') {
                $ids = Request::optionArray('file_id');
            } else {
                $ids = array($file_id);
            }
            FileHelper::checkAccess($ids);

            if ($this->checkCopyQuota($ids)) {
                foreach ($ids as $id) {
                    $source_id = $source_id ? : FileHelper::getParentId($file_id) ?: $this->context_id;
                    $entry = DirectoryEntry::find($id);
                    $folder->copy($entry->file, FileHelper::ExtendFilename($entry->name, _('Kopie')), $entry->description);
                 }
                PageLayout::postMessage(MessageBox::success(_('Die ausgewählten Dateien wurden erfolgreich kopiert')));
            } else {
                PageLayout::postMessage(MessageBox::error(_('Der Kopiervorgang wurde abgebrochen, '.
                    'da Ihnen nicht genügend freier Speicherplatz zur Verfügung steht')));
            }

            $this->redirect($this->url_for_parent_directory($ids));
            return;
        }


        $this->file_id  = $file_id;
        $this->dir_tree = FileHelper::getDirectoryTree($this->context_id);

        if ($file_id === 'flashed') {
            $this->flashed   = $this->flash['copy-ids'];
            $this->parent_id = $source_id;

            FileHelper::checkAccess($this->flashed);
        } else {
            $this->parent_id = FileHelper::getParentId($file_id) ?: $this->context_id;
            FileHelper::checkAccess($file_id);
        }
        $this->active_folders = array_keys(FileHelper::getBreadCrumbs($this->parent_id));

        try {
            $parent = new DirectoryEntry($this->parent_id);
            $this->parent_file_id = $parent->file->id;
        } catch (Exception $e) {
            $this->parent_file_id = $this->context_id;
        }
    }

    /**
     * Checks whether it is possible to copy the files (given by their ids)
     * to another folder by determining whether the remaining disk space is
     * sufficient for the files.
     *
     * @param Array $ids Directory entry ids of the files to copy
     */
    protected function checkCopyQuota($ids)
    {
        $size = 0;
        foreach ($ids as $id) {
            $size += DirectoryEntry::find($id)->getSize();
        }

        $restQuota = $this->userConfig['quota'] - DiskFileStorage::getQuotaUsage($GLOBALS['user']->id);

        return $restQuota > $copySize;
    }

    /**
     * Deletes a file.
     *
     * @param String $id Directory entry id of the file to delete
     */
    public function delete_action($id)
    {
        $entry = DirectoryEntry::find($id);
        $parent_id = FileHelper::getParentId($id) ?: $this->context_id;

        $entry->checkAccess();

        if (!Request::isPost()) {
            $question = createQuestion2(_('Soll die Datei wirklich gelöscht werden?'),
                                        array(), array(),
                                        $this->url_for('document/files/delete/' . $id));
            $this->flash['question'] = $question;
        } elseif (Request::isPost() && Request::submitted('yes')) {
            File::get($entry->directory->id)->unlink($entry->name);
            PageLayout::postMessage(MessageBox::success(_('Die Datei wurde gelöscht.')));
        }

        $this->redirect('document/files/index/' . $parent_id);
    }

    /**
     * General handler for bulk actions. Support the following actions:
     *
     * - Download
     * - Move
     * - Copy
     * - Delete
     *
     * @param String $folder_id Directory entry id of the origin folder
     */
    public function bulk_action($folder_id, $page = 1)
    {
        $ids = Request::optionArray('ids');
        FileHelper::checkAccess($ids);

        if (empty($ids)) {
            $this->redirect('document/files/index/' . $folder_id . '/' . $page);
        } else if (Request::submitted('download')) {
            $this->flash['ids'] = $ids;
            $this->redirect('document/download/flashed');
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

            $this->redirect('document/files/index/' . $folder_id . '/' . $page);
        }
    }

    public function settings_action($limit, $page, $directory)
    {
        if (!in_array($limit, self::$possible_limits)) {
            $limit = Config::get()->ENTRIES_PER_PAGE;
        }
        $GLOBALS['user']->cfg->store('PERSONAL_FILES_ENTRIES_PER_PAGE', $limit);

        $page = $this->getPageForIndex(($page - 1) * $this->limit + 1, $limit);

        $this->redirect('document/files/index/' . $directory . '/' . $page);
    }

    /**
     * Defines the elements in the sidebar.
     *
     * @param String $current_entry Directory entry id of the current folder
     * @param String $current_dir   File id of the current folder
     */
    private function setupSidebar($current_entry, $current_dir, $page = 1)
    {
        $root_dir   = RootDirectory::find($this->context_id);
        $root_count = $root_dir->countFiles(true, false);

        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/files-sidebar.png');

        $widget = new ActionsWidget();

        $widget->addLink(_('Datei hochladen'),
                         $this->url_for('document/files/upload/' . $current_entry . '/' . $page),
                         'icons/16/blue/upload.png',
                         $this->userConfig['forbidden']
                             ? array('disabled' => '',
                                     'title' => _('Ihre Upload-Funktion wurde gesperrt.'))
                             : array())
               ->asDialog('size=auto');

        $widget->addLink(_('Neuen Ordner erstellen'),
                         $this->url_for('document/folder/create/' . $current_entry),
                         'icons/16/blue/add/folder-empty.png')
               ->asDialog('size=auto');

        $attributes = $root_count > 0
                    ? array()
                    : array(
                        'disabled' => true,
                        'title'    => _('Ihr Dateibereich enthält keine Dateien'),
                      );

        $widget->addLink(_('Dateibereich leeren'),
                         $this->url_for('document/folder/delete/all'),
                         'icons/16/blue/trash.png',
                         $attributes);

        $sidebar->addWidget($widget);

        $widget = new OptionsWidget();
        $widget->setTitle(_('Darstellung anpassen'));
        foreach (self::$possible_limits as $limit) {
            $widget->addRadioButton(sprintf(_('%u Einträge pro Seite anzeigen'), $limit),
                                    $this->url_for('document/files/settings/' . $limit . '/' . $page . '/' .  $current_entry),
                                    $limit == $this->limit);
        }
        $sidebar->addWidget($widget);

        // Show export options only if zip extension is loaded
        // TODO: Implement fallback
        if (extension_loaded('zip')) {
            $widget = new ExportWidget();

            $this_dir = $current_dir === $this->context_id
                      ? $root_dir
                      : StudipDirectory::find($current_dir);

            $attributes = $this_dir->countFiles(true, false) > 0
                        ? array()
                        : array(
                            'disabled' => true,
                            'title'    => _('Dieser Ordner enthält keine Dateien'),
                          );
            $widget->addLink(_('Inhalt dieses Ordners herunterladen'),
                             $this->url_for('document/download/' . $current_dir),
                             'icons/16/blue/file-archive.png',
                             $attributes);

            $attributes = $root_count > 0
                        ? array()
                        : array(
                            'disabled' => true,
                            'title'    => _('Ihr Dateibereich enthält keine Dateien'),
                          );
            $widget->addLink(_('Alle meine Dateien herunterladen'),
                             $this->url_for('document/download/' . $this->context_id),
                             'icons/16/blue/download.png',
                             $attributes);

            $sidebar->addWidget($widget);
        }
    }
}
