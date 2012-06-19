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
            $this->setImage('header/seminar.png', array('title' => $courseinfo));
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

        $object_type = $SessSemName['class'];
        if ($object_type === "inst") {
            $this->initInstSubNavigation();
            return;
        }
        
        // list of used modules
        $Modules = new Modules();
        $modules = $Modules->getLocalModules($_SESSION['SessionSeminar']);
        $sem_class = $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']];

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
        if ($user->id != 'nobody' && ($modules['participants'] || $sem_class->isSlotMandatory("participants"))) {
            foreach ($sem_class->getNavigationForSlot("participants") as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
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
    
    protected function initInstSubNavigation() {
        global $SessSemName, $forum, $perm, $user;

        // list of used modules
        $Modules = new Modules();
        $modules = $Modules->getLocalModules($SessSemName[1]);
        
        // general information
        $navigation = new Navigation(_('Übersicht'));
        $navigation->setImage('icons/16/white/seminar.png');
        $navigation->setActiveImage('icons/16/black/seminar.png');
            $navigation->addSubNavigation('info', new Navigation(_('Info'), 'institut_main.php'));
            $navigation->addSubNavigation('courses', new Navigation(_('Veranstaltungen'), 'show_bereich.php?level=s&id='.$SessSemName[1]));
            $navigation->addSubNavigation('schedule', new Navigation(_('Veranstaltungs-Stundenplan'), 'dispatch.php/calendar/instschedule?cid='.$SessSemName[1]));

            if ($perm->have_studip_perm('tutor', $SessSemName[1]) && $perm->have_perm('admin')) {
                $navigation->addSubNavigation('admin', new Navigation(_('Administration der Einrichtung'), 'admin_institut.php?new_inst=TRUE'));
            }
        $this->addSubNavigation('main', $navigation);

        // admin area
        $navigation = new Navigation(_('Verwaltung'));
        $navigation->setImage('icons/16/white/admin.png');
        $navigation->setActiveImage('icons/16/black/admin.png');
        if ($perm->have_studip_perm('tutor', $SessSemName[1]) && !$perm->have_perm('admin')) {
            $main = new Navigation(_('Verwaltung'), 'dispatch.php/course/management');
            $navigation->addSubNavigation('main', $main);
                }
        if ($perm->have_studip_perm('tutor', $SessSemName[1]) && !$perm->have_perm('admin')) {
            $item = new Navigation(_('Ankündigungen'), 'admin_news.php?view=news_' . $sem_class);
            $item->setDescription(_('Erstellen Sie Ankündigungen und bearbeiten Sie laufende Ankündigungen.'));
            $navigation->addSubNavigation('news', $item);

            if (get_config('VOTE_ENABLE')) {
                $item = new Navigation(_('Umfragen und Tests'), 'admin_vote.php?view=vote_' . $sem_class);
                $item->setDescription(_('Erstellen und bearbeiten Sie einfache Umfragen und Tests.'));
                $navigation->addSubNavigation('vote', $item);

                $item = new Navigation(_('Evaluationen'), 'admin_evaluation.php?view=eval_' . $sem_class);
                $item->setDescription(_('Richten Sie fragebogenbasierte Umfragen und Lehrevaluationen ein.'));
                $navigation->addSubNavigation('evaluation', $item);
            }
        }
        $this->addSubNavigation('admin', $navigation);
        
        // forum
        if ($modules['forum']) {
            $core_module = new CoreForum();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
            }
            }

        // participants
        if ($user->id != 'nobody') {
            if ($modules['personal']) {
                $navigation = new Navigation(_('Personal'));
                $navigation->setImage('icons/16/white/persons.png');
                $navigation->setActiveImage('icons/16/black/persons.png');
                $navigation->addSubNavigation('view', new Navigation(_('MitarbeiterInnen'), 'inst_admin.php'));

                if ($perm->have_studip_perm('tutor', $SessSemName[1]) && $perm->have_perm('admin')) {
                    $navigation->addSubNavigation('edit_groups', new Navigation(_('Funktionen / Gruppen verwalten'), 'admin_roles.php?new_sem=TRUE&range_id='. $SessSemName[1]));
                }

                $this->addSubNavigation('faculty', $navigation);
            }
        }

        // files
        if ($modules['documents']) {
            $core_module = new CoreDocuments();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
        }
        }

        // schedule
        if ($modules['schedule']) {
            $core_module = new CoreSchedule();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
            }
        }

        // information page
        if (get_config('SCM_ENABLE') && $modules['scm']) {
            $core_module = new CoreScm();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
            }
            }

        // literature
        if (get_config('LITERATURE_ENABLE') && $modules['literature']) {
            $core_module = new CoreLiterature();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
            }
        }

        // wiki
        if (get_config('WIKI_ENABLE') && $modules['wiki']) {
            $core_module = new CoreWiki();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
        }
        }

        // resources
        if (get_config('RESOURCES_ENABLE')) {
            $core_module = new CoreResources();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
            }
        }

        // calendar
        if (get_config('CALENDAR_GROUP_ENABLE') && get_config('COURSE_CALENDAR_ENABLE') && $modules['calendar']) {
            $core_module = new CoreCalendar();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
        }
        }

        // content modules
        if (get_config('ELEARNING_INTERFACE_ENABLE') && $modules['elearning_interface'] && $user->id != 'nobody') {
            $core_module = new CoreElearningInterface();
            $navigations = $core_module->getTabNavigation($_SESSION['SessionSeminar']);
            foreach ((array) $navigations as $name => $navigation) {
                $this->addSubNavigation($name, $navigation);
                }
            }
        
        //plugins
        $standard_plugins = PluginManager::getInstance()->getPlugins("StandardPlugin", $_SESSION['SessionSeminar']);
        foreach ($standard_plugins as $plugin) {
            foreach ($plugin->getTabNavigation($_SESSION['SessionSeminar']) as $nav_name => $navigation) {
                if ($nav_name && is_a($navigation, "Navigation")) {
                    $this->addSubNavigation($nav_name, $navigation);
            }
        }
    }
    }
}
