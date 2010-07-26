<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
require_once("Ilias3ConnectedCMS.class.php");
require_once("lib/classes/Seminar.class.php");
require_once("lib/classes/Institute.class.php");

/**
* main-class for connection to ILIAS 4
*
* This class contains the main methods of the elearning-interface to connect to ILIAS 4. Extends Ilias3ConnectedCMS.
*
* @author	Arne Schröder <schroeder@data-quest.de>
* @access	public
* @modulegroup	elearning_interface_modules
* @module		Ilias4ConnectedCMS
* @package	ELearning-Interface
*/
class Ilias4ConnectedCMS extends Ilias3ConnectedCMS
{
    var $user_category_node_id;
    /**
    * constructor
    *
    * init class.
    * @access public
    * @param string $cms system-type
    */
    function Ilias4ConnectedCMS($cms) {
        global $messages, $SessSemName;
        parent::Ilias3ConnectedCMS($cms);
        if (ELearningUtils::getConfigValue("user_category_id", $cms))
            $this->user_category_node_id = ELearningUtils::getConfigValue("user_category_id", $cms);
        else
            $this->user_category_node_id = $this->main_category_node_id;
        if (ELearningUtils::getConfigValue("ldap_enable", $cms))
            $this->ldap_enable = ELearningUtils::getConfigValue("ldap_enable", $cms);
    }

    /**
    * check connected modules and update connections
    *
    * checks if there are modules in the course that are not connected to the seminar
    * @access public
    * @param string $course_id course-id
    * @return boolean successful
    */
    function updateConnections($course_id) {
        global $connected_cms, $messages, $SessSemName, $object_connections;

        $db = DBManager::get();

        $types = array();
        foreach ($this->types as $type => $name)
        {
            $types[] = $type;
        }

        // Workaround: getTreeChilds() liefert ALLE Referenzen der beteiligten Objekte, hier sollen aber nur die aus dem Kurs geprüft werden. Deshalb Abgleich der Pfade aller gefundenen Objekt-Referenzen.
        $result = $this->soap_client->getObjectByReference($course_id);
        if ($result)
            $course_path = $this->soap_client->getPath($course_id) . $this->soap_client->seperator_string . $result["title"];


        $result = $this->soap_client->getTreeChilds($course_id, $types, $this->user->getId());

        if ($result) {
            $messages["info"] .= "<b>".sprintf(_("Aktualisierung der Zuordnungen zum System \"%s\":"), $this->getName()) . "</b><br>";
            foreach($result as $ref_id => $data) {
                if (($data["accessInfo"] == "granted") AND ($this->soap_client->getPath($ref_id) == $course_path)) {
                    $rs = $db->query("SELECT * FROM object_contentmodules WHERE object_id = '" . $SessSemName[1] . "' AND module_id = '" . $ref_id . "' AND system_type = '" . $this->cms_type . "' AND module_type = '" . $data["type"] . "'");
                    if (! $rs->fetch()) {
                        $messages["info"] .= sprintf(_("Zuordnung zur Lerneinheit \"%s\" wurde hinzugefügt."), ($data["title"])) . "<br>";
                        $counter++;
                        ObjectConnections::setConnection($SessSemName[1], $ref_id, $data["type"], $this->cms_type);
                    }
                }
            }
            if ($counter < 1)
                $messages["info"] .= _("Die Zuordnungen sind bereits auf dem aktuellen Stand.") . "<br>";
        }
        ELearningUtils::bench("update connections");
    }

    /**
    * create course
    *
    * creates new ilias course
    * @access public
    * @param string $seminar_id seminar-id
    * @return boolean successful
    */
    function createCourse($seminar_id) {
        global $messages, $SessSemName, $DEFAULT_LANGUAGE, $ELEARNING_INTERFACE_MODULES;

        $crs_id = ObjectConnections::getConnectionModuleId($seminar_id, "crs", $this->cms_type);
        $this->soap_client->setCachingStatus(false);
        $this->soap_client->clearCache();

        if ($crs_id == false) {
            $seminar = Seminar::getInstance($seminar_id);
            $home_institute = Institute::find($seminar->getInstitutId());
            if ($home_institute)
                $ref_id = ObjectConnections::getConnectionModuleId($home_institute->getId(), "cat", $this->cms_type);
            if ($ref_id < 1) {
                // Kategorie für Heimateinrichtung anlegen
                $object_data["title"] = sprintf("%s", $home_institute->name);
                $object_data["description"] = sprintf(_("Hier befinden sich die Veranstaltungsdaten zur Stud.IP-Einrichtung \"%s\"."), $home_institute->name);
                $object_data["type"] = "cat";
                $object_data["owner"] =  $this->soap_client->LookupUser($ELEARNING_INTERFACE_MODULES[$this->cms_type]["soap_data"]["username"]);
                $ref_id = $this->soap_client->addObject($object_data, $this->main_category_node_id);
                ObjectConnections::setConnection($home_institute->getId(), $ref_id, "cat", $this->cms_type);
            }
            if ($ref_id < 1)
                $ref_id = $this->main_category_node_id;

            // Kurs anlegen
            $lang_array = explode("_",$DEFAULT_LANGUAGE);
            $course_data["language"] = $lang_array[0];
            $course_data["title"] = "Stud.IP-Kurs " . $seminar->getName();
            $course_data["description"] = "";
            $crs_id = $this->soap_client->addCourse($course_data, $ref_id);
            if ($crs_id == false) {
                $messages["error"] .= _("Zuordnungs-Fehler: Kurs konnte nicht angelegt werden.");
                return false;
             }
            ObjectConnections::setConnection($seminar_id, $crs_id, "crs", $this->cms_type);

            // Rollen zuordnen
            $this->permissions->CheckUserPermissions($crs_id);
        }
        return $crs_id;
    }

    /**
    * get preferences
    *
    * shows additional settings.
    * @access public
    */
    function getPreferences() {
        global $connected_cms, $role_template_name, $cat_name, $style_setting;

        $this->soap_client->setCachingStatus(false);

        if ($cat_name != "") {
            $cat = $this->soap_client->getReferenceByTitle( trim( $cat_name ), "cat");
            if ($cat == false)
                $messages["error"] .= sprintf(_("Das Objekt mit dem Namen \"%s\" wurde im System %s nicht gefunden."), $cat_name, $this->getName()) . "<br>\n";
            elseif ($cat != "") {
                    ELearningUtils::setConfigValue("category_id", $cat, $this->cms_type);
                    $this->main_category_node_id = $cat;
            }
        }

        if (($this->main_category_node_id != false) AND (ELearningUtils::getConfigValue("user_category_id", $this->cms_type) == "")) {
            $object_data["title"] = sprintf(_("User-Daten"));
            $object_data["description"] = sprintf(_("Hier befinden sich die persönlichen Ordner der Stud.IP-User."), $this->getName());
            $object_data["type"] = "cat";
            $object_data["owner"] = $this->user->getId();
            $user_cat = $connected_cms[$this->cms_type]->soap_client->addObject($object_data, $connected_cms[$this->cms_type]->main_category_node_id);
            if ($user_cat != false) {
                $this->user_category_node_id = $user_cat;
                ELearningUtils::setConfigValue("user_category_id", $user_cat, $this->cms_type);
            }
            else
                $messages["error"] .= sprintf(_("Die Kategorie für User-Daten konnte nicht angelegt werden."), $cat_name, $this->getName()) . "<br>\n";
        }

        if ($role_template_name != "") {
            $role_template = $this->soap_client->getObjectByTitle( trim( $role_template_name ), "rolt" );
            if ($role_template == false)
                $messages["error"] .= sprintf(_("Das Rollen-Template mit dem Namen \"%s\" wurde im System %s nicht gefunden."), $role_template_name, $this->getName()) . "<br>\n";
            if (is_array($role_template)) {
                ELearningUtils::setConfigValue("user_role_template_id", $role_template["obj_id"], $this->cms_type);
                ELearningUtils::setConfigValue("user_role_template_name", $role_template["title"], $this->cms_type);
                $this->user_role_template_id = $role_template["obj_id"];
            }
        }

        if ($_REQUEST["submit_x"] != "") {
            ELearningUtils::setConfigValue("encrypt_passwords", $_REQUEST["encrypt_passwords"], $this->cms_type);
            $encrypt_passwords = $_REQUEST["encrypt_passwords"];
            ELearningUtils::setConfigValue("ldap_enable", $_REQUEST["ldap_enable"], $this->cms_type);
            $this->ldap_enable = $_REQUEST["ldap_enable"];
        }
        else {
            if (ELearningUtils::getConfigValue("encrypt_passwords", $this->cms_type) != "")
                $encrypt_passwords = ELearningUtils::getConfigValue("encrypt_passwords", $this->cms_type);
        }


        if ($messages["error"] != "")
            echo "<b><img src=\"".$GLOBALS['ASSETS_URL']."images/x_small2.gif\" alt=\"Fehler\">&nbsp;" . $messages["error"] . "</b><br><br>";

        echo "<table>";
        echo "<tr valign=\"top\"><td width=30% align=\"left\"><font size=\"-1\">";
        echo "<b>" . _("SOAP-Verbindung: ") . "</b>";
        echo "</td><td><font size=\"-1\">";
        $error = $this->soap_client->getError();
        if ($error != false)
            echo sprintf(_("Beim Herstellen der SOAP-Verbindung trat folgender Fehler auf:")) . "<br><br>" . $error;
        else
            echo sprintf(_("Die SOAP-Verbindung zum Klienten \"%s\" wurde hergestellt, der Name des Administrator-Accounts ist \"%s\"."), $this->soap_data["client"], $this->soap_data["username"]);
        echo "<br>\n";
        echo "<br>\n";
        echo "</td></tr><tr><td  width=30% align=\"left\"><font size=\"-1\">";

        $cat = $this->soap_client->getObjectByReference( $this->main_category_node_id );
        echo "<b>" . _("Kategorie: ") . "</b>";
        echo "</td><td>";
        echo "<input type=\"text\" size=\"20\" border=0 value=\"" . $cat["title"] . "\" name=\"cat_name\">&nbsp;";
        echo "<img  src=\"".$GLOBALS['ASSETS_URL']."images/info.gif\" " . tooltip(_("Geben Sie hier den Namen einer bestehenden ILIAS 4 - Kategorie ein, in der die Lernmodule und User-Kategorien abgelegt werden sollen."), TRUE, TRUE) . ">";
        echo "</td></tr><tr><td></td><td><font size=\"-1\">";
        echo " (ID " . $this->main_category_node_id;
        if ($cat["description"] != "")
            echo ", " . _("Beschreibung: ") . $cat["description"];
        echo ")";
        echo "<br>\n";
        echo "<br>\n";
        echo "</td></tr><tr><td  width=30% align=\"left\"><font size=\"-1\">";

        $user_cat = $this->soap_client->getObjectByReference( $this->user_category_node_id );
        echo "<b>" . _("Kategorie für Userdaten: ") . "</b>";
        echo "</td><td>";
        $title = $this->link->getModuleLink($user_cat["title"], $this->user_category_node_id, "cat");
        if ($title)
            echo $title;
        else
            echo $user_cat["title"];
        echo "</td></tr><tr><td></td><td><font size=\"-1\">";
        echo " (ID " . $this->user_category_node_id;
        if ($cat["description"] != "")
            echo ", " . _("Beschreibung: ") . $cat["description"];
        echo ")";
        echo "<br>\n";
        echo "<br>\n";
        echo "</td></tr><tr><td  width=30% align=\"left\"><font size=\"-1\">";

        echo "<b>" . _("Rollen-Template für die perönliche Kategorie: ") . "</b>";
        echo "</td><td>";
        echo "<input type=\"text\" size=\"20\" border=0 value=\"" . ELearningUtils::getConfigValue("user_role_template_name", $this->cms_type) . "\" name=\"role_template_name\">&nbsp;";
        echo "<img  src=\"".$GLOBALS['ASSETS_URL']."images/info.gif\" " . tooltip(_("Geben Sie den Namen des Rollen-Templates ein, das für die persönliche Kategorie von DozentInnen verwendet werden soll (z.B. \"Author\")."), TRUE, TRUE) . ">"	;
        echo "</td></tr><tr><td></td><td><font size=\"-1\">";
        echo " (ID " . $this->user_role_template_id;
        echo ")";
        echo "<br>\n";
        echo "<br>\n";
        echo "</td></tr><tr><td  width=30% align=\"left\"><font size=\"-1\">";

        echo "<b>" . _("Passwörter: ") . "</b>";
        echo "</td><td><font size=\"-1\">";
        echo "<input type=\"checkbox\" border=0 value=\"md5\" name=\"encrypt_passwords\"";
        if ($encrypt_passwords == "md5")
            echo " checked";
        echo ">&nbsp;" . _("ILIAS-Passwörter verschlüsselt speichern.");
        echo "<img  src=\"".$GLOBALS['ASSETS_URL']."images/info.gif\" " . tooltip(_("Wählen Sie diese Option, wenn die ILIAS-Passwörter der zugeordneten Accounts verschlüsselt in der Stud.IP-Datenbank abgelegt werden sollen."), TRUE, TRUE) . ">"	;
        echo "</td></tr><tr><td></td><td><font size=\"-1\">";
        echo "<br>\n";
        echo "<br>\n";
        echo "</td></tr><tr><td  width=30% align=\"left\"><font size=\"-1\">";

        echo "<b>" . _("LDAP-Einstellung: ") . "</b>";
        echo "</td><td><font size=\"-1\">";
        echo "<select name=\"ldap_enable\">";
        echo '<option></option>';
        $ldap_plugins = 0;
        foreach (StudipAuthAbstract::GetInstance() as $plugin) {
            if($plugin instanceof StudipAuthLdap) {
                echo '<option '.($plugin->plugin_name == $this->ldap_enable ? 'selected' : '').'>' . $plugin->plugin_name . '</option>';
                ++$ldap_plugins;
            }
        }
        echo "</select>";
        echo "<br>";
        if ($ldap_plugins) {
            echo _("Authentifizierungsplugin (nur LDAP) beim Anlegen von externen Accounts übernehmen.");
            echo "<img  src=\"".$GLOBALS['ASSETS_URL']."images/info.gif\" " . tooltip(_("Wählen Sie hier ein Authentifizierungsplugin, damit neu angelegte ILIAS-Accounts den Authentifizierungsmodus LDAP erhalten, wenn dieser Modus auch für den vorhandenen Stud.IP-Account gilt. Andernfalls erhalten alle ILIAS-Accounts den default-Modus"), TRUE, TRUE) . ">"	;
        } else {
            echo _("(Um diese Einstellung zu nutzen muss zumindest ein LDAP Authentifizierungsplugin aktiviert sein.)");
        }
        echo "</td></tr><tr><td></td><td><font size=\"-1\">";
        echo "<br>\n";
        echo "<br>\n";
        echo "</td></tr>";

        echo "<tr><td></td><td><font size=\"-1\">";
        echo "<br>\n";
        echo "<br>\n";

        echo "</td></tr>";
        echo "</table>";
        echo "<center><input type=\"IMAGE\" " . makeButton("uebernehmen", "src") . " border=0 value=\"" . _("Abschicken") . "\" name=\"submit\"></center><br>";
        echo "<br>\n";

        ConnectedCMS::getPreferences();

        echo "<br>\n";
    }

}
?>
