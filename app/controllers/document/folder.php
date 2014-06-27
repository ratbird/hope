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
 */

require_once 'document_controller.php';

class Document_FolderController extends DocumentController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        Navigation::activateItem('/profile/files');

        // Setup the user's sub-directory in $USER_DOC_PATH
        // TODO: This shouldn't be here
        $userdir = $GLOBALS['USER_DOC_PATH'] . '/' . $GLOBALS['user']->id . '/';

        if (!file_exists($userdir)) {
            mkdir($userdir, 0755, true);
        }
    }

    public function create_action($parent_id)
    {
        $this->parent_id = $parent_id;

        if (Request::isPost()) {
            $name = Request::get('name');

            try {
                $entry = new DirectoryEntry($parent_id);
                $parent_dir = $entry->file;
            } catch (Exception $e) {
                $parent_dir = new RootDirectory($this->context_id);
            }

            do {
                $check = true;
                try {
                    $directory = $parent_dir->mkdir($name);
                } catch (Exception $e) {
                    $check = false;

                    $name = FileHelper::AdjustFilename($name);
                }
            } while (!$check);

            $directory->description = Request::get('description', '');
            $directory->name        = $name;
            $directory->store();

            $directory->file->filename = $name;
            $directory->file->store();

            PageLayout::postMessage(MessageBox::success(_('Der Ordner wurde erstellt.')));
            $this->redirect('document/files/index/' . $parent_id);
        }
    }
    
    public function edit_action($folder_id)
    {   $folder    = new DirectoryEntry($folder_id);
        $parent_id = FileHelper::getParentId($folder_id) ?: $this->context_id;
        
        if (Request::isPost()) {
            $folder->name        = Request::get('name');
            $folder->Description = Request::get('description');
            $folder->store();

            $folder->file->filename = Request::get('name');
            $folder->file->store();
            
            PageLayout::postMessage(MessageBox::success(_('Der Ordner wurde bearbeitet.')));
            $this->redirect('document/files/index/' . $parent_id);
        }
        
        if (Request::isXhr()) {
            header('X-Title: ' . _('Ordner bearbeiten'));
        }
        
        $this->setDialogLayout('icons/48/blue/edit.png');

        $this->folder_id = $folder_id;
        $this->folder    = $folder;
    }
    
    public function delete_action($folder_id)
    {
        $parent_id = FileHelper::getParentId($folder_id) ?: $this->context_id;
        
        if (!Request::isPost()) {
            $message = $folder_id === 'all'
                     ? _('Soll der gesamte Dateibereich inklusive aller Order und Dateien wirklich gelöscht werden?')
                     : _('Soll der Ordner inklusive aller darin enthaltenen Dateien wirklich gelöscht werden?');
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
                File::get($entry->directory->file->id)->unlink($entry->name);
                PageLayout::postMessage(MessageBox::success(_('Der Ordner wurde gelöscht.')));
            }
        }
        $this->redirect('document/files/index/' . $parent_id);
    }
}
