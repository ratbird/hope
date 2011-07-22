<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* admin_room_requests.php
*
* edit the settings for the admission system
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @module       admin_rooms.php
* @modulegroup      admin
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_admission.php
// Zugangsberechtigungen fuer Veranstaltungen verwalten
// Copyright (C) 2004 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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


require '../lib/bootstrap.php';

if ($RESOURCES_ENABLE) {
    include_once ($RELATIVE_PATH_RESOURCES."/resourcesFunc.inc.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
    include_once ($RELATIVE_PATH_RESOURCES."/lib/RoomRequest.class.php");
}

// temporary link to this help page untill a separate page is available
PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAendernVonZeitenUndTerminen");

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$perm->check("tutor");
if ((!$RESOURCES_ENABLE) || (!$RESOURCES_ALLOW_ROOM_REQUESTS)) //we need resources management and room request ability
    die;

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once('lib/msg.inc.php');    //Ausgaben
require_once('config.inc.php'); //Settings....
require_once 'lib/functions.php';   //basale Funktionen
require_once('lib/visual.inc.php'); //Darstellungsfunktionen
require_once('lib/classes/Seminar.class.php');  //Seminar-class
require_once 'lib/admin_search.inc.php';

// -- here you have to put initialisations for the current page



$db = new DB_Seminar;
$db2 = new DB_Seminar;

$cssSw = new cssClassSwitcher;
$sess->register("admin_rooms_data");

/**
* This function creates a snapshot for all the values the admin_rooms script uses
*
* The function serializes all the data which is used on this page. So you can
* compare an old and a new state of the whole set. It is used to inform the user,
* that the data isn't saved yet.
*
* @preturn  string  all the data in a serialized form
*
*/
function get_snapshot() {
    global $admin_rooms_data;
    return  md5(serialize($admin_rooms_data["resRequest"]));
}


//get ID
if ($SessSemName[1]) {
    $seminar_id=$SessSemName[1];
} else if ($_REQUEST['seminar_id']) {
    $seminar_id = $_REQUEST['seminar_id'];
}

PageLayout::setTitle(getHeaderLine($seminar_id)." -  "._("Raumanfrage"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/dates');
} else {
    Navigation::activateItem('/course/admin/dates');
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
include 'lib/include/admin_search_form.inc.php';

//wenn wir frisch reinkommen, werden benoetigte Daten eingelesen
if ((($seminar_id) || ($termin_id) || ($metadate_id)) && (!$uebernehmen_x) && (!$search_room_x) && (!$reset_room_search_x) && (!$send_room_x)
    && (!$search_properties_x) && (!$select_room_type) && (!$send_room_type_x) && (!$reset_room_type_x)
    && (!$reset_resource_id_x)) {
    $db->query("SELECT admission_turnout FROM seminare WHERE Seminar_id = '$seminar_id' ");
    $db->next_record();
    $admin_rooms_data = array();
    $admin_rooms_data["admission_turnout"] = $db->f("admission_turnout");

    //initialisations for room-requests
    if ($RESOURCES_ENABLE && $RESOURCES_ALLOW_ROOM_REQUESTS) {
        if (isset($termin_id)) {
            $cond = "termin_id = '$termin_id'";
        } elseif (isset($metadate_id)) {
            $cond = "metadate_id = '$metadate_id'";
        } else {
            $cond = "seminar_id = '$seminar_id' AND metadate_id='' AND termin_id=''";
        }

        $db->query("SELECT request_id FROM resources_requests WHERE " . $cond);
        
        $db->next_record();

        if ($db->nf()) {
            $admin_rooms_data["resRequest"] = new RoomRequest($db->f("request_id"));
        } else {
            $admin_rooms_data["resRequest"] = new RoomRequest();
            $admin_rooms_data["resRequest"]->setSeminarId($seminar_id);
            $admin_rooms_data["resRequest"]->setDefaultSeats($admin_rooms_data["admission_turnout"]);
        }

        //if we start with a termin_id, we want to create a request for a single date, so save it!
        if ($termin_id) {
            $admin_rooms_data["resRequest"]->setTerminId($termin_id);
            $db->query("SELECT date, end_time FROM termine WHERE termin_id = '$termin_id' ");
            $db->next_record();
            $admin_rooms_data["date_begin"] = $db->f("date");
            $admin_rooms_data["date_end"] = $db->f("end_time");
        }
        if ($metadate_id) {
            $admin_rooms_data["resRequest"]->setMetadateId($metadate_id);
        }

    }
    $admin_rooms_data["sem_id"] = $seminar_id;
    $admin_rooms_data["original"] = get_snapshot();

//nur wenn wir schon Daten haben kann was zurueckkommen
} else {
    //Sicherheitscheck ob ueberhaupt was zum Bearbeiten gewaehlt ist.
    if (!$admin_rooms_data["sem_id"]) {
        echo "</td></tr></table>";
        die;
    }

    //Room-Requests
    if ($send_room_x)
        $admin_rooms_data["resRequest"]->setResourceId($select_room);
    if ($reset_resource_id_x)
        $admin_rooms_data["resRequest"]->setResourceId(FALSE);
    if ($send_room_type_x)
        $admin_rooms_data["resRequest"]->setCategoryId($select_room_type);
    if ($reset_room_type_x)
        $admin_rooms_data["resRequest"]->setCategoryId(FALSE);

    $admin_rooms_data["resRequest"]->setComment(remove_magic_quotes($comment));

    //Property Requests
    if ($admin_rooms_data["resRequest"]->getCategoryId()) {
        $available_properties = $admin_rooms_data["resRequest"]->getAvailableProperties();
        if (is_array($available_properties)) {
            foreach ($available_properties as $key=>$val) {
                if ($val["system"] == 2) { //it's the property for the seat/room-size!
                    if ($seats_are_admission_turnout)
                        $admin_rooms_data["resRequest"]->setPropertyState($key, $admin_rooms_data["admission_turnout"]);
                    elseif (!$send_room_type_x)
                        $admin_rooms_data["resRequest"]->setPropertyState($key, abs($request_property_val[$key]));
                } else {
                    $admin_rooms_data["resRequest"]->setPropertyState($key, $request_property_val[$key]);
                }
            }
        }
    }

    /*
    if ($admin_rooms_data["resRequest"]->store()) {
        $errormsg.="msgß"._("Die Raumanfragen und gew&uuml;nschte Raumeingenschaften wurden gespeichert");
        $admin_rooms_data["original"] = get_snapshot();
    }
    */

    $semObj = Seminar::getInstance($admin_rooms_data["sem_id"]);
    $hasDates = false;
    if ($semObj->getSingleDates() || $semObj->getCycles()) {
        $hasDates = true;
    }


    //Save changes
    if (($uebernehmen_x) && (!$errormsg)) {
        if (((!$admin_rooms_data["resRequest"]->getSettedPropertiesCount()) && (!$admin_rooms_data["resRequest"]->getResourceId())) || !$hasDates) {
            if(!$hasDates) {
                $errormsg .= "errorß"._('Die Anfrage konnte nicht gespeichert werden,'
                          .  ' da in der Veranstaltung noch keine Termine vorhanden sind!');
            } else {
                $errormsg.="errorß"._("Die Anfrage konnte nicht gespeichert werden, da Sie mindestens einen Raum oder mindestens eine Eigenschaft (z.B. Anzahl der Sitzpl&auml;tze) angeben m&uuml;ssen!");
            }
        } else {
            $admin_rooms_data["resRequest"]->setClosed(0);
            if ($admin_rooms_data["resRequest"]->store()) {
                $errormsg.="msgß"._("Die Raumanfragen und gew&uuml;nschte Raumeigenschaften wurden gespeichert");
                $admin_rooms_data["original"] = get_snapshot();
            }
        }
    }
}

//initiate the seminar-class
$semObj = Seminar::getInstance($admin_rooms_data["sem_id"]);

//load my request, if user is the appropriciate admin too
if ($perm->have_perm("admin"))
    $my_requests = getMyRoomRequests();

    //Output & Forms
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
    <?
    $errormsg.=$infomsg;
    if (isset($errormsg)) {
    ?>
    <tr>
        <td class="blank" colspan=2><br>
        <?parse_msg($errormsg);?>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td class="blank" valign="top">
            <blockquote>
            <b><?=_("Raumanfragen und gew&uuml;nschte Raumeigenschaften") ?></b><br><br>
            <?=_("Sie k&ouml;nnen hier Angaben &uuml;ber einen gew&uuml;nschten Raum und gew&uuml;nschte Raumeigenschaften machen.")?> <br>
            <?
            if ($my_requests[$admin_rooms_data["resRequest"]->getId()])
                printf (_("Sie k&ouml;nnen diese Anfrage auch selbst %saufl&ouml;sen%s."), "<a href=\"resources.php?view=edit_request&single_request=".$admin_rooms_data["resRequest"]->getId()."\">", "</a>");
            else
                print _("Diese Anfragen werden von den zust&auml;ndigen Raumadministratoren bearbeitet. Ihnen wird ein passender Raum f&uuml;r Ihre Veranstaltung zugewiesen."); ?>
            <br><br>
                <a href="<?= UrlHelper::getLink('raumzeit.php?seminar_id='. $seminar_id) ?>">
                    <?= _("Zur¸ck zur Seite Zeiten / R‰ume") ?>
                </a><br>
            <br>
            </blockquote>
        </td>
        <td class="blank" align="right">
            <?= Assets::img("infobox/board2.jpg") ?>
        </td>
    </tr>
    <tr>
    <td class="blank" colspan=2>
    <form method="POST" name="room_requests" action="<? UrlHelper::getLink() ?>#anker" >
        <?= CSRFProtection::tokenTag() ?>
        <table width="99%" border=0 cellpadding=2 cellspacing=0 align="center">
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" align="center" colspan=4>
                <input type="image" name="uebernehmen" <?=makeButton("uebernehmen", "src")?> border=0 value="uebernehmen">
                <? if ($admin_rooms_data["original"] != get_snapshot()) { ?>
                <?= MessageBox::info(_("Diese Daten sind noch nicht gespeichert.")) ?>
                <? } ?>
            </td>
        </tr>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                &nbsp;
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="2">
                <font size="-1">
                <?
                print _("Sie haben die M&ouml;glichkeit, gew&uuml;nschte Raumeigenschaften sowie einen konkreten Raum anzugeben. Diese Raumw&uuml;nsche werden von der zentralen Raumverwaltung bearbeitet.");
                print "<br>"._("<b>Achtung:</b> Um sp&auml;ter einen passenden Raum f&uuml;r Ihre Veranstaltung zu bekommen, geben Sie bitte <u>immer</u> die gew&uuml;nschten Eigenschaften mit an!");
                ?>
            </td>
        </tr>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                &nbsp;
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="47%" valign="top">
                <font size="-1"><b><?=("Art des Wunsches:")?></b><br><br>
                <?
                if ($admin_rooms_data["resRequest"]->getTerminId()) {
                    print _("Einzeltermin der Veranstaltung");
                    print "<br>"._("am:")."&nbsp;".date("d.m.Y, H:i", $admin_rooms_data["date_begin"]).(($admin_rooms_data["date_end"]) ? " - ".date("H:i", $admin_rooms_data["date_end"]) : "");
                } elseif ($admin_rooms_data["resRequest"]->getMetadateId()) {
                    print _("alle Termine einer regelm‰ﬂigen Zeit");
                    print "<br>".SeminarCycleDate::find($admin_rooms_data["resRequest"]->getMetadateId())->toString('full');
                } else {
                    print _("alle regelm‰ﬂigen und unregelm‰ﬂigen Termine der Veranstaltung");
                    print "<br>"._("am:")."&nbsp;".htmlReady($semObj->getFormattedTurnus());
                }
                ?>
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="49%" valign="top">
                <font size="-1"><b><?=("Bearbeitungsstatus:")?></b><br><br>
                <?
                if ($admin_rooms_data["resRequest"]->isNew())
                    print _("Diese Anfrage ist noch nicht gespeichert");
                else
                    print ((!$admin_rooms_data["resRequest"]->getClosed()) ? _("Die Anfrage wurde noch nicht bearbeitet") : (($admin_rooms_data["resRequest"]->getClosed() == 3) ?_("Die Anfrage wurde bearbeitet und abgelehnt") :_("Die Anfrage wurde bearbeitet")))."<br>";

                ?>
            </td>
        </tr>
        <?
        if ($request_resource_id = $admin_rooms_data["resRequest"]->getResourceId()) {
            $resObject = ResourceObject::Factory($request_resource_id);
        ?>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                &nbsp;
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="2">
                <font size="-1"><b><?=("gew&uuml;nschter Raum:")?></b><br><br>
                    <?
                    print "<b>".htmlReady($resObject->getName())."</b>,&nbsp;"._("verantwortlich:")."&nbsp;<a href=\"".$resObject->getOwnerLink()."\">".$resObject->getOwnerName()."</a>";
                    print "&nbsp;&nbsp;<input type=\"IMAGE\" src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\" ".tooltip(_("den ausgew‰hlten Raum lˆschen"))." border=\"0\" name=\"reset_resource_id\">";

                    ?>
                </font>
                <img  src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/grey/info-circle.png"
                    <? echo tooltip(_("Der ausgew‰hlte Raum bietet folgende der w¸nschbaren Eigenschaften:")." \n".$resObject->getPlainProperties(TRUE), TRUE, TRUE) ?>
                >
            </td>
        </tr>
        <?
        }
        ?>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                &nbsp;
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="2">
                <table border="0" width="100%" cellspaceing="2" cellpadding="0">
                    <tr>
                        <td width="49%" valign="top">
                            <font size="-1">
                            <?
                            //$sem_create_data["room_request_type"] = FALSE;
                            print "<b>"._("Raumeigenschaften angeben:")."</b><br><br>";
                            if (!$uebernehmen_x)
                                print "<a name=\"anker\"></a>";
                            $query = "SELECT * FROM resources_categories  WHERE is_room = '1' ORDER BY name";
                            $db->query($query);
                            $room_categories = $db->nf();

                            if (($db->nf() == 1) || ($admin_rooms_data["resRequest"]->getCategoryId())) {
                                if (($db->nf() == 1) && (!$admin_rooms_data["resRequest"]->getCategoryId())) {
                                    $db->next_record();
                                    $category_id = $db->f("category_id");
                                    $admin_rooms_data["resRequest"]->setCategoryId($category_id);
                                } else
                                    $category_id = $admin_rooms_data["resRequest"]->getCategoryId();

                                $query2 = sprintf("SELECT  b.*, c.name AS cat_name FROM resources_categories_properties a LEFT JOIN resources_properties b USING (property_id) LEFT JOIN resources_categories c ON (a.category_id = c.category_id) WHERE c.is_room = '1' AND a.requestable = '1' AND a.category_id = '%s' ORDER BY b.name", $category_id);
                                $db2->query($query2);

                                $i=0;
                                while ($db2->next_record()) {
                                    if (!$i) {
                                        if ($room_categories> 1) {
                                            print ("Gew&auml;hlter Raumtyp:");
                                            print "&nbsp;<select name=\"select_room_type\">";
                                            while ($db->next_record()) {
                                                printf ("<option value=\"%s\" %s>%s </option>", $db->f("category_id"), ($category_id == $db->f("category_id")) ? "selected" : "", htmlReady(my_substr($db->f("name"), 0, 30)));
                                            }
                                            print "</select>";
                                            print "&nbsp;<input type=\"IMAGE\" value=\""._("Raumtyp ausw&auml;hlen")."\" name=\"send_room_type\" src=\"" . Assets::image_path('icons/16/blue/accept.png') . " border=\"0\" ".tooltip(_("Raumtyp ausw‰hlen")).">";
                                            print "&nbsp;&nbsp;<input type=\"IMAGE\" src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\" ".tooltip(_("alle Angaben zur¸cksetzen"))." name=\"reset_room_type\"><br><br>";
                                        }

                                        print _("Folgende Eigenschaften sind w&uuml;nschbar:")."<br><br>";
                                        print "<table border=\"0\" width=\"100%\" cellspaceing=\"2\" cellpadding=\"0\">";
                                    }

                                    ?>
                                    <tr>
                                        <td width="30%" valign="top">
                                            <font size="-1"><?=htmlReady($db2->f("name"))?></font>
                                        </td>
                                        <td width="70%" align ="left" valign="top">
                                        <?
                                        switch ($db2->f("type")) {
                                            case "bool":
                                                printf ("<input type=\"CHECKBOX\" name=\"request_property_val[%s]\" %s><font size=-1>&nbsp;%s</font>", $db2->f("property_id"), ($admin_rooms_data["resRequest"]->getPropertyState($db2->f("property_id"))) ? "checked": "", htmlReady($db2->f("options")));
                                            break;
                                            case "num":
                                                if ($db2->f("system") == 2) {
                                                    printf ("<input type=\"TEXT\" name=\"request_property_val[%s]\" value=\"%s\" size=5 maxlength=10>", $db2->f("property_id"), htmlReady($admin_rooms_data["resRequest"]->getPropertyState($db2->f("property_id"))));
                                                    if ($admin_rooms_data["admission_turnout"]) {
                                                        printf ("<br><input type=\"CHECKBOX\" name=\"seats_are_admission_turnout\" %s>&nbsp;",  (($admin_rooms_data["resRequest"]->getPropertyState($db2->f("property_id")) == $admin_rooms_data["admission_turnout"]) && ($admin_rooms_data["admission_turnout"])>0) ? "checked" :"");
                                                        print "<font size=\"-1\">"._("max. Teilnehmeranzahl &uuml;bernehmen")."</font>";
                                                    }
                                                } else
                                                    printf ("<input type=\"TEXT\" name=\"request_property_val[%s]\" value=\"%s\" size=30 maxlength=255>", $db2->f("property_id"), htmlReady($admin_rooms_data["resRequest"]->getPropertyState($db2->f("property_id"))));
                                            break;
                                            case "text";
                                                printf ("<textarea name=\"request_property_val[%s]\" cols=30 rows=2 >%s</textarea>", $db2->f("property_id"), htmlReady($admin_rooms_data["resRequest"]->getPropertyState($db2->f("property_id"))));
                                            break;
                                            case "select";
                                                $options=explode (";",$db2->f("options"));
                                                printf ("<select name=\"request_property_val[%s]\">", $db2->f("property_id"));
                                                print   "<option value=\"\">--</option>";
                                                foreach ($options as $a) {
                                                    printf ("<option %s value=\"%s\">%s</option>", ($admin_rooms_data["resRequest"]->getPropertyState($db2->f("property_id")) == $a) ? "selected":"", $a, htmlReady($a));
                                                }
                                                printf ("</select>");
                                            break;
                                        }
                                        ?>
                                        </td>
                                    </tr>
                                    <?
                                    $i++;
                                    if ($i == $db2->nf()) {
                                        print "</table>";
                                    }
                                }

                            } elseif (($db->nf() > 0) && (!$admin_rooms_data["resRequest"]->getCategoryId())){
                                print _("Bitte geben Sie zun&auml;chst einen Raumtyp an, der f&uuml;r Sie am besten geeignet ist:")."<br><br>";
                                print "<select name=\"select_room_type\">";
                                    while ($db->next_record()) {
                                        printf ("<option value=\"%s\">%s </option>", $db->f("category_id"), htmlReady(my_substr($db->f("name"), 0, 30)));
                                    }
                                print "</select></font>";
                                print "&nbsp;<input type=\"IMAGE\" value=\""._("Raumtyp ausw&auml;hlen")."\" name=\"send_room_type\" src=\"".Assets::image_path('icons/16/blue/accept.png')."\" ".tooltip(_("Raumtyp ausw‰hlen")).">";
                            }
                            ?>
                            </font>
                        </td>
                        <td width="1px" align="center" valign="top" style="background-image: url('<?= $GLOBALS['ASSETS_URL'] ?>images/line2.gif');" nowrap>
                            &nbsp;&nbsp;
                        </td>
                        <td width="50%" valign="top">
                            <font size="-1">
                            <?
                            print "<b>"._("Raum suchen:")."</b><br>";
                            if ((($search_exp_room) && ($search_room_x)) || ($search_properties_x)) {
                                $result = $admin_rooms_data["resRequest"]->searchRoomsToRequest(remove_magic_quotes($search_exp_room), ($search_properties_x) ? TRUE : FALSE);
                                if ($result) {
                                    printf ("<br><font size=-1><b>%s</b> ".((!$search_properties_x) ? _("R&auml;ume gefunden:") : _("passende R&auml;ume gefunden."))."<br><br>", sizeof($result));
                                    print "<select name=\"select_room\">";
                                    foreach ($result as $key => $val) {
                                        printf ("<option value=\"%s\">%s </option>", $key, htmlReady(my_substr($val, 0, 30)));
                                    }
                                    print "</select></font>";
                                    print "&nbsp;<input type=\"IMAGE\" src=\"" . Assets::image_path('icons/16/blue/accept.png') . "\" ".tooltip(_("Den Raum als Wunschraum ausw‰hlen"))." border=\"0\" name=\"send_room\">";
                                    print "&nbsp;&nbsp;<input type=\"IMAGE\" src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\" ".tooltip(_("neue Suche starten"))." border=\"0\" name=\"reset_room_search\">";
                                    if ($search_properties_x)
                                        print "<br><br>"._("(Diese R&auml;ume erf&uuml;llen die Wunschkriterien, die Sie links angegeben haben.)");
                                }
                            }
                            if (((!$search_exp_room) && (!$search_properties_x)) || (($search_exp_room) && (!$result)) || (($search_properties_x) && (!$result))) {
                                ?>
                                <font size=-1>
                                <? print ((($search_exp_room) || ($search_properties_x)) && (!$result)) ? "<br>"._("<b>Keinen</b> Raum gefunden.")."<br>" : "";?>
                                </font><br>
                                <font size=-1><?=_("Geben Sie zur Suche den Raumnamen ganz oder teilweise ein:"); ?></font>
                                <input type="text" size="30" maxlength="255" name="search_exp_room">
                                <input type="image" src="<?= Assets::image_path('icons/16/blue/search.png') ?>" <? echo tooltip(_("Suche starten")) ?> name="search_room"><br>
                                <?
                            }
                            ?>
                            </font>
                        </td>
                    </tr>
                    <?
                    if ($category_id) {
                    ?>
                    <tr>
                        <td colspan="2" align="right">
                            <font size="-1"><?=("passende R&auml;ume suchen")?></font>
                            <input type="image" src="<?= Assets::image_path('icons/16/yellow/arr_2right.png') ?>" <? echo tooltip(_("passende R‰ume suchen")) ?> name="search_properties">
                        </td>
                        <td>
                            &nbsp;
                        </td>
                    </tr>
                    <?
                    }
                    ?>
                </table>
                </font>

            </td>
        </tr>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                &nbsp;
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="2">
                <font size="-1"><b><?=("Nachricht an den Raumadministrator:")?></b><br><br>
                    <?=_("Sie k&ouml;nnen hier eine Nachricht an den Raumadministrator verfassen, um weitere W&uuml;nsche oder Bemerkungen zur gew&uuml;nschten Raumbelegung anzugeben.")?> <br><br>
                    <textarea name="comment" cols=58 rows=4><?=htmlReady($admin_rooms_data["resRequest"]->getComment()); ?></textarea>
                </font>
            </td>
        </tr>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" align="center" colspan=4>
                <input type="image" name="uebernehmen" <?=makeButton("uebernehmen", "src")?> border=0 value="uebernehmen">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=3>&nbsp;
            </td>
        </tr>
        <?php

?>
    </table>
</td>
</tr>
</table>
<?php
include ('lib/include/html_end.inc.php');
page_close();
