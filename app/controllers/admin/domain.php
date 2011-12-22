<?php
# Lifter010: TODO
/**
 * domain.php - user domain admin controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

//Imports
require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/classes/UserDomain.php';
require_once 'app/controllers/authenticated_controller.php';

class Admin_DomainController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm, $template_factory;

        parent::before_filter($action, $args);

        # user must have root permission
        $perm->check('root');

        # set page title and navigation
        $layout = $template_factory->open('layouts/base');
        $layout->infobox = $this->infobox_content();
        $this->set_layout($layout);

        PageLayout::setTitle(_('Verwaltung der Nutzerdomänen'));
        PageLayout::setHelpKeyword('Admins.Nutzerdomaenen');
        Navigation::activateItem('/admin/config/user_domains');

        # fetch user domain
        $this->domains = UserDomain::getUserDomains();
    }

    /**
     * Display the list of user domains.
     */
    function index_action()
    {
    }

    /**
     * Create a new user domain.
     */
    function new_action()
    {
        $this->render_action('edit');
    }

    /**
     * Edit an existing user domain.
     */
    function edit_action()
    {
        $this->edit_id = Request::get('id');
    }

    /**
     * Save changes to a user domain.
     */
    function save_action()
    {
        $id = Request::get('id');
        $name = Request::get('name');

        if ($id && $name) {
            try {
                $domain = new UserDomain($id);
                $old_name = $domain->getName();

                if (Request::get('new_domain') && isset($old_name)) {
                    throw new Exception(_('Diese ID wird bereits verwendet'));
                }

                $domain->setName($name);
                $domain->store();
            } catch (Exception $ex) {
                $this->message = MessageBox::error($ex->getMessage());
            }
        } else {
            $this->message = MessageBox::error(_('Sie haben keinen Namen und keine ID angegeben.'));
        }

        $this->domains = UserDomain::getUserDomains();
        $this->render_action('index');
    }

    /**
     * Delete an existing user domain.
     */
    function delete_action()
    {
        $id = Request::get('id');
        $domain = new UserDomain($id);

        if (count($domain->getUsers()) == 0) {
            $domain->delete();
        } else {
            $this->message = MessageBox::error(_('Domänen, denen noch Nutzer zugewiesen sind, können nicht gelöscht werden.'));
        }

        $this->domains = UserDomain::getUserDomains();
        $this->render_action('index');
    }

    /**
     * Get contents of the info box for this action.
     */
    function infobox_content()
    {
        $infobox_content = array(
            array(
                'kategorie' => _('Nutzerdomänen verwalten'),
                'eintrag'   => array(array(
                    'icon' => 'icons/16/black/plus.png',
                    'text' => '<a href="'.$this->url_for('admin/domain/new').'">'._('Neue Nutzerdomäne anlegen').'</a>'
                ))
            ), array(
                'kategorie' => _('Informationen'),
                'eintrag'   => array(array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => sprintf(_('In der Stud.IP-Hilfe finden Sie %sHinweise zur Verwendung von Nutzerdomänen%s.'),
                                        '<a target="_blank" href="'.format_help_url('Admins.Nutzerdomaenen').'">', '</a>')
                ))
            )
        );

        return array('picture' => 'infobox/board1.jpg', 'content' => $infobox_content);
    }
}
