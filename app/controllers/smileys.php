<?php
require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/smiley.php';

/**
 * smileys.php - controller class for the smileys
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
class SmileysController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setTitle(_('Smiley-Übersicht'));
        PageLayout::addSqueezePackage('smileys');

        $this->set_layout(null);
    }

    /**
     * Displays (a subset of) the smileys in the system
     *
     * @param mixed $view Subset to display, defaults to favorites for logged in users
     */
    function index_action($view = null)
    {
        $this->characters          = Smiley::getUsedCharacters();
        $this->statistics          = Smiley::getStatistics();

        $this->favorites_activated = SmileyFavorites::isEnabled()
                                     && $GLOBALS['user']->id != nobody;
        if ($this->favorites_activated) {
            $this->favorites = new SmileyFavorites($GLOBALS['user']->id);
            $default = 'favorites';
        } else {
            $default = Smiley::getFirstUsedCharacter();
        }

        $this->view = $view ?: $default;

        // Redirect to index if favorites is selected but user is not logged in
        if (!$this->favorites_activated and $this->view == 'favorites') {
            $this->redirect('smileys');
        }

        $this->smileys = $this->view == 'favorites'
                       ? Smiley::getByIds($this->favorites->get())
                       : Smiley::getGrouped($this->view);
    }

    /**
     * Toggles whether a certain smiley is favored for the current user
     *
     * @param int    $id    Id of the smiley to favor/disfavor
     * @param String $view  View to return to
     */
    function favor_action($id, $view) {
        try {
            $favorites = new SmileyFavorites($GLOBALS['user']->id);
            $state = $favorites->toggle($id);

            $message = $state
                     ? _('Der Smiley wurde zu Ihren Favoriten hinzugefügt.')
                     : _('Der Smiley gehört nicht mehr zu Ihren Favoriten.');
            $msg_box = MessageBox::success($message);
        } catch (OutOfBoundsException $e) {
            $state = $favorites->contain($id);
            $message = _('Maximale Favoritenzahl erreicht. Vielleicht sollten Sie mal ausmisten? :)');
            $msg_box = Messagebox::error($message);
        }

        if (Request::isXhr()) {
            $this->response->add_header('Content-Type', 'application/json');
            $this->render_text(json_encode(array(
                'state'   => $state,
                'message' => studip_utf8encode($msg_box),
            )));
        } else {
            PageLayout::postMessage($msg_box);
            $this->redirect('smileys/index/' . $view . '#smiley' . $id);
        }
    }
}