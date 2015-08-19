<?php
/**
 * folder.php
 *
 * Der Controller stellt angemeldeten Benutzer/innen einen Dateimanager
 * fuer deren pers�nlichen Dateibereich im Stud.IP zur Verfuegung. In
 * diesem Controller werden s�mtliche Operationen auf Verzeichnissen
 * gekapselt.
 *
 * @author    Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author    Stefan Osterloh <s.osterloh@uni-oldenburg.de>
 * @license   GPL2 or any later version
 * @copyright Stud.IP Core-Group
 * @since     3.1
 */

require_once 'document_controller.php';

class Document_FolderController extends DocumentController
{
    /**
     * Before filter, basically initializes the controller by actvating the
     * according navigation entry.
     *
     * @param String $action Action to execute
     * @param Array $args    Arguments passed for the action (might be empty)
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        Navigation::activateItem('/profile/files');
    }

    /**
     * Create a new folder.
     *
     * @param String $parent_id Directory entry id of the parent directory
     */
    public function create_action($parent_id, $page = 1)
    {
        if (!$this->full_access) {
            throw new AccessDeniedException();
        }

        PageLayout::setTitle(_('Ordner erstellen'));

        FileHelper::checkAccess($parent_id);
        $this->parent_id = $parent_id;
        $this->page      = $page;

        if (Request::isPost()) {
            $name        = Request::get('name');
            $description = Request::get('description', '');
            try {
                $entry = new DirectoryEntry($parent_id);
                $parent_dir = $entry->file;
            } catch (Exception $e) {
                $parent_dir = new RootDirectory($this->context_id);
            }

            $name      = $parent_dir->ensureUniqueFilename($name);
            $directory = $parent_dir->mkdir($name, $description, $this->context_id);

            $directory->file->filename = $name;
            $directory->file->store();

            PageLayout::postMessage(MessageBox::success(_('Der Ordner wurde erstellt.')));
            $this->redirect('document/files/index/' . $parent_id . '/' . $page);
        }
    }

    /**
     * Edits a folder.
     *
     * @param String $folder_id Directory entry id of the folder
     */
    public function edit_action($folder_id)
    {
        if (!$this->full_access) {
            throw new AccessDeniedException();
        }

        PageLayout::setTitle(_('Ordner bearbeiten'));

        $folder = new DirectoryEntry($folder_id);
        $folder->checkAccess();

        if (Request::isPost()) {
            $name = Request::get('name');
            $name = $folder->directory->ensureUniqueFilename($name, $folder->file);

            $folder->name        = $name;
            $folder->description = Request::get('description');
            
            if ($folder->isDirty()) {
                $folder->store();

                $message = sprintf(_('Der Ordner "%s" wurde gespeichert.'), $folder->name);
                PageLayout::postMessage(MessageBox::success($message));
            }

            $this->redirect($this->url_for_parent_directory($folder));
            return;
        }

        $this->setDialogLayout('icons/100/lightblue/folder-' . ($folder->file->isEmpty() ? 'empty' : 'full') . '.png');

        $this->folder = $folder;
    }

    /**
     * Deletes a folder.
     *
     * @param String $folder_id Directory entry id of the folder
     */
    public function delete_action($folder_id)
    {
        if (!$this->full_access) {
            throw new AccessDeniedException();
        }

        FileHelper::checkAccess($folder_id);

        $parent_id = FileHelper::getParentId($folder_id) ?: $this->context_id;

        if (!Request::isPost()) {
            $message = $folder_id === 'all'
                     ? _('Soll der gesamte Dateibereich inklusive aller Order und Dateien wirklich gel�scht werden?')
                     : _('Soll der Ordner inklusive aller darin enthaltenen Dateien wirklich gel�scht werden?');
            $question = createQuestion2($message, array(), array(),
                                        $this->url_for('document/folder/delete/' . $folder_id));
            $this->flash['question'] = $question;
        } elseif (Request::isPost() && Request::submitted('yes')) {
            if ($folder_id === 'all') {
                $entry = RootDirectory::find($this->context_id);
                foreach ($entry->listFiles() as $file) {
                    $entry->unlink($file->name);
                }
                PageLayout::postMessage(MessageBox::success(_('Der Dateibereich wurde geleert.')));
            } else {
                $entry = DirectoryEntry::find($folder_id);
                $entry->directory->unlink($entry->name);
                PageLayout::postMessage(MessageBox::success(_('Der Ordner wurde gel�scht.')));
            }
        }
        $this->redirect('document/files/index/' . $parent_id);
    }
}
