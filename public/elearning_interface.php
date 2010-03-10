<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// elearning_interface.php
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("autor");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('config.inc.php');
require_once ('lib/visual.inc.php');

mark_public_course();

$HELP_KEYWORD="Basis.Ilias";
$CURRENT_PAGE = $SessSemName["header_line"]. " - " . _("Lernmodule");
Navigation::activateItem('/course/elearning/' . Request::get('view'));

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if ($ELEARNING_INTERFACE_ENABLE AND (($view == "edit") OR ($view == "show")))
{
    $caching_active = false;

    checkObject();
    checkObjectModule("elearning_interface");
    object_set_visit_module("elearning_interface");

    require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ELearningUtils.class.php");
    require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ObjectConnections.class.php");
    ELearningUtils::bench("start");

    ELearningUtils::bench("checkObject");

    if ((! $rechte) AND ($view == "edit"))
        $view = "show";

//  echo "wp: " . $write_permission . "<br>";
    $seminar_id = $SessSemName[1];
    if ($seminar_id != $elearning_open_close["id"])
    {
        $sess->unregister("cache_data");
        unset($cache_data);
        $sess->unregister("elearning_open_close");
        unset($elearning_open_close);
    }
    if ($open_all != "")
        $elearning_open_close["all open"] = true;
    elseif ($close_all != "")
        $elearning_open_close["all open"] = "";
    $elearning_open_close["type"] = "seminar";
    $elearning_open_close["id"] = $seminar_id;
    if (isset($do_open))
    {
        $anker_target = $do_open;
        $elearning_open_close[$do_open] = true;
    }
    elseif (isset($do_close))
    {
        $anker_target = $do_close;
        $elearning_open_close[$do_close] = false;
    }
    $sess->register("elearning_open_close");

    ELearningUtils::bench("init");

    if (($view=="show") AND (isset($new_account_cms)))
    {
        $page_content = ELearningUtils::getNewAccountForm($new_account_cms);

        //Dummy-Instanz der Zuordnungs-Klasse ohne Verbindung zur Veranstaltung
        $object_connections = new ObjectConnections();
    }
    if ($new_account_cms == "")
    {

        if ($view == "edit")
        {

            if ($module_system_type != "")
            {
                $user_crs_role = $connected_cms[$module_system_type]->crs_roles[$auth->auth["perm"]];
                ELearningUtils::loadClass($module_system_type);
            }
            if (isset($_REQUEST['remove_x']) AND $rechte AND ($user_crs_role != "admin"))
            {
                $connected_cms[$module_system_type]->newContentModule($module_id, $module_type, true);
                if ($connected_cms[$module_system_type]->content_module[$module_id]->unsetConnection($seminar_id, $module_id, $module_type, $module_system_type))
                    $messages["info"] .= _("Die Zuordnung wurde entfernt.");
                unset($connected_cms[$module_system_type]->content_module[$module_id]);
            }
            elseif (isset($_REQUEST['add_x']) AND $rechte AND ($user_crs_role != "admin"))
            {
                $connected_cms[$module_system_type]->newContentModule($module_id, $module_type, true);
                if ($connected_cms[$module_system_type]->content_module[$module_id]->setConnection($seminar_id))
                    $messages["info"] .= _("Die Zuordnung wurde gespeichert.");
                unset($connected_cms[$module_system_type]->content_module[$module_id]);
            }
            if ($search_key != "")
            {
                ELearningUtils::loadClass($cms_select);
                if ( strlen( trim($search_key) ) > 2)
                    $searchresult_content_modules = $connected_cms[$cms_select]->searchContentModules($search_key);
                else
                    $messages["error"] = _("Jeder Suchbegriff muss mindestens 3 Zeichen lang sein!");
            }
        }
        ELearningUtils::bench("new account, operations, search");

        //Instanz mit den Zuordnungen von Content-Modulen zur Veranstaltung
        $object_connections = new ObjectConnections($seminar_id);

        $connected_modules = $object_connections->getConnections();
        ELearningUtils::bench("connections");
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

    echo $page_content;

    $module_count = 0;
    if ($object_connections->isConnected())
    {
        $caching_active = true;
        foreach ($connected_modules as $key => $connection)
        {
            if (ELearningUtils::isCMSActive($connection["cms"]))
            {

                ELearningUtils::loadClass($connection["cms"]);

                $connected_cms[$connection["cms"]]->newContentModule($connection["id"], $connection["type"], true);
                $connected_modules[$key]['title'] = $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->getTitle();
                $title_tmp[$key] = str_replace(array('ä','ö','ü','ß'),array('ae','oe','ue','ss'),strtolower($connected_modules[$key]['title']));
                $type_tmp[$key] = array_search($connection['type'], array_keys($ELEARNING_INTERFACE_MODULES[$connection["cms"]]['types']));
                $class_tmp[$key] = $ELEARNING_INTERFACE_MODULES[$connection["cms"]]["CLASS_PREFIX"];
            }
        }

        array_multisort($class_tmp, SORT_ASC, $type_tmp, SORT_ASC, $title_tmp, SORT_ASC, $connected_modules);

        foreach ($connected_modules as $connection)
        {
            $current_module = $connection["id"]; //Arrrghhhh

            if ($module_count == 0) echo ELearningUtils::getModuleHeader(_("Angebundene Lernmodule"));
                $module_count++;
                if ($open_all != "")
                    $elearning_open_close[$connected_cms[$connection["cms"]]->content_module[$connection["id"]]->getReferenceString()] = true;
                elseif ($close_all != "")
                    $elearning_open_close[$connected_cms[$connection["cms"]]->content_module[$connection["id"]]->getReferenceString()] = false;
                // USE_CASE 1: show connected contentmodules
                if ($view == "show")
                {
                    $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->view->setChangeDate($connection["chdate"]);
                    $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->view->show();
                }
                // USE_CASE 2: edit contentmodule connections
                elseif ($view == "edit")
                    $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->view->showAdmin();

                ELearningUtils::bench("module");
        }
        echo "<br>\n";
        echo "<br>\n";
    }

    if (($module_count == 0) AND ($new_account_cms == ""))
    {
        if ($SessSemName["class"]=="inst")
            echo "<b>" . _("Momentan sind dieser Einrichtung keine Lernmodule zugeordnet.") . "</b><br>\n<br>\n<br>\n";
        else
            echo "<b>" . _("Momentan sind dieser Veranstaltung keine Lernmodule zugeordnet.") . "</b><br>\n<br>\n<br>\n";
    }

    $caching_active = false;
    if ($view == "edit")
    {

//      echo "<br>\n";

        if (isset($ELEARNING_INTERFACE_MODULES[$cms_select]["name"]))
        {
            ELearningUtils::loadClass($cms_select);

            $user_content_modules = $connected_cms[$cms_select]->getUserContentModules();
            echo ELearningUtils::getCMSHeader($connected_cms[$cms_select]->getName());
            echo "<br>\n";
            if (! ($user_content_modules == false))
            {
                echo ELearningUtils::getHeader(sprintf(_("Ihre Lernmodule in %s"), $connected_cms[$cms_select]->getName()));
                foreach ($user_content_modules as $key => $connection)
                {
                    // show only those modules which are not already connected to the seminar
//                  if ($connection_id["ref_id"] == "")
//                      continue;
                    if (is_object($connected_cms[$cms_select]->content_module[$connection["ref_id"]]))
                        continue;
                    $connected_cms[$cms_select]->setContentModule($connection, false);
                    $connected_cms[$cms_select]->content_module[$current_module]->view->showAdmin();
                }
                echo "<br>\n";
            }
//          else
//              echo sprintf(_("Sie haben im System %s keine eigenen Lernmodule."), $connected_cms[$cms_select]->getName()) . "<br><br>\n\n";
            ELearningUtils::bench("user modules");

            if ($anker_target == "search")
                echo "<a name='anker'></a>";
            echo ELearningUtils::getHeader(_("Suche"));
            echo ELearningUtils::getSearchfield(sprintf(_("Um im System %s nach Lernmodulen zu suchen, geben Sie einen Suchbegriff ein:"), $connected_cms[$cms_select]->getName()));
            echo "<br>\n";

            if (! ($searchresult_content_modules == false))
            {
                echo ELearningUtils::getHeader( sprintf( _("Gefundene Lernmodule zum Suchbegriff \"%s\""), $search_key ) );
                foreach ($searchresult_content_modules as $key => $connection)
                {
                    // show only those modules which are not already connected to the seminar
                    if (is_object($connected_cms[$cms_select]->content_module[$connection["ref_id"]]))
                        continue;
                    $connected_cms[$cms_select]->setContentModule($connection, false);
                    $connected_cms[$cms_select]->content_module[$current_module]->view->showAdmin();
                }
                echo "<br>\n";
            }
            if ( ( strlen( trim($search_key) ) > 2 ) AND ($searchresult_content_modules == false))
            echo "<br>\n<b><font size=\"-1\">&nbsp;" . sprintf( _("Es gibt im System %s zu diesem Suchbegriff keine Lernmodule."),  $connected_cms[$cms_select]->getName()) . "</font></b>";
            echo "<br>\n";
            echo ELearningUtils::getCMSFooter($connected_cms[$cms_select]->getLogo());
        }

        echo "<br>\n";
        if ($anker_target == "choose")
            echo "<a name='anker'></a>";
        if ($cms_select == "")
            echo ELearningUtils::getCMSSelectbox("<b>" . _("Um Lernmodule hinzuzuf&uuml;gen, w&auml;hlen Sie ein angebundenes System aus:") . "</b>");
        else
            echo ELearningUtils::getCMSSelectbox(_("Um Lernmodule hinzuzuf&uuml;gen, w&auml;hlen Sie ein angebundenes System aus:"));
        ELearningUtils::bench("search");
    }


// Cachen der SOAP-Daten
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();

/**/
    if ($debug != "")
    {
        ELearningUtils::showbench();
/*/
        foreach($cache_data as $cms => $data)
        {
            echo "<br>$cms";
            foreach($data as $id => $module)
            {
                echo "<br>$id<br>";
                foreach($module as $key => $value)
                {
                    echo "$key = $value<br>";
                }
            }
        }
/**/
    }
    // Anzeige, wenn noch keine Account-Zuordnung besteht
    if ($view=="edit")
    {
        $infobox = array    (
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => "ausruf_small.gif",
                                    "text"  => _("Hier k&ouml;nnen Sie Lernmodule f&uuml;r die Veranstaltung einh&auml;ngen und aush&auml;ngen. Das Laden dieser Seite kann etwas l&auml;nger dauern, da Daten zwischen Stud.IP und den angebundenen Systemen ausgetauscht werden.")
                                 )
                            )
            )
        );
        $infobox[1]["kategorie"] = _("Aktionen:");
            $infobox[1]["eintrag"][] = array (  "icon" => "forumgrau.gif" ,
                                        "text"  => _("W&auml;hlen Sie das System, aus dem Sie ein Modul einh&auml;ngen wollen. Anschlie&szlig;end k&ouml;nnen Sie nach Modulen suchen. Gefundene Module k&ouml;nnen Sie mit dem Button \"hinzuf&uuml;gen\" der Veranstaltung zuordnen.")
                                    );

            $infobox[1]["eintrag"][] = array (  "icon" => "icon-lern.gif" ,
                                        "text"  => sprintf(_("Um neue Lernmodule zu erstellen, wechseln Sie auf die Seite %s, auf der sie Ihre Lernmodule und externen Nutzer-Accounts verwalten k&ouml;nnen."), "<a href=\"my_elearning.php\">\"" . _("Meine Lernmodule") . "\"</a>")
                                    );

        $cssSw = new cssClassSwitcher;                                  // Klasse für Zebra-Design
    }
    else
    {
        $infobox = array    (
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => "ausruf_small.gif",
                                    "text"  => _("Hier sehen sie die Lernmodule, die an diese Veranstaltung angeh&auml;ngt wurden.")
                                 )
                            )
            )
        );
        $infobox[1]["kategorie"] = _("Aktionen:");
            $infobox[1]["eintrag"][] = array (  "icon" => "forumgrau.gif" ,
                                        "text"  => _("Wenn Sie in einem Lernmodul auf 'Starten' klicken, &ouml;ffnet sich ein neues Fenster mit dem Lernmodul.")
                                    );

            $infobox[1]["eintrag"][] = array (  "icon" => "icon-lern.gif" ,
                                        "text"  => sprintf(_("Um neue Lernmodule zu erstellen, wechseln Sie auf die Seite %s, auf der sie Ihre Lernmodule und externen Nutzer-Accounts verwalten k&ouml;nnen."), "<a href=\"my_elearning.php\">\"" . _("Meine Lernmodule") . "\"</a>")
                                    );

        $cssSw = new cssClassSwitcher;                                  // Klasse für Zebra-Design
    }


        ?>
        <br>
        </td>
        <td width="270" class="blank" align="center" valign="top">
        <?
            print_infobox ($infobox,"lernmodule.jpg");
        ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="3">&nbsp;
        </td>
    </tr>
    </table>
    <?php
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
