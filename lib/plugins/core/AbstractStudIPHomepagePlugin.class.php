<?php
# Lifter007: TODO

/**
 * Abstract plugin for plugins shown on the homepage of a user
 * @author Dennis Reil <dennis.reil@offis.de>
 * @package pluginengine
 * @subpackage core
 */

class AbstractStudIPHomepagePlugin extends AbstractStudIPLegacyPlugin
  implements HomepagePlugin {

    var $requesteduser; // StudIPUser for which user the homepage should be shown
    var $status_showOverview; // Uebersichtsseite unterdruecken

    function AbstractStudIPHomepagePlugin(){
        parent::__construct();

        // ignore errors about unknown users here
        try {
            $this->requesteduser = $this->getRequestedUser();
        } catch (Exception $ex) {
        }

        $this->status_showOverview = 1;
    }

    /**
     * Sets the navigation of this plugin.
     *
     * @deprecated
     */
    function setNavigation(StudipPluginNavigation $navigation) {
        parent::setNavigation($navigation);

        // prepend copy of navigation to its sub navigation
        $item_names = array_keys($navigation->getSubNavigation());
        $navigation_copy = clone $navigation;
        $navigation_copy->clearSubmenu();
        $navigation->insertSubNavigation('self', $item_names[0], $navigation_copy);
        $navigation->setTitle($this->getDisplayTitle());

        if (Navigation::hasItem('/homepage')) {
            Navigation::addItem('/homepage/' . $this->getPluginclassname(), $navigation);
        }
    }

    /**
     * Used to show an overview on the homepage of a user.
     *
     * @deprecated
     */
    function showOverview(){
        // has to be implemented
    }

    /**
     * true:  overviewpage is enabled
     * false: overviewpage is disabled
     *
     * @deprecated
     */
    function getStatusShowOverviewPage(){
        return $this->status_showOverview;
    }

    /**
     * @deprecated
     */
    function setStatusShowOverviewPage($status){
        $this->status_showOverview = $status;
    }


    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setRequestedUser($user){
    }

    /**
     * Return current user - deprecated, do not use.
     *
     * @deprecated
     */
    function getRequestedUser(){
        $username = Request::quoted('username', $GLOBALS['auth']->auth['uname']);
        $user_id = get_userid($username);

        if ($user_id == '') {
            throw new Exception(_('Es wurde kein Nutzer unter dem angegebenen Nutzernamen gefunden!'));
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
?>
