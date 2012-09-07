<?php
# Lifter010: TODO
/*
 * CourseNavigation.php - navigation for course / institute area
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/functions.php';
require_once 'lib/classes/Modules.class.php';
require_once 'lib/classes/StudipScmEntry.class.php';
require_once 'lib/classes/LockRules.class.php';
require_once 'lib/classes/AuxLockRules.class.php';
require_once 'lib/classes/AutoInsert.class.php';

require_once 'lib/modules/CoreAdmin.class.php';
require_once 'lib/modules/CoreStudygroupAdmin.class.php';
require_once 'lib/modules/CoreOverview.class.php';
require_once 'lib/modules/CoreForum.class.php';
require_once 'lib/modules/CoreParticipants.class.php';
require_once 'lib/modules/CoreStudygroupParticipants.class.php';
require_once 'lib/modules/CoreDocuments.class.php';
require_once 'lib/modules/CoreSchedule.class.php';
require_once 'lib/modules/CoreScm.class.php';
require_once 'lib/modules/CoreLiterature.class.php';
require_once 'lib/modules/CoreWiki.class.php';
require_once 'lib/modules/CoreResources.class.php';
require_once 'lib/modules/CoreCalendar.class.php';
require_once 'lib/modules/CoreElearningInterface.class.php';

if (get_config('ELEARNING_INTERFACE_ENABLE')) {
    require_once $GLOBALS['RELATIVE_PATH_ELEARNING_INTERFACE'].'/ObjectConnections.class.php';
}

if (get_config('RESOURCES_ENABLE')) {
    require_once $GLOBALS['RELATIVE_PATH_RESOURCES'].'/resourcesFunc.inc.php';
}

class CourseNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $perm;

        // check if logged in
        if (is_object($user) && $user->id != 'nobody') {
            $coursetext = _('Veranstaltungen');
            $courseinfo = _('Meine Veranstaltungen & Einrichtungen');
            $courselink = 'meine_seminare.php';
        } else {
            $coursetext = _('Freie');
            $courseinfo = _('Freie Veranstaltungen');
            $courselink = 'freie.php';
        }

        parent::__construct($coursetext, $courselink);

        if (is_object($user) && !$perm->have_perm('root')) {
            $this->setImage('header/seminar.png', array('title' => $courseinfo, "@2x" => TRUE));
        }
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $SEM_CLASS, $SEM_TYPE;
        global $SessSemName, $user;

        parent::initSubNavigation();

        /*$object_type = $SessSemName['class'];
        */
        
        // list of used modules
        $Modules = new Modules();
        $modules = $Modules->getLocalModules($_SESSION['SessionSeminar']);
        $sem_class = $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']];
        if (!$sem_class || $SessSemName['class'] == "inst") {
            $sem_class = SemClass::getDefaultSemClass();
        }

        // general information
        if ($modules['overview'] || $sem_class->isSlotMandatory("overview")) {
            foreach ($sem_class->getNavigationForSlot("overview") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }

        // admin area
        if ($modules['admin'] || $sem_class->isSlotMandatory("admin")) {
            foreach ($sem_class->getNavigationForSlot("admin") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }

        // forum
        if ($modules['forum'] || $sem_class->isSlotMandatory("forum")) {
            foreach ($sem_class->getNavigationForSlot("forum") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
        }
            }
        }

        // participants
        if ($user->id != 'nobody') {
            if ($modules['personal']) {
                $navigation = new Navigation(_('Personal'));
                $navigation->setImage('icons/16/white/persons.png');
                $navigation->setActiveImage('icons/16/black/persons.png');
                $navigation->addSubNavigation('view', new Navigation(_('MitarbeiterInnen'), 'inst_admin.php'));

                if ($GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar']) && $GLOBALS['perm']->have_perm('admin')) {
                    $navigation->addSubNavigation('edit_groups', new Navigation(_('Funktionen / Gruppen verwalten'), 'admin_roles.php?new_sem=TRUE&range_id='. $SessSemName[1]));
                }

                $this->addSubNavigation('faculty', $navigation);
            }
            if ($modules['participants'] || $sem_class->isSlotMandatory("participants")) {
                foreach ($sem_class->getNavigationForSlot("participants") as $nav_name => $navigation) {
                    if ($nav_name && is_a($navigation, "Navigation")) {
                        $this->addSubNavigation($nav_name, $navigation);
                    }
                }
            }
        }

        // files
        if ($modules['documents'] || $sem_class->isSlotMandatory("documents")) {
            foreach ($sem_class->getNavigationForSlot("documents") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }

        // schedule
        if ($modules['schedule'] || $sem_class->isSlotMandatory("schedule")) {
            foreach ($sem_class->getNavigationForSlot("schedule") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }

        // information page
        if (get_config('SCM_ENABLE') && ($modules['scm'] || $sem_class->isSlotMandatory("scm"))) {
            foreach ($sem_class->getNavigationForSlot("scm") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
            }
            }
        }

        // literature
        if (get_config('LITERATURE_ENABLE') && ($modules['literature'] || $sem_class->isSlotMandatory("literature"))) {
            foreach ($sem_class->getNavigationForSlot("literature") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
            }
            }
        }

        // wiki
        if (get_config('WIKI_ENABLE') && ($modules['wiki'] || $sem_class->isSlotMandatory("wiki"))) {
            foreach ($sem_class->getNavigationForSlot("wiki") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
            }
            }
        }

        // resources
        if (get_config('RESOURCES_ENABLE')) {
            foreach ($sem_class->getNavigationForSlot("resources") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }

        // calendar
        if (get_config('CALENDAR_GROUP_ENABLE') && get_config('COURSE_CALENDAR_ENABLE') 
                && ($modules['calendar'] || $sem_class->isSlotMandatory("calendar") )) {
            foreach ($sem_class->getNavigationForSlot("calendar") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }

        // content modules
        if (get_config('ELEARNING_INTERFACE_ENABLE') && $user->id != 'nobody'
                && ($modules['elearning_interface'] || $sem_class->isSlotMandatory("elearning_interface"))) {
            foreach ($sem_class->getNavigationForSlot("elearning_interface") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
                }
            }
        }
        
        //plugins
        $standard_plugins = PluginManager::getInstance()->getPlugins("StandardPlugin", $_SESSION['SessionSeminar']);
        foreach ($standard_plugins as $plugin) {
            if (!$sem_class->isSlotModule(get_class($plugin))) {
                foreach ($sem_class->getNavigationForSlot(get_class($plugin)) as $nav_name => $navigation) {
                    if ($nav_name && is_a($navigation, "Navigation")) {
                        $this->addSubNavigation($nav_name, $navigation);
                    }
                }
            }
        }
        
    }
    
}
