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

        PageLayout::setTitle(_("Mehr Funktionen"));

        $id = $GLOBALS['SessionSeminar'];
        if (!$id) {
            if ($GLOBALS['perm']->have_perm('admin')) {
                Navigation::activateItem('/admin/institute/modules');
                require_once 'lib/admin_search.inc.php';
            } else {
                throw new AccessDeniedException(_("Keine Berechtigung."));
            }
        }

        $object_type = get_object_type($id);

        if ($object_type !== "sem") {
            Navigation::activateItem('/admin/institute/modules');
        } else {
            Navigation::activateItem('/course/modules');
        }

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

        if (Request::submitted('deleteContent')) $this->deleteContent($this->available_modules);
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
            PageLayout::postMessage(MessageBox::info(sprintf(_("Sie beabsichtigen die Inhalte von %s zu löschen."), $displayname)
                . "<br>" . _("Wollen Sie die Inhalte wirklich löschen?") . "<br>"
                . LinkButton::createAccept(_('Ja'), URLHelper::getURL("?deleteContent=true&check=true&name=" . $name))
                . LinkButton::createCancel(_('Nein'))));
        }
    }


    private function setupSidebar()
    {

        $plusconfig = UserConfig::get($GLOBALS['user']->id)->PLUS_SETTINGS;

        if (!isset($_SESSION['plus'])) {
            if (isset($plusconfig['course_plus'])){
                $usr_conf = $plusconfig['course_plus'];

                $_SESSION['plus']['Kategorie']['Lehr- und Lernorganisation'] = $usr_conf['Kategorie']['Lehr- und Lernorganisation'];
                $_SESSION['plus']['Kategorie']['Kommunikation und Zusammenarbeit'] = $usr_conf['Kategorie']['Kommunikation und Zusammenarbeit'];
                $_SESSION['plus']['Kategorie']['Inhalte und Aufgabenstellungen'] = $usr_conf['Kategorie']['Inhalte und Aufgabenstellungen'];
                $_SESSION['plus']['Kategorie']['Sonstiges'] = $usr_conf['Kategorie']['Sonstiges'];

                foreach ($usr_conf['Kategorie'] as $key => $val){
                    if(!array_key_exists($key, $_SESSION['plus']['Kategorie'])){
                        $_SESSION['plus']['Kategorie'][$key] = $val;
                    }
                }

                $_SESSION['plus']['View'] = $usr_conf['View'];
                $_SESSION['plus']['displaystyle'] = $usr_conf['displaystyle'];

            } else {
                $_SESSION['plus']['Kategorie']['Lehr- und Lernorganisation'] = 1;
                $_SESSION['plus']['Kategorie']['Kommunikation und Zusammenarbeit'] = 1;
                $_SESSION['plus']['Kategorie']['Inhalte und Aufgabenstellungen'] = 1;
                $_SESSION['plus']['Kategorie']['Sonstiges'] = 1;
                $_SESSION['plus']['View'] = 'openall';
                $_SESSION['plus']['displaystyle'] = 'category';
            }
        }

        if(isset($_SESSION['plus']['Kategorielist'])){
            foreach ($_SESSION['plus']['Kategorie'] as $key => $val){
                if(!array_key_exists($key, $_SESSION['plus']['Kategorielist']) && $key != 'Sonstiges'){
                    unset($_SESSION['plus']['Kategorie'][$key]);
                }
            }
        }
        if (Request::Get('mode') != null) $_SESSION['plus']['View'] = Request::Get('mode');
        if (Request::Get('displaystyle') != null) $_SESSION['plus']['displaystyle'] = Request::Get('displaystyle');

        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/plugin-sidebar.png');


        $widget = new OptionsWidget();
        $widget->setTitle(_('Kategorien'));

        foreach ($_SESSION['plus']['Kategorie'] as $key => $val) {

            if (Request::Get(md5('cat_' . $key)) != null) $_SESSION['plus']['Kategorie'][$key] = Request::Get(md5('cat_' . $key));

            if ($_SESSION['plus']['displaystyle'] == 'alphabetical') {
                $_SESSION['plus']['Kategorie'][$key] = 1;
            }

            if ($key == 'Sonstiges') continue;
            $widget->addCheckbox(_($key), $_SESSION['plus']['Kategorie'][$key],
                URLHelper::getLink('?', array(md5('cat_' . $key) => 1, 'displaystyle' => 'category')), URLHelper::getLink('?', array(md5('cat_' . $key) => 0, 'displaystyle' => 'category')));

        }

        $widget->addCheckbox(_('Sonstiges'), $_SESSION['plus']['Kategorie']['Sonstiges'],
            URLHelper::getLink('?', array(md5('cat_Sonstiges') => 1, 'displaystyle' => 'category')), URLHelper::getLink('?', array(md5('cat_Sonstiges') => 0, 'displaystyle' => 'category')));

        $sidebar->addWidget($widget, "Kategorien");

        $widget = new ActionsWidget();
        $widget->setTitle(_('Ansichten'));

        if ($_SESSION['plus']['View'] == 'openall') {
            $widget->addLink(_("Alles zuklappen"),
                URLHelper::getLink('?', array('mode' => 'closeall')),
                'icons/16/blue/assessment.png');
        } else {
            $widget->addLink(_("Alles aufklappen"),
                URLHelper::getLink('?', array('mode' => 'openall')),
                'icons/16/blue/assessment.png');
        }

        if ($_SESSION['plus']['displaystyle'] == 'category') {
            $widget->addLink(_("Alphabetische Anzeige ohne Kategorien"),
                    URLHelper::getLink('?', array('displaystyle' => 'alphabetical')),
                    'icons/16/blue/assessment.png');
        } else {
            $widget->addLink(_("Anzeige nach Kategorien"),
                    URLHelper::getLink('?', array('displaystyle' => 'category')),
                    'icons/16/blue/assessment.png');
        }

        $sidebar->addWidget($widget, "aktion");

        unset($_SESSION['plus']['Kategorielist']);
        $plusconfig['course_plus'] = $_SESSION['plus'];
        UserConfig::get($GLOBALS['user']->id)->store(PLUS_SETTINGS,$plusconfig);
    }


    private function getSortedList()
    {

        $list = array();
        $cat_index = array();

        foreach (PluginEngine::getPlugins('StandardPlugin') as $plugin) {

            if ((!$this->sem_class && !$plugin->isCorePlugin())
                || ($this->sem_class && !$this->sem_class->isModuleMandatory($plugin->getPluginname())
                    && $this->sem_class->isModuleAllowed($plugin->getPluginname())
                    && !$this->sem_class->isSlotModule(get_class($plugin)))
            ) {

                $info = $plugin->getMetadata();

                $indcat = isset($info['category']) ? $info['category'] : 'Sonstiges';
                if(!array_key_exists($indcat, $cat_index)) array_push($cat_index, $indcat);

                if($_SESSION['plus']['displaystyle'] != 'category'){

                    $key = isset($info['displayname']) ? $info['displayname'] : $plugin->getPluginname();


                    $list['Funktionen von A-Z'][strtolower($key)]['object'] = $plugin;
                    $list['Funktionen von A-Z'][strtolower($key)]['type'] = 'plugin';

                } else {

                    $cat = isset($info['category']) ? $info['category'] : 'Sonstiges';

                    if (!isset($_SESSION['plus']['Kategorie'][$cat])) $_SESSION['plus']['Kategorie'][$cat] = 1;

                    $key = isset($info['displayname']) ? $info['displayname'] : $plugin->getPluginname();

                    if ($_SESSION['plus']['Kategorie'][$cat]
                        || !isset($_SESSION['plus'])
                    ) {

                        $list[$cat][strtolower($key)]['object'] = $plugin;
                        $list[$cat][strtolower($key)]['type'] = 'plugin';
                    }
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

                $indcat = isset($info['category']) ? $info['category'] : 'Sonstiges';
                if(!array_key_exists($indcat, $cat_index)) array_push($cat_index, $indcat);

                if($_SESSION['plus']['displaystyle'] != 'category'){

                    $list['Funktionen von A-Z'][strtolower($val['name'])]['object'] = $val;
                    $list['Funktionen von A-Z'][strtolower($val['name'])]['type'] = 'modul';
                    $list['Funktionen von A-Z'][strtolower($val['name'])]['modulkey'] = $key;

                } else {

                    $cat = isset($info['category']) ? $info['category'] : 'Sonstiges';

                    if (!isset($_SESSION['plus']['Kategorie'][$cat])) $_SESSION['plus']['Kategorie'][$cat] = 1;

                    if ($_SESSION['plus']['Kategorie'][$cat]
                        || !isset($_SESSION['plus'])
                    ) {

                        $list[$cat][strtolower($val['name'])]['object'] = $val;
                        $list[$cat][strtolower($val['name'])]['type'] = 'modul';
                        $list[$cat][strtolower($val['name'])]['modulkey'] = $key;
                    }
                }
            }
        }

        $sortedcats['Lehr- und Lernorganisation'] = array();
        $sortedcats['Kommunikation und Zusammenarbeit'] = array();
        $sortedcats['Inhalte und Aufgabenstellungen'] = array();

        foreach ($list as $cat_key => $cat_val) {
            ksort($cat_val);
            $list[$cat_key] = $cat_val;
            if ($cat_key != 'Sonstiges') $sortedcats[$cat_key] = $list[$cat_key];
        }

        if (isset($list['Sonstiges'])) $sortedcats['Sonstiges'] = $list['Sonstiges'];

        $_SESSION['plus']['Kategorielist'] = array_flip($cat_index);

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
