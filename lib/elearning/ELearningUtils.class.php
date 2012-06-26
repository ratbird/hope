<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* class with several forms and tools for the elearning interface
*
* This class contains Utilities for the elearning-interface.
*
* @author   Arne Schröder <schroeder@data-quest.de>
* @package  ELearning-Interface
*/

use Studip\Button, Studip\LinkButton;

class ELearningUtils
{
    /**
    * load class ConnectedCMS
    *
    * loads class ConnectedCMS for given system-type and creates an instance
    * @access public
    * @param string $cms system-type
    */
    function loadClass($cms)
    {
        global $connected_cms, $RELATIVE_PATH_ELEARNING_INTERFACE, $ELEARNING_INTERFACE_MODULES, $SessSemName, $object_connections;

        if (! is_object($connected_cms[$cms]))
        {
            require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/" . $ELEARNING_INTERFACE_MODULES[$cms]["CLASS_PREFIX"] . "ConnectedCMS.class.php");
            $classname = $ELEARNING_INTERFACE_MODULES[$cms]["CLASS_PREFIX"] . "ConnectedCMS";
            $connected_cms[$cms] = new $classname($cms);
            $connected_cms[$cms]->initSubclasses();
        }
    }

    function initElearningInterfaces(){
        global $ELEARNING_INTERFACE_MODULES, $connected_cms;
        if(is_array($ELEARNING_INTERFACE_MODULES)){
            foreach(array_keys($ELEARNING_INTERFACE_MODULES) as $cms){
                if (ELearningUtils::isCMSActive($cms)) {
                    ELearningUtils::loadClass($cms);
                }
            }
        }
        return is_array($connected_cms) ? count($connected_cms) : false;
    }

    /**
    * get config-value
    *
    * gets config-value with given name from globals
    * @access public
    * @param string $name entry-name
    * @param string $cms system-type
    * @return boolean returns false if no cms is given
    */
    function getConfigValue($name, $cms)
    {
        if ($cms != "")
            return Config::get()->getValue("ELEARNING_INTERFACE_" . $cms . "_" . $name);
        else
            return false;
    }

    /**
    * set config-value
    *
    * writes config-value with given name and value to database
    * @access public
    * @param string $name entry-name
    * @param string $value value
    * @param string $cms system-type
    */
    function setConfigValue($name, $value, $cms)
    {
        if ($cms != "")
            write_config("ELEARNING_INTERFACE_" . $cms . "_" . $name, $value);
    }

    /**
    * check cms-status
    *
    * checks if connected content-management-system is activated
    * @access public
    * @param string $cms system-type
    */
    function isCMSActive($cms = "")
    {
        if ($cms != "")
            return $GLOBALS["ELEARNING_INTERFACE_" . $cms . "_ACTIVE"];
    }

    /**
    * get cms-selectbox
    *
    * returns a form to select a cms
    * @access public
    * @param string $message description-text
    * @param boolean $check_active show only activated systems
    * @return string returns html-code
    */
    function getCMSSelectbox($message, $check_active = true)
    {
        global $ELEARNING_INTERFACE_MODULES, $cms_select, $search_key, $view;
        if (! is_array($ELEARNING_INTERFACE_MODULES))
        {
            $msg = sprintf(_("Die ELearning-Schnittstelle ist nicht korrekt konfiguriert. Die Variable \"%s\" muss in der Konfigurationsdatei von Stud.IP erst mit den Verbindungsdaten angebundener Learning-Content-Management-Systeme aufgef&uuml;llt werden. Solange dies nicht geschehen ist, setzen Sie die Variable \"%s\" auf FALSE!"),"\$ELEARNING_INTERFACE_MODULES", "\$ELEARNING_INTERFACE_ENABLE");
            parse_window ("error§" . $msg, "§", _("Konfigurationsfehler"));
            die();
        }
        $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink() . "#anker\">\n";
        $output .= CSRFProtection::tokenTag();
        $output .= "<table border=\"0\" cellspacing=0 cellpadding=0 width = \"99%\">";
        $output .= "<tr><td class=\"steel1\" align=\"center\" valign=\"middle\" ><font size=\"-1\">";
        $output .=  ELearningUtils::getHeader(_("Angebundenes System"));
        $output .= "<br>\n";
        $output .= $message;
        $output .= "<br>\n";
        $output .=  "&nbsp;&nbsp;";
        $output .= "<br>\n";
        $output .= "<input type=\"HIDDEN\" name=\"anker_target\" value=\"choose\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"search_key\" value=\"" . $search_key . "\">\n";
        $output .= "<select name=\"cms_select\" style=\"vertical-align:middle\">\n";
        $output .=  "<option value=\"\">" . _("Bitte ausw&auml;hlen") . "</option>\n";
        foreach($ELEARNING_INTERFACE_MODULES as $cms => $cms_preferences)
        {
            if (($check_active == false) OR (ELearningUtils::isCMSActive($cms)))
            {
                $output .=  "<option value=\"$cms\"";
                if ($cms_select == $cms)
                    $output .=  " selected";
                $output .=  ">" . $cms_preferences["name"] . "</option>\n";
            }
        }
        $output .=  "</select>";
        $output .=  "&nbsp;&nbsp;";
        $output .=  Button::create(_('Auswählen'));
        $output .= "<br>\n";
        $output .= "<br>\n";
        $output .= "</font>";
        $output .=  "</td></tr></table>";
        $output .=  "</form>";
        return $output;
    }

    /**
    * get moduletype-selectbox
    *
    * returns a form to select type for new contentmodule
    * @access public
    * @param string $cms system-type
    * @return string returns html-code
    */
    function getTypeSelectbox($cms)
    {
        global $ELEARNING_INTERFACE_MODULES;//, $module_type, $module_type_cms;
        if (sizeof($ELEARNING_INTERFACE_MODULES[$cms]["types"]) > 1)
        {
            $output .= "<select name=\"module_type_" . $cms . "\" style=\"vertical-align:middle\">\n";
            $output .=  "<option value=\"\">" . _("Bitte ausw&auml;hlen") . "</option>\n";
            foreach($ELEARNING_INTERFACE_MODULES[$cms]["types"] as $type => $info)
            {
                $output .=  "<option value=\"$type\"";
                if ($GLOBALS["module_type_" . $cms] == $type)
                    $output .=  " selected";
                $output .=  ">" . $info["name"] . "</option>\n";
            }
            $output .=  "</select>";
        }
        else
        {
            foreach($ELEARNING_INTERFACE_MODULES[$cms]["types"] as $type => $info)
            {
                $output = "\"" . $info["name"] . "\"";
                $output .= "<input type=\"HIDDEN\" name=\"module_type\" value=\"" . $type . "\">\n";
            }
        }
        return $output;
    }

    /**
    * get searchfield
    *
    * returns a form to search for modules
    * @access public
    * @param string $message description-text
    * @return string returns html-code
    */
    function getSearchfield($message)
    {
        global $cms_select, $search_key, $view;
        $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink('#anker')."\">\n";
        $output .= CSRFProtection::tokenTag();
        $output .= "<table border=\"0\" cellspacing=0 cellpadding=0 width = \"99%\">";
        $output .= "<tr><td class=\"steel1\" align=\"center\" valign=\"middle\" ><font size=\"-1\">";
        $output .= "<br>\n";
        $output .= $message;
        $output .= "<br>\n";

        $output .=  "&nbsp;&nbsp;";
        $output .= "<br>\n";
        $output .= "<input type=\"HIDDEN\" name=\"anker_target\" value=\"search\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
        $output .= "<input name=\"search_key\" size=\"30\" style=\"vertical-align:middle;font-size:9pt;\" value=\"" . $search_key . "\">\n";

        $output .=  "&nbsp;";
        $output .=  Button::create(_('Suchen'));
        $output .= "<br>\n";
        $output .= "<br>\n";
        $output .= "</font>";
        $output .=  "</td></tr></table>";
        $output .=  "</form>";
        return $output;
    }

    /**
    * get form for new content-module
    *
    * returns a form to choose module-type and to create a new content-module
    * @access public
    * @param string $cms system-type
    * @return string returns html-code
    */
    function getNewModuleForm($cms)
    {
        global $ELEARNING_INTERFACE_MODULES, $connected_cms, $module_type;

        if (sizeof($ELEARNING_INTERFACE_MODULES[$cms]["types"]) == 1)
            foreach($ELEARNING_INTERFACE_MODULES[$cms]["types"] as $type => $info)
                $GLOBALS["module_type_" . $cms] = $type;
        $link = $connected_cms[$cms]->link->getNewModuleLink();
        if ($link == false)
            return false;
        $output .= ELearningUtils::getHeader(sprintf(_("Neues Lernmodul erstellen")));
        $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink('#anker')."\">\n";
        $output .= CSRFProtection::tokenTag();
        $output .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"6\" width=\"100%\">";
        $output .= "<tr><td>";
        foreach ($ELEARNING_INTERFACE_MODULES as $cms_type => $cms_data)
            $output .= "<input type=\"HIDDEN\" name=\"module_type_" . $cms_type . "\" value=\"" . $GLOBALS["module_type_" . $cms_type] . "\">\n";
//      $output .= "<input type=\"HIDDEN\" name=\"module_type_cms\" value=\"" . $cms . "\">\n";
        $output .= "<font size=\"-1\">";
        $output .= sprintf(_("Typ f&uuml;r neues Lernmodul: %s"), ELearningUtils::getTypeSelectbox($cms));
        $output .= "</font>";
//      $output .= "&nbsp;</td><td align=\"left\">";
        $output .= "</td><td align=\"right\" valign=\"middle\">";
        if (sizeof($ELEARNING_INTERFACE_MODULES[$cms]["types"]) > 1)
            $output .=  Button::create(_('Auswählen'), 'choose');
        $output .= $link;
        $output .= "</td></tr>";
        $output .= "</table>";
        $output .=  "</form>\n";
        return $output;
    }

    /**
    * get form for external user-account
    *
    * returns a form for administration of external user-account
    * @access public
    * @param string message message-string
    * @param string my_account_cms cms-type
    * @return string returns html-code
    */
    function getMyAccountForm($message, $my_account_cms)
    {
        global $connected_cms;

        $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink('#anker')."\">\n";
        $output .= CSRFProtection::tokenTag();
        $output .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"6\" width=\"100%\">";
        $output .= "<tr><td>";
        $output .= "<font size=\"-1\">";
        $output .= $message;
        $output .= "</font>";
        $output .= "</td><td align=\"right\">";
        $output .= "<input type=\"HIDDEN\" name=\"new_account_step\" value=\"1\">\n";
        $output .= "<input type=\"HIDDEN\" name=\"new_account_cms\" value=\"" . $my_account_cms . "\">\n";
        if ($connected_cms[$my_account_cms]->user->isConnected())
            $output .=  Button::create(_('Bearbeiten'), 'change');
        else
            $output .=  Button::create(_('Erstellen'), 'create');
        $output .= "</td></tr>";
        $output .= "</table>";
        $output .=  "</form>\n";
        return $output;
    }

    /**
    * get form for new user
    *
    * returns a form to add a user-account to connected cms
    * @access public
    * @param string $new_account_cms system-type
    * @return string returns html-code
    */
    function getNewAccountForm(&$new_account_cms)
    {
        global $connected_cms, $cms_select, $search_key, $view, $new_account_step, $current_module,
            $start, $next, $go_back, $assign, $ext_username, $ext_password, $ext_password_2, $messages, $ref_id, $module_type, $assign,
            $RELATIVE_PATH_ELEARNING_INTERFACE, $ELEARNING_INTERFACE_MODULES;

        ELearningUtils::loadClass($new_account_cms);

//      echo "nas:$new_account_step.cm:$current_module.n:$next.gb:$go_back.a:$assign.<br>";
//      print_r($_POST);

        //Password was sent, but is to short
        if (isset($ext_password_2) AND ! Request::submitted('go_back') AND Request::submitted('next') AND (strlen($ext_password_2) < 6))
        {
            $messages["error"] .= _("Das Passwort muss mindestens 6 Zeichen lang sein!");
            $new_account_step--;
        }
        elseif (isset($ext_password_2) AND ! Request::submitted('go_back')  AND Request::submitted('next') AND ($ext_password != $ext_password_2))
        {
            $messages["error"] .= _("Das Passwort entspricht nicht der Passwort-Wiederholung!");
            $new_account_step--;
        }

        // Benutzername was sent
        if (($ext_username != "") AND ! Request::submitted('go_back') AND Request::submitted('assign'))
        {
            $caching_status = $connected_cms[$new_account_cms]->soap_client->getCachingStatus();
            $connected_cms[$new_account_cms]->soap_client->setCachingStatus(false);
            if ($connected_cms[$new_account_cms]->user->verifyLogin($ext_username, $ext_password))
            {
                $ready = true;
                $messages["info"] .= _("Der Account wurde zugeordnet.");
                $connected_cms[$new_account_cms]->user->setCategory("");
                $connected_cms[$new_account_cms]->user->setUsername($ext_username);
                $connected_cms[$new_account_cms]->user->setPassword($ext_password);
                $connected_cms[$new_account_cms]->user->setConnection(USER_TYPE_ORIGINAL);
                if ($ref_id != "")
                {
                    $connected_cms[$new_account_cms]->newContentModule($ref_id, $module_type, true);
                    $output .= sprintf( _("Hier gelangen Sie zum gew&auml;hlten Lernmodul \"%s\":"), $connected_cms[$new_account_cms]->content_module[$current_module]->getTitle() ) . "<br>\n<br>\n";
                    $output .= $connected_cms[$new_account_cms]->link->getUserModuleLinks();
                    $output .= "<br>";
                    $output .= "<br>";
                }
                $new_account_cms = "";
                return $output;
            }
            else
            {
                $new_account_step = 1;
                $messages["error"] .= _("Die eingegebenen Login-Daten sind nicht korrekt.");
            }
            $connected_cms[$new_account_cms]->soap_client->setCachingStatus($caching_status);
        }

        if (Request::submitted('start'))
            $new_account_step = 1;
        if (Request::submitted('go_back'))
        {
            $new_account_step--;
            if ($new_account_step < 1)
            {
                $new_account_cms = "";
                return false;
            }
        }
        elseif (Request::submitted('next') OR Request::submitted('assign'))
            $new_account_step++;

        if (($new_account_step == 2) AND Request::submitted('assign'))
        {
            // Assign existing Account
            $output .= "<a name='anker'></a>";
            $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink('#anker')."\">\n";
            $output .= CSRFProtection::tokenTag();
            $output .= "<table border=\"0\" cellspacing=0 cellpadding=6 width = \"99%\">";
            $output .= "<tr><td class=\"steel1\" align=\"left\" valign=\"middle\" colspan=\"2\"><br>\n";
            $output .= "<font size=\"-1\">";
            $output .= sprintf(_("Geben Sie nun Benutzernamen und Passwort Ihres Benutzeraccounts in %s ein."),  $connected_cms[$new_account_cms]->getName()) . "";
            $output .= "</font>";
            $output .= "<br></td></tr>\n";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" colspan=\"2\">";
            $output .= "<br></td></tr>\n";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" width=\"20%\">";
            $output .= "<font size=\"-1\">";
            $output .= "&nbsp;" . _("Benutzername: ") . "&nbsp;\n";
            $output .= "</font>";
            $output .= "</td><td class=\"steel1\" align=\"left\" valign=\"middle\">";
            $output .= "" . "<input name=\"ext_username\" size=\"30\" style=\"vertical-align:middle;font-size:9pt;\" value=\"" . $ext_username . "\">";
            $output .= "</td></tr>";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" colspan=\"2\">";
            $output .= "<br></td></tr>\n";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" width=\"20%\">";
            $output .= "<font size=\"-1\">";
            $output .= "&nbsp;" . _("Passwort: ") . "&nbsp;\n";
            $output .= "</font>";
            $output .= "</td><td class=\"steel1\" align=\"left\" valign=\"middle\">";
            $output .= "" . "<input name=\"ext_password\" type=\"PASSWORD\" size=\"30\" style=\"vertical-align:middle;font-size:9pt;\" value=\"" ."\">";
            $output .= "</td></tr><tr><td class=\"steel1\">&nbsp;</td><td class=\"steel1\" align=\"left\" valign=\"middle\">";
            $output .=  "<br>&nbsp;". Button::createAccept(_('Bestätigen'), 'next') . "<br>";
            $output .= "</td></tr>";
            $output .=  "<tr><td align=\"center\" valign=\"middle\" colspan=\"2\"><br>";
            $output .= "<input type=\"HIDDEN\" name=\"assign\" value=\"1\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"new_account_step\" value=\"" . $new_account_step . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"ref_id\" value=\"" . $ref_id . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"module_type\" value=\"" . $module_type . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"new_account_cms\" value=\"" . $new_account_cms . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"search_key\" value=\"" . $search_key . "\">\n";
            $output .=  Button::create('<< ' . _('Zurück'), 'go_back');
            $output .= "</td></tr>";
            $output .=  "</table>\n";
            $output .=  "</form>\n";

//          getLoginForm();
        }
        elseif (($new_account_step == 2) AND Request::submitted('next'))
        {
            // Create new Account: ask for new password
            $output .= "<a name='anker'></a>";
            $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink('#anker')."\">\n";
            $output .= CSRFProtection::tokenTag();
            $output .= "<table border=\"0\" cellspacing=0 cellpadding=6 width = \"99%\">";
            $output .= "<tr><td class=\"steel1\" align=\"left\" valign=\"middle\" colspan=\"2\"><br>\n";
            $output .= "<font size=\"-1\">";
            $output .= sprintf(_("Geben Sie nun ein Passwort f&uuml;r Ihren neuen Benutzeraccount in %s ein."),  $connected_cms[$new_account_cms]->getName());
            $output .= "</font>";
            $output .= "<br></td></tr>\n";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" colspan=\"2\">";
            $output .= "<br></td></tr>\n";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" width=\"20%\">";
            $output .= "<font size=\"-1\">";
            $output .= "&nbsp;" . _("Passwort:") . "\n";
            $output .= "</font>";
            $output .= "</td><td class=\"steel1\" align=\"left\" valign=\"middle\">";
            $output .= "" . "&nbsp;<input name=\"ext_password\" type=\"PASSWORD\" size=\"30\" style=\"vertical-align:middle;font-size:9pt;\" value=\"" ."\">";
            $output .= "</td></tr>";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" colspan=\"2\">";
            $output .= "<br></td></tr>\n";
            $output .=  "<tr><td class=\"steel1\" align=\"right\" valign=\"middle\" width=\"20%\" nowrap>";
            $output .= "<font size=\"-1\">";
            $output .= "&nbsp;" . _("Passwort-Wiederholung:") . "\n";
            $output .= "</font>";
            $output .= "</td><td class=\"steel1\" align=\"left\" valign=\"middle\">";
            $output .= "" . "&nbsp;<input name=\"ext_password_2\" type=\"PASSWORD\" size=\"30\" style=\"vertical-align:middle;font-size:9pt;\" value=\"" ."\">";
            $output .= "</td></tr><tr><td class=\"steel1\">&nbsp;</td><td class=\"steel1\" align=\"left\" valign=\"middle\">";
            $output .=  "<br>&nbsp;" . Button::createAccept(_('Bestätigen'), 'next') ."<br>";
            $output .= "</td></tr>";
            $output .=  "<tr><td align=\"center\" valign=\"middle\" colspan=\"2\"><br>";
            $output .= "<input type=\"HIDDEN\" name=\"next\" value=\"" . true . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"new_account_step\" value=\"" . $new_account_step . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"ref_id\" value=\"" . $ref_id . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"module_type\" value=\"" . $module_type . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"new_account_cms\" value=\"" . $new_account_cms . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"search_key\" value=\"" . $search_key . "\">\n";
            $output .=  Button::create('<< ' . _('Zurück'), 'go_back');
            $output .= "</td></tr>";
            $output .=  "</table>\n";
            $output .=  "</form>\n";

        }
        elseif (($new_account_step == 3) AND Request::submitted('next'))
        {
            $output .= "<a name='anker'></a>";
            // Create new Account
            $connected_cms[$new_account_cms]->user->setPassword($ext_password);
            if ($connected_cms[$new_account_cms]->user->newUser() != false)
            {
                $messages["info"] .= sprintf(_("Der Account wurde erzeugt und zugeordnet. Ihr Loginname ist %s."), "<b>" . $connected_cms[$new_account_cms]->user->getUsername() . "</b>");
                if ($ref_id != "")
                {
                    $connected_cms[$new_account_cms]->newContentModule($ref_id, $module_type, true);
                    $output .= "<font size=\"-1\">";
                    $output .= sprintf( _("Hier gelangen Sie zum gew&auml;hlten Lernmodul \"%s\":"), $connected_cms[$new_account_cms]->content_module[$current_module]->getTitle() ) . "<br>\n<br>\n";
                    $output .= $connected_cms[$new_account_cms]->link->getUserModuleLinks();
                    $output .= "<br>";
                    $output .= "<br>";
                    $output .= "</font>";
                }
            }
            $new_account_cms = "";

        }
        else
        {
            $output .= "<a name='anker'></a>";
            $output .=  "<form method=\"POST\" action=\"" . URLHelper::getLink('#anker')."\">\n";
            $output .= CSRFProtection::tokenTag();
            $output .= "<table border=\"0\" cellspacing=0 cellpadding=6 width = \"99%\">";
            $output .= "<tr><td>\n";
            $output .= "<font size=\"-1\">";
            if (Request::submitted('start'))
                $messages["info"] = sprintf(_("Sie versuchen zum erstem Mal ein Lernmodul des angebundenen Systems %s zu starten. Bevor Sie das Modul nutzen k&ouml;nnen, muss Ihrem Stud.IP-Benutzeraccount ein Account im angebundenen System zugeordnet werden."), $connected_cms[$new_account_cms]->getName()) . "<br><br>\n\n";
            if ($connected_cms[$new_account_cms]->user->isConnected())
            {
                $output .= sprintf(_("Ihr Stud.IP-Account wurde bereits mit einem %s-Account verkn&uuml;pft. Wenn Sie den verkn&uuml;pften Account durch einen anderen, bereits existierenden Account ersetzen wollen, klicken Sie auf \"zuordnen\"."),  $connected_cms[$new_account_cms]->getName(),  $connected_cms[$new_account_cms]->getName());
//              $output .= "&nbsp;" . sprintf(_("Wenn Sie den verkn&uuml;pften Account durch einen neuen, automatisch erstellten Account ersetzen wollen, klicken Sie auf \"weiter\"."));
                $output .= "<br>\n<br>\n";
            }
            else
                $output .= sprintf(_("Wenn Sie innerhalb von %s bereits &uuml;ber einen BenutzerInnen-Account verf&uuml;gen, k&ouml;nnen Sie ihn jetzt \"zuordnen\". Anderenfalls wird automatisch ein neuer Account in %s f&uuml;r Sie erstellt, wenn Sie auf \"weiter\" klicken."),  $connected_cms[$new_account_cms]->getName(),  $connected_cms[$new_account_cms]->getName()) . "<br>\n<br>\n";
            $output .= "<input type=\"HIDDEN\" name=\"new_account_step\" value=\"" . $new_account_step . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"ref_id\" value=\"" . $ref_id . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"module_type\" value=\"" . $module_type . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"new_account_cms\" value=\"" . $new_account_cms . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
            $output .= "<input type=\"HIDDEN\" name=\"search_key\" value=\"" . $search_key . "\">\n";

            $output .=  "<center>";
            $output .=  Button::create('<< ' . _('Zurück'), 'go_back');
            $output .=  Button::create(_('Zuordnen'), 'assign', array('title' => _('Bestehenden Account zuordnen')));
            if (! $connected_cms[$new_account_cms]->user->isConnected())
                $output .=  Button::create(_('Weiter') . ' >>', 'next');
            $output .=  "</center>\n";
            $output .= "</font>";
            $output .= "</td></tr>";
            $output .=  "</table>\n";
            $output .=  "</form>\n";
        }
//      echo "nas:$new_account_step.cm:$current_module.n:$_REQUEST['next_x'].gb:$_REQUEST['go_back_x'].a:$_REQUEST['assign_x'].<br>";
        return $output;
    }

    /**
    * get table-header for connected cms
    *
    * returns a table-header for connected cms
    * @access public
    * @param string $title table-title
    * @return string returns html-code
    */
    function getCMSHeader($title)
    {
        $output .= "<table border=\"0\" cellspacing=0 cellpadding=0 width = \"99%\">";
        $output .= "<tr><td class=\"steel\" align=\"left\" valign=\"middle\" colspan=\"4\">";
//      $output .= "<font size=\"-1\">";
        $output .= "<b>&nbsp;";
        $output .= $title;
        $output .= "</b>";
//      $output .= "</font>";
        $output .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"25\"></td></tr><tr><td class=\"steel1\" width=\"1%\">&nbsp</td><td class=\"steel1\"  align=\"left\"  valign=\"top\" colspan=\"1\">";
        return $output;
    }

    /**
    * get table-footer for connected cms
    *
    * returns a table-footer for connected cms
    * @access public
    * @param string $logo system-logo
    * @return string returns html-code
    */
    function getCMSFooter($logo)
    {
        $output .= "</td><td class=\"steel1\" width=\"1%\">&nbsp</td><td class=\"steelblau_schatten\" align=\"center\" valign=\"top\" width=\"10%\" colspan=\"1\">";
//      $output .= "<br>\n";
        $output .= $logo;
        $output .= "&nbsp;<br>\n";
        $output .= "<br>\n";
        $output .= "</td></tr>";
        $output .=  "</table>";
        return $output;
    }

    /**
    * get headline for modules
    *
    * returns a table with a headline
    * @access public
    * @param string $title headline
    * @return string returns html-code
    */
    function getModuleHeader($title)
    {
        global $view, $cms_select, $search_key, $elearning_open_close;
        $output .= "<table class=\"blank\"  align=\"center\" valign=\"top\" width=\"100%\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\">";
        $output .= "<tr valign=\"top\"><td class=\"steelgraulight\" align=\"left\" width=\"40%\">&nbsp;";
        $output .= "<font size=\"-1\"><b>";
        $output .= $title;
        $output .= "</b></font>";
        $output .= "</td><td class=\"steelgraulight\" align=\"left\" width=\"40%\">";
        if ($elearning_open_close["all open"] != "")
            $output .= "<a href=\"" . URLHelper::getLink('?close_all=1&view='.$view.'&cms_select='.$cms_select.'&search_key='.$search_key)."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/close_all.png\" alt=\"" . _("Alle Module schlie&szlig;en") . "\" title=\"" . _("Alle Module schlie&szlig;en") . "\"  border=\"0\">";
        else
            $output .= "<a href=\"" . URLHelper::getLink('?open_all=1&view='.$view.'&cms_select='.$cms_select.'&search_key='.$search_key)."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/open_all.png\" alt=\"" . _("Alle Module &ouml;ffnen") . "\" title=\"" . _("Alle Module &ouml;ffnen") . "\"  border=\"0\">";
        $output .= "</a></td></tr>";
        $output .= "</table>";
        return $output;
    }

    /**
    * get Headline
    *
    * returns a table with a headline
    * @access public
    * @param string $title headline
    * @return string returns html-code
    */
    function getHeader($title)
    {
        $output .= "<table class=\"blank\"  align=\"center\" valign=\"top\" width=\"100%\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\">";
        $output .= "<tr valign=\"top\"><td class=\"steelgraulight\" align=\"left\">&nbsp;";
        $output .= "<font size=\"-1\"><b>";
        $output .= $title;
        $output .= "</b></font>";
        $output .= "</td></tr>";
        $output .= "</table>";
        return $output;
    }

    /**
    * save timestamp
    *
    * saves a timestamp for debugging and performance-check
    * @access public
    * @param string $stri description
    */
    function bench($stri)
    {
        global $timearray;

        list($usec, $sec) = explode(" ", microtime());
        $t = ((float)$usec + (float)$sec);
        $nr = sizeof($timearray);
        $timearray[$nr]["zeit"] = $t;
        $timearray[$nr]["name"] = $stri;
    }

    /**
    * show benchmark
    *
    * shows saved timestamps with descriptions
    * @access public
    * @param string $stri description
    */
    function showbench()
    {
        global $timearray;
        echo "<table><tr><td>Zeit (".$timearray[0]["name"].")</td><td align=\"right\"></td></tr>";
        for ($i=1;$i<sizeof($timearray);$i++)
        {
            echo "<tr><td>".$timearray[$i]["name"].": </td><td align=\"right\">" . number_format(($timearray[$i]["zeit"]-$timearray[$i-1]["zeit"])*1000,2) . " msek</td></tr>";
        }
        echo "<tr><td>Gesamtzeit: </td><td align=\"right\">" . number_format(($timearray[$i-1]["zeit"]-$timearray[0]["zeit"])*1000,2)." msek</td></tr></table>";
    }

    /**
    * delete cms-data
    *
    * deletes all data belonging to the specified cms from stud.ip database
    * @access public
    * @return boolean successful
    */
    function deleteCMSData($cms_type) {
        $db = DBManager::get();
        $db->exec("DELETE FROM auth_extern WHERE external_user_system_type = " . $db->quote($cms_type));
        $db->exec("DELETE FROM object_contentmodules WHERE system_type =  " . $db->quote($cms_type));
        $config = Config::get();
        foreach ($config->getFields('global' ,null , 'ELEARNING_INTERFACE_' . $cms_type) as $key) {
            $config->delete($key);
        }
    }

    /**
    * get ilias courses
    *
    * creates output of ilias courses linked to the chosen seminar. also updates object-connections.
    * @access public
    * @return boolean successful
    */
    function getIliasCourses($sem_id) {
        global  $connected_cms, $messages, $view, $cms_select;
        $db = DBManager::get();

        $rs = $db->query("SELECT DISTINCT system_type, module_id
                          FROM object_contentmodules
                          WHERE module_type = 'crs' AND object_id = " . $db->quote($sem_id))
                        ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rs as $row) $courses[$row['system_type']] = $row['module_id'];
        if (is_array($courses))
            foreach($courses as $system_type => $crs_id)
                if (ELearningUtils::isCMSActive($system_type)) {
                    ELearningUtils::loadClass($system_type);
                    $course_output[] = "<a href=\"" . $connected_cms[$system_type]->link->cms_link . "?" . "client_id=" . $connected_cms[$system_type]->getClientId() . "&cms_select=" . $system_type . "&ref_id=" . $crs_id . "&type=crs&target=start\" target=\"_blank\">".sprintf(_("Kurs in %s"), $connected_cms[$system_type]->getName())."</a>";
                    // gegebenenfalls zugeordnete Module aktualisieren
                    if (Request::submitted('update')) {
                        if ((method_exists($connected_cms[$system_type], "updateConnections"))) {
                            $connected_cms[$system_type]->updateConnections( $crs_id );
                        }
                    }
                    if (method_exists($connected_cms[$system_type]->permissions, 'CheckUserPermissions')) {
                        $connected_cms[$system_type]->permissions->CheckUserPermissions($crs_id);
                    }
                }

        if ($course_output) {
            if (sizeof($course_output) > 1)
                $output["courses"] = _("Diese Veranstaltung ist mit folgenden Ilias-Kursen verkn&uuml;pft. Hier gelangen Sie direkt in den jeweiligen Kurs: ") . "<br>".implode($course_output, "<br>")."<br><br>";
            else
                $output["courses"] = _("Diese Veranstaltung ist mit einem Ilias-Kurs verkn&uuml;pft. Hier gelangen Sie direkt in den Kurs: ") . "<br>".implode($course_output, "<br>")."<br><br>";
            $output["update"] .=  "<font style=\"font-size: -1\">" . _("Hier k&ouml;nnen Sie die Zuordnungen zu den verkn&uuml;pften Kursen aktualisieren."). "<br></font>";
            $output["update"] .=  "<form method=\"POST\" action=\"" . UrlHelper::getLink() . "#anker\">\n";
            $output["update"] .= CSRFProtection::tokenTag();
            $output["update"] .= "<input type=\"HIDDEN\" name=\"view\" value=\"" . $view . "\">\n";
            $output["update"] .= "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
            $output["update"] .= Button::create(_('Aktualisieren'), 'update');
            $output["update"] .= "</form>";
        }

        return $output;
    }

    /**
    * check db-integrity
    *
    * checks if there are broken links in the database
    * @access public
    * @return boolean successful
    */
    function checkIntegrity() {
        global $ELEARNING_INTERFACE_MODULES, $messages;
        $db = DBManager::get();

        foreach ($ELEARNING_INTERFACE_MODULES as $cms_type =>$data) $cmsystems[$cms_type] = array();

        $config = Config::get();
        foreach ($config->getFields('global' ,null , 'ELEARNING_INTERFACE_') as $key) {
            $parts = explode("_", $key);
            $cmsystems[$parts[2]]["config"]++;
        }

        $rs = $db->query("SELECT external_user_system_type, COUNT(*) as c FROM auth_extern GROUP BY external_user_system_type");
        while ($row = $rs->fetch())
            $cmsystems[$row["external_user_system_type"]]["accounts"] = $row['c'];
        $rs = $db->query("SELECT system_type, COUNT(*) FROM object_contentmodules GROUP BY system_type");
        while ($row = $rs->fetch())
            $cmsystems[$row["system_type"]]["modules"] = $row['c'];

        if (Request::submitted('delete')) {
            $messages["info"] .= "<form method=\"POST\" action=\"" . UrlHelper::getLink() . "\">";
            $messages["info"] .= CSRFProtection::tokenTag();
            $messages["info"] .= "<table>";
            $messages["info"] .= "<tr><td>&nbsp;</td></tr>";
            $messages["info"] .= "<tr><td>" . sprintf(_("Durch das L&ouml;schen der Daten zum System mit dem Index \"%s\" werden %s Konfigurationseintr&auml;ge und Verkn&uuml;pfungen von Stud.IP-Veranstaltungen und -User-Accounts unwiederbringlich aus der Stud.IP-Datenbank entfernt. Wollen Sie diese Daten jetzt l&ouml;schen?"), $_REQUEST['delete_cms'], $cmsystems[$_REQUEST['delete_cms']]["accounts"]+$cmsystems[$_REQUEST['delete_cms']]["modules"]+$cmsystems[$_REQUEST['delete_cms']]["config"] ) . "</td></tr>";
            $messages["info"] .= "<tr><td align=\"center\"><input type=\"hidden\" name=\"delete_cms\" value=\"".$_REQUEST['delete_cms']."\">";
            $messages["info"] .= '<div class="button-group">' . Button::create(_('Alle löschen'), 'confirm_delete') . Button::createCancel(_('Abbrechen'), 'abbruch') . '<div></td></tr>';
            $messages["info"] .= "<tr><td align=\"center\"></td></tr>";
            $messages["info"] .= "</table>";
            $messages["info"] .= "</form>";
        }

        if (Request::submitted('confirm_delete')) {
            unset($cmsystems[$_REQUEST['delete_cms']]);
//          deleteCMSData($_REQUEST['delete_cms']);
            $messages["info"] .= _("Daten wurden gel&ouml;scht.");
        }

        foreach ($cmsystems as $cms_type =>$data) {
            if ($ELEARNING_INTERFACE_MODULES[$cms_type]) {
                $output .= ELearningUtils::getCMSHeader($ELEARNING_INTERFACE_MODULES[$cms_type]["name"]);
                $output .= "<table>";
                $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                if (ELearningUtils::getConfigValue("ACTIVE", $cms_type)) {
                    $output .= "<tr><td>" .  Assets::img('icons/16/blue/checkbox-checked.png', array('class' => 'text-top')) . "</td><td><b>". sprintf(_("Die Schnittstelle zum System %s ist aktiv."), $ELEARNING_INTERFACE_MODULES[$cms_type]["name"]) . "</b></td></tr>";
                    $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                }
                elseif ($data["config"] < 1)
                    $output .= "<tr><td>" .  Assets::img('icons/16/blue/checkbox-unchecked.png', array('class' => 'text-top')) . "</td><td><i>". sprintf(_("Die Schnittstelle f&uuml;r das System %s wurde noch nicht eingerichtet."), $ELEARNING_INTERFACE_MODULES[$cms_type]["name"]) . "</i></td></tr>";
                elseif ($data["config"] < 1)
                    $output .= "<tr><td>" .  Assets::img('icons/16/blue/checkbox-unchecked.png', array('class' => 'text-top')) . "</td><td><i>". sprintf(_("Die Schnittstelle wurde noch nicht aktiviert."), $ELEARNING_INTERFACE_MODULES[$cms_type]["name"]) . "</i></td></tr>";

                if ($data["accounts"])
                    $output .= "<tr><td colspan=\"2\">". sprintf(_("%s Stud.IP-User-Accounts sind mit Accounts im System %s verkn&uuml;pft."), $data["accounts"], $ELEARNING_INTERFACE_MODULES[$cms_type]["name"]) . "</td></tr>";
                if ($data["modules"])
                    $output .= "<tr><td colspan=\"2\">". sprintf(_("%s Objekte sind Stud.IP-Veranstaltungen oder -Einrichtungen zugeordnet."), $data["modules"]) . "</td></tr>";
                if ($data["config"])
                    $output .= "<tr><td colspan=\"2\">". sprintf(_("%s Eintr&auml;ge in der config-Tabelle der Stud.IP-Datenbank."), $data["config"]) . "</td></tr>";
                $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                $output .= "</table>";
                $output .= ELearningUtils::getCMSFooter(($ELEARNING_INTERFACE_MODULES[$cms_type]["logo_file"] ? "<img src=\"".$ELEARNING_INTERFACE_MODULES[$cms_type]["logo_file"]."\" border=\"0\">" : $cms_type));
            }
            else {
                $output .= ELearningUtils::getCMSHeader("<font color=FF0000> Unbekanntes System: " . $cms_type . "</font>");
                $output .= "<form method=\"POST\" action=\"" . UrlHelper::getLink() . "\">";
                $output .= CSRFProtection::tokenTag();
                $output .= "<table>";
                $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                $output .= "<tr><td>" . Assets::img('icons/16/red/decline.png', array('class' => 'text-top')) . "</td><td><i>".sprintf(_("F&uuml;r das System mit dem Index \"%s\" existieren keine Voreinstellungen in den Konfigurationsdateien mehr."), $cms_type) . "</i></td></tr>";
                $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                $output .= "<tr><td colspan=\"2\"><b>". _("In der Stud.IP-Datenbank sind noch folgende Informationen zu diesem System gespeichert:") . "</b></td></tr>";
                if ($data["accounts"])
                    $output .= "<tr><td colspan=\"2\">". sprintf(_("%s Stud.IP-User-Accounts sind mit externen Accounts mit dem Index \"%s\" verkn&uuml;pft."), $data["accounts"], $cms_type) . "</td></tr>";
                if ($data["modules"])
                    $output .= "<tr><td colspan=\"2\">". sprintf(_("%s Objekte sind Stud.IP-Veranstaltungen oder -Einrichtungen zugeordnet."), $data["modules"]) . "</td></tr>";
                if ($data["config"])
                    $output .= "<tr><td colspan=\"2\">". sprintf(_("%s Eintr&auml;ge in der config-Tabelle der Stud.IP-Datenbank."), $data["config"]) . "</td></tr>";
                $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                $output .= "<tr><td align=\"center\" colspan=\"2\"><input type=\"hidden\" name=\"delete_cms\" value=\"".$cms_type."\">" . Button::create(_('Löschen'), 'delete') . "</td></tr>";
                $output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>";
                $output .= "</table>";
                $output .= "</form>";
                $output .= ELearningUtils::getCMSFooter('');
            }
            $output .= "<br>";
        }

        return $output;
    }
}
?>