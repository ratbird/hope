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

unregister_globals();
require_once 'lib/statusgruppe.inc.php';
require_once 'lib/user_visible.inc.php';

/* initialise Stud.IP-Session */
page_open (array ("sess" => "Seminar_Session", "auth" => "Seminar_Auth",
    "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check ("autor");
include ('lib/seminar_open.php');


$contactid = Request::option('contactid');
$username = Request::quoted('username');
$groupid = Request::option('groupid');
/* ************************************************************************** *
/*                                                                            *
/* including needed files                                                     *
/*                                                                            *
/* ************************************************************************* */
// if you wanna export a vCard no html-header should be send to the browser
if (!( (Request::submitted('export_vcard'))
    || (!empty($contactid))
    || (!empty($username))
    || (!empty($groupid)) )){
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
elseif (!empty($contactid))
    $mode = "ext_export";
elseif (!empty($username))
    $mode = "ext_export_username";
elseif (!empty($groupid)) 
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
    $contacts = getContactGroupData($groupid);
} elseif ($mode == "ext_export"){
    $contacts = getContactGroupData($contactid,"user");
} elseif ($mode == "ext_export_username"){
    $contacts = getContactGroupData($username,"username");
} elseif ($mode == "ext_export_group"){
    $contacts = getContactGroupData($groupid,"group");
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
        . "    <form action=\"".URLHelper::getURL()."\" method=post>\n"
        . CSRFProtection::tokenTag()
        . "       &nbsp;<select name=\"groupid\" style=\"vertical-align:middle;\">\n";
    // the groups
    for ($i=0;$i<=sizeof($groups)-1;$i++){
        $html .= "        <option value=\"".$groups[$i]["id"]."\">".$groups[$i]["name"]."</option>\n";
    }
    $html .="       </select>\n"
        . Button::create(_('Export'), 'export_vcard', array('title' => _("Diese Gruppe nun exportieren")))
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

    $query = "SELECT statusgruppe_id AS id, name, size 
              FROM statusgruppen
              WHERE range_id = '".$user->id."'
              ORDER BY position ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $groups = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Insert selection "all entries" at the top
    array_unshift($groups, array('id' => 'all', 'name' => _('Alle Einträge des Adressbuches')));

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
function getContactGroupData($exportID, $mode = 'group')
{
    global $user, $_fullname_sql;

    // Prepare inner statement, collects office data
    $query = "SELECT a.sprechzeiten AS consultation_hours, a.raum AS room, a.Telefon AS TEL, a.Fax AS FAX,
                     b.Institut_id AS inst_id, b.Name AS fak_name, b.Strasse AS fak_strasse, b.Plz AS fak_plz,
                     b.url AS fak_url, b.telefon AS fak_TEL, b.email AS fak_mail, b.fax AS fak_FAX
              FROM user_inst AS a
              LEFT JOIN Institute b USING (Institut_id)
              WHERE user_id = ? AND inst_perms != 'user' AND externdefault = ? AND visible = 1";
    $inner_statement = DBManager::get()->prepare($query);

    // Get contacts
    $needle = $exportID;
    if ($mode == 'group' && $exportID != 'all') {
        // the users from one group
        $query = "SELECT statusgruppe_id,
                         user_id AS id, {$_fullname_sql['full']} AS FN,
                         auth_user_md5.username AS NICKNAME, auth_user_md5.Email AS EMAIL,
                         auth_user_md5.Vorname AS gname, auth_user_md5.Nachname AS fname,
                         user_info.Home AS URL, user_info.privatnr AS TEL, user_info.privadr AS ADR,
                         user_info.title_front AS prefix, user_info.title_rear AS suffix
                  FROM statusgruppe_user
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE statusgruppe_id = ?";
    
    } elseif ($mode == 'group') {
        // all contacts from this user
        $query = "SELECT user_id AS id, {$_fullname_sql['full']} AS FN,
                         auth_user_md5.username AS NICKNAME, auth_user_md5.Email AS EMAIL,
                         auth_user_md5.Vorname AS gname, auth_user_md5.Nachname AS fname,
                         user_info.Home AS URL, user_info.privatnr AS TEL, user_info.privadr AS ADR,
                         user_info.title_front AS prefix, user_info.title_rear AS suffix
                  FROM contact
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE owner_id = ?";
        $needle = $user->id;
    } elseif ($mode == 'username') {
        // contact by username
        $query = "SELECT user_id AS id, {$_fullname_sql['full']} AS FN,
                         auth_user_md5.username AS NICKNAME, auth_user_md5.Email AS EMAIL,
                         auth_user_md5.Vorname AS gname, auth_user_md5.Nachname AS fname,
                         user_info.Home AS URL, user_info.privatnr AS TEL, user_info.privadr AS ADR,
                         user_info.title_front AS prefix, user_info.title_rear AS suffix
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  WHERE username = ?";
    } else {
        $query = "SELECT user_id AS id, {$_fullname_sql['full']} AS FN,
                         auth_user_md5.username AS NICKNAME, auth_user_md5.Email AS EMAIL,
                         auth_user_md5.Vorname AS gname, auth_user_md5.Nachname AS fname,
                         user_info.Home AS URL, user_info.privatnr AS TEL, user_info.privadr AS ADR,
                         user_info.title_front AS prefix, user_info.title_rear AS suffix
                  FROM contact
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE contact_id = ?";
    }
    $statement = DBManager::Get()->prepare($query);
    $statement->execute(array($needle));

    $contacts = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if (!get_visibility_by_id($row['id'])) {
            continue;
        }

        $contact = $row;
        $contact['fak'] = array();
        unset($contact['statusgruppe_id']);

        $inner_statement->execute(array($row['id'], 1));
        $temp = $inner_statement->fetchAll(PDO::FETCH_ASSOC);
        $inner_statement->closeCursor();
        
        if (empty($temp)) {
            $inner_statement->execute(array($row['id'], 0));
            $temp = $inner_statement->fetchAll(PDO::FETCH_ASSOC);
            $inner_statement->closeCursor();
        }

        foreach ($temp as $inner_row) {
            $grouppositions = GetRoleNames(GetAllStatusgruppen($inner_row['inst_id'], $row['id']));
            if (is_array($grouppositions)) {
                $inner_row['fak_position'] = implode(', ', $grouppositions);
            } else {
                $inner_row['fak_position'] = null;
            }
            unset($inner_row['inst_id']);
            
            $contact['fak'][] = $inner_row;
        }
        
        $contacts[] = $contact;

        $statusgruppe_id = $row['statusgruppe_id'];
    }

    //geting the groupname
    if ($exportID != 'all') {
        $query = "SELECT name FROM statusgruppen WHERE statusgruppe_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($statusgruppe_id));
        $groupname = $statement->fetchColumn();
    } else {
        $groupname  = _('StudIP-Kontakte');
    }
    $contacts['groupname'] = $groupname;

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

    global $contactid,$username;

    if (empty($contactid) && empty($username))
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
