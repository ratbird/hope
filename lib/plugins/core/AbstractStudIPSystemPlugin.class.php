<?php
# Lifter007: TODO

/**
 * Starting point for system plugins. System plugins can be integrated into the main menu for system wide
 * functions or can do background tasks like logging without having a menu entry.
 *
 * @author Dennis Reil, <dennis.reil@offis.de>
 * @package pluginengine
 * @subpackage core
 */

define('SYSTEM_PLUGIN_TOOLBAR',   1);
define('SYSTEM_PLUGIN_STARTPAGE', 2);

class AbstractStudIPSystemPlugin extends AbstractStudIPLegacyPlugin
  implements SystemPlugin {

        protected $display_type;

	function AbstractStudIPSystemPlugin(){
		parent::__construct();
		$this->display_type = SYSTEM_PLUGIN_TOOLBAR;
	}


	/**
	 * Sets the navigation of this plugin.
	 *
	 * @deprecated
	 */
	function setNavigation(StudipPluginNavigation $navigation) {
		parent::setNavigation($navigation);

		$navigation->setImage($this->getPluginiconname(),
				array('title' => $navigation->getTitle()));

		if ($this->getDisplayType(SYSTEM_PLUGIN_TOOLBAR)) {
			Navigation::addItem('/' . $this->getPluginclassname(), $navigation);
		}
		if ($this->getDisplayType(SYSTEM_PLUGIN_STARTPAGE)) {
			Navigation::insertItem('/start/' . $this->getPluginclassname(), 'search', $navigation);
		}
	}


	/**
	 * A system plugin can do system tasks like logging in the background.
	 * This function
	 *
	 * @deprecated
	 *
	 * @return true - plugin should be called for background task
	 * 		   false - plugin has no background task
	 */
	function hasBackgroundTasks(){
		return false;
	}


	/**
	 * abstract function for doing all background tasks
	 *
	 * @deprecated
	 */
	function doBackgroundTasks(){
	}


     /**
     * returns the score which the current user get's for activities in this plugin
     *
     * @deprecated
     */
    function getScore(){
    	return 0;
    }


    /**
     * define where the plugin will be visible (toolbar and/or start page)
     *
     * @deprecated
     */
    function setDisplayType ($display_type) {
        $changes = $this->display_type ^ $display_type;
        $this->display_type = $display_type;

        if ($this->hasNavigation()) {
            if ($changes & SYSTEM_PLUGIN_TOOLBAR) {
                if ($this->getDisplayType(SYSTEM_PLUGIN_TOOLBAR)) {
                    Navigation::addItem('/' . $this->getPluginclassname(), $this->getNavigation());
                } else {
                    Navigation::removeItem('/' . $this->getPluginclassname());
                }
            }
            if ($changes & SYSTEM_PLUGIN_STARTPAGE) {
                if ($this->getDisplayType(SYSTEM_PLUGIN_STARTPAGE)) {
                    Navigation::insertItem('/start/' . $this->getPluginclassname(), 'search', $this->getNavigation());
                } else {
                    Navigation::removeItem('/start/' . $this->getPluginclassname());
                }
            }
        }
    }


    /**
     * returns where the plugin will be visible (toolbar and/or start page)
     *
     * @deprecated
     */
    function getDisplayType ($filter = -1) {
        return $this->display_type & $filter;
    }
}
