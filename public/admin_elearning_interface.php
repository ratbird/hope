<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_elearning_interface.php
//
// Copyright (c) 2005 Arne Schroeder <schroeder@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

use Studip\Button, Studip\LinkButton; 

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
// -- here you have to put initialisations for the current page

require_once ('config.inc.php');

require_once ('lib/elearning/ELearningUtils.class.php');
require_once ('lib/elearning/ConnectedCMS.class.php');

PageLayout::setHelpKeyword("Basis.Ilias");
PageLayout::setTitle(_("Verwaltung der Lernmodul-Schnittstelle"));
Navigation::activateItem('/admin/config/elearning');

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if ($ELEARNING_INTERFACE_ENABLE)
{

    if ($cms_select != "")
    {
        $connected_cms[$cms_select] = new ConnectedCMS();
        $connection_status = $connected_cms[$cms_select]->getConnectionStatus($cms_select);
        if (Request::submitted('activate'))
        {
            ELearningUtils::setConfigValue("ACTIVE", "1", $cms_select);
        }
        if (Request::submitted('deactivate'))
        {
            ELearningUtils::setConfigValue("ACTIVE", "0", $cms_select);
        }

        if ($error_count == 0)
        {
            require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/" . $ELEARNING_INTERFACE_MODULES[$cms_select]["CLASS_PREFIX"] . "ConnectedCMS.class.php");
            $classname = $ELEARNING_INTERFACE_MODULES[$cms_select]["CLASS_PREFIX"] . "ConnectedCMS";
            $connected_cms[$cms_select] = new $classname($cms_select);
            $connected_cms[$cms_select]->initSubclasses();
        }
    }

    ?><table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td class="blank" colspan="3">&nbsp;
        </td>
    </tr>
    <tr valign="top">
                <td width="1%" class="blank">
                    &nbsp;
                </td>
        <td width="90%" class="blank">


    <?
    if ($messages["error"] != "")
    {
        echo "<table>";
        my_error($messages["error"]);
        echo "</table>";
    }
    if ($messages["info"] != "")
    {
        echo "<table>";
        my_info($messages["info"]);
        echo "</table>";
    }

    echo "<font size=\"-1\">";
    if ($cms_select == "")
        echo ELearningUtils::getCMSSelectbox("<b>" . _("Bitte w&auml;hlen Sie ein angebundenes System f&uuml;r die Schnittstelle: ") . "</b>", false) . "\n\n<br><br>";
    else
        echo ELearningUtils::getCMSSelectbox(_("Bitte w&auml;hlen Sie ein angebundenes System f&uuml;r die Schnittstelle: "), false) . "\n\n<br><br>";
    echo "</font>";

    if ($cms_select != "")
    {
        echo "<table>";
        $error_count = 0;
        foreach ($connection_status as $type => $msg)
        {
            if ($msg["error"] != "")
            {
                echo "<tr><td valign=\"middle\">" . Assets::img('icons/16/red/decline.png', array('class' => 'text-top', 'title' => _('Fehler'))) . $msg["error"] . "</td></tr>";
                $error_count++;
            }
            else
                echo "<tr><td valign=\"middle\">" . Assets::img('icons/16/green/accept.png', array('class' => 'text-top', 'title' => _('OK'))) . $msg["info"] . "</td></tr>";
        }
        echo "<tr><td><br></td></tr>";
        if ($error_count > 0)
        {
            $status_info = "error";
            echo "<tr><td valign=\"middle\">" . Assets::img('icons/16/red/decline.png', array('class' => 'text-top', 'title' => _('Fehler'))) . "<b>";
            echo _("Beim Laden der Schnittstelle sind Fehler aufgetreten. ");
            if (ELearningUtils::isCMSActive($cms_select))
            {
                ELearningUtils::setConfigValue("ACTIVE", "0", $cms_select);
                echo _("Die Schnittstelle wurde automatisch deaktiviert!");
            }
            echo "</b></td></tr>";
        }
        else
            echo "<tr><td valign=\"middle\">" . Assets::img('icons/16/green/accept.png', array('class' => 'text-top', 'title' => _('OK'))) . "<b>" .sprintf( _("Die Schnittstelle zum %s-System ist korrekt konfiguriert."), $connected_cms[$cms_select]->getName()) . "</b></td></tr>";
        echo "</table>";
        echo "<br>\n";
        echo ELearningUtils::getCMSHeader($connected_cms[$cms_select]->getName());
        echo "<form method=\"POST\" action=\"" . $PHP_SELF . "\">\n";
        echo CSRFProtection::tokenTag();
        echo "<font size=\"-1\">";
        echo "<br>\n";
        if (ELearningUtils::isCMSActive($cms_select))
        {
            $status_info = "active";
            echo ELearningUtils::getHeader(_("Status"));
            echo "<br>\n";
            echo _("Die Schnittstelle ist <b>aktiv</b>.");
            echo "<br><br>\n<center>";
            echo _("Hier k&ouml;nnen Sie die Schnittstelle deaktivieren.");
            echo "<br><br>\n";
            echo Button::create(_('deaktivieren'), 'deactivate')."</center>";
        }
        else
        {
            echo ELearningUtils::getHeader(_("Status"));
            echo "<br>\n";
            echo _("Die Schnittstelle ist nicht aktiv.");
            echo "<br><br>\n<center>";
            if ($error_count == 0)
            {
                $status_info = "not active";
                echo _("Hier k&ouml;nnen Sie die Schnittstelle aktivieren.");
                echo "<br><br>\n";
                echo Button::create(_('aktivieren'), 'activate')."</center>";
            }
        }
        echo "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
        echo "</font>";
        echo "</form>";
        echo "<br>\n";

        echo "<form method=\"POST\" action=\"" . $PHP_SELF . "\">\n";
        echo CSRFProtection::tokenTag();
        echo "<font size=\"-1\">";
        if ($error_count == 0)
        {
            echo ELearningUtils::getHeader(_("Einstellungen"));
            echo "<br>\n";
            $connected_cms[$cms_select]->getPreferences();
        }
        echo "<input type=\"HIDDEN\" name=\"cms_select\" value=\"" . $cms_select . "\">\n";
        echo "</font>";
        echo "</form>";

        echo ELearningUtils::getCMSFooter($connected_cms[$cms_select]->getLogo());
    }

    // Anzeige, wenn noch keine Account-Zuordnung besteht
        $infobox = array    (
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => "icons/16/black/info.png",
                                    "text"  => _("Hier k&ouml;nnen Sie an angebundene Systeme verwalten.")
                                 )
                            )
            )
        );
        $infobox[1]["kategorie"] = _("Aktionen:");
            $infobox[1]["eintrag"][] = array (  'icon' => "icons/16/black/info.png" ,
                                        "text"  => _("Nachdem Sie ein angebundenes System ausgew&auml;hlt haben, wird die Verbindung zum System gepr&uuml;ft.")
                                    );

        switch($status_info)
        {
            case "active":
            $infobox[1]["eintrag"][] = array (  'icon' => "icons/16/green/accept.png" ,
                                        "text"  => sprintf(_("Die Verbindung zum System \"%s\" ist <b>aktiv</b>. Sie k&ouml;nnen die Einbindung des Systems in Stud.IP jederzeit deaktivieren."), $connected_cms[$cms_select]->getName())
                                    );
            break;
            case "not active":
            $infobox[1]["eintrag"][] = array (  'icon' => "icons/16/black/exclaim.png" ,
                                        "text"  => sprintf(_("Die Verbindung zum System \"%s\" steht, das System ist jedoch nicht aktiviert. Sie k&ouml;nnen die Einbindung des Systems in Stud.IP jederzeit aktivieren. Solange die Verbindung nicht aktiviert wurde, werden die Module des Systems \"%s\" in Stud.IP nicht angezeigt."), $connected_cms[$cms_select]->getName(), $connected_cms[$cms_select]->getName())
                                    );
            break;
            case "error":
            $infobox[1]["eintrag"][] = array (  'icon' => "icons/16/black/decline.png" ,
                                        "text"  => sprintf(_("Bei der Pr&uuml;fung der Verbindung sind Fehler aufgetreten. Sie m&uuml;ssen zun&auml;chst die Eintr&auml;ge in der Konfigurationsdatei korrigieren, bevor das System angebunden werden kann."), $connected_cms[$cms_select]->getName())
                                    );
            break;
        }
        $cssSw = new cssClassSwitcher;                                  // Klasse für Zebra-Design

    ?>
    <br>
    </td>
    <td width="270" class="blank" align="center" valign="top">
    <?
        print_infobox ($infobox, "infobox/lernmodule.jpg");
    ?>
    </td>
</tr>
<tr>
    <td class="blank" colspan="3">&nbsp;
    </td>
</tr>
</table><?

// terminate objects
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();

}
else
{
    // Start of Output
    parse_window ("error§" . _("Die Schnittstelle für die Integration von Lernmodulen ist nicht aktiviert. Damit Lernmodule verwendet werden können, muss die Verbindung zu einem LCM-System in der Konfigurationsdatei von Stud.IP hergestellt werden. Wenden Sie sich bitte an den/die AdministratorIn."), "§",
                _("E-Learning-Schnittstelle nicht eingebunden"));
}
include ('lib/include/html_end.inc.php');
page_close();
?>
