<?php
/**
* config.inc.php
*
* Configuration file for studip. In this file you can change the options of many
* Stud.IP Settings. Please note: to setup the system, set the basic settings in the
* local.inc of the phpLib package first.
*
* @access       public
* @package      studip_core
* @modulegroup  library
* @module       config.inc.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// functions.php
// Stud.IP Kernfunktionen
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>,
// Ralf Stockmann <rstockm@gwdg.de>, André Noack André Noack <andre.noack@gmx.net>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

/*basic settings for Stud.IP
----------------------------------------------------------------
you find here the indivual settings for your installation.
please note the LOCAL.INC.PHP in the php-lib folder for the basic system settings!*/

global
  $CALENDAR_MAX_EVENTS,
  $export_ex_types,
  $export_icon,
  $export_o_modes,
  $FLASHPLAYER_DEFAULT_CONFIG_MIN,
  $FLASHPLAYER_DEFAULT_CONFIG_MAX,
  $INST_ADMIN_DATAFIELDS_VIEW,
  $INST_MODULES,
  $INST_STATUS_GROUPS,
  $INST_TYPE,
  $LIT_LIST_FORMAT_TEMPLATE,
  $NAME_FORMAT_DESC,
  $output_formats,
  $PERS_TERMIN_KAT,
  $record_of_study_templates,
  $SCM_PRESET,
  $SEM_CLASS,
  $SEM_STATUS_GROUPS,
  $SEM_TYPE,
  $SEM_TYPE_MISC_NAME,
  $skip_page_3,
  $SMILE_SHORT,
  $SYMBOL_SHORT,
  $TERMIN_TYP,
  $TIME_PRESETS,
  $TITLE_FRONT_TEMPLATE,
  $TITLE_REAR_TEMPLATE,
  $UNI_CONTACT,
  $UNI_INFO,
  $UNI_LOGIN_ADD,
  $UNI_LOGOUT_ADD,
  $UNI_URL,
  $UPLOAD_TYPES,
  $username_prefix,
  $xml_filename,
  $xslt_filename,
  $SEM_TREE_TYPES;

//Daten ueber die Uni
    // der Name wird in der local.inc festgelegt
$UNI_URL = "http://www.studip.de";
$UNI_LOGOUT_ADD=sprintf(_("Und hier geht's direkt zum %sMensaplan%s&nbsp;;-)"), "<a href=\"http://studentenwerk.stud.uni-goettingen.de/mensa/mensen/alle_heute.php\"><b>", "</b></a>");
$UNI_CONTACT = "studip-users@lists.sourceforge.net";
$UNI_INFO = "Kontakt:\nStud.IP Crew c/o data-quest Suchi & Berg GmbH\nFriedländer Weg 20a\n37085 Göttingen\nTel. 0551-3819850\nFax 0551-3819853\nstudip@data-quest.de";


// define default names for status groups
$DEFAULT_TITLE_FOR_STATUS = array(
    'dozent'   => array(_('DozentIn'), _('DozentInnen')),
    'deputy'   => array(_('Vertretung'), _('Vertretungen')),
    'tutor'    => array(_('TutorIn'), _('TutorInnen')),
    'autor'    => array(_('AutorIn'), _('AutorInnen')),
    'user'     => array(_('LeserIn'), _('LeserInnen')),
    'accepted' => array(_('Vorläufig akzeptierte TeilnehmerIn'),
                        _('Vorläufig akzeptierte TeilnehmerInnen')));

//Festlegen der zulaessigen Typen fuer Veranstaltungen
$SEM_TYPE_MISC_NAME="sonstige"; //dieser Name wird durch die allgemeine Bezechnung (=Veranstaltung ersetzt)
$SEM_TYPE[1]=array("name"=>_("Vorlesung"), "class"=>1);
$SEM_TYPE[2]=array("name"=>_("Grundstudium"), "en"=>"Basic classes", "class"=>1);
$SEM_TYPE[3]=array("name"=>_("Hauptstudium"), "en"=>"Advanced classes", "class"=>1);
$SEM_TYPE[4]=array("name"=>_("Seminar"), "en"=>"Seminar", "class"=>1);
$SEM_TYPE[5]=array("name"=>_("Praxisveranstaltung"), "en"=>"Practical course", "class"=>1);
$SEM_TYPE[6]=array("name"=>_("Kolloquium"), "en"=>"Colloqia", "class"=>1);
$SEM_TYPE[7]=array("name"=>_("Forschungsgruppe"), "en"=>"Research group", "class"=>1);
$SEM_TYPE[8]=array("name"=>_("Arbeitsgruppe"), "en"=>"Workgroup", "class"=>5);
$SEM_TYPE[9]=array("name"=>_("sonstige"), "en"=>"Miscellaneous", "class"=>1);
$SEM_TYPE[10]=array("name"=>_("Forschungsgruppe"), "en"=>"Research group", "class"=>2, 'title_dozent' => array(_("Papst"),_("Päpste")), 'title_tutor' => array(_("Kardinal"),_("Kardinäle")), 'title_autor' => array(_("Scherge"),_("Schergen")) );
$SEM_TYPE[11]=array("name"=>_("sonstige"), "en"=>"Miscellaneous", "class"=>2);
$SEM_TYPE[12]=array("name"=>_("Gremiumsveranstaltung"), "en"=>"Board meeting", "class"=>3);
$SEM_TYPE[13]=array("name"=>_("sonstige"), "en"=>"Miscellaneous", "class"=>3);
$SEM_TYPE[14]=array("name"=>_("Community-Forum"), "en"=>"Community forum", "class"=>4);
$SEM_TYPE[15]=array("name"=>_("sonstige"), "en"=>"Miscellaneous", "class"=>4);
$SEM_TYPE[16]=array("name"=>_("Praktikum"), "en"=>"Practical course", "class"=>1);
$SEM_TYPE[17]=array("name"=>_("Lehrveranstaltung nach PVO-Lehr I"), "en"=>"", "class"=>1);
$SEM_TYPE[18]=array("name"=>_("Anleitung zu selbständigen wissenschaftlichen Arbeiten"), "en"=>"", "class"=>1);
$SEM_TYPE[19]=array("name"=>_("Sprachkurs"), "en"=>"Language Course", "class"=>1);
$SEM_TYPE[20]=array("name"=>_("Fachdidaktik"), "en"=>"Didactics", "class"=>1);
$SEM_TYPE[21]=array("name"=>_("Übung"), "en"=>"Exercise Course", "class"=>1);
$SEM_TYPE[22]=array("name"=>_("Proseminar"), "en"=>"Proseminar", "class"=>1);
$SEM_TYPE[23]=array("name"=>_("Oberseminar"), "en"=>"Oberseminar", "class"=>1);
$SEM_TYPE[24]=array("name"=>_("Arbeitsgemeinschaft"), "en"=>"Workgroup", "class"=>1);
$SEM_TYPE[25]=array("name"=>_("Vorlesung"), "class"=>6);
$SEM_TYPE[26]=array("name"=>_("Grundstudium"), "class"=>6);
$SEM_TYPE[27]=array("name"=>_("Hauptstudium"), "class"=>6);
$SEM_TYPE[28]=array("name"=>_("Seminar"), "class"=>6);
$SEM_TYPE[29]=array("name"=>_("Praxisveranstaltung"), "class"=>6);
$SEM_TYPE[30]=array("name"=>_("Kolloquium"), "class"=>6);
$SEM_TYPE[31]=array("name"=>_("Forschungsgruppe"), "class"=>6);
//weitere Typen koennen hier angefuegt werden

// required config settings for study groups (courses that can be created by students)
$SEM_TYPE[99]=array("name"=>_("Studiengruppe"), "class"=>99,
                    "title_dozent" => array(_("GruppengründerIn"), _("GruppengründerInnen")),
                    "title_tutor" => array(_("ModeratorIn"), _("ModeratorInnen")),
                    "title_autor" => array(_("Mitglied"), _("Mitglieder")));

$SEM_CLASS[99]=array("name"=>_("Studiengruppen"),
                    "studygroup_mode"=>TRUE,
                    "topic_create_autor"=>TRUE,
                    "course_creation_forbidden" => TRUE);

//Festlegen der zulaessigen Klassen fuer Veranstaltungen. Jeder sem_type referenziert auf eine dieser Klassen
$SEM_CLASS[1]=array("name"=>_("Lehre"),                         //the name of the class
                    "compact_mode"=>FALSE,          //indicates, if all fields are used in the creation process or only the fields that are necessary for workgroups
                    "workgroup_mode"=>FALSE,            //indicates, if the workgroup mode is used (to use different declarations)
                    "only_inst_user"=>true,             //indicates, that olny staff from the Einrichtungen which own the Veranstaltung, are allowed for tutor and dozent
                    "turnus_default"=>0 ,               //indicates, whether the turnus field is default set to "regulary" (0), "not regulary" (1) or "no dates" (-1) in the creation process
                    "default_read_level"=>1,                //the default read acces level. "without signed in" (0), "signed in" (1), "password" (2)
                    "default_write_level" =>1,              //the default write acces level. "without signed in" (0), "signed in" (1), "password" (2)
                    "bereiche"=>TRUE,                   //indicates, if bereiche should be used
                    "show_browse"=>TRUE,                //indicates, if the hierachy-system should be shown in the search-process
                    "write_access_nobody"=>FALSE,       //indicates, if write access level 0 is possible. If this is not possibly, don't set default_write_level to 0
                    "topic_create_autor"=>TRUE,
                    "visible"=>TRUE,
                    //modules, select the active modules for this class
                    "forum"=>TRUE,              //forum, this modul is stud_ip core; always avaiable
                    "documents"=>TRUE,          //documents, this modul is stud_ip core; always avaiable
                    "schedule"=>TRUE,
                    "participants"=>TRUE,
                    "literature"=>TRUE,
                    "chat"=>TRUE,               //chat, only, if the modul is global activated; see local.inc
                    "support"=>FALSE,           //support, only, if the modul is global activated; see local.inc (this modul is not part of the main distribution)
                    "scm"=>TRUE,
                    //descriptions
                    "description"=>_("Hier finden Sie alle in Stud.IP registrierten Lehrveranstaltungen"),                      //the description
                    "create_description"=>_("Verwenden Sie diese Kategorie, um normale Lehrveranstaltungen anzulegen"));        //the description in the creation process

$SEM_CLASS[2]=array("name"=>_("Forschung"),
                    "compact_mode"=>TRUE,
                    "workgroup_mode"=>TRUE,
                    "only_inst_user"=>TRUE,
                    "turnus_default"=>-1,
                    "default_read_level"=>2,
                    "default_write_level" =>2,
                    "bereiche"=>TRUE,
                    "show_browse"=>TRUE,
                    "write_access_nobody"=>FALSE,
                    "visible"=>TRUE,
                    "forum"=>TRUE,
                    "topic_create_autor" => true,
                    "documents"=>TRUE,
                    "schedule"=>TRUE,
                    "participants"=>TRUE,
                    "literature"=>TRUE,
                    "chat"=>TRUE,
                    "description"=>_("Hier finden Sie virtuelle Veranstaltungen zum Thema Forschung an der Universit&auml;t"),
                    "create_description"=>_("In dieser Kategorie k&ouml;nnen Sie virtuelle Veranstaltungen f&uuml;r Forschungsprojekte anlegen."));

$SEM_CLASS[3]=array("name"=>_("Organisation"),
                    "compact_mode"=>TRUE,
                    "workgroup_mode"=>TRUE,
                    "only_inst_user"=>FALSE,
                    "turnus_default"=>-1,
                    "default_read_level"=>2,
                    "default_write_level" =>2,
                    "bereiche"=>TRUE,                   //indicates, if bereiche should be used
                    "show_browse"=>TRUE,
                    "write_access_nobody"=>TRUE,
                    "visible"=>TRUE,
                    "forum"=>TRUE,
                    "topic_create_autor" => true,
                    "documents"=>TRUE,
                    "schedule"=>TRUE,
                    "participants"=>TRUE,
                    "literature"=>TRUE,
                    "chat"=>TRUE,
                    "description"=>_("Hier finden Sie virtuelle Veranstaltungen zu verschiedenen Gremien an der Universit&auml;t"),
                    "create_description"=>_("Um virtuelle Veranstaltungen f&uuml;r Uni-Gremien anzulegen, verwenden Sie diese Kategorie"));

$SEM_CLASS[4]=array("name"=>_("Community"),
                    "compact_mode"=>TRUE,
                    "workgroup_mode"=>FALSE,
                    "only_inst_user"=>FALSE,
                    "turnus_default"=>-1,
                    "default_read_level"=>0,
                    "default_write_level" =>0,
                    "bereiche"=>TRUE,                   //indicates, if bereiche should be used
                    "show_browse"=>FALSE,
                    "write_access_nobody"=>TRUE,
                    "visible"=>TRUE,
                    "forum"=>TRUE,
                    "documents"=>TRUE,
                    "schedule"=>TRUE,
                    "participants"=>TRUE,
                    "chat"=>TRUE,
                    "description"=>_("Hier finden Sie virtuelle Veranstaltungen zu unterschiedlichen Themen"),
                    "create_description"=>_("Wenn Sie Veranstaltungen als Diskussiongruppen zu unterschiedlichen Themen anlegen m&ouml;chten, verwenden Sie diese Kategorie."));

$SEM_CLASS[5]=array("name"=>_("Arbeitsgruppen"),
                    "compact_mode"=>FALSE,
                    "workgroup_mode"=>FALSE,
                    "only_inst_user"=>TRUE,
                    "turnus_default"=>1,
                    "default_read_level"=>1,
                    "default_write_level" =>1,
                    "bereiche"=>TRUE,
                    "show_browse"=>FALSE,
                    "topic_create_autor"=>TRUE,
                    "write_access_nobody"=>FALSE,
                    "visible"=>TRUE,
                    "forum"=>TRUE,
                    "documents"=>TRUE,
                    "schedule"=>TRUE,
                    "participants"=>TRUE,
                    "literature"=>TRUE,
                    "chat"=>TRUE,
                    "description"=>sprintf(_("Hier finden Sie verschiedene Arbeitsgruppen an der %s"), htmlentities($GLOBALS['UNI_NAME_CLEAN'])),
                    "create_description"=>_("Verwenden Sie diese Kategorie, um unterschiedliche Arbeitsgruppen anzulegen."));

$SEM_CLASS[6]=array("name"=>_("importierte Kurse"),
                    "compact_mode"=>FALSE,
                    "workgroup_mode"=>FALSE,
                    "only_inst_user"=>TRUE,
                    "turnus_default"=>1,
                    "default_read_level"=>1,
                    "default_write_level" =>1,
                    "bereiche"=>TRUE,
                    "show_browse"=>FALSE,
                    "topic_create_autor"=>TRUE,
                    "write_access_nobody"=>FALSE,
                    "visible"=>TRUE,
                    "course_creation_forbidden" => TRUE,
                    "forum"=>TRUE,
                    "documents"=>TRUE,
                    "schedule"=>TRUE,
                    "participants"=>TRUE,
                    "literature"=>TRUE,
                    "chat"=>TRUE,
                    "description"=> "Hier finden Sie importierte Kurse",
                    "create_description"=> "Sie sollten diesen Text garnicht sehen.");
//weitere Klassen koennen hier angefuegt werden. Bitte Struktur wie oben exakt uebernehmen.

/*
possible types of sem_tree ("Veranstaltungshierarchie") types
the "editable" flag could be used to prevent modifications, e.g. imported data
the "is_module" flag specifies an entry which represents a "Studienmodul", if the "studienmodulmanagement"
plugin interface is used
*/
$SEM_TREE_TYPES[0] = array("name" => "", "editable" => true); //default type, must be present
$SEM_TREE_TYPES[1] = array("name" => _("Studienmodul") , "editable" => true, "is_module" => true);

//Festlegen der erlaubten oder verbotenen Dateitypen
$UPLOAD_TYPES=array(    "default" =>                                                //Name bezeichnet den zugehoerigen SEM_TYPE, name "1" waere entsprechend die Definition der Dateiendungen fuer SEM_TYPE[1]; default wird verwendet, wenn es keine spezielle Definition fuer einen SEM_TYPE gibt
                        array(  "type"=>"allow",                                    //Type bezeichnet den grundsetzlichen Typ der Deklaration: deny verbietet alles ausser den angegebenen file_types, allow erlaubt alle ausser den angegebenen file_types
                                "file_types" => array ("exe"),  //verbotene bzw. erlaubte Dateitypen
                                "file_sizes" => array ( "root" => 14 * 1048576,         //Erlaubte Groesse je nach Rechtestufe
                                                    "admin" => 14 * 1048576,
                                                    "dozent" => 14 * 1048576,
                                                    "tutor" => 14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "7" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" =>   14 * 1048576,
                                                    "admin" =>
14 * 1048576,
                                                    "dozent" =>
14 * 1048576,
                                                    "tutor" =>
14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "8" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" =>
14 * 1048576,
                                                    "admin" =>
14 * 1048576,
                                                    "dozent" =>
14 * 1048576,
                                                    "tutor" =>
14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "9" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" =>
14 * 1048576,
                                                    "admin" =>
14 * 1048576,
                                                    "dozent" =>
14 * 1048576,
                                                    "tutor" =>
14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "10" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" =>
14 * 1048576,
                                                    "admin" =>
14 * 1048576,
                                                    "dozent" =>
14 * 1048576,
                                                    "tutor" =>
14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "11" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" => 100 * 1048576,
                                                    "admin" => 100 * 1048576,
                                                    "dozent" => 100 * 1048576,
                                                    "tutor" => 100 * 1048576,
                                                    "autor" => 100 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "12" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" =>
14 * 1048576,
                                                    "admin" =>
14 * 1048576,
                                                    "dozent" =>
14 * 1048576,
                                                    "tutor" =>
14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            ),
                        "13" =>
                        array(  "type"=>"allow",
                                "file_types" => array ("exe"),
                                "file_sizes" => array ( "root" =>
14 * 1048576,
                                                    "admin" =>
14 * 1048576,
                                                    "dozent" =>
14 * 1048576,
                                                    "tutor" =>
14 * 1048576,
                                                    "autor" => 7 * 1048576,
                                                    "nobody" => 1.38 * 1048576
                                                )
                            )
                    );
//weitere Definitionen fuer spezielle Veranstaltungstypen koennen hier angefuegt werden. Bitte Struktur wie oben exakt uebernehmen.

/* Set the allowed and prohibited file types for mail attachments (if activated by ENABLE_MAIL_ATTACHMENTS).
*
*  "type"=>"deny" means: only the listed "file_types" are allowed
*  "type"=>"allow" means: all, but the listed "file_types" are allowed
*
*  "file_sizes" determines how much each user class can upload per file (multiple of 1 MB = 1048576 Bytes)
*/

$UPLOAD_TYPES["attachments"] =
                array(  "type" => "allow",
                        "file_types" => array ("exe"),
                        "file_sizes" => array ( "root" => 7 * 1048576,
                                    "admin" => 7 * 1048576,
                                    "dozent" => 7 * 1048576,
                                    "tutor" => 7 * 1048576,
                                    "autor" => 7 * 1048576,
                                    "nobody" => 1.38 * 1048576
                                    )
                );

/*
 * define additional fields that can be shown in participant list view.
 */

$TEILNEHMER_VIEW[0] = array("field" => "user_picture",
  "name" => _("Nutzerbilder"), "table" => "special", "export" => 0, "display"=> 1);
$TEILNEHMER_VIEW[1] = array("field" => "geschlecht",
  "name" => _("Geschlecht"), "table" => "datafields", "export" => 1, "display"=> 1);
$TEILNEHMER_VIEW[2] = array("field" => "preferred_language",
  "name" => _("Sprache"), "table" => "user_info", "export" => 1, "display"=> 1);


//Festlegen von zulaessigen Bezeichnungen fuer Einrichtungen (=Institute)
$INST_TYPE[1]=array("name"=>_("Einrichtung"));
$INST_TYPE[2]=array("name"=>_("Zentrum"));
$INST_TYPE[3]=array("name"=>_("Lehrstuhl"));
$INST_TYPE[4]=array("name"=>_("Abteilung"));
$INST_TYPE[5]=array("name"=>_("Fachbereich"));
$INST_TYPE[6]=array("name"=>_("Seminar"));
$INST_TYPE[7]=array("name"=>_("Fakultät"));
$INST_TYPE[8]=array("name"=>_("Arbeitsgruppe"));
//weitere Typen koennen hier angefuegt werden


//define the presets of statusgroups for Veranstaltungen (refers to the key of the $SEM_CLASS array)
$SEM_STATUS_GROUPS["default"] = array ("DozentInnen", "TutorInnen", "AutorInnen", "LeserInnen", "sonstige");    //the default. Don't delete this entry!
$SEM_STATUS_GROUPS["2"] = array ("Projektleitung", "Koordination", "Forschung", "Verwaltung", "sonstige");
$SEM_STATUS_GROUPS["3"] = array ("Organisatoren", "Mitglieder", "Ausschu&szlig;mitglieder", "sonstige");
$SEM_STATUS_GROUPS["4"] = array ("Moderatoren des Forums","Mitglieder", "sonstige");
$SEM_STATUS_GROUPS["5"] = array ("ArbeitsgruppenleiterIn", "Arbeitsgruppenmitglieder", "sonstige");
//you can add more specifig presets for the different classes


//define the presets of statusgroups for Einrichtungen (refers to the key of the $INST_TYPE array)
$INST_STATUS_GROUPS["default"] = array ("DirektorIn", "HochschullehrerIn", "Lehrbeauftragte", "Zweitmitglied", "wiss. Hilfskraft","wiss. MitarbeiterIn",
                                    "stud. Hilfskraft", "Frauenbeauftragte", "Internetbeauftragte(r)", "StudentIn", "techn. MitarbeiterIn", "Sekretariat / Verwaltung",
                                    "stud. VertreterIn");
//you can add more specifig presets for the different types


//preset names for scm (simple content module)
$SCM_PRESET[1] = array("name"=>_("Informationen"));     //the first entry is the default label for scms, it'll be used if the user give no information for another
$SCM_PRESET[2] = array("name"=>_("Literatur"));
$SCM_PRESET[3] = array("name"=>_("Links"));
$SCM_PRESET[4] = array("name"=>_("Verschiedenes"));
//you can add more presets here

//preset template for formatting of literature list entries
$LIT_LIST_FORMAT_TEMPLATE = "**{dc_creator}** |({dc_contributor})||\n"
                        . "{dc_title}||\n"
                        . "{dc_identifier}||\n"
                        . "%%{published}%%||\n"
                        . "{note}||\n"
                        . "[{lit_plugin}]{external_link}|\n";

//define the used modules for instiutes
$INST_MODULES["default"] = array(
            "forum"=>TRUE,              //forum, this module is stud_ip core; always available
            "documents"=>TRUE,          //documents, this module is stud_ip core; always available
            "personal"=>TRUE,           //personal, this module is stud_ip core; always available
            "literature"=>TRUE,         //literature, this module is stud_ip core; always available
            "scm"=>FALSE,               //simple content module, this modul is stud_ip core; always available
            "chat"=>TRUE,               //chat, only, if the module is global activated; see config_local.inc.php
            "wiki"=>FALSE,              //wikiwiki-web, this module is stud_ip core; always available
            );
//you can add more specific presets for the different types


//Festlegen der Veranstaltungs Termin Typen
$TERMIN_TYP[1]=array("name"=>_("Sitzung"), "sitzung"=>1, "color"=>"#2D2C64");       //dieser Termin Typ wird immer als Seminarsitzung verwendet und im Ablaufplan entsprechend markiert. Der Titel kann veraendert werden, Eintraege aus dem Seminar Assistenten und Terminverwaltung fuer Seminar-Sitzungsterrmine bekommen jedoch immer diesen Typ
$TERMIN_TYP[2]=array("name"=>_("Vorbesprechung"), "sitzung"=>0, "color"=>"#5C2D64");    //dieser Termin Typ wird immer als Vorbesprechung verwendet. Der Titel kann veraendert werden, Eintraege aus dem Seminar Assistenten fuer Vorbesprechungen bekommen jedoch immer diesen Typ
$TERMIN_TYP[3]=array("name"=>_("Klausur"), "sitzung"=>0,  "color"=>"#526416");
$TERMIN_TYP[4]=array("name"=>_("Exkursion"), "sitzung"=>0, "color"=>"#505064");
$TERMIN_TYP[5]=array("name"=>_("anderer Termin"), "sitzung"=>0, "color"=>"#41643F");
$TERMIN_TYP[6]=array("name"=>_("Sondersitzung"), "sitzung"=>0, "color"=>"#64372C");
$TERMIN_TYP[7]=array("name"=>_("Vorlesung"), "sitzung"=>1, "color"=>"#627C95");
//weitere Typen koennen hier angefuegt werden


// Festlegen der Kategorien für persönlichen Terminkalender
$PERS_TERMIN_KAT[1]=array("name"=>_("Sonstiges"), "color"=>"#41643F");
$PERS_TERMIN_KAT[2]=array("name"=>_("Sitzung"), "color"=>"#2D2C64");
$PERS_TERMIN_KAT[3]=array("name"=>_("Vorbesprechung"), "color"=>"#5C2D64");
$PERS_TERMIN_KAT[4]=array("name"=>_("Klausur"), "color"=>"#526416");
$PERS_TERMIN_KAT[5]=array("name"=>_("Exkursion"), "color"=>"#505064");
$PERS_TERMIN_KAT[6]=array("name"=>_("Sondersitzung"), "color"=>"#64372C");
$PERS_TERMIN_KAT[7]=array("name"=>_("Prüfung"), "color"=>"#64541E");
$PERS_TERMIN_KAT[8]=array("name"=>_("Telefonat"), "color"=>"#48642B");
$PERS_TERMIN_KAT[9]=array("name"=>_("Besprechung"), "color"=>"#957C29");
$PERS_TERMIN_KAT[10]=array("name"=>_("Verabredung"), "color"=>"#956D42");
$PERS_TERMIN_KAT[11]=array("name"=>_("Geburtstag"), "color"=>"#66954F");
$PERS_TERMIN_KAT[12]=array("name"=>_("Familie"), "color"=>"#2C5964");
$PERS_TERMIN_KAT[13]=array("name"=>_("Urlaub"), "color"=>"#951408");
$PERS_TERMIN_KAT[14]=array("name"=>_("Reise"), "color"=>"#18645C");
$PERS_TERMIN_KAT[15]=array("name"=>_("Vorlesung"), "color"=>"#627C95");
// weitere Kategorien können hier angefügt werden

//standardtimes for date-begin and date-end
$TIME_PRESETS = array ( //starthour, startminute, endshour, endminute
        array ('07','45','09','15'), // 07:45 - 09:15
        array ('09','30','11','00'), // 09:30 - 11:00
        array ('11','15','12','45'), // 11:15 - 12:45
        array ('13','30','15','00'), // 13:30 - 15:00
        array ('15','15','16','45'), // 15:15 - 16:45
        array ('17','00','18','30'), // 17:00 - 18:30
        array ('18','45','20','15')  // 18:45 - 20:15
        );
//$TIME_PRESETS = false;

$CALENDAR_MAX_EVENTS = 1000;

//Vorgaben für die Titelauswahl
$TITLE_FRONT_TEMPLATE = array("","Prof.","Prof. Dr.","Dr.","PD Dr.","Dr. des.","Dr. med.","Dr. rer. nat.","Dr. forest.",
                            "Dr. sc. agr.","Dipl.-Biol.","Dipl.-Chem.","Dipl.-Ing.","Dipl.-Sozw.","Dipl.-Geogr.",
                            "Dipl.-Geol.","Dipl.-Geophys.","Dipl.-Ing. agr.","Dipl.-Kfm.","Dipl.-Math.","Dipl.-Phys.",
                            "Dipl.-Psych.","M. Sc","B. Sc");
$TITLE_REAR_TEMPLATE = array("","M.A.","B.A.","M.S.","MBA","Ph.D.","Dipl.-Biol.","Dipl.-Chem.","Dipl.-Ing.","Dipl.-Sozw.","Dipl.-Geogr.",
                            "Dipl.-Geol.","Dipl.-Geophys.","Dipl.-Ing. agr.","Dipl.-Kfm.","Dipl.-Math.","Dipl.-Phys.",
                            "Dipl.-Psych.","M. Sc","B. Sc");

$NAME_FORMAT_DESC['full'] = _("Titel1 Vorname Nachname Titel2");
$NAME_FORMAT_DESC['full_rev'] = _("Nachname, Vorname, Titel1, Titel2");
$NAME_FORMAT_DESC['no_title'] = _("Vorname Nachname");
$NAME_FORMAT_DESC['no_title_rev'] = _("Nachname, Vorname");
$NAME_FORMAT_DESC['no_title_short'] = _("Nachname, V.");
$NAME_FORMAT_DESC['no_title_motto'] = _("Vorname Nachname, Motto");

//Shorts for Smiley
$SMILE_SHORT = array( //diese Kuerzel fuegen das angegebene Smiley ein (Dateiname + ".gif")
    ":)"=>"smile" ,
    ":-)"=>"asmile" ,
    ":#:"=>"zwinker" ,
    ":("=>"frown" ,
    ":o"=>"redface" ,
    ":D"=>"biggrin",
    ";-)"=>"wink");

//Shorts for symbols
$SYMBOL_SHORT = array(
    "=)"    => "&rArr;" ,
    "(="    => "&lArr;" ,
    "(c)"   => "&copy;" ,
    "(r)"   => "&reg;" ,
    " tm "  => "&trade;"
);


/*configuration for additional modules
----------------------------------------------------------------
this options are only needed, if you are using the addional modules (please see in local.inc
which modules are activated). It's a good idea to leave them untouched...*/

// Literature-Import Plugins
$LIT_IMPORT_PLUGINS[1] = array('name' => 'EndNote', 'visual_name' => 'EndNote ab Version 7 / Reference Manager 11', 'description' => _("Exportieren Sie Ihre Literaturliste aus EndNote / Reference Manager als XML-Datei."));
$LIT_IMPORT_PLUGINS[2] = array('name' => 'GenericXML', 'visual_name' => _("Einfaches XML nach fester Vorgabe"),
        'description' => _("Die XML-Datei muss folgende Struktur besitzen:").'[code]
        <?xml version="1.0" encoding="UTF-8"?>
        <xml>
        <eintrag>
            <titel></titel>
            <autor></autor>
            <beschreibung></beschreibung>
            <herausgeber></herausgeber>
            <ort></ort>
            <isbn></isbn>
            <jahr></jahr>
        </eintrag>
        </xml>[/code]'.
        _("Jeder Abschnitt darf mehrfach vorkommen oder kann weggelassen werden, mindestens ein Titel pro Eintrag muss vorhanden sein."));
$LIT_IMPORT_PLUGINS[3] = array('name' => 'CSV', 'visual_name' => _("CSV mit Semikolon als Trennzeichen"), 'description' => _("Exportieren Sie Ihre Literaturliste in eine mit Trennzeichen getrennte Datei (CSV). Wichtig hierbei ist die Verwendung des Semikolons als Trennzeichen. Folgende Formatierung wird dabei in jeder Zeile erwartet:").'[pre]'._("Titel;Verfasser oder Urheber;Verleger;Herausgeber;Thema und Stichworte;ISBN").'[/pre]');
$LIT_IMPORT_PLUGINS[4] = array('name' => 'StudipLitList', 'visual_name' => _("Literaturliste im Stud.IP Format"), 'description' => _("Benutzen Sie die Export-Funktion innerhalb von Stud.IP, um eine Literaturliste im Stud.IP Format zu exportieren."));

// <<-- EXPORT-EINSTELLUNGEN
// Ausgabemodi für den Export
$export_o_modes = array("start","file","choose", "direct","processor","passthrough");
// Exportierbare Datenarten
$export_ex_types = array("veranstaltung", "person", "forschung");

$skip_page_3 = true;
// Name der erzeugten XML-Datei
$xml_filename = "data.xml";
// Name der erzeugten Ausgabe-Datei
$xslt_filename_default = "studip";

// Vorhandene Ausgabeformate
$output_formats = array(
    "html"      =>      "Hypertext (HTML)",
    "rtf"       =>      "Rich Text Format (RTF)",
    "txt"       =>      "Text (TXT)",
    "csv"       =>      "Comma Separated Values (Excel)",
    "fo"        =>      "Adobe Postscript (PDF)",
    "xml"       =>      "Extensible Markup Language (XML)"
);

// Icons für die Ausgabeformate
$export_icon["xml"] = "icons/16/blue/file-generic.png";
$export_icon["xslt"] = "icons/16/blue/file-xls.png";
$export_icon["xsl"] = "icons/16/blue/file-xls.png";
$export_icon["rtf"] = "icons/16/blue/file-text.png";
$export_icon["fo"] = "icons/16/blue/file-pdf.png";
$export_icon["pdf"] = "icons/16/blue/file-pdf.png";
$export_icon["html"] = "icons/16/blue/file-text.png";
$export_icon["htm"] = "icons/16/blue/file-text.png";
$export_icon["txt"] = "icons/16/blue/file-text.png";
$export_icon["csv"] = "icons/16/blue/file-xls.png";

// weitere Icons und Formate können hier angefügt werden

// PDF-Vorlagen für den Veranstaltungsexport (Index von 1 bis X)
// title = Beschreibung der Vorlage
// template = PDF-Vorlage in '/export'
$record_of_study_templates[1] = array("title" => "Allgemeine Druckvorlage", "template" =>"general_template.pdf");
$record_of_study_templates[2] = array("title" => "Studienbuch", "template" => "recordofstudy_template.pdf");

// EXPORT -->>

// cofiguration for flash player
$FLASHPLAYER_DEFAULT_CONFIG_MIN = "&amp;showstop=1&amp;showvolume=1&amp;bgcolor=A6B6C6&amp;bgcolor1=A6B6C6&amp;bgcolor2=7387AC&amp;playercolor=7387AC&amp;buttoncolor=254580&amp;buttonovercolor=E9EFFD&amp;slidercolor1=CAD7E1&amp;slidercolor2=A6B6C6&amp;sliderovercolor=E9EFFD&amp;loadingcolor=E9B21A&amp;buffer=5&amp;buffercolor=white&amp;buffershowbg=0&amp;playeralpha=90&amp;playertimeout=500&amp;shortcut=1&amp;phpstream=0&amp;onclick=playpause&amp;showloading=always";
$FLASHPLAYER_DEFAULT_CONFIG_MAX = "&amp;showstop=1&amp;showvolume=1&amp;bgcolor=A6B6C6&amp;bgcolor1=A6B6C6&amp;bgcolor2=7387AC&amp;playercolor=7387AC&amp;buttoncolor=254580&amp;buttonovercolor=E9EFFD&amp;slidercolor1=CAD7E1&amp;slidercolor2=A6B6C6&amp;sliderovercolor=E9EFFD&amp;loadingcolor=E9B21A&amp;buffer=5&amp;buffercolor=white&amp;buffershowbg=0&amp;playeralpha=90&amp;playertimeout=500&amp;shortcut=1&amp;showtime=1&amp;showfullscreen=1&amp;showplayer=always&amp;phpstream=0&amp;onclick=playpause&amp;showloading=always";

//Here you have to add the datafield_ids as elements. They will be shown in the standard / extended view on inst_admin.php
$INST_ADMIN_DATAFIELDS_VIEW = array(
    'extended' => array(
    ),
    'default' => array(
    )
);

/*
 * Fields that may not be hidden by users in their privacy settings.
 * Can be configured per permission level.
 * @see lib/edit_about.inc.php in function get_homepage_elements for
 * available fields.
 * Entries look like "'field_name' => true".
 */
$NOT_HIDEABLE_FIELDS = array(
    'user' => array(),
    'autor' => array(),
    'tutor' => array(),
    'dozent' => array(),
    'admin' => array(),
    'root' => array()
);
//Add ids of datafields to use for import on teilnehmer.php
$TEILNEHMER_IMPORT_DATAFIELDS = array('36908df6f81f7401d96856f69e522d20');
