<?php
/*basic settings for Stud.IP
----------------------------------------------------------------
you find here the basic system settings. You shouldn't have to touch much of them...
please note the CONFIG.INC.PHP in the system folder for the indivual settings of your installation!*/

$UNI_NAME_CLEAN = "Stud.IP Entwicklungs- und Anwendungsforum";                          //the clean-name of your master-faculty (e.g. University of G�ttingen), without html-entities (used for mail-system)
$STUDIP_INSTALLATION_ID = 'develop';      //unique identifier for installation


/*settings for database access
----------------------------------------------------------------
please fill in your database connection settings.
*/

// default Stud.IP database (DB_Seminar)
$DB_STUDIP_HOST = "localhost";
$DB_STUDIP_USER = "";
$DB_STUDIP_PASSWORD = "";
$DB_STUDIP_DATABASE = "studip";
@include "dbpass.inc";
/*
// optional Stud.IP slave database
$DB_STUDIP_SLAVE_HOST = "localhost";
$DB_STUDIP_SLAVE_USER = "";
$DB_STUDIP_SLAVE_PASSWORD = "";
$DB_STUDIP_SLAVE_DATABASE = "studip-slave";
*/

#####    ##   ##### #    #  ####
#    #  #  #    #   #    # #
#    # #    #   #   ######  ####
#####  ######   #   #    #      #
#      #    #   #   #    # #    #
#      #    #   #   #    #  ####


// ABSOLUTE_PATH_STUDIP should end with a '/'
$ABSOLUTE_PATH_STUDIP = $STUDIP_BASE_PATH . '/public/';


// CANONICAL_RELATIVE_PATH_STUDIP should end with a '/'
$CANONICAL_RELATIVE_PATH_STUDIP = dirname($_SERVER['PHP_SELF']);
if (DIRECTORY_SEPARATOR != '/') {
    $CANONICAL_RELATIVE_PATH_STUDIP = str_replace(DIRECTORY_SEPARATOR, '/', $CANONICAL_RELATIVE_PATH_STUDIP);
}

if (substr($CANONICAL_RELATIVE_PATH_STUDIP,-1) != "/"){
    $CANONICAL_RELATIVE_PATH_STUDIP .= "/";
}

// ABSOLUTE_URI_STUDIP: insert the absolute URL to your Stud.IP installation; it should end with a '/'
$ABSOLUTE_URI_STUDIP = "http://develop.studip.de/studip/";

// automagically compute ABSOLUTE_URI_STUDIP if $_SERVER['SERVER_NAME'] is set
if (isset($_SERVER['SERVER_NAME'])) {
    // work around possible bug in lighttpd
    if (strpos($_SERVER['SERVER_NAME'], ':') !== false) {
        list($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']) =
            explode(':', $_SERVER['SERVER_NAME']);
    }

    $ABSOLUTE_URI_STUDIP = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    $ABSOLUTE_URI_STUDIP .= '://'.$_SERVER['SERVER_NAME'];

    if ($_SERVER['HTTPS'] == 'on' && $_SERVER['SERVER_PORT'] != 443 ||
        $_SERVER['HTTPS'] != 'on' && $_SERVER['SERVER_PORT'] != 80) {
        $ABSOLUTE_URI_STUDIP .= ':'.$_SERVER['SERVER_PORT'];
    }

    $ABSOLUTE_URI_STUDIP .= $CANONICAL_RELATIVE_PATH_STUDIP;
}

// default ASSETS_URL, customize if required
$ASSETS_URL = $ABSOLUTE_URI_STUDIP . 'assets/';

// construct absolute URL for ASSETS_URL
if ($ASSETS_URL[0] === '/') {
    $host = preg_replace('%^([a-z]+:/*[^/]*).*%', '$1', $ABSOLUTE_URI_STUDIP);
    $ASSETS_URL = $host . $ASSETS_URL;
} else if (!preg_match('/^[a-z]+:/', $ASSETS_URL)) {
    $ASSETS_URL = $ABSOLUTE_URI_STUDIP . $ASSETS_URL;
}


// absolute filesystem path to the plugin packages
$PLUGINS_PATH = $ABSOLUTE_PATH_STUDIP . 'plugins_packages';


// path to uploaded documents (wwwrun needs write-perm there)
$UPLOAD_PATH = $STUDIP_BASE_PATH . "/data/upload_doc";
// path to Stud.IP archive (wwwrun needs write-perm there)
$ARCHIV_PATH = $STUDIP_BASE_PATH . "/data/archiv";
//path to store configs (wwwrun needs write-perm there)
$EXTERN_CONFIG_FILE_PATH =  $STUDIP_BASE_PATH . "/data/extern_config/";


// path and url for dynamically generated static content like smilies..
$DYNAMIC_CONTENT_PATH = $ABSOLUTE_PATH_STUDIP . "pictures";
$DYNAMIC_CONTENT_URL  = $ABSOLUTE_URI_STUDIP  . "pictures";


//path to the temporary folder
$TMP_PATH ="/tmp/studip";                                   //the system temp path

//paths to the command line tools, used in Stud.IP
$ZIP_USE_INTERNAL = false;                              //set to true, if command-line zip/unzip is not available
$ZIP_PATH = "/usr/bin/zip";                             //zip tool
$ZIP_OPTIONS = "-K";                                    //command line options for zip, e.g. when using SuSE try "-K" to correct long filenames for windows
$UNZIP_PATH = "/usr/bin/unzip";

// media proxy settings
$MEDIA_CACHE_PATH = $STUDIP_BASE_PATH . '/data/media_cache';
$MEDIA_CACHE_MAX_LENGTH = 1000000;
$MEDIA_CACHE_LIFETIME = 86400;
$MEDIA_CACHE_MAX_FILES = 3000;

//path to Stud.IP modules (this folders only have to exist, if the corresponcing module is active)
$RELATIVE_PATH_RESOURCES = "lib/resources";                         //Stud.IP module: resourge management
$RELATIVE_PATH_CALENDAR = "lib/calendar";                           //Stud.IP module: calendar
$RELATIVE_PATH_ADMIN_MODULES = "lib/admin";                         //Stud.IP module: admin tools
$RELATIVE_PATH_EXTERN = "lib/extern";                           //Stud.IP module: SRI-System for including Stud.IP data in other websites
$RELATIVE_PATH_ELEARNING_INTERFACE = "lib/elearning";                   //Stud.IP module: Ilias 3 lerningmodules-connection / general E-Learning-interface
$RELATIVE_PATH_SOAP = "lib/soap";

$PATH_EXPORT = "lib/export";                                //Stud.IP module: export

//caching
$CACHING_ENABLE = true;
$CACHING_FILECACHE_PATH = $TMP_PATH . '/studip_cache';

/*Stud.IP modules
----------------------------------------------------------------
enable or disable the Stud.IP internal modules, set and basic settings*/

$CALENDAR_DRIVER = "MySQL";                                 //calendar driver: database to use (MySQL in default installation)*/

$XSLT_ENABLE = TRUE;
$FOP_ENABLE = TRUE;
$FOP_SH_CALL = "JAVACMD=/usr/bin/java /opt/fop-0.20.5/fop.sh";                       //path to fop

$EXTERN_SERVER_NAME = "";                               //define name, if you use special setup
$EXTERN_SRI_ENABLE = TRUE;                              //allow the usage of SRI-interface (Stud.IP Remote Include)
$EXTERN_SRI_ENABLE_BY_ROOT = FALSE;                         //only root allows the usage of SRI-interface for specific institutes
$EXTERN_ALLOW_ACCESS_WITHOUT_CONFIG = FALSE;                        //free access to external pages (without the need of a configuration), independent of SRI settings above

$SOAP_ENABLE = TRUE;
$SOAP_USE_PHP5 = TRUE;

$WEBSERVICES_ENABLE = TRUE;

$PLUGINS_UPLOAD_ENABLE = TRUE;                  //Upload of Plugins is enabled

$PLUGIN_REPOSITORIES = array(
    'http://plugins.studip.de/plugin-wiki.php'
);

/*system functions
----------------------------------------------------------------
activate or deactivate some basic system-functions here*/

$SMILEY_COUNTER = TRUE;                             //enable Smiley-counter

/*domain name and path translation
----------------------------------------------------------------
to translate internal links (within Stud.IP) to the different
domain names. To activate this feature uncomment these lines
and add all used domain names. Below, some examples are given.
*/

//server-root is stud.ip root dir, or virtual server for stud.ip
//$STUDIP_DOMAINS[1] = "<your.server.name>";
//$STUDIP_DOMAINS[2] = "<your.server.ip>";
//$STUDIP_DOMAINS[3] = "<your.virtual.server.name>";
//
// or
//
//stud.ip root is a normal directory
$STUDIP_DOMAINS[1] = "test.studip.de/studip";
$STUDIP_DOMAINS[2] = "develop.studip.de/studip";


/*mail settings
----------------------------------------------------------------
possible settings for $MAIL_TRANSPORT:
smtp      use smtp to deliver to $MAIL_HOST_NAME
php       use php's mail() function
sendmail  use local sendmail script
qmail     use local Qmail MTA
debug     mails are only written to a file in $TMP_PATH
*/
$MAIL_TRANSPORT = "smtp";

/*smtp settings
----------------------------------------------------------------
leave blank or try 127.0.0.1 if localhost is also the mailserver
ignore if you don't use smtp as transport*/
$MAIL_HOST_NAME = "127.0.0.1";                               //which mailserver should we use? (must allow mail-relaying from $MAIL_LOCALHOST, defaults to SERVER_NAME)

$MAIL_LOCALHOST = "develop.studip.de";                               //name of the mail sending machine (the web server) defaults to SERVER_NAME
$MAIL_CHARSET = "";                                 //character set of mail body, defaults to WINDOWS-1252
$MAIL_ENV_FROM = "develop-noreply@studip.de";                                //sender mail adress, defaults to wwwrun @ $MAIL_LOCALHOST
$MAIL_FROM = "";                                    //name of sender, defaults to "Stud.IP"
$MAIL_ABUSE = "abuse@studip.de";                                   //mail adress to reply to in case of abuse, defaults to abuse @  $MAIL_LOCALHOST

$MAIL_BULK_DELIVERY = TRUE;                        //try to improve the message queueing rate (experimental, does not work for php transport)

$MAIL_VALIDATE_HOST = TRUE;                             //check for valid mail host when user enters email adress
$MAIL_VALIDATE_BOX = FALSE;                              //check for valid mail account when user enters email adress; set to false if the webserver got no valid MX record

$MESSAGING_FORWARD_AS_EMAIL = TRUE;                         //enable to forward every internal message to the user-mail (the user is able to deactivate this function in his personal settings)
$MESSAGING_FORWARD_DEFAULT = 3;                             //the default setting: if 1, the user has to switch it on; if 2, every message will be forwarded; if 3 every message will be forwarded on request of the sender

$ENABLE_EMAIL_TO_STATUSGROUP = TRUE;                                // enable to send messages to whole status groups

$ENABLE_EMAIL_ATTACHMENTS = TRUE;                               // enable attachment functions for internal and external messages

/*advanced system settings
----------------------------------------------------------------
this are some settings to activate some special features, special
behaviour of some features and other advanced options. Change on your
own risk :) */

$ALLOW_GROUPING_SEMINARS = TRUE;                            //if true, administrators can group seminars - students
                                            //will only be able to register for one of the grouped seminars

$ALLOW_SELFASSIGN_STUDYCOURSE = TRUE;                           //if true, students are allowed to set or change
                                            //their studycourse (studiengang)

$SHOW_TERMS_ON_FIRST_LOGIN = TRUE;                         //if true, the user has to accept the terms on his first login
                                            //(this feature makes only sense, if you use disable $ENABLE_SELF_REGISTRATION).

$USER_VISIBILITY_CHECK = TRUE;             // enable presentation of visibility decision texts for users after first login
                                            // see lib/include/header.php and lib/user_visible.inc.php for further info

$CONVERT_IDNA_URL = TRUE;                               //if true, urls with german "umlauts" are converted

$USERNAME_REGULAR_EXPRESSION = '/^([a-zA-Z0-9_@.-]{4,})$/'; //regex for allowed characters in usernames

/*timezone
----------------------------------------------------------------*/
$DEFAULT_TIMEZONE = 'Europe/Berlin';

/*language settings
----------------------------------------------------------------*/

$INSTALLED_LANGUAGES["de_DE"] = array ("path"=>"de", "picture"=>"lang_de.gif", "name"=>"Deutsch");
$INSTALLED_LANGUAGES["en_GB"] = array ("path"=>"en", "picture"=>"lang_en.gif", "name"=>"English");

$DEFAULT_LANGUAGE = "de_DE";  // which language should we use if we can gather no information from user?

$_language_domain = "studip";  // the name of the language file. Should not be changed except in cases of individual translations or special terms.

/*literature search plugins
----------------------------------------------------------------
If you write your own plugin put it in studip-htdocs/lib/classes/lit_search_plugins
and enable it here. The name of the plugin is the classname excluding "StudipLitSearchPlugin".
If the catalog your plugin is designed for offers the possibility to create a link to an entry, you
could provide the link here. Place templates for the needed attributes in curly braces. (see examples below)*/

//standard plugin, searches in Stud.IP Database (table lit_catalog), you should leave this one enabled !
$_lit_search_plugins[] = array('name' => "Studip",'display_name' =>'Katalog der Stud.IP Datenbank', 'link' => '');

//Plugins derived from Z3950Abstract, used for querying Z39.50 Servers
//only activate these plugins, if your Version of PHP supports the YAZ extension!

/* Gemeinsamer Verbundkatalog - GVK */
$_lit_search_plugins[] = array('name' => "Gvk",'display_name' =>'Gemeinsamer Verbundkatalog', 'link' => 'http://gso.gbv.de/DB=2.1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Nieders�chsische Staats- und Universit�tsbibliothek G�ttingen, OPAC */
$_lit_search_plugins[] = array('name' => "SUBGoeOpac",'display_name' => "Opac der SUB G�ttingen" , 'link' => 'http://goopc4.sub.uni-goettingen.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* G�ttinger Gesamtkatalog (Regionalkatalog G�ttingen) */
$_lit_search_plugins[] = array('name' => 'Rkgoe', 'display_name' =>'Regionalkatalog G�ttingen', 'link' => 'http://gso.gbv.de/DB=2.90/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Bibliotheken der Wirtschafts- und Sozialwiss. Fakult�ten Goettingen" */
//$_lit_search_plugins[] = array('name' => 'WisoFak', 'link' => 'http://goopc4.sub.uni-goettingen.de:8080/DB=2/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Technische Informationsbibliothek / Universit�tsbibliothek Hannover, OPAC */
//$_lit_search_plugins[] = array('name' => 'TIBUBOpac', 'link' => 'http://opc4.tib.uni-hannover.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}', 'display_name' => "UB Katalog");

/* Hannover Gesamtkatalog (Regionalkatalog Hannover) */
//$_lit_search_plugins[] = array('name' => 'Rkhan', 'link' => 'http://gso.gbv.de/DB=2.92/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}', 'display_name' => "Gesamtkatalog Hannover");

/* Bibliotheken der Fachhochschule Hildesheim/Holzminden/G�ttingen */
//$_lit_search_plugins[] = array('name' => 'FHHIOpac', 'link' => 'http://hidbs2.bib.uni-hildesheim.de:8080/DB=2/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Th�ringer Universit�ts- und Landesbibliothek Jena */
//$_lit_search_plugins[] = array('name' => 'ThULB_Jena', 'link' => 'http://jenopc4.thulb.uni-jena.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Bibliothek der Fachhochschule Jena */
//$_lit_search_plugins[] = array('name' => 'FH_Jena', 'link' => 'http://jenopc4.thulb.uni-jena.de:8080/DB=2/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Universit�tsbibliothek der Bauhaus-Universit�t Weimar */
//$_lit_search_plugins[] = array('name' => 'UB_Weimar', 'link' => 'http://weias.ub.uni-weimar.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Herzogin Anna Amalia Bibliothek Weimar */
//$_lit_search_plugins[] = array('name' => 'HAAB_Weimar', 'link' => 'http://weias.ub.uni-weimar.de:8080/DB=2/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Bibliothek der Hochschule f�r Musik Franz Liszt Weimar */
//$_lit_search_plugins[] = array('name' => 'HSfMFL_Weimar', 'link' => 'http://weias.ub.uni-weimar.de:8080/DB=3/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Universit�tsbibliothek Erfurt */
//$_lit_search_plugins[] = array('name' => 'UB_Erfurt', 'link' => 'http://opac.uni-erfurt.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Bibliothek der Fachhochschule Erfurt */
//$_lit_search_plugins[] = array('name' => 'FH_Erfurt', 'link' => 'http://opac.uni-erfurt.de:8080/DB=4/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Bibliothek der Fachhochschule Nordhausen */
//$_lit_search_plugins[] = array('name' => 'FH_Nordhausen', 'link' => 'http://opac.uni-erfurt.de:8080/DB=5/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Universit�tsbibliothek Ilmenau */
//$_lit_search_plugins[] = array('name' => 'UB_Ilmenau', 'link' => 'http://ilmopc4.bibliothek.tu-ilmenau.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Bibliothek der Fachhochschule Schmalkalden */
//$_lit_search_plugins[] = array('name' => 'FH_Schmalkalden', 'link' => 'http://ilmopc4.bibliothek.tu-ilmenau.de:8080/DB=2/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Universit�ts- und Landesbibliothek Sachsen-Anhalt Halle */
//$_lit_search_plugins[] = array('name' => "Ulb", 'link' => 'http://haweb1.bibliothek.uni-halle.de:8080/DB=1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* FB Technik ULB Halle und FH Merseburg  */
//$_lit_search_plugins[] = array('name' => "FBTechnik", 'link' => 'http://haweb1.bibliothek.uni-halle.de:8080/DB=2/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Deutsche Akademie der Naturforscher Leopoldina */
//$_lit_search_plugins[] = array('name' => "Leopoldina", 'link' => 'http://haweb1.bibliothek.uni-halle.de:8080/DB=4/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/* Universit�tsbibliothek Trier */
//$_lit_search_plugins[] = array('name' => 'UB_Trier', 'display_name' =>'BIB-KAT Universit�t Trier', 'link' => 'http://bibkat.uni-trier.de/F/?func=find-c&local_base=tri01&ccl_term={accession_number}');

/* S�dwestdeutscher Bibliotheksverbund SWB Online */
//$_lit_search_plugins[] = array('name' => "Swb", 'display_name' => "SWB Online Katalog", 'link' => 'http://swb.bsz-bw.de/DB=2.1/SET=1/TTL=2/CLK?IKT=12&TRM={accession_number}');

/* IWF Campusmedien */
//$_lit_search_plugins[] = array('name' => "IWFdigiClips", 'display_name' => "IWF Campusmedien", 'link' => 'http://gso.gbv.de/DB=1.65/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=12&SRT=YOP&TRM={accession_number}');

/*authentication plugins
----------------------------------------------------------------
the following plugins are available:
Standard        authentication using the local Stud.IP database
StandardExtern      authentication using an alternative Stud.IP database, e.g. another installation
Ldap            authentication using an LDAP server, this plugin uses anonymous bind against LDAP to retrieve the user dn,
            then it uses the submitted password to authenticate with this user dn
LdapReader      authentication using an LDAP server, this plugin binds to the server using a given dn and a password,
            this account must have read access to gather the attributes for the user who tries to authenticate.
CAS         authentication using a central authentication server (CAS)
Shib            authentication using a Shibboleth identity provider (IdP)

If you write your own plugin put it in studip-htdocs/lib/classes/auth_plugins
and enable it here. The name of the plugin is the classname excluding "StudipAuth".

You could also place your configuration here, name it $STUDIP_AUTH_CONFIG_<plugin name>,
all uppercase each item of the configuration array will become a member of your plugin class.*/

//$STUDIP_AUTH_PLUGIN[] = "LdapReader";
//$STUDIP_AUTH_PLUGIN[] = "Ldap";
//$STUDIP_AUTH_PLUGIN[] = "StandardExtern";
$STUDIP_AUTH_PLUGIN[] = "Standard";
// $STUDIP_AUTH_PLUGIN[] = "CAS";
$STUDIP_AUTH_PLUGIN[] = "Shib";

$STUDIP_AUTH_CONFIG_STANDARD = array("error_head" => "intern");

$STUDIP_AUTH_CONFIG_SHIB = array(
    // SessionInitator URL for remote SP
    'session_initiator' => 'https://shib-sp.uni-osnabrueck.de/secure/studip-sp.php',
    // validation URL for remote SP
    'validate_url'      => 'https://shib-sp.uni-osnabrueck.de/auth/studip-sp.php',
    // standard user data mapping
    'user_data_mapping' => array(
        'auth_user_md5.username' => array('callback' => 'dummy', 'map_args' => ''),
        'auth_user_md5.password' => array('callback' => 'dummy', 'map_args' => ''),
        'auth_user_md5.Vorname' => array('callback' => 'getUserData', 'map_args' => 'givenname'),
        'auth_user_md5.Nachname' => array('callback' => 'getUserData', 'map_args' => 'sn'),
        'auth_user_md5.Email' => array('callback' => 'getUserData', 'map_args' => 'mail')
    )
);
/*
// create a config for your own user data mapping class
$CASAbstractUserDataMapping_CONFIG = array();
$STUDIP_AUTH_CONFIG_CAS = array("host" => "cas.studip.de",
                                        "port" => 8443,
                                        "uri"  => "cas",
                                        "user_data_mapping_class" => "CASAbstractUserDataMapping",
                                        "user_data_mapping" => // map_args are dependent on your own data mapping class
                                                array(  "auth_user_md5.username" => array("callback" => "getUserData", "map_args" => "username"),
                                                        "auth_user_md5.Vorname" => array("callback" => "getUserData", "map_args" => "givenname"),
                                                        "auth_user_md5.Nachname" => array("callback" => "getUserData", "map_args" => "surname"),
                                                        "auth_user_md5.Email" => array("callback" => "getUserData", "map_args" => "email"),
                                                        "auth_user_md5.perms" => array("callback" => "getUserData", "map_args" => "status"))
$STUDIP_AUTH_CONFIG_LDAPREADER = array(     "host" => "localhost",
                                        "base_dn" => "dc=studip,dc=de",
                                        "protocol_version" => 3,
                                        "start_tls" => false,
                                        "bad_char_regex" => '/[^0-9_a-zA-Z]/',
                                        "username_attribute" => "uid",
                                        "user_password_attribute" => "userpassword",
                                        "reader_dn" => "uid=reader,dc=studip,dc=de",
                                        "reader_password" => "<password>",
                                        "error_head" => "LDAP reader plugin",
                                        "user_data_mapping" =>
                                        array(  "auth_user_md5.username" => array("callback" => "dummy", "map_args" => ""),
                                                "auth_user_md5.password" => array("callback" => "dummy", "map_args" => ""));

$STUDIP_AUTH_CONFIG_LDAP = array(       "host" => "localhost",
                                        "base_dn" => "dc=data-quest,dc=de",
                                        "protocol_version" => 3,
                                        "start_tls" => false,
                                        "bad_char_regex" => '/[^0-9_a-zA-Z]/',
                                        "username_attribute" => "uid",
                                        "anonymous_bind" => true,
                                        "error_head" => "LDAP plugin",
                                        "user_data_mapping" =>
                                        array(  "auth_user_md5.username" => array("callback" => "dummy", "map_args" => ""),
                                                "auth_user_md5.password" => array("callback" => "dummy", "map_args" => ""));

$STUDIP_AUTH_CONFIG_STANDARDEXTERN = array( "db_host" => "localhost",
                                        "db_username" => "extern",
                                        "db_name" => "extern_studip",
                                        "db_password" => "<password>",
                                        "error_head" => "Stud.IP extern plugin",
                                        "user_data_mapping" =>
                                        array(  "auth_user_md5.username" => array("callback" => "dummy", "map_args" => ""),
                                                "auth_user_md5.password" => array("callback" => "dummy", "map_args" => ""),
                                                "auth_user_md5.Email" => array("callback" => "doExternMap", "map_args" => "Email"),
                                                "auth_user_md5.Nachname" => array("callback" => "doExternMap", "map_args" => "Nachname"),
                                                "auth_user_md5.Vorname" => array("callback" => "doExternMap", "map_args" => "Vorname"),
                                                "auth_user_md5.perms" => array("callback" => "doExternMapPerms", "map_args" => "perms"),
                                                "user_info.privatnr" => array("callback" => "doExternMap", "map_args" => "privatnr"),
                                                "user_info.privadr" => array("callback" => "doExternMap", "map_args" => "privadr"),
                                                "user_info.geschlecht" => array("callback" => "doExternMap", "map_args" => "geschlecht"),
                                                "user_info.hobby" => array("callback" => "doExternMap", "map_args" => "hobby"),
                                                "user_info.lebenslauf" => array("callback" => "doExternMap", "map_args" => "lebenslauf"),
                                                "user_info.title_front" => array("callback" => "doExternMap", "map_args" => "title_front"),
                                                "user_info.title_rear" => array("callback" => "doExternMap", "map_args" => "title_rear"),
                                                "user_info.publi" => array("callback" => "doExternMap", "map_args" => "publi"),
                                                "user_info.schwerp" => array("callback" => "doExternMap", "map_args" => "schwerp"),
                                                "user_info.Home" => array("callback" => "doExternMap", "map_args" => "Home")));
*/

//some additional authification-settings
//NOTE: you MUST enable Standard authentication-plugin for this settings to take effect!

$ALLOW_CHANGE_USERNAME = TRUE;                          //if true, users are allowed to change their username
$ALLOW_CHANGE_EMAIL = TRUE;                         //if true, users are allowed to change their email-address
$ALLOW_CHANGE_NAME = TRUE;                          //if true, users are allowed to change their name
$ALLOW_CHANGE_TITLE = TRUE;                         //if true, users are allowed to change their titles
$ENABLE_SELF_REGISTRATION = TRUE;               //should it be possible for an user to register himself

$ENABLE_REQUEST_NEW_PASSWORD_BY_USER = TRUE;            //if true, users are able to request a new password themselves
$REQUEST_NEW_PASSWORD_SECRET = 'jh896fajsb974b4850aMhlf'; // if the above feature is used, set this to somthing different!!!

$ENABLE_FREE_ACCESS = TRUE;                         //if true, courses with public access are available


/*path generation
-----------------------------------------------------------------
(end of user defined settings)*/


//create the html-version of $UNI_NAME clean
$UNI_NAME = htmlspecialchars($UNI_NAME_CLEAN, ENT_QUOTES, 'cp1252');

