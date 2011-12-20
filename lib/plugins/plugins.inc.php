<?php
# Lifter010: TODO
/*
 * plugins.inc.php - plugin API for Stud.IP
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// helper classes (deprecated)
require_once 'core/Permission.class.php';
require_once 'core/StudIPInstitute.class.php';
require_once 'core/StudIPUser.class.php';
require_once 'core/StudIPCore.class.php';

// plugin base class
require_once 'core/StudIPPlugin.class.php';

// plugin interfaces
require_once 'core/AdministrationPlugin.class.php';
require_once 'core/HomepagePlugin.class.php';
require_once 'core/PortalPlugin.class.php';
require_once 'core/StandardPlugin.class.php';
require_once 'core/StudienmodulManagementPlugin.class.php';
require_once 'core/SystemPlugin.class.php';
require_once 'core/WebServicePlugin.class.php';

// old navigation classes (deprecated)
require_once 'core/StudipPluginNavigation.class.php';
require_once 'core/PluginNavigation.class.php';

// old plugin base classes (deprecated)
require_once 'core/AbstractStudIPLegacyPlugin.class.php';
require_once 'core/AbstractStudIPAdministrationPlugin.class.php';
require_once 'core/AbstractStudIPCorePlugin.class.php';
require_once 'core/AbstractStudIPHomepagePlugin.class.php';
require_once 'core/AbstractStudIPPortalPlugin.class.php';
require_once 'core/AbstractStudIPStandardPlugin.class.php';
require_once 'core/AbstractStudIPSystemPlugin.class.php';

// core plugin API
require_once 'core/Role.class.php';
require_once 'db/RolePersistence.class.php';
require_once 'engine/PluginEngine.class.php';

// old plugin API (deprecated)
require_once 'engine/StudIPTemplateEngine.class.php';
