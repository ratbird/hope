<?php
# Lifter010: TODO
/**
 * AbstractStudIPHomepagePlugin.clayss.php - Abstract plugin for plugins shown on
 * the homepage of a user
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Dennis Reil <dennis.reil@offis.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     pluginengine
 */

/**
 *
 * @deprecated  since Stud.IP 1.11
 * @link        http://hilfe.studip.de/index.php/Entwickler/PluginSchnittstelle#toc9
 *
 */
class AbstractStudIPHomepagePlugin extends AbstractStudIPLegacyPlugin implements HomepagePlugin
{

    var $requesteduser; // StudIPUser for which user the homepage should be shown
    var $status_showOverview; // Uebersichtsseite unterdruecken

    /**
     *
     */
    function AbstractStudIPHomepagePlugin()
    {
        parent::__construct();

        // ignore errors about unknown users here
        try {
            $this->requesteduser = $this->getRequestedUser();
            $this->setRequestedUser($this->requesteduser);
        } catch (Exception $ex) {
        }

        $this->status_showOverview = 1;
    }

    /**
     * Sets the navigation of this plugin.
     *
     * @deprecated
     */
    function setNavigation(StudipPluginNavigation $navigation)
    {
        parent::setNavigation($navigation);

        $user_id = $this->requesteduser->getUserid();

        // prepend copy of navigation to its sub navigation
        $item_names = array_keys($navigation->getSubNavigation());
        $navigation_copy = clone $navigation;
        $navigation_copy->clearSubmenu();
        $navigation_copy->freezeActivation();
        $navigation->insertSubNavigation('self', $navigation_copy, $item_names[0]);
        $navigation->setTitle($this->getDisplayTitle());

        if (Navigation::hasItem('/profile') && $GLOBALS['perm']->have_profile_perm('user', $user_id)) {
            Navigation::addItem('/profile/' . $this->getPluginclassname(), $navigation);
        }
    }

    /**
     * Used to show an overview on the homepage of a user.
     *
     * @deprecated
     */
    function showOverview()
    {
        // has to be implemented
    }

    /**
     * true:  overviewpage is enabled
     * false: overviewpage is disabled
     *
     * @deprecated
     */
    function getStatusShowOverviewPage()
    {
        return $this->status_showOverview;
    }

    /**
     * @deprecated
     */
    function setStatusShowOverviewPage($status)
    {
        $this->status_showOverview = $status;
    }


    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setRequestedUser($user)
    {
    }

    /**
     * Return current user - deprecated, do not use.
     *
     * @deprecated
     */
    function getRequestedUser()
    {
        $username = Request::quoted('username', $GLOBALS['auth']->auth['uname']);
        $user_id = get_userid($username);

        if ($user_id == '') {
            return NULL;
        }

        return new StudIPUser($user_id);
    }

    /**
     * Return a template (an instance of the Flexi_Template class)
     * to be rendered on the given user's home page. Return NULL to
     * render nothing for this plugin.
     *
     * The template will automatically get a standard layout, which
     * can be configured via attributes set on the template:
     *
     * $title       title to display, defaults to plugin name
     * $icon_url    icon for this plugin (if any)
     * $admin_url   admin link for this plugin (if any)
     *
     * @return object   template object to render or NULL
     */
    function getHomepageTemplate($user_id)
    {
        global $user, $template_factory;

        if (!$this->getStatusShowOverviewPage()) {
            return NULL;
        }

        $template = $template_factory->open('shared/string');
        $template->title = $this->getDisplaytitle();
        $template->icon_url = $this->getPluginiconname();

        if ($user_id == $user->id) {
            $template->admin_url = $this->getAdminLink();
        }

        ob_start();
        $this->showOverview();
        $template->content = ob_get_clean();

        return $template;
    }
}
