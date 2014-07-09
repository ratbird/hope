<?php
# Lifter010: TODO
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
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.0
 */

/**
 * This navigation includes all tools for a user depending on the
 * activated modules.
 */
class ToolsNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Tools'));

        $this->setImage('header/tools.png', array('title' => _('Tools'), "@2x" => TRUE));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $auth, $perm;

        parent::initSubNavigation();

        $username = $auth->auth['uname'];

        // news
        $navigation = new Navigation(_('Ankündigungen'), 'dispatch.php/news/admin_news');
        $this->addSubNavigation('news', $navigation);

        // votes and tests, evaluations
        if (get_config('VOTE_ENABLE')) {
            $navigation = new Navigation(_('Umfragen und Tests'), 'admin_vote.php', array('page' => 'overview', 'showrangeID' => $username));
            $this->addSubNavigation('vote', $navigation);

            $navigation = new Navigation(_('Evaluationen'), 'admin_evaluation.php', array('rangeID' => $username));
            $this->addSubNavigation('evaluation', $navigation);
        }

        // rss feeds
        $navigation = new Navigation(_('RSS-Feeds'), 'dispatch.php/admin/rss_feeds');
        $this->addSubNavigation('rss', $navigation);

        // literature
        if (get_config('LITERATURE_ENABLE')) {
            if ($perm->have_perm('admin')) {
                $this->addSubNavigation('literature', new Navigation(_('Literaturübersicht'), 'admin_literatur_overview.php'));
            } elseif (get_config('LITERATURE_ENABLE')) {
                $navigation = new Navigation(_('Literatur'), 'dispatch.php/literature/edit_list.php', array('_range_id' => 'self'));
                $this->addSubNavigation('literature', $navigation);
                $navigation->addSubNavigation('edit_list', new Navigation(_('Literatur bearbeiten'), 'dispatch.php/literature/edit_list?_range_id=self'));
                $navigation->addSubNavigation('search', new Navigation(_('Literatur suchen'), 'dispatch.php/literature/search?return_range=self'));
            }
        }

        // elearning
        if (get_config('ELEARNING_INTERFACE_ENABLE')) {
            $navigation = new Navigation(_('Lernmodule'), 'dispatch.php/elearning/my_accounts');
            $this->addSubNavigation('my_elearning', $navigation);
        }

        // export
        if (get_config('EXPORT_ENABLE') && $perm->have_perm('tutor')) {
            $navigation = new Navigation(_('Export'), 'export.php');
            $this->addSubNavigation('export', $navigation);
        }

        if ($perm->have_perm('root')) {
            $this->addSubNavigation('db_integrity_new', new Navigation(_('DB Integrität'), 'dispatch.php/admin/db_integrity_check'));
        }

        if ($perm->have_perm('admin') || ($perm->have_perm('dozent') && get_config('ALLOW_DOZENT_COURSESET_ADMIN'))) {
            $navigation = new Navigation(_('Anmeldesets'), 'dispatch.php/admission/courseset/index');
            $this->addSubNavigation('coursesets', $navigation);
            $navigation->addSubNavigation('sets', new Navigation(_('Anmeldesets verwalten'), 'dispatch.php/admission/courseset/index'));
            $navigation->addSubNavigation('userlists', new Navigation(_('Nutzerlisten'), 'dispatch.php/admission/userlist/index'));
            $navigation->addSubNavigation('restricted_courses', new Navigation(_('teilnahmebeschränkte Veranstaltungen'), 'dispatch.php/admission/restricted_courses'));
        }

    }
}
