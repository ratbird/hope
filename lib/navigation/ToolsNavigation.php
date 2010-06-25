<?php
/*
 * ToolsNavigation.php - navigation for tools page
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class ToolsNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Tools'), 'admin_news.php', array('range_id' => 'self'));

        $image = 'header/header_einst';
        $tip = _('Tools');

        $this->setImage($image, array('title' => $tip));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $auth;

        parent::initSubNavigation();

        $username = $auth->auth['uname'];

        //news
        $this->addSubNavigation('news', new Navigation(_('News'), 'admin_news.php', array('range_id' => 'self')));

        //votes and tests
        if (get_config('VOTE_ENABLE')) {
            $this->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php', array('page' => 'overview', 'showrangeID' => $username)));
            $this->addSubNavigation('evaluation', new Navigation(_('Evaluationen'), 'admin_evaluation.php', array('rangeID' => $username)));
        }

        //literature
        if (get_config('LITERATURE_ENABLE')) {
            $this->addSubNavigation('literature', new Navigation(_('Literatur'), 'admin_lit_list.php', array('_range_id' => 'self')));
        }

        // elearning
        if (get_config('ELEARNING_INTERFACE_ENABLE')) {
            $this->addSubNavigation('elearning', new Navigation(_('Lernmodule'), 'my_elearning.php'));
        }
    }
}
