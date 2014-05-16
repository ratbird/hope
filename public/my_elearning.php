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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("autor");
$new_account_cms = Request::get('new_account_cms');
if (!isset($ELEARNING_INTERFACE_MODULES[$new_account_cms])) {
    unset($new_account_cms);
}
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/visual.inc.php');

PageLayout::setTitle(_("Meine Lernmodule und Benutzer-Accounts"));
PageLayout::setHelpKeyword("Basis.Ilias");

Navigation::activateItem('/tools/elearning');

$cms_list = array();
if (!get_config('ELEARNING_INTERFACE_ENABLE')) {
    PageLayout::postMessage(MessageBox::error(_("Die Schnittstelle für die Integration von Lernmodulen ist nicht aktiviert. Damit Lernmodule verwendet werden können, muss die Verbindung zu einem LCM-System in der Konfigurationsdatei von Stud.IP hergestellt werden. Wenden Sie sich bitte an den/die AdministratorIn.")));
} else {
    require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ELearningUtils.class.php");
    ELearningUtils::bench("start");

    if ($_SESSION['elearning_open_close']["type"] != "user")
    {
       unset($_SESSION['elearning_open_close']);
    }
    $_SESSION['elearning_open_close']["type"] = "user";
    $_SESSION['elearning_open_close']["id"] = $auth->auth["uid"];
    if (Request::get('do_open'))
        $_SESSION['elearning_open_close'][Request::get('do_open')] = true;
    elseif (Request::get('do_close'))
        $_SESSION['elearning_open_close'][Request::get('do_close')] = false;

    if ($new_account_cms != "")
        $new_account_form = ELearningUtils::getNewAccountForm($new_account_cms);
    foreach($ELEARNING_INTERFACE_MODULES as $cms => $cms_preferences) {
        if (ELearningUtils::isCMSActive($cms)) {
            ELearningUtils::loadClass($cms);
            if ( $cms_preferences["auth_necessary"] == true) {
                $new_module_form[$cms] = ELearningUtils::getNewModuleForm($cms);
            }
            $connection_status = $connected_cms[$cms]->getConnectionStatus($cms);

            foreach ($connection_status as $type => $msg) {
                if ($msg["error"] != "") {
                    PageLayout::postMessage(MessageBox::error(sprintf(_("Es traten Probleme bei der Anbindung einzelner Lermodule auf. Bitte wenden Sie sich an Ihren Systemadministrator."),$cms)));
                }
            }
        }
    }
    ELearningUtils::bench("init");

    $connected_cms = array();
    // prepare cms list
    foreach($ELEARNING_INTERFACE_MODULES as $cms => $cms_preferences) {
        if (ELearningUtils::isCMSActive($cms) AND $cms_preferences["auth_necessary"]) {
            ELearningUtils::loadClass($cms);
            $cms_list[$cms] = $cms_preferences;
            $cms_list[$cms]['name'] = $connected_cms[$cms]->getName();
            $cms_list[$cms]['logo'] = $connected_cms[$cms]->getLogo();
            $cms_list[$cms]['modules'] = array();
            if ($new_account_cms != $cms)
                $cms_list[$cms]['show_account_form'] = $cms_preferences;
            if ($GLOBALS["module_type_" . $cms] != "")
                $cms_list[$cms]['cms_anker_target'] = true;
            if ($connected_cms[$cms]->user->isConnected())
                $cms_list[$cms]['start_link'] = $connected_cms[$cms]->link->getStartpageLink();
                
            if ($new_account_cms != $cms) {
                if ($connected_cms[$cms]->user->isConnected()) {
                    $cms_list[$cms]['user'] = $connected_cms[$cms]->user->getUsername();
                    $connected_cms[$cms]->soap_client->setCachingStatus(false);
                    $user_content_modules = $connected_cms[$cms]->getUserContentModules();
                    $connected_cms[$cms]->soap_client->setCachingStatus(true);
                    if (! ($user_content_modules == false)) {
                        foreach ($user_content_modules as $key => $connection) {
                            $connected_cms[$cms]->setContentModule($connection, false);
                            $cms_list[$cms]['modules'][] = $connected_cms[$cms]->content_module[$current_module]->view->show();
                        }
                    } 
                    $cms_list[$cms]['new_module_form'] = $new_module_form[$cms];
                }
            } else {
                $cms_list[$cms]['account_form'] = $new_account_form;
            }
            ELearningUtils::bench("fetch data from $cms");
        }
     }

    // Cachen der SOAP-Daten
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();

    if ($debug != "")
        ELearningUtils::showbench();

    // help texts for help center -> to be put into db!
    $help_text[] = _('Auf dieser Seite sehen Sie Ihre Benutzer-Accounts und Lernmodule in angebundenen Systemen.');
    $help_text[] = _('Sie können für jedes externe System einen eigenen Benutzer-Account erstellen oder zuordnen.');
    $help_text[] = _('Wenn Sie über die entsprechenden Rechte verfügen, können Sie eigene Lernmodule erstellen.');

    $sidebar = Sidebar::get();
    $sidebar->setImage('sidebar/learnmodule-sidebar.png');
    $widget = new LinksWidget();
    $widget->setTitle(_('Aktionen'));
    
    if ($GLOBALS['perm']->have_perm('autor') AND count($cms_list)) {
        foreach($cms_list as $cms_data) {
            //TODO: target = '_blank' setzen
            $widget->addLink(sprintf(_('Zur %s Startseite'), $cms_data['name']), $cms_data['start_link'], 'icons/16/black/link-extern.png');
        }
    }
    $sidebar->addWidget('actions', $widget);

    // terminate objects
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();
}

$template = $GLOBALS['template_factory']->open('elearning/my_elearning.php');
$template->set_layout('layouts/base');
$template->set_attribute('cms_list', $cms_list);
echo $template->render();
page_close();