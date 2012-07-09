<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * my_elearning.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Arne Schroeder <schroeder@data-quest.de>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/


require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("autor");
$new_account_cms = Request::option('new_account_cms');
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/visual.inc.php');

PageLayout::setTitle(_("Meine Lernmodule und Benutzer-Accounts"));
Navigation::activateItem('/tools/elearning');

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head


if (get_config('ELEARNING_INTERFACE_ENABLE')) {
    require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ELearningUtils.class.php");
    ELearningUtils::bench("start");

    if ($_SESSION['elearning_open_close']["type"] != "user")
    {
       unset($_SESSION['elearning_open_close']);
    }
    $_SESSION['elearning_open_close']["type"] = "user";
    $_SESSION['elearning_open_close']["id"] = $auth->auth["uid"];
    if (Request::option('do_open'))
        $_SESSION['elearning_open_close'][Request::option('do_open')] = true;
    elseif (Request::option('do_close'))
        $_SESSION['elearning_open_close'][Request::option('do_close')] = false;
    


    ?><table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td valign="top" class="blank">
    <?

    if ($new_account_cms != "")
        $new_account_form = ELearningUtils::getNewAccountForm($new_account_cms);
    foreach($ELEARNING_INTERFACE_MODULES as $cms => $cms_preferences) {
        if (ELearningUtils::isCMSActive($cms))
        {
            ELearningUtils::loadClass($cms);
            if ( $cms_preferences["auth_necessary"] == true) {
                $new_module_form[$cms] = ELearningUtils::getNewModuleForm($cms);
            }
            $connection_status = $connected_cms[$cms]->getConnectionStatus($cms);

            foreach ($connection_status as $type => $msg)
            {
                if ($msg["error"] != "")
                {
                    $messages['error'] = sprintf(_("Es traten Probleme bei der Anbindung einzelner Lermodule auf. Bitte wenden Sie sich an Ihren Systemadministrator."),$cms);
                    $errors[] = $msg['error'];
                }
            }
        }
    }
    if ($messages["info"] != "")
    {
        echo MessageBox::info($messages["info"]);
    }
    if ($messages["error"] != "")
    {
        echo MessageBox::error($messages["error"], $errors);

    }

    ELearningUtils::bench("init");

    echo $page_content;
    foreach($ELEARNING_INTERFACE_MODULES as $cms => $cms_preferences)
    {
        if (ELearningUtils::isCMSActive($cms))
        {
            $connected_cms = array();
            ELearningUtils::loadClass($cms);
            if (($cms_preferences["auth_necessary"] == true))
            {
                if ($GLOBALS["module_type_" . $cms] != "")
                    echo "<a name='anker'></a>";
//              ELearningUtils::loadClass($cms);
//              ELearningUtils::bench("load cms $cms");

                echo ELearningUtils::getCMSHeader($connected_cms[$cms]->getName());
                echo "<font size=\"-1\">";
                echo "<br>\n";
                echo "</font>";

                echo ELearningUtils::getHeader(sprintf(_("Mein Benutzeraccount")));
                if ($connected_cms[$cms]->user->isConnected())
                {
                    $account_message = "<b>" . _("Loginname: ") . "</b>" . $connected_cms[$cms]->user->getUsername();
                    $start_link = $connected_cms[$cms]->link->getStartpageLink(_("Startseite"));
                    if ($start_link != false)
                        $account_message .=  "<br><br>" . sprintf(_("Hier gelangen Sie in das angebundene System: %s"), $start_link);
                }
                else
                    $account_message = sprintf(_("Sie haben im System %s bisher keinen Benutzer-Account."), $connected_cms[$cms]->getName());

                if ($new_account_cms != $cms)
                {
                    echo ELearningUtils::getMyAccountForm("<font size=\"-1\">" . $account_message . "</font>", $cms);

                    echo "<br>\n";

                    if ($connected_cms[$cms]->user->isConnected())
                    {
                        echo ELearningUtils::getHeader(sprintf(_("Meine Lernmodule")));

                        $connected_cms[$cms]->soap_client->setCachingStatus(false);
                        $user_content_modules = $connected_cms[$cms]->getUserContentModules();
                        $connected_cms[$cms]->soap_client->setCachingStatus(true);

                        if (! ($user_content_modules == false))
                        {
                            foreach ($user_content_modules as $key => $connection)
                            {
                                $connected_cms[$cms]->setContentModule($connection, false);
                                $connected_cms[$cms]->content_module[$current_module]->view->show();
                            }
                        }
                        else
                            echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"6\"><tr><td><font size=\"-1\">" . sprintf(_("Sie haben im System %s keine eigenen Lernmodule."), $connected_cms[$cms]->getName()) . "<br>\n<br>\n</font></td></tr></table>";

                        echo "<br>\n";
                        echo $new_module_form[$cms];

                    }
                }
                else
                {
                    echo $new_account_form;
                    echo "<br>\n";
                }

//              echo "<br>\n";
                echo ELearningUtils::getCMSFooter($connected_cms[$cms]->getLogo());
                echo "<br>\n";
                ELearningUtils::bench("fetch data from $cms");
            }
        }
     }

// Cachen der SOAP-Daten
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();

//  ELearningUtils::bench("fetch data");
    if ($debug != "")
        ELearningUtils::showbench();

        $cssSw = new cssClassSwitcher; // Klasse für Zebra-Design

        ?>
        </td>
        <td width="270" class="blank" align="right" valign="top">
        <?
    // Anzeige, wenn noch keine Account-Zuordnung besteht
        $infobox = array    (
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => 'icons/16/black/info.png',
                                    "text"  => _("Auf dieser Seite sehen Sie Ihre Benutzer-Accounts und Lernmodule in angebundenen Systemen.")
                                 )
                            )
            )
        );
        $infobox[1]["kategorie"] = _("Aktionen:");
            $infobox[1]["eintrag"][] = array (  "icon" => 'icons/16/black/person.png' ,
                                        "text"  => _("Sie k&ouml;nnen f&uuml;r jedes externe System einen eigenen Benutzer-Account erstellen oder zuordnen.")
                                    );

            $infobox[1]["eintrag"][] = array (  "icon" => 'icons/16/black/learnmodule.png' ,
                                        "text"  => sprintf(_("Wenn Sie &uuml;ber die entsprechenden Rechte verf&uuml;gen, k&ouml;nnen Sie eigene Lernmodule erstellen."))
                                    );
            print_infobox($infobox, "infobox/lernmodule.jpg");
        ?>
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
    //TODO use messagebox
    parse_window ("error§" . _("Die Schnittstelle für die Integration von Lernmodulen ist nicht aktiviert. Damit Lernmodule verwendet werden können, muss die Verbindung zu einem LCM-System in der Konfigurationsdatei von Stud.IP hergestellt werden. Wenden Sie sich bitte an den/die AdministratorIn."), "§",
                _("E-Learning-Schnittstelle nicht eingebunden"));
}

include ('lib/include/html_end.inc.php');
page_close();
