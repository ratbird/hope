<?php
# Lifter007: TODO

/**
 * Ausgangspunkt für Administrationsplugins, also Plugins, die speziell im
 * Adminstrator- / Root-Bereich angezeigt werden.
 * @author Dennis Reil <dennis.reil@offis.de>
 * @package pluginengine
 * @subpackage core
 */

class AbstractStudIPAdministrationPlugin extends AbstractStudIPLegacyPlugin
    implements AdministrationPlugin {

    var $topnavigation;

    function AbstractStudIPAdministrationPlugin(){
        parent::__construct();

        // Administration-Plugins only accessible by users with admin rights
        if (!$GLOBALS['perm']->have_perm('admin')) {
            throw new Exception(_('Sie verfügen nicht über ausreichend Rechte für diese Aktion.'));
        }
    }

    /**
     * @deprecated
     */
    function setNavigation(StudipPluginNavigation $navigation) {
        parent::setNavigation($navigation);

        if (Navigation::hasItem('/admin/plugins')) {
            Navigation::addItem('/admin/plugins/' . $this->getPluginclassname(), $navigation);
        }
    }

    /**
     * Verfügt dieses Plugin über einen Eintrag auf der Startseite des
     * Administrators
     *
     * @deprecated
     *
     * @return  true    - Hauptmenü vorhanden
     *          false   - kein Hauptmenü vorhanden
     */
    function hasTopNavigation(){
        return $this->topnavigation != NULL;
    }

    /**
     * Liefert den Menüeintrag zurück
     *
     * @deprecated
     *
     * @return das Menü, oder NULL, wenn kein Menü vorhanden ist
     */
    function getTopNavigation(){
        return $this->topnavigation;
    }

    /**
     * Setzt das Hauptmenü des Plugins
     *
     * @deprecated
     */
    function setTopnavigation(StudipPluginNavigation $navigation){
        $this->topnavigation = $navigation;

        if ($navigation instanceof PluginNavigation) {
            $this->topnavigation->setPlugin($this);
        }

        Navigation::insertItem('/start/' . $this->getPluginclassname(), $navigation, 'search');
    }
}
?>
