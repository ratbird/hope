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
require_once 'app/models/studygroup.php';
require_once 'lib/classes/AdminModules.class.php';

use Studip\Button, Studip\LinkButton;

class Course_PlusController extends AuthenticatedController
{

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // user must have tutor permission
        if (!$_SESSION['SessionSeminar']
            && !$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff.");
        }
    }

    public function index_action()
    {
        if ($GLOBALS['perm']->have_perm('admin')) {
            if ($_SESSION['links_admin_data']['topkat'] == 'sem') {
                Navigation::activateItem('/admin/course/modules');
            } else {
                Navigation::activateItem('/admin/institute/modules');
            }
        } else {
            Navigation::activateItem('/course/modules');
        }
        
        $this->save();
        
        $object_type = get_object_type($_SESSION['SessionSeminar']);
        if ($object_type === "sem") {
            $this->sem           = new Seminar($_SESSION['SessionSeminar']);
            $this->sem_class     = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$this->sem->status]['class']];
        }
        $this->available_modules = StudygroupModel::getInstalledModules();
        $this->available_plugins = PluginEngine::getPlugins('StandardPlugin');
        $this->modules           = new AdminModules();
        $this->save_url          = "?";
        
        if (!Request::submitted('uebernehmen') && (!$resolve_conflicts)) {
            $_SESSION['admin_modules_data']["modules_list"] = $this->modules->getLocalModules($_SESSION['SessionSeminar']);
            $_SESSION['admin_modules_data']["orig_bin"] = $this->modules->getBin($_SESSION['SessionSeminar']);
            $_SESSION['admin_modules_data']["changed_bin"] = $this->modules->getBin($_SESSION['SessionSeminar']);
            $_SESSION['admin_modules_data']["range_id"] = $_SESSION['SessionSeminar'];
            $_SESSION['admin_modules_data']["conflicts"] = array();
            $_SESSION['plugin_toggle'] = array();
        }

        if (isset($_SESSION['admin_modules_data']['msg'])) {
            $this->msg = $_SESSION['admin_modules_data']['msg'];
            unset($_SESSION['admin_modules_data']['msg']);
        }
    }
    
    protected function save() 
    {
        $modules = new AdminModules();
        $plugins = PluginEngine::getPlugins('StandardPlugin');
        //consistency: kill objects
        foreach ($modules->registered_modules as $key => $val) {
            $moduleXxDeactivate = "module".$key."Deactivate";
            if ((Request::option('delete_'.$key)=='TRUE')) {
                if (method_exists($modules,$moduleXxDeactivate)) {
                    $modules->$moduleXxDeactivate($_SESSION['admin_modules_data']["range_id"]);
                }
                $modules->clearBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                $resolve_conflicts = TRUE;
            }
        }
        //consistency: cancel kill objects
        foreach ($modules->registered_modules as $key => $val) {
            if (Request::option('cancel_'.$key)=='TRUE') {
                $modules->setBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                $resolve_conflicts = TRUE;
            }
        }

        if (Request::submitted('uebernehmen') || Request::get('retry')) {
            $msg='';
            if (Request::submitted('uebernehmen')) {
                foreach ($modules->registered_modules as $key => $val) {
                    //after sending, set all "conflicts" to TRUE (we check them later)
                    $_SESSION['admin_modules_data']["conflicts"][$key] = TRUE;

                    if (Request::option($key.'_value') == "TRUE") {
                        $modules->setBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                    } else {
                        $modules->clearBit($_SESSION['admin_modules_data']["changed_bin"], $modules->registered_modules[$key]["id"]);
                    }
                }
                // Setzen der Plugins
                foreach ($plugins as $plugin) {
                    $check = ( $_POST[ "plugin_" . $plugin->getPluginId() ] == "TRUE" );
                    $setting = $plugin->isActivated($_SESSION['admin_modules_data']['range_id']);
                    if( $check != $setting ){
                        array_push( $_SESSION['plugin_toggle'] , $plugin->getPluginId() );
                    }

                }
            }

            //consistency checks
            foreach ($modules->registered_modules as $key => $val) {
                $delete_xx = "delete_".$key;
                $cancel_xx = "cancel_".$key;

                //checks for deactivating a module
                $getModuleXxExistingItems = "getModule".$key."ExistingItems";

                if (method_exists($modules,$getModuleXxExistingItems)) {
                    if (($modules->isBit($_SESSION['admin_modules_data']["orig_bin"],  $modules->registered_modules[$key]["id"])) &&
                        (!$modules->isBit($_SESSION['admin_modules_data']["changed_bin"],  $modules->registered_modules[$key]["id"])) &&
                        ($modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"])) &&
                        ($_SESSION['admin_modules_data']["conflicts"][$key])) {

                        $msg.="info§".$modules->registered_modules[$key]["msg_warning"];
                        $msg.="<br>";
                        $msg.=LinkButton::createAccept(_('Ja'), URLHelper::getURL("?delete_$key=TRUE&retry=TRUE"));
                        $msg.="&nbsp; \n";
                        $msg.=LinkButton::createCancel(_('NEIN!'), URLHelper::getURL("?cancel_$key=TRUE&retry=TRUE"));
                        $msg.="\n§";
                    } else {
                        unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                    }
                } else {
                    unset($_SESSION['admin_modules_data']["conflicts"][$key]);
                }

                //checks for activating a module
                $moduleXxActivate = "module".$key."Activate";

                if (method_exists($modules,$moduleXxActivate)) {
                    if ((!$modules->isBit($_SESSION['admin_modules_data']["orig_bin"],  $modules->registered_modules[$key]["id"])) &&
                        ($modules->isBit($_SESSION['admin_modules_data']["changed_bin"],  $modules->registered_modules[$key]["id"]))) {

                        $modules->$moduleXxActivate($_SESSION['admin_modules_data']["range_id"]);
                    }
                }

            }
            if ($msg) {
                $this->msg = $msg;
            }
        }
        if( !count( $_SESSION['admin_modules_data']["conflicts"] ) )  {
            $changes = false;
            // Inhaltselemente speichern
            if( $_SESSION['admin_modules_data']["orig_bin"] != $_SESSION['admin_modules_data']["changed_bin"] ){
                $modules->writeBin($_SESSION['admin_modules_data']["range_id"], $_SESSION['admin_modules_data']["changed_bin"]);
                $_SESSION['admin_modules_data']["orig_bin"] = $_SESSION['admin_modules_data']["changed_bin"];
                $_SESSION['admin_modules_data']["modules_list"] = $modules->getLocalModules($_SESSION['admin_modules_data']["range_id"]);
                $changes = true;
            }
            // Plugins speichern
            if( count( $_SESSION['plugin_toggle'] ) > 0 ){
                $context = $_SESSION['admin_modules_data']['range_id'];
                $plugin_manager = PluginManager::getInstance();

                foreach ($plugins as $plugin){
                    $plugin_id = $plugin->getPluginId();

                    if( in_array( $plugin_id , $_SESSION['plugin_toggle'] ) ){
                        $activated = !$plugin_manager->isPluginActivated($plugin_id, $context);
                        $plugin_manager->setPluginActivated($plugin_id, $context, $activated);
                        $changes = true;
                        // logging
                        if ($activated) {
                            log_event('PLUGIN_ENABLE',$context,$plugin_id, $user->id);
                        }
                        else {
                            log_event('PLUGIN_DISABLE',$context,$plugin_id, $user->id);
                        }
                    }
                }
                $_SESSION['plugin_toggle'] = array();
            }
            if( $changes ){
                $_SESSION['admin_modules_data']['msg'] = 'msg§'._('Die veränderte Konfiguration wurde übernommen.');
                header('Location: ' . URLHelper::getURL());
                page_close();
                die();
            }
        }
    }


    
}
