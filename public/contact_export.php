<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * Exports contacts to a vCard file
 *
 * @author      Christian Bauer <alfredhitchcock@gmx.net>
 * @copyright   2003 Stud.IP-Project
 */

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

require_once 'lib/statusgruppe.inc.php';
require_once 'lib/user_visible.inc.php';

/* initialise Stud.IP-Session */
page_open (array ("sess" => "Seminar_Session", "auth" => "Seminar_Auth",
    "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check ("autor");
include ('lib/seminar_open.php');



/* ************************************************************************** *
/*                                                                            *
/* including needed files                                                     *
/*                                                                            *
/* ************************************************************************* */
// if you wanna export a vCard no html-header should be send to the browser
if (!( (Request::submitted('export_vcard'))
    || (isset($_GET["contactid"]))
    || (isset($_GET["username"]))
    || (isset($_GET["groupid"])) )){
    PageLayout::setTitle(_("Adressbuch exportieren"));
    Navigation::activateItem('/community/contacts/export');
    // add skip link
    SkipLinks::addIndex(Navigation::getItem('/community/contacts/export')->getTitle(), 'main_content', 100);

    require_once('lib/include/html_head.inc.php');
    require_once('lib/include/header.php');
}
/* **END*of*initialize*post/get*variables*********************************** */

/* ************************************************************************** *
/*                                                                            *
/* identify the current site-mode                                             *
/*                                                                            *
/* ************************************************************************* */
if (Request::submitted('export_vcard'))
    $mode = "export_vcard";
elseif (isset($_GET["contactid"]))
    $mode = "ext_export";
elseif (isset($_GET["username"]))
    $mode = "ext_export_username";
elseif (isset($_GET["groupid"]))
    $mode = "ext_export_group";
else
    $mode = "select_group";
/* **END*of*identify*the*current*site-mode*********************************** */


/* ************************************************************************** *
/*                                                                            *
/* collecting the data                                                        *
/*                                                                            *
/* ************************************************************************* */
if ($mode == "select_group"){
    // creats the content for the infobox
    $infobox = array (
        array ("kategorie"  => "Information:",
            "eintrag" => array  (
                array ( "icon" => "icons/16/black/info.png",
                    "text"  => _("Bitte wählen Sie eine bestimme Gruppe Ihres Adressbuches oder Ihr vollständiges Adressbuch und drücken anschließend auf 'Export'.")
                ),
            )
        ),
    );

    $groups = getContactGroups();
} elseif ($mode == "export_vcard"){
    $contacts = getContactGroupData($_POST["groupid"]);
} elseif ($mode == "ext_export"){
    $contacts = getContactGroupData($_GET["contactid"],"user");
} elseif ($mode == "ext_export_username"){
    $contacts = getContactGroupData($_GET["username"],"username");
} elseif ($mode == "ext_export_group"){
    $contacts = getContactGroupData($_GET["groupid"],"group");
}



/* **END*of*collecting*the*data********************************************* */

/* ************************************************************************** *
/*                                                                            *
/* displays the site                                                          *
/*                                                                            *
/* ************************************************************************* */
if ($mode == "select_group"){

    printSiteTitle();
    printSelectGroup($infobox,$groups);

} elseif (($mode == "export_vcard")
    || ($mode == "ext_export")
    || ($mode == "ext_export_username")
    || ($mode == "ext_export_group") ){

    exportVCard($contacts);

}
page_close ();
/* **END*of*displays*the*site*********************************************** */


/* ************************************************************************** *
/*                                                                            *
/* private functions                                                          *
/*                                                                            *
/* ************************************************************************* */

/* ************************************************************************** *
/* html-output                                                                *
/* ************************************************************************* */

/**
 * displays the site title
 *
 * @access  private
 *
 */
function printSiteTitle(){
    $html = "";
    echo $html;
}

/**
 * displays the semester selection page
 *
 * @access  private
 * @param   array $infobox      the infobox for this site
 * @param   array $semestersAR  the array with the semesters to select
 *
 */
function printSelectGroup($infobox, $groups)
{
    $html = "<table border=\"0\" class=\"blank\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n"
        . " <tr valign=\"top\">\n"
        . "  <td class=\"blank\" valign=\"top\">\n"
        . "  <table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n"
        . "  <tr>"
        . "   <td valign=\"top\" id=\"main_content\"><font size=\"-1\">\n"
        . _("Bitte wählen Sie ein Gruppe aus, deren Daten Sie in eine vCard-Datei exportieren möchten:")."\n"
        . "    <form action=\"$PHP_SELF\" method=post>\n"
        . CSRFProtection::tokenTag()
        . "       &nbsp;<select name=\"groupid\" style=\"vertical-align:middle;\">\n";
    // the groups
    for ($i=0;$i<=sizeof($groups)-1;$i++){
        $html .= "        <option value=\"".$groups[$i]["id"]."\">".$groups[$i]["name"]."</option>\n";
    }
    $html .="       </select>\n"
        . Button::create(_('export'), 'export_vcard', array('title' => _("Diese Gruppe nun exportieren")))
        . "      </form>\n"
        . "   </font></td>\n"
        . "   <td align=\"right\" width=\"270\" valign=\"top\">\n";
    echo $html;
    print_infobox($infobox, "infobox/export.jpg");
    $html = "     </td>\n"
        . "  </tr>\n"
        . " </table>\n"
        . "  <br></td>\n"
        . " </tr>\n"
        . "</table>\n";
    echo $html;

    include('lib/include/html_end.inc.php');
    page_close();
}


/* ************************************************************************** *
/* db-requests                                                                *
/* ************************************************************************* */

/**
 * collects the contactgroups from user
 *
 * @access  private
 * @returns array the contact groups
 *
 */
function getContactGroups(){
    global $user;

    // all contacts
    $groups[0] = array ("id" => "all", "name" => _("Alle Einträge des Adressbuches"));

    $db=new DB_Seminar;

    $query = "SELECT name, statusgruppe_id, size "
        . "FROM statusgruppen "
        . "WHERE range_id = '".$user->id."' "
        . "ORDER BY position ASC";

    $db->query ($query);

    $i = 1;
    while ($db->next_record()){
        $groups[$i] = array(
            "id" => $db->f("statusgruppe_id"),
            "name" => $db->f("name"),
            "size" => $db->f("size")
            );
        $i++;
    }

    return $groups;
}

/**
 * collects the data from one contactgoup or all contacts
 *
 * @access  private
 * @param   string $groupID the selected group
 * @returns array the contact group data
 *
 */
function getContactGroupData($exportID,$mode = "group"){
    global $user, $_fullname_sql;

    $db = new DB_Seminar;
    $db2 = new DB_Seminar;

    // the users from one group
    if (($mode == "group") && ($exportID != "all")){
        $query = "SELECT statusgruppe_user.user_id, statusgruppe_user.statusgruppe_id, "
            . $_fullname_sql['full'] . " AS fullname , auth_user_md5.username, auth_user_md5.Email, auth_user_md5.Vorname, auth_user_md5.Nachname, "
            . "user_info.Home, user_info.privatnr, user_info.privadr, user_info.title_front, user_info.title_rear "
            . "FROM statusgruppe_user "
            . "LEFT JOIN auth_user_md5 USING(user_id) "
            . "LEFT JOIN user_info USING (user_id) "
            . "WHERE statusgruppe_id = '$exportID'";

    // all contacts from this user
    } elseif ($mode == "group") {
        $query = "SELECT contact.user_id, "
            . $_fullname_sql['full'] . " AS fullname , auth_user_md5.username, auth_user_md5.Email, auth_user_md5.Vorname, auth_user_md5.Nachname, "
            . "user_info.Home, user_info.privatnr, user_info.privadr, user_info.title_front, user_info.title_rear "
            . "FROM contact "
            . "LEFT JOIN auth_user_md5 USING(user_id) "
            . "LEFT JOIN user_info USING (user_id) "
            . "WHERE owner_id = '".$user->id."'";
    // contact by username
    } elseif ($mode == "username") {
        $query = "SELECT auth_user_md5.user_id, "
            . $_fullname_sql['full'] . " AS fullname , auth_user_md5.Email, auth_user_md5.username, auth_user_md5.Vorname, auth_user_md5.Nachname, "
            . "user_info.Home, user_info.privatnr, user_info.privadr, user_info.title_front, user_info.title_rear "
            . "FROM auth_user_md5 "
            . "LEFT JOIN user_info USING (user_id) "
            . "WHERE username = '".$exportID."'";
    } else {
        $query = "SELECT contact.user_id, "
            . $_fullname_sql['full'] . " AS fullname , auth_user_md5.username, auth_user_md5.Email, auth_user_md5.Vorname, auth_user_md5.Nachname, "
            . "user_info.Home, user_info.privatnr, user_info.privadr, user_info.title_front, user_info.title_rear "
            . "FROM contact "
            . "LEFT JOIN auth_user_md5 USING(user_id) "
            . "LEFT JOIN user_info USING (user_id) "
            . "WHERE contact_id = '".$exportID."'";
    }

    $db->query($query);

    $i = 0;
    while ($db->next_record()){
        if (get_visibility_by_id($db->f("user_id"))) {
            $contacts[$i] = array(
                    "id" => $db->f("user_id"),
                    "FN" => $db->f("fullname"),
                    "NICKNAME" => $db->f("username"),
                    "URL" => $db->f("Home"),
                    "TEL" => $db->f("privatnr"),
                    "ADR" => $db->f("privadr"),
                    "EMAIL" => $db->f("Email"),
                    "gname" => $db->f("Vorname"),
                    "fname" => $db->f("Nachname"),
                    "prefix" => $db->f("title_front"),
                    "suffix" => $db->f("title_rear")
                    );

            // collecting the office data
            $query = "SELECT a.*, "
                . "b.Institut_id as inst_id, b.Name as fak_name, b.Strasse as fak_strasse, b.Plz as fak_plz, "
                . "b.url as fak_url, b.telefon as fak_TEL, b.email as fak_mail, b.fax as fak_FAX "
                . "FROM user_inst a "
                . "LEFT JOIN Institute b USING (Institut_id) "
                . "WHERE user_id = '".$contacts[$i]["id"]."' AND inst_perms != 'user'"
                . "AND externdefault = 1 "
                . "AND visible = 1";
            $db2->query($query);

            // if externdefault isn't set
            if ($db2->num_rows() == 0) {
                $query = "SELECT a.*, "
                . "b.Institut_id as inst_id, b.Name as fak_name, b.Strasse as fak_strasse, b.Plz as fak_plz, "
                . "b.url as fak_url, b.telefon as fak_TEL, b.email as fak_mail, b.fax as fak_FAX "
                . "FROM user_inst a "
                . "LEFT JOIN Institute b USING (Institut_id) "
                . "WHERE user_id = '".$contacts[$i]["id"]."' AND inst_perms != 'user' "
                . "AND visible = 1 ORDER BY priority ASC";
                $db2->query($query);
            }

            $j = 0;

            while ($db2->next_record()){
                $grouppositions = GetRoleNames(GetAllStatusgruppen($db2->f("inst_id"),$contacts[$i]["id"]));
                if (is_array($grouppositions)){
                    $positions_tmp = array_values($grouppositions);
                    $positions = $positions_tmp[0];
                    for ($k=1;$k<sizeof($positions_tmp);$k++){
                        $positions .= ", ".$positions_tmp[$k];
                    }
                }
                else
                    $positions = NULL;

                $contacts[$i]["fak"][$j] = array(
                        "fak_name" => $db2->f("fak_name"),
                        "consultation_hours" => $db2->f("sprechzeiten"),
                        "room" => $db2->f("raum"),
                        "TEL" => $db2->f("Telefon"),
                        "FAX" => $db2->f("Fax"),
                        "fak_strasse" => $db2->f("fak_strasse"),
                        "fak_plz" => $db2->f("fak_plz"),
                        "fak_url" => $db2->f("fak_url"),
                        "fak_TEL" => $db2->f("fak_TEL"),
                        "fak_mail" => $db2->f("fak_mail"),
                        "fak_FAX" => $db2->f("fak_FAX"),
                        "fak_position" => $positions
                        );
                //              print "TEL: ".$contacts[$i]["fak"][$j]["TEL"]."<br>";
                $j++;
            }
            $statusgruppe_id = $db->f("statusgruppe_id");
            $i++;
        }
    }

    //geting the groupname
    if ($exportID != "all"){
        $query = "SELECT name FROM statusgruppen WHERE statusgruppe_id = '".$statusgruppe_id."'";
        $db->query($query);
        $db->next_record();
        $groupname = $db->f("name");
    } else {
        $groupname  = _("StudIP-Kontakte");
    }
    $contacts["groupname"] = $groupname;
    return $contacts;
}

/**
 * collects the data from one contactgoup or all contacts
 *
 * @access  private
 * @param   string $groupID the selected group
 * @returns array the contact group data
 *
 */
function exportVCard($contacts){

    if (!isset($_GET["contactid"]) && !isset($_GET["username"]))
        $filename = $contacts["groupname"];
    else
        $filename = $contacts[0]["NICKNAME"];

    header("Content-type: text/x-vCard"); //application/octet-stream MIME
    header("Content-disposition: attachment; filename=".$filename.".vcf");
    header("Pragma: private");

    $br = "=0D=0A";

    for ($i=0;$i<=sizeof($contacts)-2;$i++){

        $vcard .= "BEGIN:VCARD\r\n"
            . "VERSION:2.1\r\n";

        // the full name
        $vcard .= "FN:".$contacts[$i]["FN"]."\r\n";

        // the name in parts
        $vcard .= "N:";
            //Family Name
            $vcard .= $contacts[$i]["fname"];
            $vcard .= ";";
            //Given Name
            $vcard .= $contacts[$i]["gname"];
            $vcard .= ";";
            //no Additional Name in stud.ip
            $vcard .= ";";
            //Honorific Prefix
            $vcard .= $contacts[$i]["prefix"];
            $vcard .= ";";
            //Honorific Suffix
            $vcard .= $contacts[$i]["suffix"];
            $vcard .= ";";
            //closing this entry
            $vcard .= "\r\n";

        // the nick-name: 'NICKNAME:'

        // the private adress
        $vcard .= "ADR;HOME:;;";
        $vcard .= $contacts[$i]["ADR"];
        $vcard .= "\r\n";

        // the private phone
        $vcard .= "TEL;HOME:";
        $vcard .= $contacts[$i]["TEL"];
        $vcard .= "\r\n";

        // the e-mail
        $vcard .= "EMAIL;INTERNET:";
        $vcard .= $contacts[$i]["EMAIL"];
        $vcard .= "\r\n";

        // the private url
        $vcard .= "URL:";
        $vcard .= $contacts[$i]["URL"];
        $vcard .= "\r\n";

        // work data

        // if there is any workplace
        if (sizeof($contacts[$i]["fak"]) > 0){
            // the work adress
            $vcard .= "ADR;WORK:";
            //name
//          if ($contacts[$i]["fak"][0]["fak_name"]){
//              $vcard .= $contacts[$i]["fak"][0]["fak_name"];
//          }
            $vcard .= ";";
            if ($contacts[$i]["fak"][0]["room"]){
                $vcard .= $contacts[$i]["fak"][0]["room"];
            }
            $vcard .= ";";
            if ($contacts[$i]["fak"][0]["fak_strasse"]){
                $vcard .= $contacts[$i]["fak"][0]["fak_strasse"];
            }
            $vcard .= ";";
            if ($contacts[$i]["fak"][0]["fak_plz"]){
                $vcard .= $contacts[$i]["fak"][0]["fak_plz"];
            }
//          $vcard .= ";";
//          if ($contacts[$i]["fak"][0]["consultation_hours"])
//              $vcard .= $contacts[$i]["fak"][0]["consultation_hours"];
            $vcard .= "\r\n";

            // the position
            if ($contacts[$i]["fak"][0]["fak_position"]){
                $vcard .= "TITLE:"
                    . $contacts[$i]["fak"][0]["fak_position"]
                    . "\r\n";
            }

            // the work org
            $vcard .= "ORG;WORK:";
            if ($contacts[$i]["fak"][0]["fak_name"]){
                $vcard .= $contacts[$i]["fak"][0]["fak_name"];
//              $vcard .= ",";
            }
//          if ($contacts[$i]["fak"][0]["room"]){
//              $vcard .= $contacts[$i]["fak"][0]["room"];
//              $vcard .= ",";
//          }
//          if ($contacts[$i]["fak"][0]["consultation_hours"])
//              $vcard .= $contacts[$i]["fak"][0]["consultation_hours"];
            $vcard .= "\r\n";

            // the work phone
            $vcard .= "TEL;WORK:";
            $vcard .= $contacts[$i]["fak"][0]["TEL"];
            $vcard .= "\r\n";

            // the work fax
            $vcard .= "TEL;WORK;FAX:";
            $vcard .= $contacts[$i]["fak"][0]["FAX"];
            $vcard .= "\r\n";

            // the work url
            $vcard .= "URL;WORK:";
            $vcard .= $contacts[$i]["fak"][0]["fak_url"];
            $vcard .= "\r\n";

            // the consulting hours
            $vcard .= "LABEL;WORK;ENCODING=QUOTED-PRINTABLE:";
            $vcard .= _("Sprechstunde: ");
            $vcard .= $contacts[$i]["fak"][0]["consultation_hours"];
            $vcard .= "\r\n";
        }
        // if there are more than one workplace
        if (sizeof($contacts[$i]["fak"]) > 1){
            $vcard .= "NOTE;"
                . "ENCODING=QUOTED-PRINTABLE:";
            $vcard .= _("Weitere Arbeitsplaetze").": ".$br;
            for ($j=1;$j<=sizeof($contacts[$i]["fak"])-1;$j++){
                // the work adress
                $vcard .= $contacts[$i]["fak"][$j]["fak_name"];
                $vcard .= $br;

                if ($contacts[$i]["fak"][$j]["fak_position"]){
                    $vcard .= _("Position").": ";
                    $vcard .= $contacts[$i]["fak"][$j]["fak_position"];
                    $vcard .= $br;
                }
                if ($contacts[$i]["fak"][$j]["room"]){
                    $vcard .= _("Raum").": ";
                    $vcard .= $contacts[$i]["fak"][$j]["room"];
                    $vcard .= $br;
                }
                if ($contacts[$i]["fak"][$j]["consultation_hours"]){
                    $vcard .= _("Sprechstunde").": ";
                    $vcard .= $contacts[$i]["fak"][$j]["consultation_hours"];
                    $vcard .= $br;
                }

                // the work phone
                if ($contacts[$i]["fak"][$j]["TEL"]){
                    $vcard .= "Tel: ";
//                  $vcard .= "TEL;TYPE=work:";
                    $vcard .= $contacts[$i]["fak"][$j]["TEL"];
                    $vcard .= $br;
                }

                // the work fax
                if ($contacts[$i]["fak"][$j]["FAX"]){
                    $vcard .= "Fax: ";
//                  $vcard .= "TEL;TYPE=work,fax:";
                    $vcard .= $contacts[$i]["fak"][$j]["FAX"];
                    $vcard .= $br;
//                  $vcard .= "";
                }
                if (sizeof($contacts[$i]["fak"])-1 > $j+1)
                    $vcard .= "---".$br;
            }
            $vcard .= "\r\n";
        }


        // the revisions and closing this entry
        $vcard .= "REV:".date("Y-m-d")."T".date("H:i:s")."Z\r\n"
            . "END:VCARD\r\n";

    }
    echo $vcard;
/*  global $TMP_PATH;
    $tempfile = tempnam($TMP_PATH."/export/","vcard");
    $file = fopen($tempfile,"w");
    fwrite($file,$vcard);
    fclose($file);
*/
}
?>
