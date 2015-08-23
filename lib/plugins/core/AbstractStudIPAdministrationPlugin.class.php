<?php
# Lifter007: TODO
# Lifter010: TODO

/**
 * Ausgangspunkt f�r Administrationsplugins, also Plugins, die speziell im
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
            throw new Exception(_('Sie verf�gen nicht �ber ausreichend Rechte f�r diese Aktion.'));
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
     * Verf�gt dieses Plugin �ber einen Eintrag auf der Startseite des
     * Administrators
     *
     * @deprecated
     *
     * @return  true    - Hauptmen� vorhanden
     *          false   - kein Hauptmen� vorhanden
     */
    function hasTopNavigation(){
        return $this->topnavigation != NULL;
    }

    /**
     * Liefert den Men�eintrag zur�ck
     *
     * @deprecated
     *
     * @return das Men�, oder NULL, wenn kein Men� vorhanden ist
     */
    function getTopNavigation(){
        return $this->topnavigation;
    }

    /**
     * Setzt das Hauptmen� des Plugins
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
