<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("autor");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/visual.inc.php');

checkObject();
checkObjectModule("elearning_interface");
object_set_visit_module("elearning_interface");

PageLayout::setHelpKeyword("Basis.Ilias");
PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Lernmodule"));
Navigation::activateItem('/course/elearning/' . Request::option('view'));
$template = $GLOBALS['template_factory']->open('elearning/elearning_interface_show.php');

$search_key = Request::get('search_key');
$cms_select = Request::option('cms_select');
if (Request::option('view'))
    $view = Request::option('view');
else
    $view = 'show';
$open_all = Request::get('open_all');
$close_all = Request::get('close_all');
$new_account_cms = Request::get('new_account_cms');
$module_system_type = Request::get('module_system_type');
$module_id = Request::get('module_id');
$module_type = Request::get('module_type');
$anker_target = Request::get('anker_target');
if (!isset($ELEARNING_INTERFACE_MODULES[$new_account_cms])) {
    unset($new_account_cms);
}
if (!isset($ELEARNING_INTERFACE_MODULES[$cms_select])) {
    unset($cms_select);
}


if ($ELEARNING_INTERFACE_ENABLE AND (($view == "edit") OR ($view == "show")))
{
    $elearning_active = true;
    $caching_active = false;

    require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ELearningUtils.class.php");
    require_once ($RELATIVE_PATH_ELEARNING_INTERFACE . "/ObjectConnections.class.php");
    ELearningUtils::bench("start");

    ELearningUtils::bench("checkObject");

    $rechte = $perm->have_studip_perm('tutor', $SessSemName[1]);
    $seminar_id = $SessSemName[1];

    if ((! $rechte) AND ($view == "edit"))
        $view = "show";

    if ($seminar_id != $_SESSION['elearning_open_close']["id"])
    {
        unset($_SESSION['cache_data']);
        unset($_SESSION['elearning_open_close']);
    }
    if ($open_all != "")
        $_SESSION['elearning_open_close']["all open"] = true;
    elseif ($close_all != "")
        $_SESSION['elearning_open_close']["all open"] = "";
    $_SESSION['elearning_open_close']["type"] = "seminar";
    $_SESSION['elearning_open_close']["id"] = $seminar_id;
    if (Request::get('do_open')) {
        $anker_target = Request::get('do_open');
        $_SESSION['elearning_open_close'][Request::get('do_open')] = true;
    } elseif (Request::get('do_close')) {
        $anker_target = Request::get('do_close');
        $_SESSION['elearning_open_close'][Request::get('do_close')] = false;
    }

    // ggf. neuen Ilias4-Kurs anlegen
    if (Request::submitted('create_course') AND $rechte) {
        ELearningUtils::loadClass($cms_select);
        if ((method_exists($connected_cms[$cms_select], "createCourse")))
            if ($connected_cms[$cms_select]->createCourse($SessSemName[1]))
                //$messages["info"] .= "Kurs wurde angelegt.<br>";
                PageLayout::postMessage(MessageBox::info(_('Kurs wurde angelegt.')));
    }

    // ggf. bestehenden Ilias4-Kurs zuordnen
    if (Request::submitted('connect_course')) {
        if ((ObjectConnections::getConnectionModuleId(Request::option("connect_course_sem_id"), "crs", $cms_select)) AND ($perm->have_studip_perm("dozent", Request::option("connect_course_sem_id")))) {
            ObjectConnections::setConnection($SessSemName[1], ObjectConnections::getConnectionModuleId(Request::option("connect_course_sem_id"), "crs", $cms_select), "crs", $cms_select);
            //$messages["info"] .= "Zuordnung wurde gespeichert.<br>";
            PageLayout::postMessage(MessageBox::info(_('Zuordnung wurde gespeichert.')));
            ELearningUtils::loadClass($cms_select);
            if ((method_exists($connected_cms[$cms_select], "updateConnections")))
                $connected_cms[$cms_select]->updateConnections( ObjectConnections::getConnectionModuleId(Request::option("connect_course_sem_id"), "crs", $cms_select) );
        }
    }

    if ($view == "edit")
        $template = $GLOBALS['template_factory']->open('elearning/elearning_interface_edit.php');
    
    // Zugeordnete Ilias-Kurse ermitteln und ggf. aktualisieren
    $course_output = ELearningUtils::getIliasCourses($SessSemName[1]);
    ELearningUtils::bench("init");

    if (($view=="show") AND (!empty($new_account_cms))) {
        //Dummy-Instanz der Zuordnungs-Klasse ohne Verbindung zur Veranstaltung
        $object_connections = new ObjectConnections();
    }
    if ($new_account_cms == "") {
        if ($view == "edit") {
            if ($module_system_type != "") {
                $user_crs_role = $connected_cms[$module_system_type]->crs_roles[$auth->auth["perm"]];
                ELearningUtils::loadClass($module_system_type);
            }
            if (Request::submitted('remove') AND $rechte) {
                $connected_cms[$module_system_type]->newContentModule($module_id, $module_type, true);
                if ($connected_cms[$module_system_type]->content_module[$module_id]->unsetConnection($seminar_id, $module_id, $module_type, $module_system_type))
                    PageLayout::postMessage(MessageBox::info(_('Die Zuordnung wurde entfernt.')));
                unset($connected_cms[$module_system_type]->content_module[$module_id]);
            } elseif (Request::submitted('add') AND $rechte) {
                $connected_cms[$module_system_type]->newContentModule($module_id, $module_type, true);
                if ($connected_cms[$module_system_type]->content_module[$module_id]->setConnection($seminar_id))
                    PageLayout::postMessage(MessageBox::info(_('Die Zuordnung wurde gespeichert.')));
                unset($connected_cms[$module_system_type]->content_module[$module_id]);
            }
            if ($search_key != "") {
                ELearningUtils::loadClass($cms_select);
                if ( strlen( trim($search_key) ) > 2)
                    $searchresult_content_modules = $connected_cms[$cms_select]->searchContentModules($search_key);
                else
                    PageLayout::postMessage(MessageBox::error(_('Jeder Suchbegriff muss mindestens 3 Zeichen lang sein!')));
            }
        }
        ELearningUtils::bench("new account, operations, search");

        //Instanz mit den Zuordnungen von Content-Modulen zur Veranstaltung
        $object_connections = new ObjectConnections($seminar_id);
        
        $connected_modules = $object_connections->getConnections();
        ELearningUtils::bench("connections");
    }
    
    $module_count = 0;
    $content_modules_list = array();
    $user_modules_list = array();
    $search_modules_list = array();
    if ($object_connections->isConnected()) {
        $caching_active = true;
        foreach ($connected_modules as $key => $connection) {
            if (ELearningUtils::isCMSActive($connection["cms"])) {

                ELearningUtils::loadClass($connection["cms"]);

                $connected_cms[$connection["cms"]]->newContentModule($connection["id"], $connection["type"], true);
                $connected_modules[$key]['title'] = $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->getTitle();
                $title_tmp[$key] = str_replace(array('ä','ö','ü','ß'),array('ae','oe','ue','ss'),strtolower($connected_modules[$key]['title']));
                $type_tmp[$key] = array_search($connection['type'], array_keys($ELEARNING_INTERFACE_MODULES[$connection["cms"]]['types']));
                $class_tmp[$key] = $ELEARNING_INTERFACE_MODULES[$connection["cms"]]["CLASS_PREFIX"];
            }
        }
        array_multisort($class_tmp, SORT_ASC, $type_tmp, SORT_ASC, $title_tmp, SORT_ASC, $connected_modules);

        foreach ($connected_modules as $index => $connection) {
            $current_module = $connection["id"]; //Arrrghhhh

            if ($module_count == 0)
                $content_modules_list[$index]['show_header'] = true;
            $module_count++;
            $module_system_count[$connection["cms"]]++;

                if ($open_all != "")
                    $_SESSION['elearning_open_close'][$connected_cms[$connection["cms"]]->content_module[$connection["id"]]->getReferenceString()] = true;
                elseif ($close_all != "")
                    $_SESSION['elearning_open_close'][$connected_cms[$connection["cms"]]->content_module[$connection["id"]]->getReferenceString()] = false;
                // USE_CASE 1: show connected contentmodules
                if ($view == "show") {
                    $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->view->setChangeDate($connection["chdate"]);
                    $content_modules_list[$index]['module'] = $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->view->show();
                }
                // USE_CASE 2: edit contentmodule connections
                elseif ($view == "edit") {
                    $content_modules_list[$index]['module'] = $connected_cms[$connection["cms"]]->content_module[$connection["id"]]->view->showAdmin();
                }
                ELearningUtils::bench("module");
        }
    }

    if (($module_count == 0) AND ($new_account_cms == "")) {
        if ($SessSemName["class"]=='inst') {
            PageLayout::postMessage(MessageBox::info(_('Momentan sind dieser Einrichtung keine Lernmodule zugeordnet.')));
        } else {
            PageLayout::postMessage(MessageBox::info(_('Momentan sind dieser Veranstaltung keine Lernmodule zugeordnet.')));
        }
    }
    
    $caching_active = false;
    if ($view == "edit") {
        if (isset($ELEARNING_INTERFACE_MODULES[$cms_select]["name"])) {
            ELearningUtils::loadClass($cms_select);

            $user_content_modules = $connected_cms[$cms_select]->getUserContentModules();
            if (! ($user_content_modules == false)) {
                foreach ($user_content_modules as $key => $connection) {
                    // show only those modules which are not already connected to the seminar
                    if (is_object($connected_cms[$cms_select]->content_module[$connection["ref_id"]]))
                        continue;
                    $connected_cms[$cms_select]->setContentModule($connection, false);
                    $connected_cms[$cms_select]->content_module[$current_module]->view->showAdmin();
                    $user_modules_list[$key]['module'] = $connected_cms[$cms_select]->content_module[$current_module]->view->showAdmin();
                }
            }
            ELearningUtils::bench("user modules");

            if (!$connected_cms[$cms_select]->isAuthNecessary()
                || $connected_cms[$cms_select]->user->isConnected()) {
                $show_search = true;
            }

            if (! ($searchresult_content_modules == false))
            {
                foreach ($searchresult_content_modules as $key => $connection)
                {
                    // show only those modules which are not already connected to the seminar
                    if (is_object($connected_cms[$cms_select]->content_module[$connection["ref_id"]]))
                        continue;
                    $connected_cms[$cms_select]->setContentModule($connection, false);
                    $search_modules_list[$key]['module'] = $connected_cms[$cms_select]->content_module[$current_module]->view->showAdmin();
                }
            }

            // ILIAS 4: Leeren Kurs anlegen oder Kurse von anderen Veranstaltungen zuordnen
            if ((method_exists($connected_cms[$cms_select], "updateConnections")) AND ! ($module_system_count[$cms_select]) AND ! (ObjectConnections::getConnectionModuleId($SessSemName[1], "crs", $cms_select)))
            {
                $show_ilias_empty_course = true;
                if ($perm->have_perm('root')) {
                    $query = "SELECT DISTINCT object_id, module_id, Name
                              FROM object_contentmodules
                              LEFT JOIN seminare ON (object_id = Seminar_id)
                              WHERE module_type = 'crs' AND system_type = ?";
                } else {
                    $query = "SELECT DISTINCT object_id, module_id, Name
                              FROM object_contentmodules
                              LEFT JOIN seminare ON (object_id = Seminar_id)
                              LEFT JOIN seminar_user USING (Seminar_id)
                              WHERE module_type = 'crs' AND system_type = ? AND seminar_user.status = 'dozent'";
                }
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($cms_select));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    if ($perm->have_studip_perm('dozent', $row['object_id'])) {
                        $existing_courses[$row['object_id']] = my_substr($row['Name'],0,60)." ".sprintf(_("(Kurs-ID %s)"), $row['module_id']); 
                    }
                }
            }

            // ILIAS 4: ggf. Hinweis auf Möglichkeit, weitere Modulformen als Link einzubinden
            elseif (method_exists($connected_cms[$cms_select], "updateConnections") AND count($connected_cms[$cms_select]->types['webr'])) {
                $show_ilias_link_info = true;
                $crs_data = ObjectConnections::getConnectionModuleId($SessSemName[1], "crs", $cms_select);
            }
        }
        ELearningUtils::bench("search");
    }

    // Cachen der SOAP-Daten
    if (is_array($connected_cms))
        foreach($connected_cms as $system)
            $system->terminate();

    if ($debug != "") {
        ELearningUtils::showbench();
    }
    if ($view=="edit") {
        // help texts for help center -> to be put into db!
        $help_text[] = _('Hier können Sie Lernmodule für die Veranstaltung einhängen und aushängen. Das Laden dieser Seite kann etwas länger dauern, da Daten zwischen Stud.IP und den angebundenen Systemen ausgetauscht werden.');
        $help_text[] = _('Wählen Sie das System, aus dem Sie ein Modul einhängen wollen. Anschließend können Sie nach Modulen suchen. Gefundene Module können Sie mit dem Button \"hinzufügen\" der Veranstaltung zuordnen.');
        $help_text[] = _('Um neue Lernmodule zu erstellen, wechseln Sie auf die Seite "Meine Lernmodule", auf der Sie Ihre Lernmodule und externen Nutzer-Accounts verwalten können.');
    } else {
        // help texts for help center -> to be put into db!
        $help_text[] = _('Hier sehen Sie die Lernmodule, die an diese Veranstaltung angehängt wurden.');
        $help_text[] = _('Wenn Sie in einem Lernmodul auf "Starten" klicken, öffnet sich ein neues Fenster mit dem Lernmodul.');
        $help_text[] = _('Um neue Lernmodule zu erstellen, wechseln Sie auf die Seite "Meine Lernmodule", auf der Sie Ihre Lernmodule und externen Nutzer-Accounts verwalten können.');
    }
} else {
    PageLayout::postMessage(MessageBox::error(_("Die Schnittstelle für die Integration von Lernmodulen ist nicht aktiviert. Damit Lernmodule verwendet werden können, muss die Verbindung zu einem LCM-System in der Konfigurationsdatei von Stud.IP hergestellt werden. Wenden Sie sich bitte an den/die AdministratorIn.")));
}

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/learnmodule-sidebar.png');

$widget = new ActionsWidget();
$widget->addLink(_('Externe Accounts verwalten'), URLHelper::getURL('my_elearning.php'), 'icons/16/black/person.png');
if ($GLOBALS['perm']->have_perm('autor')) {
    if ($GLOBALS['perm']->have_perm('tutor') AND ($view=='edit')) {
        if (count($course_output['courses'])) {
            $widget->addLink(_('Zuordnungen aktualisieren'), URLHelper::getURL('?view='.$view.'&cms_select='.$cms_select.'&update=1'), 'icons/16/black/refresh.png');
        }
    }
}
$sidebar->addWidget($widget);

$template->set_layout('layouts/base');
$template->set_attribute('elearning_active', $elearning_active);
$template->set_attribute('new_account', $new_account_cms);
$template->set_attribute('course_output', $course_output);
$template->set_attribute('view', $view);
$template->set_attribute('is_inst', ($SessSemName['class']=='inst'));
if ($cms_select) {
    $template->set_attribute('cms_name', $connected_cms[$cms_select]->getName());
    $template->set_attribute('cms_logo', $connected_cms[$cms_select]->getLogo());
    $template->set_attribute('cms_select', $cms_select);
    $template->set_attribute('show_search', $show_search);
    $template->set_attribute('show_ilias_empty_course', $show_ilias_empty_course);
    $template->set_attribute('show_ilias_link_info', $show_ilias_link_info);
    $template->set_attribute('user_modules', $user_modules_list);
    $template->set_attribute('search_key', $search_key);
    $template->set_attribute('search_modules', $search_modules_list);
    $template->set_attribute('existing_courses', $existing_courses);
}
$template->set_attribute('anker_target', $anker_target);
$template->set_attribute('content_modules', $content_modules_list);
echo $template->render();

page_close();        