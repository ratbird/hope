<?php
/*
 * DocumentNavigation.php - navigation for document page
 *
 * Navigation for the document page used for user interaction.
 * It includes a filemanager for the user's personal disk-space in Stud.IP.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Gerd Hoffmann
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2 or later
 * @category    Stud.IP
 * @version     3.0
 */

class DocumentNavigation extends Navigation
{
    public function __construct()
   	{
        parent::__construct(_('Dokumente'));

        $this->setImage('header/files.png', array(
            'title' => _('Zur Dateiverwaltung'),
            '@2x' => true
        ));
   	}

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        parent::initSubNavigation();

        $navigation = new Navigation(_('Dateien'), URLHelper::getLink('dispatch.php/document/files'));
        $this->addSubNavigation('files', $navigation);
    }
}