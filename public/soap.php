<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * soap.php - SOAP Backend for Stud.IP web services
 *
 * Copyright (C) 2006 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


# set include path
$include_path = ini_get('include_path');
$include_path .= PATH_SEPARATOR . dirname(__FILE__) . '/..';
ini_set('include_path', $include_path);

# requiring nusoap
require_once 'vendor/nusoap/nusoap.php';
require_once 'vendor/nusoap/class.delegating_soap_server.php';
require_once 'vendor/nusoap/class.soap_server_delegate.php';


# requiring soap_server_delegate
require_once 'vendor/studip_ws/studip_ws.php';
require_once 'vendor/studip_ws/soap_dispatcher.php';

# requiring all the webservices
require_once 'lib/webservices/services/user_webservice.php';
require_once 'lib/webservices/services/session_webservice.php';
require_once 'lib/webservices/services/contentmodule_webservice.php';
require_once 'lib/webservices/services/seminar_webservice.php';
require_once 'lib/webservices/services/lecture_tree_webservice.php';
require_once 'lib/webservices/services/institute_webservice.php';

if (empty($GLOBALS['STUDIP_API_KEY'])
        || ! $GLOBALS['WEBSERVICES_ENABLE'])
{
    die("Webservices not available");
}

$delegate = new Studip_Ws_SoapDispatcher('UserService', 'SessionService', 'SeminarService', 'ContentmoduleService', 'LectureTreeService', 'InstituteService');
$server   = new DelegatingSoapServer($delegate);

# creating WSDL
$namespace = 'urn:studip_wsd';
$server->configureWSDL('Stud.IP Webservice', $namespace);
$server->wsdl->schemaTargetNamespace = $namespace;

# register operations
$delegate->register_operations($server);

# start server
$server->service(isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '');
