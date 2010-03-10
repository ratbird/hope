<?php
/**
* Displays the details of a requested institute
*
* Parameters received via stdin<br/>
* <code>
*   $session_id
*   $first_name
*   $last_name
*   $user_id
*   $inst_id
*   $institutes_flag
*   $directory_search_pc    (page counter)
*   $institutes_pc          (page counter)
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.12    12.09.2003  14:11:51
* @access       public
* @modulegroup  wap_modules
* @module       show_institute.php
* @package      WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// show_institute.php
// Institute details
// Copyright (c) 2003 Florian Hansen <f1701h@gmx.net>
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

/**
* workaround for PHPDoc
*
* Use this if module contains no elements to document!
* @const PHPDOC_DUMMY
*/
define("PHPDOC_DUMMY", TRUE);

include_once("wap_adm.inc.php");
include_once("wap_txt.inc.php");
include_once("wap_hlp.inc.php");
include_once("wap_buttons.inc.php");
require_once('lib/statusgruppe.inc.php');

$session_user_id = wap_adm_start_card($session_id);

$db = new DB_Seminar;
$current_time = time();

if ($institutes_flag)
    $q_string = "SELECT Strasse, Plz, url, telefon, email, fax ";
else
    $q_string = "SELECT Strasse, Plz ";
$q_string .= "FROM Institute ";
$q_string .= "WHERE Institut_id = '" . $inst_id . "'";
$db->query($q_string);
$db->next_record();

$inst_street = $db->f("Strasse");
$inst_post   = $db->f("Plz");
if ($institutes_flag)
{
    wap_hlp_get_global_user_var($session_user_id, "CurrentLogin");

    $inst_url   = $db->f("url");
    $inst_phone = $db->f("telefon");
    $inst_email = $db->f("email");
    $inst_fax   = $db->f("fax");

    $q_string  = "SELECT COUNT(news_range.news_id) AS num_news ";
   $q_string .= "FROM news_range LEFT JOIN news USING (news_id) ";
    $q_string .= "WHERE news_range.range_id= '" . $inst_id . "' ";
   $q_string .= "AND date < $current_time AND (date + expire) > $current_time ";
   $q_string .= "AND date > $CurrentLogin";
   $db->query($q_string);
   $db->next_record();
   $num_news = $db->f("num_news");
}
else
{
    $q_string  = "SELECT auth_user_md5.user_id, raum, Telefon, Fax, sprechzeiten ";
    $q_string .= "FROM auth_user_md5 LEFT JOIN user_inst ";
    $q_string .= "USING (user_id) WHERE username = '" . $user_name . "' ";
    $q_string .= "AND Institut_id = '" . $inst_id . "'";
    $db->query($q_string);
    $db->next_record();

    $user_room    = $db->f("raum");
    $user_phone  = $db->f("Telefon");
    $user_fax      = $db->f("Fax");
    $user_cons_time = $db->f("sprechzeiten");
    $user_groups    = GetRoleNames(GetAllStatusgruppen ($inst_id, $db->f("user_id")));
}

if ($num_news)
{
    echo "<p align=\"center\">\n";
    echo "<anchor>" . wap_txt_encode_to_wml(_("News")) . "\n";
    echo "  <go method=\"post\" href=\"inst_news.php\">\n";
    echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
    echo "      <postfield name=\"inst_id\" value=\"$inst_id\"/>\n";
    echo "      <postfield name=\"institutes_pc\" value=\"$institutes_pc\"/>\n";
    echo "      <postfield name=\"institutes_flag\" value=\"$institutes_flag\"/>\n";
    echo "  </go>\n";
    echo "</anchor><br/>\n";
    echo "</p>\n";
}

echo "<p align=\"left\">\n";
if ($user_groups)
    echo join(", ", array_values($user_groups)) . "<br/>\n";

if ($user_phone)
{
    echo wap_txt_encode_to_wml(_("Tel:")) . "&#32;";
    echo wap_txt_encode_to_wml($user_phone) . "<br/>\n";
}

if ($user_fax)
{
    echo wap_txt_encode_to_wml(_("Fax:")) . "&#32;";
    echo wap_txt_encode_to_wml($user_fax) . "<br/>\n";
}

if ($inst_phone)
        {
    echo wap_txt_encode_to_wml(_("Tel:")) . "&#32;";
    echo wap_txt_encode_to_wml($inst_phone) . "<br/>\n";
}

if ($inst_fax)
        {
    echo wap_txt_encode_to_wml(_("Fax:")) . "&#32;";
    echo wap_txt_encode_to_wml($inst_fax) . "<br/>\n";
}

if ($inst_email)
    echo wap_txt_encode_to_wml($inst_email) . "<br/>\n";

if ($inst_url)
    echo wap_txt_encode_to_wml($inst_url) . "<br/>\n";

if ($inst_street)
    echo wap_txt_encode_to_wml($inst_street) . "<br/>\n";

if ($inst_post)
    echo wap_txt_encode_to_wml($inst_post) . "<br/>\n";

if ($user_room)
{
    echo wap_txt_encode_to_wml(_("Raum:")) . "&#32;";
    echo wap_txt_encode_to_wml($user_room) . "<br/>\n";
}

if ($user_cons_time)
{
    echo wap_txt_encode_to_wml(_("Sprechzeiten:")) . "<br/>\n";
    echo wap_txt_encode_to_wml($user_cons_time);
}
echo "</p>\n";

echo "<p align=\"right\">\n";
if ($institutes_flag)
{
    echo "<anchor>" . wap_buttons_back() . "\n";
    echo "  <go method=\"post\" href=\"institutes.php\">\n";
    echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
    echo "      <postfield name=\"institutes_pc\" value=\"$institutes_pc\"/>\n";
    echo "  </go>\n";
    echo "</anchor><br/>\n";
}
else
{
    if ($back_to == "show_sms") {
        $postfields_back_to = "     <postfield name=\"sms_id\" value=\"$sms_id\"/>\n";
        $postfields_back_to .= "        <postfield name=\"no_search_link\" value=\"1\"/>\n";
        $postfields_back_to .= "        <postfield name=\"back_to\" value=\"show_sms\"/>\n";
    }
    else
        $postfields_back_to = "     <postfield name=\"directory_search_pc\" value=\"$directory_search_pc\"/>\n";
    
    echo "<anchor>" . wap_buttons_back() . "\n";
    echo "  <go method=\"post\" href=\"show_user.php\">\n";
    echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
    echo "      <postfield name=\"first_name\" value=\"$first_name\"/>\n";
    echo "      <postfield name=\"last_name\" value=\"$last_name\"/>\n";
    echo "      <postfield name=\"user_name\" value=\"$user_name\"/>\n";
    echo $postfields_back_to;
    echo "  </go>\n";
    echo "</anchor><br/>\n";
    
    if (!$no_search_link) {
        echo "<anchor>" . wap_buttons_new_search() . "\n";
        echo "  <go method=\"post\" href=\"directory.php\">\n";
        echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "  </go>\n";
        echo "</anchor><br/>\n";
    }
}

wap_buttons_menu_link ($session_id);
echo "</p>\n";

wap_adm_end_card();
?>
