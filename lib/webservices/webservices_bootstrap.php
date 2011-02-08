<?php

# requiring nusoap
require_once 'vendor/nusoap/nusoap.php';
require_once 'vendor/nusoap/class.delegating_soap_server.php';
require_once 'vendor/nusoap/class.soap_server_delegate.php';


# requiring soap_server_delegate
require_once 'vendor/studip_ws/studip_ws.php';
require_once 'vendor/studip_ws/soap_dispatcher.php';

# requiring phpxmlrpc
require_once 'vendor/phpxmlrpc/xmlrpc.inc';
require_once 'vendor/phpxmlrpc/xmlrpcs.inc';
require_once 'vendor/phpxmlrpc/docxmlrpcs.inc';
require_once 'vendor/phpxmlrpc/jsonrpc.inc';
require_once 'vendor/phpxmlrpc/jsonrpcs.inc';

# requiring xmlrpc_dispatcher
require_once 'vendor/studip_ws/studip_ws.php';
require_once 'vendor/studip_ws/xmlrpc_dispatcher.php';

# requiring jsonrpc_dispatcher
require_once 'vendor/studip_ws/studip_ws.php';
require_once 'vendor/studip_ws/jsonrpc_dispatcher.php';

# requiring all the webservices
require_once 'lib/webservices/services/access_controlled_webservice.php';
require_once 'lib/webservices/services/user_webservice.php';
require_once 'lib/webservices/services/session_webservice.php';
require_once 'lib/webservices/services/contentmodule_webservice.php';
require_once 'lib/webservices/services/seminar_webservice.php';
require_once 'lib/webservices/services/lecture_tree_webservice.php';
require_once 'lib/webservices/services/institute_webservice.php';

$AVAILABLE_SERVICES = array('UserService', 'SessionService', 'SeminarService', 'ContentmoduleService', 'LectureTreeService', 'InstituteService');

if (!get_config('WEBSERVICES_ENABLE'))
{
    throw new Exception("Webservices not available");
}