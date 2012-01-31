<?php
require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/smiley.php';

/**
 * smileys.php - controller class for the smileys administration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author   Tobias Thelen <tthelen@uos.de>
 * @author   Jens Schmelzer <jens.schmelzer@fh-jena.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @package  smiley
 * @since    2.3
 */
class Admin_SmileysController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // user must have root permission
        $GLOBALS['perm']->check('root');

        // set navigation
        Navigation::activateItem('/admin/config/smileys');

        //pagelayout
        PageLayout::setTitle(_('Verwaltung der Smileys'));

        // Remove layout and add charset for ajax requests
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('Content-Type', 'text/html;charset=iso-8859-1');
        }
    }

    /**
     * Administrtion view for smileys
     */
    function index_action()
    {        
        $this->view              = Request::option('view', Smiley::getFirstUsedCharacter() ?: 'a');
        $this->smileys           = Smiley::getGrouped($this->view);
        $this->favorites_enabled = SmileyFavorites::isEnabled();

        $this->addInfobox($this->view);
    }
    
    /**
     * Displays edit form and performs according actions upon submit
     *
     * @param int    $id   Id of the smiley to edit
     * @param String $view View to return to after editing
     */
    function edit_action($id, $view)
    {
        $smiley = Smiley::getById($id);

        if (Request::submitted('edit')) {
            $success = true;
            
            $name = Request::get('name', $smiley->name);
            if ($smiley->name != $name) { // rename smiley
                if (Smiley::getByName($name)->id) {
                    $error = sprintf(_('Es existiert bereits eine Datei mit dem Namen "%s".'), $name . '.gif');
                    PageLayout::postMessage(Messagebox::error($error));
                    $success = false;
                } elseif (!$smiley->rename($name)) {
                    $error = sprintf(_('Die Datei "%s" konnte nicht umbenannt werden.'), $smiley->name . '.gif');
                    PageLayout::postMessage(Messagebox::error($error));
                    $success = false;
                } else {
                    PageLayout::postMessage(Messagebox::success(_('Smiley erfolgreich umbenannt.')));
                }
            }

            $short = Request::get('short', $smiley->short);
            if (!$message and $smiley->short != $short) { // rename short
                if (Smiley::getByShort($short)->id) {
                    $error = sprintf(_('Es gibt bereits einen Smileys mit dem Kürzel "%s".'), $short);
                    PageLayout::postMessage(Messagebox::error($error));
                    $success = false;
                } else {
                    $smiley->short = $short;
                    $smiley->store();
                    PageLayout::postMessage(Messagebox::success(_('Kürzel erfolgreich geändert.')));
                }
            }

            if ($success) {
                $this->redirect('admin/smileys?view=' . $smiley->name{0} . '#smiley' . $smiley->id);
            } else {
                $this->redirect($this->url_for('admin/smileys/edit', $id, $view));
            }
        }

        $this->smiley = $smiley;
        $this->view   = $view;
    }

    /**
     * Deletes a smiley
     *
     * @param int    $id   Id of the smiley to delete
     * @param String $view View to return to after deletion
     * @todo needs some of confirmation
     */
    function delete_action($id, $view) {
        if ($id == 'bulk') {
            $ids = Request::intArray('smiley_id');
            Smiley::remove($ids);

            $message = sprintf( _('%d Smiley(s) erfolgreich gelöscht.'), count($ids));
        } else {
            $smiley = Smiley::getById($id);
            $name = $smiley->name;
            $smiley->delete();

            $message = sprintf( _('Smiley "%s" erfolgreich gelöscht.'), $name);
        }
        PageLayout::postMessage(Messagebox::success($message));
        
        $this->redirect('admin/smileys?view=' . $view);
    }
    
    /**
     * Counts all smiley occurences systemwide and updates the smileys' counters
     *
     * @param String $view View to return to
     */
    function count_action($view) {
        $updated = Smiley::updateUsage();

        $message  = sprintf(_('%d Zählerstände aktualisiert'), $updated);
        $msg = $updated > 0
            ? Messagebox::success($message)
            : Messagebox::info($message);        
        PageLayout::postMessage($msg);
        
        $this->redirect('admin/smileys?view=' . $view);
    }

    /**
     * Refreshes the smiley table by aligning it with the file system
     *
     * @param String $view View to return to
     */
    function refresh_action($view) {
        $result = Smiley::refresh();

        $message = sprintf(_('%u Operationen wurden durchgeführt.'), array_sum($result));
        $details = array(
            sprintf(_('%d Smileys aktualisiert'), $result['update']),
            sprintf(_('%d Smileys eingefügt'), $result['insert']),
            sprintf(_('%d Smileys gelöscht'), $result['delete'])
        );
        if (isset($result['favorites'])) {
            $details[] = sprintf(_('%d Favoriten geändert'), $result['favorites']);
        }
        $msg = array_sum($result) > 0
            ? Messagebox::success($message, $details, true)
            : Messagebox::info($message, $details, true);
        PageLayout::postMessage($msg);

        $this->redirect('admin/smileys?view=' . $view);
    }

    /**
     * Displays upload form and processes the upload command
     *
     * @param String $view View to return to if canceled
     */
    function upload_action($view) {        
        if (!Request::submitted('upload')) {
            $this->view = $view;
            return;
        }
        
        // File submitted?
        $upload = $_FILES['smiley_file'];
        if (empty($upload) or empty($upload['name'])) {
            $error = _('Sie haben keine Datei zum Hochladen ausgewählt!');
            PageLayout::postMessage(Messagebox::error($error));
            return;
        }
        
        // Error upon upload?
        if ($upload['error']) {
            $error = _('Es gab einen Fehler beim Upload. Bitte versuchen Sie es erneut.');
            PageLayout::postMessage(Messagebox::error($error));
            return;
        }

        // Correct mime-type?
        $no_gif = !empty($upload['type']) && $upload['type'] != 'image/gif';
        if (!$no_gif) {
            $image_info = getimagesize($upload['tmp_name']); // Used later on!
            $no_gif = $image_info[2] != IMAGETYPE_GIF;
        }
        if ($no_gif) {
            $error = sprintf(_('Der Dateityp der Bilddatei ist falsch (%s).<br>'
                              .'Es ist nur die Dateiendung .gif erlaubt!'), $upload['type']);
            PageLayout::postMessage(Messagebox::error($error));
            return;
        }

        // Extract smiley information
        $smiley_file = $upload['name'];
        $smiley_name = substr($smiley_file, 0, strrpos($smiley_file, '.'));

        // Replace smiley?
        $smiley = Smiley::getByName($smiley_name);
        $replace = Request::int('replace');
        if ($smiley->id && !$replace) {
            $error = sprintf(_('Es ist bereits eine Bildatei mit dem Namen "%s" vorhanden.'), $smiley_file);
            PageLayout::postMessage(Messagebox::error($error));
            return;
        }

        // Copy file into file system
        $destination = Smiley::getFilename(basename($smiley_file, '.gif'));
        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            $error = _('Es ist ein Fehler beim Kopieren der Datei aufgetreten. Das Bild wurde nicht hochgeladen!');
            PageLayout::postMessage(Messagebox::error($error));
            return;
        }
        
        // set permissions for uploaded file
        chmod($destination, 0666 & ~umask());

        // Import smiley into database
        Smiley::refresh($destination);

        // Output appropriate wurde message
        $message = $replace
                 ? sprintf(_('Die Bilddatei "%s" wurde erfolgreich ersetzt.'), $smiley_file)
                 : sprintf(_('Die Bilddatei "%s" wurde erfolgreich hochgeladen.'), $smiley_file);
        PageLayout::postMessage(Messagebox::success($message));

        // Return to index and display the view the uploaded smiley is in
        $this->redirect('admin/smileys?view=' . $smiley_file{0});
    }
    
    /**
     * Extends this controller with neccessary infobox
     *
     * @param String $view Currently viewed group
     */
    private function addInfobox($view)
    {
        $this->setInfoboxImage('infobox/administration.jpg');

        // Render items
        $factory = new Flexi_TemplateFactory($this->dispatcher->trails_root . '/views/admin/smileys/');

        $filter = $factory->render('selector', array(
            'characters' => Smiley::getUsedCharacters(),
            'controller' => $this,
            'view'       => $view,
        ));
        $statistics = $factory->render('statistics', Smiley::getStatistics());
        
        // :Filters
        $this->addToInfobox(_('Filter'), $filter, 'icons/16/black/search.png');

        // :Actions
        $upload = sprintf('<a href="%s">%s</a>',
                          $this->url_for('admin/smileys/upload', $view),
                          _('Neues Smiley hochladen'));
        $this->addToInfobox(_('Aktionen'), $upload, 'icons/16/black/plus.png');

        $count = sprintf('<a href="%s">%s</a>',
                         $this->url_for('admin/smileys/count', $view),
                         _('Smileys zählen'));
        $this->addToInfobox(_('Aktionen'), $count, 'icons/16/black/code.png');

        $refresh = sprintf('<a href="%s">%s</a>',
                           $this->url_for('admin/smileys/refresh', $view),
                           _('Tabelle aktualisieren'));
        $this->addToInfobox(_('Aktionen'), $refresh, 'icons/16/black/refresh.png');

        $open = sprintf('<a href="%s" target="_smileys">%s</a>',
                        URLHelper::getURL('dispatch.php/smileys', array('view' => null)),
                         _('Smiley-Übersicht öffnen'));
        $this->addToInfobox(_('Aktionen'), $open, 'icons/16/black/smiley.png');

        // :Statistics
        $this->addToInfobox(_('Statistiken'), $statistics, 'icons/16/black/stat.png');
    }
}
