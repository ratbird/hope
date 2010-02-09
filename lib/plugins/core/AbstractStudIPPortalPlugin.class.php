<?php
# Lifter007: TODO

/**
 * Starting point for creating "normal" course or institute plugins.
 * @author Dennis Reil <dennis.reil@offis.de>
 * @package pluginengine
 * @subpackage core
 */

class AbstractStudIPPortalPlugin extends AbstractStudIPLegacyPlugin
  implements PortalPlugin {

	function AbstractStudIPPortalPlugin(){
		parent::__construct();
	}

	/**
	 * Used to show an overview on the start page or portal page
	 *
	 * @deprecated
	 *
	 * @param is the user already logged in?
	 */
	function showOverview($unauthorizedview=true){
		// has to be implemented
	}

	/**
	 * Does this plugin have an administration page, which should be shown?
	 * This default implementation only shows it for admin or root user.
	 *
	 * @deprecated
	 */
	function hasAdministration(){
		return $GLOBALS['perm']->have_perm('admin');
	}

	/**
	 * Does the plugin have a view for a user not currently logged in?
	 *
	 * @deprecated
	 */
	function hasUnauthorizedView(){
		return false;
	}

	/**
	 * Does the plugin have a view for a currently logged in user?
	 *
	 * @deprecated
	 */
	function hasAuthorizedView(){
		return true;
	}

    /**
     * Return a template (an instance of the Flexi_Template class)
     * to be rendered on the start or portal page. Return NULL to
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
    function getPortalTemplate()
    {
        global $user, $template_factory;

        if (is_object($user) && $user->id != 'nobody') {
            if ($this->hasAuthorizedView()) {
                $view_mode = true;
            }
        } else {
            if ($this->hasUnauthorizedView()) {
                $view_mode = false;
            }
        }

        if (!isset($view_mode)) {
            return NULL;
        }

        $template = $template_factory->open('shared/string');
        $template->title = $this->getDisplaytitle();
        $template->icon_url = $this->getPluginiconname();

        if ($this->hasAdministration()) {
            $template->admin_url = $this->getAdminLink();
        }

        ob_start();
        $this->showOverview($view_mode);
        $template->content = ob_get_clean();

        return $template;
    }
}
?>
