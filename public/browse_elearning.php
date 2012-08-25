<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// browse_lernmodule.php
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


require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("autor");

$search_key = Request::quoted('search_key');
$cms_select = Request::quoted('cms_select');
$new_account_cms = Request::quoted('new_account_cms');
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('config.inc.php');
include_once ('lib/visual.inc.php');
require_once ('lib/messaging.inc.php');

PageLayout::setTitle(_("Lernmodulsuche"));

if (Request::option('do_open'))
    $_SESSION['print_open_search'][Request::option('do_open')] = true;
elseif (Request::option('do_close'))
    $_SESSION['print_open_search'][Request::option('do_close')] = false;

if ($ELEARNING_INTERFACE_ENABLE)
{

    include_once ($RELATIVE_PATH_ELEARNING_INTERFACE ."/" . "ELearningUtils.class.php");

    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head

    if ($_SESSION['elearning_open_close']["type"] != "search")
    {
      unset($_SESSION['elearning_open_close']);
    }/**/
    $_SESSION['elearning_open_close']["type"] = "search";
    $_SESSION['elearning_open_close']["id"] = "";
    if (Request::option('do_open'))
        $_SESSION['elearning_open_close'][Request::option('do_open')] = true;
    elseif (Request::option('do_close'))
        $_SESSION['elearning_open_close'][Request::option('do_close')] = false;
    

    if ($search_key != "")
    {
        ELearningUtils::loadClass($cms_select);
//      require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/" . $ELEARNING_INTERFACE_MODULES[$cms_select]["CLASS_PREFIX"] . "ConnectedCMS.class.php");
//      $classname = $ELEARNING_INTERFACE_MODULES[$cms_select]["CLASS_PREFIX"] . "ConnectedCMS";
//      $connected_cms[$cms_select] = new $classname($cms_select);
//      $connected_cms[$cms_select]->initSubclasses();
        if ( strlen( trim($search_key) ) > 2)
            $searchresult_content_modules = $connected_cms[$cms_select]->searchContentModules($search_key);
        else
            $messages["error"] = _("Der Suchbegriff muss mindestens 3 Zeichen lang sein!");
    }

    checkObjectModule("elearning_interface");

    $infobox = array    (array ("kategorie"  => _("Information:"),
            "eintrag" => array  (array (    "icon" => "icons/16/black/info.png",
                                    "text"  => sprintf(_("Auf dieser Seite k&ouml;nnen Sie nach Lernmodulen im angebundenen ILIAS-System suchen.")) ) ) ) );

    $infobox[1]["kategorie"] = _("Aktionen:");
    $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/learnmodule.png" ,
                                    "text"  => sprintf(_("Geben Sie einen Suchbegriff ein und klicken Sie auf 'Suchen'. Die Suche bezieht sich auf den ausgew&auml;hlten Suchbereich.")));

    ?>
    <table cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="table_header_bold" colspan="3">&nbsp;</td>
        </tr>
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
    if (!empty($new_account_cms))
    {
        $output = ELearningUtils::getNewAccountForm($new_account_cms);
    }

    if ($messages["info"] != "")
    {
        echo "<table>";
        my_info($messages["info"]);
        echo "</table>";
    }
    if ($messages["error"] != "")
    {
        echo "<table>";
        my_error($messages["error"]);
        echo "</table>";
    }

    ?>
    <table cellpadding="10" cellspacing="01" border="0" width="100%"><tr><td>
    <?

    echo $output;

    if ($new_account_cms == "")
    {
        echo _("Hier k&ouml;nnen Sie nach Lernmodulen suchen.");
        ?>
        <br><br>
        <?
        if (isset($ELEARNING_INTERFACE_MODULES[$cms_select]["name"]))
        {
            ELearningUtils::loadClass($cms_select);

            echo ELearningUtils::getCMSHeader($connected_cms[$cms_select]->getName());
            echo "<br>\n";
            echo ELearningUtils::getHeader(_("Suche"));
            echo ELearningUtils::getSearchfield(sprintf(_("Um im System %s nach Content-Modulen zu suchen, geben Sie einen Suchbegriff ein:"), $connected_cms[$cms_select]->getName()));
            echo "<br>\n";
            if (! ($searchresult_content_modules == false))
            {
                echo ELearningUtils::getHeader( sprintf( _("Gefundene Lernmodule zum Suchbegriff \"%s\""), $search_key ) );
                foreach ($searchresult_content_modules as $key => $connection)
                {
                    $connected_cms[$cms_select]->setContentModule($connection, false);
                    $connected_cms[$cms_select]->content_module[$current_module]->view->show("searchresult");
                }
                echo "<br>\n";
            }
            if ( ( strlen( trim($search_key) ) > 2 ) AND ($searchresult_content_modules == false))
            echo MessageBox::info(sprintf( _("Es gibt im System %s zu diesem Suchbegriff keine Content-Module."),  $connected_cms[$cms_select]->getName()));
            echo ELearningUtils::getCMSFooter($connected_cms[$cms_select]->getLogo());
        }

        echo "<br>\n";
        if ($cms_select == "")
            echo ELearningUtils::getCMSSelectbox("<b>" . _("W&auml;hlen Sie ein angebundenes System f&uuml;r die Suche:") . "</b>");
        else
            echo ELearningUtils::getCMSSelectbox(_("W&auml;hlen Sie ein angebundenes System f&uuml;r die Suche:"));
    }

// Cachen der SOAP-Daten
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();


    ?>
    </td></tr></table>

        </td>
        <td width="270" class="blank" align="right" valign="top">
        <? print_infobox($infobox, "infobox/lernmodule.jpg"); ?>
        </td>
    </tr>
       <tr>
                <td class="blank" colspan="3">&nbsp;
                </td>
        </tr>
    </table>
<?

// terminate objects
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();

}
else
{
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    parse_window ("error§" . _("Die Schnittstelle für die Integration von Lernmodulen ist nicht aktiviert. Damit Lernmodule verwendet werden können, muss die Verbindung zu einem LCM-System in der Konfigurationsdatei von Stud.IP hergestellt werden. Wenden Sie sich bitte an den/die AdministratorIn."), "§",
                _("E-Learning-Schnittstelle nicht eingebunden"));
}
include ('lib/include/html_end.inc.php');
page_close();
?>
