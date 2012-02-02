<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

require_once("Ilias3ConnectedLink.class.php");

/**
 * class to generate links to ILIAS 4
 *
 * This class contains methods to generate links to ILIAS 4.
 *
 * @author    Arne Schröder <schroeder@data-quest.de>
 * @access    public
 * @modulegroup    elearning_interface_modules
 * @module        Ilias4ConnectedLink
 * @package    ELearning-Interface
 */
class Ilias4ConnectedLink extends Ilias3ConnectedLink
{
    /**
     * constructor
     *
     * init class.
     * @access
     * @param string $cms system-type
     */
    function Ilias4ConnectedLink($cms)
    {
        parent::Ilias3ConnectedLink($cms);
        $this->cms_link = "ilias3_referrer.php";
    }

    /**
     * get module link
     *
     * returns link to the specified ilias object. works without initializing module-class.
     * @access public
     * @return string html-code
     */
    function getModuleLink($title, $module_id, $module_type)
    {
        global $connected_cms, $view, $search_key, $cms_select, $current_module;

        if ($connected_cms[$this->cms_type]->isAuthNecessary() AND (! $connected_cms[$this->cms_type]->user->isConnected())) {
            return false;
        }
        $output = "<a href=\"" . $this->cms_link . "?"
        . "client_id=" . $connected_cms[$this->cms_type]->getClientId()
        . "&cms_select=" . $this->cms_type
        . "&ref_id=" . $module_id
        . "&type=" . $module_type
        . "&target=start\" target=\"_blank\">";
        $output .= $title;
        $output .= "</a>&nbsp;";

        return $output;
    }

    /**
     * get admin module links
     *
     * returns links add or remove a module from course
     * @access public
     * @return string returns html-code
     */
    function getAdminModuleLinks()
    {
        global $connected_cms, $view, $search_key, $cms_select, $current_module;

        if (! $connected_cms[$this->cms_type]->content_module[$current_module]->isDummy()) {
            $result = $connected_cms[$this->cms_type]->soap_client->getPath($connected_cms[$this->cms_type]->content_module[$current_module]->getId());
        }
        if ($result) {
            $output .= "<i>Pfad: ". $connected_cms[$this->cms_type]->soap_client->getPath($connected_cms[$this->cms_type]->content_module[$current_module]->getId()) . "</i><br><br>";
        }
        $output .= "<form method=\"POST\" action=\"" . $GLOBALS["PHP_SELF"] . "\">\n";
        $output .= CSRFProtection::tokenTag();
        $output .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"search_key\" value=\"" . $search_key . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"module_type\" value=\"" . $connected_cms[$this->cms_type]->content_module[$current_module]->getModuleType() . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"module_id\" value=\"" . $connected_cms[$this->cms_type]->content_module[$current_module]->getId() . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"module_system_type\" value=\"" . $this->cms_type . "\">\n";

        if ($connected_cms[$this->cms_type]->content_module[$current_module]->isConnected()) {
            $output .= "&nbsp;" . Button::create(_('entfernen'), 'remove');
        } elseif ($connected_cms[$this->cms_type]->content_module[$current_module]->isAllowed(OPERATION_WRITE)) {
            $output .= "<div align=\"left\">";
            if ($connected_cms[$this->cms_type]->content_module[$current_module]->isAllowed(OPERATION_COPY) AND (! in_array($connected_cms[$this->cms_type]->content_module[$current_module]->module_type, array("lm", "htlm", "sahs", "cat", "crs", "dbk")))) {
                $output .= "<input type=\"CHECKBOX\" name=\"copy_object\" value=\"1\">";
                $output .= _("Als Kopie anlegen") . "&nbsp;<img  src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\" " . tooltip(_("Wenn Sie diese Option wählen, wird eine identische Kopie als eigenständige Instanz des Lernmoduls erstellt. Anderenfalls wird ein Link zum Lernmodul gesetzt."), TRUE, TRUE) . ">" . "<br>";
            }
            $output .= "<input type=\"RADIO\" name=\"write_permission\" value=\"none\" checked>";
            $output .= _("Keine Schreibrechte") . "&nbsp;<img  src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\" " . tooltip(_("Nur der/die BesitzerIn des Lernmoduls hat Schreibzugriff für Inhalte und Struktur des Lernmoduls. TutorInnen und DozentInnen können die Verknüpfung zur Veranstaltung wieder löschen."), TRUE, TRUE) . ">" . "<br>";
            $output .= "<input type=\"RADIO\" name=\"write_permission\" value=\"dozent\">";
            $output .= _("Mit Schreibrechten f&uuml;r alle DozentInnen dieser Veranstaltung") . "&nbsp;<img  src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\" " . tooltip(_("DozentInnen haben Schreibzugriff für Inhalte und Struktur des Lernmoduls. TutorInnen und DozentInnen können die Verknüpfung zur Veranstaltung wieder löschen."), TRUE, TRUE) . ">" . "<br>";
            $output .= "<input type=\"RADIO\" name=\"write_permission\" value=\"tutor\">";
            $output .= _("Mit Schreibrechten f&uuml;r alle DozentInnen und TutorInnen dieser Veranstaltung") . "&nbsp;<img  src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\" " . tooltip(_("DozentInnen und TutorInnen haben Schreibzugriff für Inhalte und Struktur des Lernmoduls. TutorInnen und DozentInnen können die Verknüpfung zur Veranstaltung wieder löschen."), TRUE, TRUE) . ">" . "<br>";
            $output .= "<input type=\"RADIO\" name=\"write_permission\" value=\"autor\">";
            $output .= _("Mit Schreibrechten f&uuml;r alle TeilnehmerInnen dieser Veranstaltung") . "&nbsp;<img  src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\" " . tooltip(_("DozentInnen, TutorInnen und TeilnehmerInnen haben Schreibzugriff für Inhalte und Struktur des Lernmoduls. TutorInnen und DozentInnen können die Verknüpfung zur Veranstaltung wieder löschen."), TRUE, TRUE) . ">" . "</div>";
            $output .= "</div><br>" . Button::create(_('hinzufügen'), 'add') . "<br>";
        } else {
            $output .= "&nbsp;" . Button::create(_('hinzufügen'), 'add');
        }
        $output .= "</form>";

        return $output;
    }
}
?>
