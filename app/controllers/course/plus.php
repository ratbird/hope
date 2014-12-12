<?php
# Lifter001: TODO
/*
 * Copyright (C) 2012 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once 'app/controllers/authenticated_controller.php';

use Studip\Button, Studip\LinkButton;

class Course_PlusController extends AuthenticatedController
{

    public function index_action($range_id = null)
    {

        PageLayout::addSqueezePackage('lightbox');

        PageLayout::setTitle(_("Verwaltung verwendeter Inhaltselemente und Plugins"));

        $GLOBALS['view_mode'] = $_SESSION['links_admin_data']['topkat'] ? : 'sem';
        require_once 'lib/admin_search.inc.php';
        $id = $range_id ? : $_SESSION['SessionSeminar'];

        if ($GLOBALS['perm']->have_perm('admin')) {
            if ($GLOBALS['view_mode'] == 'sem') {
                Navigation::activateItem('/admin/course/modules');
            } else {
                Navigation::activateItem('/admin/institute/modules');
            }
        } else {
            Navigation::activateItem('/course/modules');
        }

        if (!$id) {
            if ($GLOBALS['perm']->have_perm('admin')) {

                include 'lib/include/admin_search_form.inc.php'; // will not return
                die(); //must not return
            } else {
                throw new Trails_Exception(400);
            }
        }

        $object_type = get_object_type($id);

        if (!$GLOBALS['perm']->have_studip_perm($object_type === 'sem' ? 'tutor' : 'admin', $id)) {
            throw new AccessDeniedException(_("Keine Berechtigung."));
        }

        if ($object_type === "sem") {
            $this->sem = Course::find($id);
            $this->sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$this->sem->status]['class']];
        } else {
            $this->sem = Institute::find($id);
        }

        PageLayout::setTitle($this->sem->getFullname() . " - " . PageLayout::getTitle());

        $this->save();

        $this->modules = new AdminModules();
        $this->registered_modules = $this->modules->registered_modules;

        if (!Request::submitted('uebernehmen')) {
            $_SESSION['admin_modules_data']["modules_list"] = $this->modules->getLocalModules($id);
            $_SESSION['admin_modules_data']["orig_bin"] = $this->modules->getBin($id);
            $_SESSION['admin_modules_data']["changed_bin"] = $this->modules->getBin($id);
            $_SESSION['admin_modules_data']["range_id"] = $id;
            $_SESSION['admin_modules_data']["conflicts"] = array();
            $_SESSION['plugin_toggle'] = array();
        }

        require_once 'lib/resources/resourcesFunc.inc.php';
        if (!checkAvailableResources($id)) {
            unset($this->registered_modules['resources']);
        }

        $this->setupSidebar();
        $this->available_modules = $this->getSortedList();

        if (Request::submitted('deleteContent')) $this->deleteContent($plugmodlist);
    }


    private function deleteContent($plugmodlist)
    {
        $name = Request::Get('name');

        foreach ($plugmodlist as $key => $val) {
            if (array_key_exists($name, $val)) {
                if ($val[$name]['type'] == 'plugin') {
                    $class = PluginEngine::getPlugin(get_class($val[$name]['object']));
                    $displayname = $class->getPluginName();
                } elseif ($val[$name]['type'] == 'modul') {
                    if ($this->sem_class) {
                        $class = $this->sem_class->getModule($this->sem_class->getSlotModule($val[$name]['modulkey']));
                        $displayname = $val[$name]['object']['name'];
                    }
                }
            }
        }

        if (Request::submitted('check')) {
            if (method_exists($class, 'deleteContent')) {
                $class->deleteContent();
            } else {
                PageLayout::postMessage(MessageBox::info(_("Das Plugin/Modul enthält keine Funktion zum Löschen der Inhalte.")));
            }
        } else {
            PageLayout::postMessage(MessageBox::info(_("Sie beabsichtigen die Inhalte von " . $displayname . " zu löschen.")
                . "<br>" . _("Wollen Sie die Inhalte wirklich löschen?") . "<br>"
                . LinkButton::createAccept(_('Ja'), URLHelper::getURL("?deleteContent=true&check=true&name=" . $name))
                . LinkButton::createCancel(_('Nein'))));
        }
    }


    private function setupSidebar()
    {

        if (!isset($_SESSION['plus'])) {
            $_SESSION['plus']['Kategorie']['Lehrorganisation'] = 1;
            $_SESSION['plus']['Kategorie']['Kommunikation und Zusammenarbeit'] = 1;
            $_SESSION['plus']['Kategorie']['Aufgaben'] = 1;
            $_SESSION['plus']['Kategorie']['Sonstiges'] = 1;
            /*$_SESSION['plus']['Kategorie']['Projekte und Entwicklung'] = 1;*/
            $_SESSION['plus']['Komplex'][1] = 1;
            $_SESSION['plus']['Komplex'][2] = 1;
            $_SESSION['plus']['Komplex'][3] = 1;
            $_SESSION['plus']['View'] = 'openall';
        }

        if (Request::Get('Komplex1') != null) $_SESSION['plus']['Komplex'][1] = Request::Get('Komplex1');
        if (Request::Get('Komplex2') != null) $_SESSION['plus']['Komplex'][2] = Request::Get('Komplex2');
        if (Request::Get('Komplex3') != null) $_SESSION['plus']['Komplex'][3] = Request::Get('Komplex3');
        if (Request::Get('mode') != null) $_SESSION['plus']['View'] = Request::Get('mode');

        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/plugin-sidebar.png');


        $widget = new OptionsWidget();
        $widget->setTitle(_('Kategorie Links'));

        foreach ($_SESSION['plus']['Kategorie'] as $key => $val) {

            if (Request::Get(md5('cat_' . $key)) != null) $_SESSION['plus']['Kategorie'][$key] = Request::Get(md5('cat_' . $key));

            if ($key == 'Sonstiges') continue;
            $widget->addCheckbox(_($key), $_SESSION['plus']['Kategorie'][$key],
                URLHelper::getLink('?', array(md5('cat_' . $key) => 1)), URLHelper::getLink('?', array(md5('cat_' . $key) => 0)));

        }

        $widget->addCheckbox(_('Sonstiges'), $_SESSION['plus']['Kategorie']['Sonstiges'],
            URLHelper::getLink('?', array(md5('cat_Sonstiges') => 1)), URLHelper::getLink('?', array(md5('cat_Sonstiges') => 0)));

        $sidebar->addWidget($widget, "Kategorien");


        $widget = new OptionsWidget();
        $widget->setTitle(_('Komplexität'));
        $widget->addCheckbox(_('Standard'), $_SESSION['plus']['Komplex'][1],
            URLHelper::getLink('?', array('Komplex1' => 1)), URLHelper::getLink('?', array('Komplex1' => 0)));
        $widget->addCheckbox(_('Erweitert'), $_SESSION['plus']['Komplex'][2],
            URLHelper::getLink('?', array('Komplex2' => 1)), URLHelper::getLink('?', array('Komplex2' => 0)));
        $widget->addCheckbox(_('Intensiv'), $_SESSION['plus']['Komplex'][3],
            URLHelper::getLink('?', array('Komplex3' => 1)), URLHelper::getLink('?', array('Komplex3' => 0)));
        $sidebar->addWidget($widget, "Komplex");


        $widget = new ActionsWidget();

        if ($_SESSION['plus']['View'] == 'openall') {
            $widget->addLink(_("Alles Zuklappen"),
                URLHelper::getLink('?', array('mode' => 'closeall')),
                'icons/16/blue/assessment.png');
        } else {
            $widget->addLink(_("Alles Aufklappen"),
                URLHelper::getLink('?', array('mode' => 'openall')),
                'icons/16/blue/assessment.png');
        }
        $sidebar->addWidget($widget, "aktion");

    }


    private function getSortedList()
    {

        $list = array();

        foreach (PluginEngine::getPlugins('StandardPlugin') as $plugin) {

            if ((!$this->sem_class && !$plugin->isCorePlugin())
                || ($this->sem_class && !$this->sem_class->isModuleMandatory($plugin->getPluginname())
                    && $this->sem_class->isModuleAllowed($plugin->getPluginname())
                    && !$this->sem_class->isSlotModule(get_class($plugin)))
            ) {

                $info = $plugin->getMetadata();

                $cat = isset($info['category']) ? $info['category'] : 'Sonstiges';

                if (!isset($_SESSION['plus']['Kategorie'][$cat])) $_SESSION['plus']['Kategorie'][$cat] = 1;

                $key = isset($info['displayname']) ? $info['displayname'] : $plugin->getPluginname();

                if ($_SESSION['plus']['Kategorie'][$cat]
                    && ($_SESSION['plus']['Komplex'][$info['complexity']] || !isset($info['complexity']))
                    || !isset($_SESSION['plus'])
                ) {

                    $list[$cat][strtolower($key)]['object'] = $plugin;
                    $list[$cat][strtolower($key)]['type'] = 'plugin';
                }
            }
        }

        foreach ($this->registered_modules as $key => $val) {

            if ($this->sem_class) {
                $mod = $this->sem_class->getSlotModule($key);
                $slot_editable = $mod && $this->sem_class->isModuleAllowed($mod) && !$this->sem_class->isModuleMandatory($mod);
            }

            if ($this->modules->isEnableable($key, $_SESSION['admin_modules_data']["range_id"]) && (!$this->sem_class || $slot_editable)) {

                if ($this->sem_class) $studip_module = $this->sem_class->getModule($mod);

                $info = ($studip_module instanceOf StudipModule) ? $studip_module->getMetadata() : ($val['metadata'] ? $val['metadata'] : array());

                $cat = isset($info['category']) ? $info['category'] : 'Sonstiges';

                if (!isset($_SESSION['plus']['Kategorie'][$cat])) $_SESSION['plus']['Kategorie'][$cat] = 1;

                if ($_SESSION['plus']['Kategorie'][$cat]
                    && ($_SESSION['plus']['Komplex'][$info['complexity']] || !isset($info['complexity']))
                    || !isset($_SESSION['plus'])
                ) {

                    $list[$cat][strtolower($val['name'])]['object'] = $val;
                    $list[$cat][strtolower($val['name'])]['type'] = 'modul';
                    $list[$cat][strtolower($val['name'])]['modulkey'] = $key;
                }
            }
        }

        $sortedcats['Lehrorganisation'] = array();
        $sortedcats['Kommunikation und Zusammenarbeit'] = array();
        $sortedcats['Aufgaben'] = array();

        foreach ($list as $cat_key => $cat_val) {
            ksort($cat_val);
            $list[$cat_key] = $cat_val;
            if ($cat_key != 'Sonstiges') $sortedcats[$cat_key] = $list[$cat_key];
        }

        if (isset($list['Sonstiges'])) $sortedcats['Sonstiges'] = $list['Sonstiges'];

        return $sortedcats;
    }


    protected function save()
    {
        $seminar_id = $_SESSION['admin_modules_data']['range_id'];
        $modules = new AdminModules();
        $plugins = PluginEngine::getPlugins('StandardPlugin');
        //consistency: kill objects
        foreach ($modules->registered_modules as $key => $val) {
            $moduleXxDeactivate = "module" . $key . "Deactivate";
            if ((Request::option('delete_' . $key) == 'TRUE')) {
                if (method_exists($modules, $moduleXxDeactivate)) {
                    $modules->$moduleXxDeactivate($seminar_id);
                    if ($this->sem_class) {
                        $studip_module = $this->sem_class->getModule($key);
                        if (is_a($studip_module, "StandardPlugin")) {
                            PluginManager::getInstance()->setPluginActivated(
                                $studip_module->getPluginId(),
                                $seminar_id,
                                false
                            );
                        }
                    }
                }
                $modules->clearBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                $resolve_conflicts = true;
            }
        }

        //consistency: cancel kill objects
        foreach ($modules->registered_modules as $key => $val) {
            if (Request::option('cancel_' . $key) == 'TRUE') {
                $modules->setBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                $resolve_conflicts = true;
            }
        }

        if (Request::submitted('uebernehmen') || Request::get('retry')) {
            if (Request::submitted('uebernehmen')) {
                foreach ($modules->registered_modules as $key => $val) {
                    //after sending, set all "conflicts" to TRUE (we check them later)
                    $_SESSION['admin_modules_data']["conflicts"][$key] = true;

                    if ($this->sem_class) $studip_module = $this->sem_class->getModule($key);
                    $info = ($studip_module instanceOf StudipModule) ? $studip_module->getMetadata() : ($val['metadata'] ? $val['metadata'] : array());
                    $info ["category"] = $info ["category"] ? : 'Sonstiges';

                    if (!isset($_SESSION['plus']) || $_SESSION['plus']['Kategorie'][$info ["category"]]) {

                        if (Request::option($key . '_value') == "TRUE") {
                            $modules->setBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                        } else {
                            $modules->clearBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                        }

                    }

                    if ($this->sem_class) {
                        $studip_module = $this->sem_class->getModule($key);
                        if (is_a($studip_module, "StandardPlugin")) {
                            PluginManager::getInstance()->setPluginActivated(
                                $studip_module->getPluginId(),
                                $seminar_id,
                                Request::option($key . '_value') == "TRUE"
                            );
                        }
                    } else {
                        // check, if the passed module is represented by a core-plugin
                        if (strtolower(get_parent_class('core' . $key)) == 'studipplugin') {
                            $plugin = PluginEngine::getPlugin('core' . $key);
                            #var_dump($plugin->getPluginId(), $seminar_id, Request::option($key . '_value') == "TRUE");die;
                            PluginManager::getInstance()->setPluginActivated(
                                $plugin->getPluginId(),
                                $seminar_id,
                                Request::option($key . '_value') == "TRUE"
                            );
                        }
                    }
                }
                // Setzen der Plugins
                foreach ($plugins as $plugin) {
                    if ((!$this->sem_class && !$plugin->isCorePlugin()) || ($this->sem_class && !$this->sem_class->isSlotModule(get_class($plugin)))) {

                        $check = ($_POST["plugin_" . $plugin->getPluginId()] == "TRUE");
                        $setting = $plugin->isActivated($seminar_id);

                        $info = $plugin->getMetadata();
                        if (isset($_SESSION['plus']) && !$_SESSION['plus']['Kategorie'][$info ["category"]]) {
                            $check = $setting;
                        }

                        if ($check != $setting) {
                            array_push($_SESSION['plugin_toggle'], $plugin->getPluginId());
                        }
                    }
                }
            }

            //consistency checks
            foreach ($modules->registered_modules as $key => $val) {
                $delete_xx = "delete_" . $key;
                $cancel_xx = "cancel_" . $key;

                //checks for deactivating a module
                $getModuleXxExistingItems = "getModule" . $key . "ExistingItems";

                if (method_exists($modules, $getModuleXxExistingItems)) {
                    if (($modules->isBit($_SESSION['admin_modules_data']["orig_bin"], $modules->registered_modules[$key]["id"])) &&
                        (!$modules->isBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"])) &&
                        ($modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"])) &&
                        ($_SESSION['admin_modules_data']["conflicts"][$key])
                    ) {

                        $msg = $modules->registered_modules[$key]["msg_warning"];
                        $msg .= "<br>";
                        $msg .= LinkButton::createAccept(_('Ja'), URLHelper::getURL("?delete_$key=TRUE&retry=TRUE"));
                        $msg .= "&nbsp; \n";
                        $msg .= LinkButton::createCancel(_('NEIN!'), URLHelper::getURL("?cancel_$key=TRUE&retry=TRUE"));
                        PageLayout::postMessage(MessageBox::info($msg));
                    } else {
                        unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                    }
                } else {
                    unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                }

                //checks for activating a module
                $moduleXxActivate = "module" . $key . "Activate";

                if (method_exists($modules, $moduleXxActivate)) {
                    if ((!$modules->isBit($_SESSION['admin_modules_data']["orig_bin"], $modules->registered_modules[$key]["id"])) &&
                        ($modules->isBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]))
                    ) {

                        $modules->$moduleXxActivate($seminar_id);
                        if ($this->sem_class) {
                            $studip_module = $this->sem_class->getModule($key);
                            if (is_a($studip_module, "StandardPlugin")) {
                                PluginManager::getInstance()->setPluginActivated(
                                    $studip_module->getPluginId(),
                                    $seminar_id,
                                    true
                                );
                            }
                        }
                    }
                }

            }
        }
        if (!count($_SESSION['admin_modules_data']["conflicts"])) {
            $changes = false;
            // Inhaltselemente speichern
            if ($_SESSION['admin_modules_data']["orig_bin"] != $_SESSION['admin_modules_data']["changed_bin"]) {
                $modules->writeBin($_SESSION['admin_modules_data']["range_id"], $_SESSION['admin_modules_data']["changed_bin"]);
                $_SESSION['admin_modules_data']["orig_bin"] = $_SESSION['admin_modules_data']["changed_bin"];
                $_SESSION['admin_modules_data']["modules_list"] = $modules->getLocalModules($_SESSION['admin_modules_data']["range_id"]);
                $changes = true;
            }
            // Plugins speichern
            if (count($_SESSION['plugin_toggle']) > 0) {
                $plugin_manager = PluginManager::getInstance();

                foreach ($plugins as $plugin) {
                    $plugin_id = $plugin->getPluginId();

                    if (in_array($plugin_id, $_SESSION['plugin_toggle'])) {
                        $activated = !$plugin_manager->isPluginActivated($plugin_id, $seminar_id);
                        $plugin_manager->setPluginActivated($plugin_id, $seminar_id, $activated);
                        $changes = true;
                        // logging
                        if ($activated) {
                            log_event('PLUGIN_ENABLE', $seminar_id, $plugin_id, $GLOBALS['user']->id);
                        } else {
                            log_event('PLUGIN_DISABLE', $seminar_id, $plugin_id, $GLOBALS['user']->id);
                        }
                    }
                }
                $_SESSION['plugin_toggle'] = array();
            }
            if ($changes) {
                PageLayout::postMessage(MessageBox::success(_('Die veränderte Konfiguration wurde übernommen.')));
                $this->redirect('course/plus/index/' . $seminar_id);
            }
        }
    }
}
