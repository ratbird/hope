<?
# Lifter002: TODO
/**
 * page_intros.inc.php
 *
 * library for the messages on the pages, contents of the infoboxes and stuff
 * to display
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @copyright   2003-2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     resources
*/

require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/ResourceObject.class.php");
require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/RoomGroups.class.php");


if ($resources_data["actual_object"]) {
    $currentObject = ResourceObject::Factory($resources_data["actual_object"]);
    $currentObjectTitelAdd=": ".(($currentObject->getCategoryName()) ? $currentObject->getCategoryName() : _("Hierachieebene"));
    if ($currentObjectTitelAdd)
        $currentObjectTitelAdd=": ";
    $currentObjectTitelAdd=": ".$currentObject->getName()." (".$currentObject->getOwnerName().")";
}


switch ($view) {
    //Reiter "Uebersicht"
    case "plan":
        $page_intro=_("Auf dieser Seite können Sie sich einen Wochenplan als CSV-Datei ausgeben lassen.");
        $CURRENT_PAGE=_("Spezielle Funktionen");
    break;
    case "regular":
        $page_intro=_("Auf dieser Seite können Sie sich einen Semesterplan als CSV-Datei ausgeben lassen.");
        $CURRENT_PAGE=_("Spezielle Funktionen");
    break;
    case "diff":
        $page_intro=_("Auf dieser Seite können Sie sich die wöchentliche Differenzliste der Belegung aller Räume als CSV-Datei ausgeben lassen.");
        $CURRENT_PAGE=_("Spezielle Funktionen");
    break;

    case "resources":
        $page_intro=_("Auf dieser Seite können Sie durch alle Ressourcen bzw. Ebenen, auf die Sie Zugriff haben, navigieren und Ressourcen verwalten.");
        $CURRENT_PAGE=_("Übersicht der Ressourcen");
        Navigation::activateItem('/resources/view/hierarchy');
    break;
    case "search":
        $page_intro=_("Sie können hier nach Ressourcen suchen. Sie haben die Möglichkeit, über ein Stichwort oder bestimmte Eigenschaften Ressourcen zu suchen oder sich durch die Ebenen zu navigieren.");
        $CURRENT_PAGE=_("Suche nach Ressourcen");
        Navigation::activateItem('/resources/view/hierarchy');

        $infobox = array(
                    array  ("kategorie" => _("Aktionen:"),
                            "eintrag" => array (
                                array   ("icon" => "suchen.gif",
                                    "text"  => (($resources_data["search_mode"] == "browse") || (!$resources_data["search_mode"]))? sprintf(_("Gewünschte Eigenschaften <br />%sangeben%s"), "<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."&mode=properties\">", "</a>") :  sprintf(_("Gewünschte Eigenschaften <br />%snicht angeben%s"), "<a href=\"$PHP_SELF?view=search&quick_view_mode=".$view_mode."&mode=browse\">", "</a>")),
                                array   ("icon" => "meinetermine.gif",
                                    "text"  => (!$resources_data["check_assigns"])? sprintf(_("Gewünschte Belegungszeit %sberücksichtigen%s"), "<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."&check_assigns=TRUE\">", "</a>") :  sprintf(_("Gewünschte Belegungszeit <br />%snicht berücksichtigen%s"), "<a href=\"$PHP_SELF?view=search&quick_view_mode=".$view_mode."&check_assigns=FALSE\">", "</a>")),
                                array   ("icon" => "cont_res5.gif",
                                    "text"  => (!$resources_data["search_only_rooms"])? sprintf(_("Nur Räume %sanzeigen%s"), "<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."&search_only_rooms=1\">", "</a>") :  sprintf(_("Alle Ressourcen %sanzeigen%s"), "<a href=\"$PHP_SELF?view=search&quick_view_mode=".$view_mode."&search_only_rooms=0\">", "</a>")),
                                array("icon" => "blank.gif",
                                    "text"  => "<br /><a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."&reset=TRUE\">".makeButton("neuesuche")."</a>"))));
        $infopic = "rooms.jpg";
        $clipboard = TRUE;
    break;
    //Reiter "Listen"
    case "lists":
    case "export_lists":
    case "search_list":
        if ($resources_data["list_open"])
            $page_intro= sprintf(_("Sie sehen alle Einträge in der Ebene <b>%s</b>"), getResourceObjectName($resources_data["list_open"]));
        $CURRENT_PAGE=_("Bearbeiten und ausgeben von Listen");
        Navigation::activateItem('/resources/lists/show');
        if ($resources_data["list_open"])
            $title.=" - "._("Ebene").": ".getResourceObjectName($resources_data["list_open"]);
        $infobox = array(
                    array  ("kategorie"  => _("Information:"),
                            "eintrag" => array (
                                array ("icon" => "ausruf_small.gif",
                                    "text"  => ($resources_data["list_recurse"]) ? _("Untergeordnete Ebenen werden ausgegeben.") : _("Untergeordnete Ebenen werden <u>nicht</u> ausgegeben.")))),
                    array  ("kategorie" => _("Aktionen:"),
                            "eintrag" => array (
                                array   ("icon" =>  (!$resources_data["list_recurse"]) ? "on_small.gif" : "off_small.gif",
                                    "text"  => ($resources_data["list_recurse"]) ? sprintf(_("Ressourcen in untergeordneten Ebenen %snicht ausgeben%s."), "<a href=\"$PHP_SELF?nrecurse_list=TRUE\">", "</a>") :  sprintf(_("Ressourcen in untergeordneten Ebenen %s(mit) ausgeben%s"), "<a href=\"$PHP_SELF?recurse_list=TRUE\">", "</a>")))));
        $infopic = "rooms.jpg";
    break;

    //Reiter "Objekt"
    case "objects":
    case "edit_object_assign":
        $page_intro=_("Sie sehen hier die Einzelheiten der Belegung. Falls Sie über entsprechende Rechte verfügen, können Sie sie bearbeiten oder eine neue Belegung erstellen.");
        $CURRENT_PAGE=_("Belegungen anzeigen/bearbeiten").$currentObjectTitelAdd;
        Navigation::activateItem('/resources/objects/edit_assign');

        if (($view_mode == "no_nav") || ($view_mode == "search")) {
            $infobox = array(
                        array  ("kategorie" => _("Aktionen:"),
                                "eintrag" => array (
                                    array   ("icon" => "link_intern.gif",
                                        "text"  => "<a href=\"$PHP_SELF?quick_view=view_schedule&quick_view_mode=".$view_mode."\">"._("zurück zum Belegungsplan")."</a>"))));
        }
    break;
    case "edit_object_properties":
        $CURRENT_PAGE=_("Eigenschaften bearbeiten").$currentObjectTitelAdd;
        Navigation::activateItem('/resources/objects/edit_properties');
    break;
    case "edit_object_perms":
        $CURRENT_PAGE=_("Rechte bearbeiten").$currentObjectTitelAdd;
        Navigation::activateItem('/resources/objects/edit_perms');
    break;
    case "view_schedule":
        $page_intro=_("Hier können Sie sich die Belegungszeiten der Ressource anzeigen  und auf unterschiedliche Art darstellen lassen.");
        $CURRENT_PAGE=_("Belegungszeiten ausgeben").$currentObjectTitelAdd;
        Navigation::activateItem('/resources/objects/view_schedule');

        $infobox[0]["kategorie"] = _("Aktionen:");
        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => sprintf (_("%sEigenschaften%s anzeigen"), "<a href=\"$PHP_SELF?quick_view=view_details&quick_view_mode=".$view_mode."\">", "</a>"));
        if (($ActualObjectPerms->havePerm("autor")) && ($currentObject->getCategoryId()))
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>sprintf (_("Eine neue Belegung %serstellen%s"), ($view_mode == "oobj") ? "<a href=\"$PHP_SELF?cancel_edit_assign=1&quick_view=openobject_assign&quick_view_mode=".$view_mode."\">" : "<a href=\"$PHP_SELF?cancel_edit_assign=1&quick_view=edit_object_assign&quick_view_mode=".$view_mode."\">", "</a>"));

        if ($view_mode == "search")
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>"<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."\">"._("zurück zur Suche")."</a>");

        if ($view_mode == "no_nav")
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>"<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."\">"._("zur Ressourcensuche")."</a>");

        if ($view_mode != "search" && $view_mode != "no_nav") {
            if ($SessSemName["class"] == "sem")
                $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                        "text"  => "<a href=\"seminar_main.php\">"._("zurück zur Veranstaltung")."</a>");
            if ($SessSemName["class"] == "inst")
                $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                        "text"  => "<a href=\"institut_main.php\">"._("zurück zur Einrichtung")."</a>");
        }

        if (get_config('RESOURCES_ENABLE_SEM_SCHEDULE'))
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => sprintf (_("%sSemesterplan%s anzeigen"), "<a href=\"$PHP_SELF?quick_view=view_sem_schedule&quick_view_mode=".$view_mode."\">", "</a>"));

        $infobox[0]["eintrag"][] = array (
            "icon" => "link_intern.gif",
            "text"  => "<a href=\"$PHP_SELF?view=view_schedule&print_view=1\" target=\"_blank\">" . _("Druckansicht") . "</a>");
    break;
    case "view_sem_schedule":
        $page_intro=_("Hier können Sie sich die Belegungszeiten der Ressource anzeigen  und auf unterschiedliche Art darstellen lassen.");
        $CURRENT_PAGE=_("Belegungszeiten pro Semester ausgeben").$currentObjectTitelAdd;
        Navigation::activateItem('/resources/objects/view_sem_schedule');

        $infobox[0]["kategorie"] = _("Aktionen:");

        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => sprintf (_("%sEigenschaften%s anzeigen"), "<a href=\"$PHP_SELF?quick_view=view_details&quick_view_mode=".$view_mode."\">", "</a>"));
        if (($ActualObjectPerms->havePerm("autor")) && ($currentObject->getCategoryId()))
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>sprintf (_("Eine neue Belegung %serstellen%s"), ($view_mode == "oobj") ? "<a href=\"$PHP_SELF?cancel_edit_assign=1&quick_view=openobject_assign&quick_view_mode=".$view_mode."\">" : "<a href=\"$PHP_SELF?cancel_edit_assign=1&quick_view=edit_object_assign&quick_view_mode=".$view_mode."\">", "</a>"));

        if ($view_mode == "search")
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>"<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."\">"._("zurück zur Suche")."</a>");

        if ($view_mode == "no_nav"){
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>"<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."\">"._("zur Ressourcensuche")."</a>");
            $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>sprintf (_("%sBelegungsplan%s anzeigen"), ($view_mode == "oobj") ? "<a href=\"$PHP_SELF?quick_view=openobject_schedule&quick_view_mode=".$view_mode."\">" : "<a href=\"$PHP_SELF?quick_view=view_schedule".(($view_mode == "no_nav") ? "&quick_view_mode=no_nav" : "")."\">", "</a>"));

        }
        if ($view_mode != "search" && $view_mode != "no_nav") {
            if ($SessSemName["class"] == "sem")
                $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                        "text"  => "<a href=\"seminar_main.php\">"._("zurück zur Veranstaltung")."</a>");
            if ($SessSemName["class"] == "inst")
                $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                        "text"  => "<a href=\"institut_main.php\">"._("zurück zur Einrichtung")."</a>");
        }

        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => "<a href=\"$PHP_SELF?view=view_sem_schedule&print_view=1\" target=\"_blank\">"
                                            . _("Druckansicht")
                                            . "</a>");
        //$infopic = "schedule.jpg";
    break;
    case "view_group_schedule":
        $room_groups = RoomGroups::GetInstance();
        $page_intro=_("Hier können Sie sich die Belegungszeiten einer Raumgruppe anzeigen lassen.");
        $CURRENT_PAGE=_("Belegungszeiten einer Raumgruppe pro Semester ausgeben:") . ' ' . $room_groups->getGroupName($resources_data['actual_room_group']);
        Navigation::activateItem('/resources/view/group_schedule');

        $infobox[0]["kategorie"] = _("Aktionen:");
        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => "<a href=\"$PHP_SELF?view=view_group_schedule&print_view=1\" target=\"_blank\">"
                                            . _("Druckansicht")
                                            . "</a>");
    break;
    case "view_group_schedule_daily":
        $room_groups = RoomGroups::GetInstance();
        $page_intro=_("Hier können Sie sich die Belegungszeiten einer Raumgruppe anzeigen lassen.");
        $CURRENT_PAGE=_("Belegungszeiten einer Raumgruppe pro Tag ausgeben:") . ' ' . $room_groups->getGroupName($resources_data['actual_room_group']);
        Navigation::activateItem('/resources/view/group_schedule_daily');

        $infobox[0]["kategorie"] = _("Aktionen:");
        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => "<a href=\"$PHP_SELF?view=view_group_schedule_daily&print_view=1\" target=\"_blank\">"
                                            . _("Druckansicht")
                                            . "</a>");
    break;
    //Reiter "Anpassen"
    case "settings":
    case "edit_types":
        $page_intro=_("Verwalten Sie auf dieser Seite die Ressourcen-Typen, wie etwa Räume, Geräte oder Gebäude. Sie können jedem Typ beliebig viele Eigenschaften zuordnen.");
        $CURRENT_PAGE=_("Typen bearbeiten");
        Navigation::activateItem('/resources/settings/edit_types');
    break;
    case "edit_properties":
        $page_intro=_("Verwalten Sie auf dieser Seite die einzelnen Eigenschaften. Diese Eigenschaften können Sie beliebigen Ressourcen-Typen zuweisen.");
        $CURRENT_PAGE=_("Eigenschaften bearbeiten");
        Navigation::activateItem('/resources/settings/edit_properties');
    break;
    case "edit_perms":
        $page_intro=_("Verwalten Sie hier AdministratorInnen des Systems, die Rechte über alle Ressourcen erhalten.");
        $CURRENT_PAGE=_("globale Rechte der Ressourcenadministratoren bearbeiten");
        Navigation::activateItem('/resources/settings/edit_perms');
    break;
    case "edit_settings":
        $page_intro=_("Verwalten Sie hier grundlegende Einstellungen der Ressourcenverwaltung.");
        $CURRENT_PAGE=_("Einstellungen der Ressourcenverwaltung");
        Navigation::activateItem('/resources/settings/edit_settings');
    break;

    //Reiter Raumplanung
    case "requests_start":
        $page_intro=_("Auf dieser Seite wird Ihnen der Status der Anfragen aus Ihren Bereichen angezeigt. Sie können das Bearbeiten der Anfragen von hier aus starten.");
        $CURRENT_PAGE=_("übersicht des Raumplanungs-Status");
        Navigation::activateItem('/resources/room_requests/start');
    break;
    case "edit_request":
        $page_intro=_("Sie können hier die einzelnen Anfragen einsehen und passenden Räume auswählen sowie zuweisen.");
        $CURRENT_PAGE=_("Bearbeiten der Anfragen");
        Navigation::activateItem('/resources/room_requests/edit');
        $infobox = array(
                    array  ("kategorie"  => _("Information:"),
                            "eintrag" => array (
                                array ("icon" => "ausruf_small.gif",
                                    "text"  => ($resources_data["skip_closed_requests"]) ? _("Bereits bearbeitete Anfragen werden <u>nicht</u> angezeigt.") : _("Bereits bearbeitete Anfragen werden weiterhin angezeigt.")))),
                    array  ("kategorie" => _("Aktionen:"),
                            "eintrag" => array (
                                array   ("icon" =>  "link_intern.gif" ,
                                    "text"  =>  "<a href=\"javascript:void(null)\" onClick=\"window.open('resources.php?quick_view=search&quick_view_mode=no_nav','','scrollbars=yes,left=10,top=10,width=1000,height=680,resizable=yes')\" >"._("Ressourcen suchen")."</a>"),
                                array   ("icon" =>  (!$resources_data["skip_closed_requests"]) ? "off_small.gif" : "on_small.gif",
                                    "text"  => ($resources_data["skip_closed_requests"]) ? sprintf(_("Bearbeitete Anfragen %sanzeigen%s."), "<a href=\"$PHP_SELF?skip_closed_requests=FALSE\">", "</a>") :  sprintf(_("Bearbeitete Anfragen %snicht anzeigen%s"), "<a href=\"$PHP_SELF?skip_closed_requests=TRUE\">", "</a>")),
                                array   ("icon" =>  "nachricht1.gif",
                                    "text"  => sprintf(_("Nachrichten zu zugewiesenen Anfragen %sversenden%s."), "<a href=\"$PHP_SELF?snd_closed_request_sms=TRUE\">", "</a>")))));
        $infopic = "rooms.jpg";
        $clipboard = TRUE;
    break;
    case 'list_requests':
        $page_intro = sprintf(_("Sie sehen hier eine Liste aller offenen Anfragen, die Sortierung folgt der Einstellung unter %sübersicht%s."), '<a href="resources.php?view=requests_start&cancel_edit_request_x=1">', '</a>'). '<br>'._("Ein Klick auf das Symbol nebem dem Zähler erlaubt es Ihnen, direkt zu der Anfrage zu springen.");
        $CURRENT_PAGE = _("Anfragenliste");
        Navigation::activateItem('/resources/room_requests/list');
    break;
    //all the intros in an open object (Veranstaltung, Einrichtung)
    case "openobject_main":
        $page_intro=sprintf(_("Auf dieser Seite sehen sie alle der %s zugeordneten Ressourcen."), $SessSemName["art_generic"]);
        $CURRENT_PAGE=$SessSemName["header_line"]." - "._("Ressourcenübersicht");
        Navigation::activateItem('/course/resources/overview');
        $infobox = array(
                    array  ("kategorie"  => _("Information:"),
                            "eintrag" => array (
                                array ("icon" => "ausruf_small.gif",
                                    "text"  => ($perm->have_studip_perm("autor", $SessSemName[1]) ?
                                                (($SessSemName["class"] == "sem") ? _("Als Teilnehmer der Veranstaltung haben Sie die Möglichkeit, diese Ressourcen frei zu belegen oder den Belegungsplan einzusehen.") :
                                                                                _("Als Mitarbeiter der Einrichtung haben Sie die Möglichkeit, diese Ressourcen frei zu belegen oder den Belegungsplan einzusehen.")) :
                                                (($SessSemName["class"] == "sem") ? _("Sie können hier die Details und den Belegungsplan der dieser Veranstaltung zugeordneten Ressourcen einsehen.") :
                                                                                _("Sie können hier den Details und Belegungsplan der dieser Einrichtung zugeordneten Ressourcen einsehen.")))))));
        $infopic = "schedule.jpg";
    break;
    case "openobject_details":
    case "view_details":
        if ($resources_data["actual_object"])
            $page_intro= sprintf(_("Hier sehen Sie detaillierte Informationen der Ressource %s"), "<b>".$currentObject->getName()."</b> (".(($currentObject->getCategoryName()) ? $currentObject->getCategoryName() : _("Hierachieebene")).").");
        if ($view_mode == "oobj") {
            $CURRENT_PAGE=$SessSemName["header_line"]." - "._("Ressourcendetails").$currentObjectTitelAdd;
            Navigation::activateItem('/course/resources/view_details');
        } else {
            $CURRENT_PAGE=_("Anzeige der Ressourceneigenschaften").$currentObjectTitelAdd;
            Navigation::activateItem('/resources/objects/view_details');
        }

        if (($view_mode == "no_nav") || ($view_mode == "search")) {
            $infobox[0]["kategorie"] = _("Aktionen:");

            if (is_object($currentObject)) {
                if ($currentObject->getCategoryId())
                    $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                            "text"  =>sprintf (_("%sBelegungsplan%s anzeigen"), ($view_mode == "oobj") ? "<a href=\"$PHP_SELF?quick_view=openobject_schedule&quick_view_mode=".$view_mode."\">" : "<a href=\"$PHP_SELF?quick_view=view_schedule".(($view_mode == "no_nav") ? "&quick_view_mode=no_nav" : "")."\">", "</a>"));
                if (($ActualObjectPerms->havePerm("autor")) && ($currentObject->getCategoryId()))
                    $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                            "text"  =>sprintf (_("Eine neue Belegung %serstellen%s"), ($view_mode == "oobj") ? "<a href=\"$PHP_SELF?cancel_edit_assign=1&quick_view=openobject_assign&quick_view_mode=".$view_mode."\">" : "<a href=\"$PHP_SELF?cancel_edit_assign=1&quick_view=edit_object_assign&quick_view_mode=".$view_mode."\">", "</a>"));
            }

            if ($view_mode == "no_nav")
                $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>"<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."\">"._("zur Ressourcensuche")."</a>");

            if ($view_mode != "search" && $view_mode != "no_nav") {
                if ($SessSemName["class"] == "sem")
                    $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                            "text"  => "<a href=\"seminar_main.php\">"._("zurück zur Veranstaltung")."</a>");
                if ($SessSemName["class"] == "inst")
                    $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                            "text"  => "<a href=\"institut_main.php\">"._("zurück zur Einrichtung")."</a>");
            }

            if ($view_mode == "search")
                $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                        "text"  =>"<a href=\"$PHP_SELF?quick_view=search&quick_view_mode=".$view_mode."\">"._("zurück zur Suche")."</a>");
        $infopic = "schedule.jpg";
        }
    break;
    case "openobject_schedule":
        if ($resources_data["actual_object"])
            $page_intro=sprintf(_("Hier können Sie sich die Belegungszeiten der Ressource %s ausgeben lassen"), "<b>".$currentObject->getName()."</b> (".$currentObject->getCategoryName().")");
        $CURRENT_PAGE=$SessSemName["header_line"]." - "._("Ressourcenbelegung");
        Navigation::activateItem('/course/resources/view_schedule');
    break;
    case "openobject_assign":
        if ($resources_data["actual_object"])
            $page_intro=sprintf(_("Anzeigen der der Belegung der Ressource %s. Sie können die Belegung auch bearbeiten, falls Sie entsprechende Rechte besitzen, oder eine neue Belegung erstellen."), "<b>".$currentObject->getName()."</b> (".$currentObject->getCategoryName().").");
        $CURRENT_PAGE=$SessSemName["header_line"]." - ".("Belegung anzeigen/bearbeiten");
        Navigation::activateItem('/course/resources/edit_assign');
    break;
    case "openobject_group_schedule":
        $page_intro=_("Hier können Sie sich die Belegungszeiten aller Ressourcen dieser Veranstaltung anzeigen lassen.");
        $CURRENT_PAGE=$SessSemName["header_line"]." - "._("Belegungszeiten aller Ressourcen pro Tag ausgeben");
        Navigation::activateItem('/course/resources/group_schedule');

        $infobox[0]["kategorie"] = _("Aktionen:");
        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                "text"  => "<a href=\"$PHP_SELF?view=openobject_group_schedule&print_view=1\" target=\"_blank\">"
                                            . _("Druckansicht")
                                            . "</a>");
    break;
    case "view_requests_schedule":
        $page_intro=_("Hier können Sie sich eine Übersicht über alle Anfragen und vorhandenenen Belegungen eines angeforderten Raums anzeigen lassen.");
        $CURRENT_PAGE=_("Anfragenübersicht eines Raums:") . ' ' . ResourceObject::Factory($resources_data["resolve_requests_one_res"])->getName();
        Navigation::activateItem('/resources/room_requests/schedule');

        $infobox[0]["kategorie"] = _("Aktionen:");
        $infobox[0]["eintrag"][] = array ("icon" => "link_intern.gif",
                                    "text"  =>  sprintf("<a href=\"javascript:void(null)\" onclick=\"window.open('resources.php?actual_object={$resources_data['resolve_requests_one_res']}&amp;quick_view=view_sem_schedule&amp;quick_view_mode=no_nav','','scrollbars=yes,left=10,top=10,width=1000,height=680,resizable=yes');\">%s</a>", _("Semesterplan")));



    break;
    //default
    default:
        $page_intro=_("Sie befinden sich in der Ressourcenverwaltung von Stud.IP. Sie können hier Räume, Gebäude, Geräte und andere Ressourcen verwalten.");
        $CURRENT_PAGE=_("Übersicht der Ressourcen");
        Navigation::activateItem('/resources/view/hierarchy');
    break;
}

// using the search page not inside the resources
if (Request::get('view_mode') == 'search' OR Request::get('quick_view_mode') == 'search') {
        Navigation::activateItem('/search/resources');
        if(!$perm->have_perm('admin')){
            Navigation::removeItem('/resources');
        }
}

//general naming of resources management pages
if (!$SessSemName) {
    if ($CURRENT_PAGE)
        $CURRENT_PAGE = ": ".$CURRENT_PAGE;
        $CURRENT_PAGE = _("Ressourcenverwaltung").$CURRENT_PAGE;
}