<?php
# Lifter010: TODO
/*
 * Copyright (c) 2009  Stud.IP CoreGroup
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

namespace Studip {
    const ENV = 'development';
}
// use default namespace for the remaining lines
namespace {
    //software version - please leave it as it is!
    $SOFTWARE_VERSION = '3.3.alpha-svn';

    global $PHP_SELF, $STUDIP_BASE_PATH;

    $PHP_SELF = $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $STUDIP_BASE_PATH = realpath(dirname(__FILE__) . '/..');

    set_include_path(
        $STUDIP_BASE_PATH
        . PATH_SEPARATOR . $STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'config'
        . PATH_SEPARATOR . get_include_path()
    );
    !ini_get('register_globals') OR require 'templates/register_globals_on.php';

    require 'lib/classes/StudipAutoloader.php';
    StudipAutoloader::register();
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'models');
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes');
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes', 'Studip');
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'exceptions');
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'exportdocument');
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'sidebar');
    StudipAutoloader::addAutoloadPath($STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'helpbar');

// sample the request time every tenth time
    register_shutdown_function(function ($timer) {
        $timer('core.request_time', 0.1);
    }, Metrics::startTimer());

    require 'lib/phplib/DB_Sql.class.php';
    require 'lib/phplib/CT_Sql.class.php';
    require 'lib/phplib/CT_Cache.class.php';
    require 'lib/phplib/Seminar_Session.class.php';
    require 'lib/phplib/Seminar_User.class.php';
    require 'lib/phplib/Seminar_Perm.class.php';
    require 'lib/phplib/Seminar_Auth.class.php';
    require 'lib/phplib/Seminar_Default_Auth.class.php';
    require 'lib/phplib/Seminar_Register_Auth.class.php';
    require 'lib/phplib/email_validation.class.php';
    require 'lib/phplib/page_open.php';

    StudipFileloader::load('config_local.inc.php', $GLOBALS, compact('STUDIP_BASE_PATH'));

    require 'config.inc.php';

    require_once 'lib/functions.php';
    require_once 'lib/visual.inc.php';
    require_once 'lib/deputies_functions.inc.php';
    require_once 'lib/classes/auth_plugins/StudipAuthAbstract.class.php';
    require_once 'lib/navigation/Navigation.php';
    require_once 'lib/navigation/AutoNavigation.php';

//setup default logger
    Log::get()->setHandler($GLOBALS['TMP_PATH'] . '/studip.log');
    if (Studip\ENV == 'development') {
        Log::get()->setLogLevel(Log::DEBUG);
    } else {
        Log::get()->setLogLevel(Log::ERROR);
    }

// set default time zone
    date_default_timezone_set($DEFAULT_TIMEZONE ? : @date_default_timezone_get());

// set assets url
    Assets::set_assets_url($GLOBALS['ASSETS_URL']);

// globale template factory anlegen
    require_once 'vendor/flexi/lib/flexi.php';
    $GLOBALS['template_factory'] =
        new Flexi_TemplateFactory($STUDIP_BASE_PATH . '/templates');

// set default exception handler
// command line or http request?
    if (isset($_SERVER['REQUEST_METHOD'])) {
        set_exception_handler('studip_default_exception_handler');
    }

// set default pdo connection
    DBManager::getInstance()
        ->setConnection('studip',
            'mysql:host=' . $GLOBALS['DB_STUDIP_HOST'] .
            ';dbname=' . $GLOBALS['DB_STUDIP_DATABASE'],
            $GLOBALS['DB_STUDIP_USER'],
            $GLOBALS['DB_STUDIP_PASSWORD']);

// set slave connection
    if (isset($GLOBALS['DB_STUDIP_SLAVE_HOST'])) {
        try {
            DBManager::getInstance()
                ->setConnection('studip-slave',
                    'mysql:host=' . $GLOBALS['DB_STUDIP_SLAVE_HOST'] .
                    ';dbname=' . $GLOBALS['DB_STUDIP_SLAVE_DATABASE'],
                    $GLOBALS['DB_STUDIP_SLAVE_USER'],
                    $GLOBALS['DB_STUDIP_SLAVE_PASSWORD']);
        } catch (PDOException $exception) {
            // if connection to slave fails, fall back to master instead
            DBManager::getInstance()->aliasConnection('studip', 'studip-slave');
        }
    } else {
        DBManager::getInstance()->aliasConnection('studip', 'studip-slave');
    }
    //include 'tools/debug/StudipDebugPDO.class.php';
    /**
     * @deprecated
     */
    class DB_Seminar extends DB_Sql
    {
        function DB_Seminar($query = false)
        {
            $this->Host = $GLOBALS['DB_STUDIP_HOST'];
            $this->Database = $GLOBALS['DB_STUDIP_DATABASE'];
            $this->User = $GLOBALS['DB_STUDIP_USER'];
            $this->Password = $GLOBALS['DB_STUDIP_PASSWORD'];
            parent::DB_Sql($query);
        }
    }

// set dummy navigation until db is ready
    Navigation::setRootNavigation(new Navigation(''));

// set up default page layout
    PageLayout::initialize();

//Besser hier globale Variablen definieren...
    $GLOBALS['_fullname_sql'] = array();
    $GLOBALS['_fullname_sql']['full'] = "TRIM(CONCAT(title_front,' ',Vorname,' ',Nachname,IF(title_rear!='',CONCAT(', ',title_rear),'')))";
    $GLOBALS['_fullname_sql']['full_rev'] = "TRIM(CONCAT(Nachname,', ',Vorname,IF(title_front!='',CONCAT(', ',title_front),''),IF(title_rear!='',CONCAT(', ',title_rear),'')))";
    $GLOBALS['_fullname_sql']['no_title'] = "CONCAT(Vorname ,' ', Nachname)";
    $GLOBALS['_fullname_sql']['no_title_rev'] = "CONCAT(Nachname ,', ', Vorname)";
    $GLOBALS['_fullname_sql']['no_title_short'] = "CONCAT(Nachname,', ',UCASE(LEFT(TRIM(Vorname),1)),'.')";
    $GLOBALS['_fullname_sql']['no_title_motto'] = "CONCAT(Vorname ,' ', Nachname,IF(motto!='',CONCAT(', ',motto),''))";
    $GLOBALS['_fullname_sql']['full_rev_username'] = "TRIM(CONCAT(Nachname,', ',Vorname,IF(title_front!='',CONCAT(', ',title_front),''),IF(title_rear!='',CONCAT(', ',title_rear),''),' (',username,')'))";

//Initialize $SEM_TYPE and $SEM_CLASS arrays
    require_once 'lib/classes/SemClass.class.php';
    require_once 'lib/classes/SemType.class.php';
    $GLOBALS['SEM_CLASS'] = SemClass::getClasses();
    $GLOBALS['SEM_TYPE'] = SemType::getTypes();

// set up global navigation
    require_once 'lib/navigation/StudipNavigation.php';
    Navigation::setRootNavigation(new StudipNavigation(''));

    /*class for config; load config in globals (should be deprecated in future)
    ----------------------------------------------------------------*/
    Config::GetInstance()->extractAllGlobal(false);

    /* set default umask to a sane value */
    umask(022);

    /*mail settings
    ----------------------------------------------------------------*/
    if ($GLOBALS['MAIL_TRANSPORT']) {
        $mail_transporter_name = strtolower($GLOBALS['MAIL_TRANSPORT']) . '_message';
    } else {
        $mail_transporter_name = 'smtp_message';
    }
    include 'vendor/email_message/email_message.php';
    include 'vendor/email_message/' . $mail_transporter_name . '.php';
    $mail_transporter_class = $mail_transporter_name . '_class';
    $mail_transporter = new $mail_transporter_class;
    if ($mail_transporter_name == 'smtp_message') {
        include 'vendor/email_message/smtp.php';
        $mail_transporter->localhost = ($GLOBALS['MAIL_LOCALHOST'] == "") ? $_SERVER["SERVER_NAME"] : $GLOBALS['MAIL_LOCALHOST'];
        $mail_transporter->smtp_host = ($GLOBALS['MAIL_HOST_NAME'] == "") ? $_SERVER["SERVER_NAME"] : $GLOBALS['MAIL_HOST_NAME'];
        if (is_array($MAIL_SMTP_OPTIONS)) {
            foreach ($MAIL_SMTP_OPTIONS as $key => $value) {
                $mail_transporter->{"smtp_$key"} = $value;
            }
            if ($mail_transporter->smtp_user !== '') {
                include 'vendor/sasl/sasl.php';
            }
        }
    }
    $mail_transporter->default_charset = 'WINDOWS-1252';
    $mail_transporter->SetBulkMail((int)$GLOBALS['MAIL_BULK_DELIVERY']);
    StudipMail::setDefaultTransporter($mail_transporter);
    unset($mail_transporter);

    require 'lib/plugins/plugins.inc.php';
    
    // Set default observer
    NotificationCenter::addObserver("WidgetHelper", "setInitialPositionsNotification", 'UserDidCreate');

// Development: Use own directory for file related classes
    if (Config::get()->PERSONALDOCUMENT_ENABLE) {
        require_once 'lib/files/FileStorage.php';
        require_once 'lib/files/DirectoryEntry.php';
        require_once 'lib/files/DiskFileStorage.php';
        require_once 'lib/files/File.php';
        require_once 'lib/files/StudipDirectory.php';
        require_once 'lib/files/RootDirectory.php';
        require_once 'lib/files/FileHelper.php';
    }

}
