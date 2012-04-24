<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * online.php - Anzeigemodul fuer Personen die Online sind
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <andre.noack@gmx.net>
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl.html GPL Licence 3
 */

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

unregister_globals();
page_open(array(
    "sess" => "Seminar_Session",
    "auth" => "Seminar_Auth",
    "perm" => "Seminar_Perm",
    "user" => "Seminar_User"
));
$perm->check("user");

// Imports
require_once 'lib/functions.php';
require_once 'lib/msg.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/contact.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/classes/Avatar.class.php';
require_once 'lib/classes/StudipKing.class.php';

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

if (get_config('CHAT_ENABLE'))
{
    include_once $RELATIVE_PATH_CHAT.'/chat_func_inc.php';
    $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
    $chatServer->caching = true;
}

$msging=new messaging;
$cssSw=new cssClassSwitcher;

PageLayout::setHelpKeyword("Basis.InteraktionWhosOnline");
PageLayout::setTitle(_("Wer ist online?"));
Navigation::activateItem('/community/online');
// add skip link
SkipLinks::addIndex(_("Wer ist Online?"), 'main_content', 100);

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

ob_start();

$kompletter_datensatz= get_users_online(5, $user->cfg->getValue("ONLINE_NAME_FORMAT"));
$alle=count($kompletter_datensatz);
/*
 * Start to filter
 */

//Only use visible users
$visible_users=array();
$myDomains = UserDomain::getUserDomainsForUser($user->id);

foreach($kompletter_datensatz as $key=>$val){
    $val['username'] = $key;
    $global_visibility = get_global_visibility_by_id($val['userid']);
    $domains = UserDomain::getUserDomainsForUser($val['userid']);
    if (count($domains) && $global_visibility == 'yes') {
        if (array_intersect($myDomains, $domains) && $val['is_visible']) {
            $visible_users[$key]=$val;
        }
    } else {
        if($val['is_visible']){
            $visible_users[$key]=$val;
        }
    }
}

if (Request::option('cmd')=="add_user") {
    $msging->add_buddy (Request::quoted('add_uname'));
    $visible_users[Request::quoted('add_uname')]['is_buddy'] = true;
}

if (Request::option('cmd')=="delete_user"){
    $msging->delete_buddy (Request::quoted('delete_uname'));
    $visible_users[Request::quoted('delete_uname')]['is_buddy'] = false;
}

//now seperate the buddies from the others
$filtered_buddies=array();
$others=array();

foreach($visible_users as $key=>$val){
    if($val['is_buddy']){
        $filtered_buddies[$key]=$val;
    } else {
        $others[$key]=$val;
    }
}

$user_count = count($others);
$weitere = $alle - count($filtered_buddies) - $user_count;

$page = Request::int('page', 1);

if($page < 1 || $page > ceil($user_count/25)) $page = 1;

//Slice the array to limit data
$other_users = array_slice($others,($page-1) * 25, 25);

if (GetNumberOfBuddies()) {

    if ($_REQUEST['newmsgset'] == '' && $_REQUEST['messaging_cmd'] == 'change_view_insert'){
        if ($_REQUEST['show_only_buddys'] == 1){
            $my_messaging_settings["show_only_buddys"] = true;
        }else{
            $my_messaging_settings["show_only_buddys"] = false;
        }
    }
    if ($my_messaging_settings["show_only_buddys"])
        $checked = " checked";

    $newInfoboxPart = array("kategorie" => _("Einstellung:"),
        "eintrag" => array(
            array(
                  "text" => _("<form action=\"".$PHP_SELF."?messaging_cmd=change_view_insert\" method=\"post\"><input type=\"checkbox\" id=\"show_only_buddys\" name=\"show_only_buddys\" $checked value=\"1\">
                  Nur Buddies in der &Uuml;bersicht der aktiven Benutzer anzeigen.".Button::create(_("Übernehmen"), 'newmsgset', array('messaging_cmd' => 'change_view_insert', 'titel' => _("Änderungen übernehmen")))."</form>")
            )
        )
    );

}else{
    $newInfoboxPart = array();
}?>

<div id="layout_container">
    <div id="layout_sidebar">
    <?
    $infobox = array(
        array("kategorie" => _("Information:"),
                "eintrag" => array(
                    array("icon" => 'icons/16/black/info.png',
                          "text"  => _("Hier können Sie sehen, wer au&szlig;er Ihnen im Moment online ist.")
                    ),
                    array("icon" => 'icons/16/black/mail.png',
                          "text"  => _("Sie können diesen Benutzern eine Nachricht schicken oder sie zum Chatten einladen.")
                    ),
                    array("icon" => 'icons/16/black/person.png',
                          "text"  => _("Wenn Sie auf den Namen klicken, kommen Sie zur Homepage des Benutzers.")
                    )
            )
        ), $newInfoboxPart
    );

    print_infobox($infobox, 'infobox/online.jpg');
    #if ($SessSemName[0] && $SessSemName["class"] == "inst")
    #    echo "<br><br><a href=\"institut_main.php\">" . _("Zur&uuml;ck zur ausgew&auml;hlten Einrichtung") . "</a>";
    #elseif ($SessSemName[0])
    #    echo "<br><br><a href=\"seminar_main.php\">" . _("Zur&uuml;ck zur ausgew&auml;hlten Veranstaltung") . "</a>";
    ?>
    </div>
    <div id="layout_content">
<? if ($_SESSION['sms_msg']) {
    parse_msg($_SESSION['sms_msg']);
    unset($_SESSION['sms_msg']);
} ?>
    <?
ob_end_flush();
ob_start();
    //Erzeugen der Liste aktiver und inaktiver Buddies
    $different_groups=FALSE;

    $owner_id = $user->id;

    foreach ($filtered_buddies as $username => $value) { //alle durchgehen die online sind
        $user_id = $value['user_id'];

        $query = "SELECT statusgruppen.position, name, statusgruppen.statusgruppe_id "
               . "FROM statusgruppen "
               .   "LEFT JOIN statusgruppe_user USING (statusgruppe_id) "
               . "WHERE range_id = ? AND user_id = ? "
               . "ORDER BY statusgruppen.position ASC "
               . "LIMIT 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($owner_id, $user_id));

        if ($row = $statement->fetch(PDO::FETCH_ASSOC)) { // er ist auch einer Gruppe zugeordnet
            $group_buddies[] = array(
                $row['position'],
                $row['name'],
                $filtered_buddies[$username]['name'],
                $filtered_buddies[$username]['last_action'],
                $username,
                $row['statusgruppe_id'],
                $user_id
            );
        } else { // buddy, aber keine Gruppe
            $non_group_buddies[] = array(
                $filtered_buddies[$username]['name'],
                $filtered_buddies[$username]['last_action'],
                $username,
                $user_id
            );
        }
    }

    foreach($other_users as $username=>$value) {
        $user_id = $value["user_id"];
            $n_buddies[]=array($other_users[$username]["name"],$other_users[$username]["last_action"],$other_users[$username]["username"],$user_id);
    }


 if (is_array($group_buddies))
    sort ($group_buddies);

if (is_array($non_group_buddies))
    sort ($non_group_buddies);

    $cssSw->switchClass();
    //Anzeige
?>
    <table class="default" id="main_content">
        <tr>

<?  //Kopfzeile
    if ($my_messaging_settings["show_only_buddys"])
        echo "\n<td class=\"topic\" width=\"50%\" align=\"center\"><b>" . _("Kontakte") . "</b></td></tr>\n";
    else
        echo "\n<td class=\"topic\" width=\"50%\" align=\"center\"><b>" . _("Kontakte") . "</b></td><td class=\"topic\" width=\"50%\" align=\"center\"><b>" . _("andere Nutzer") . "</b></td></tr>\n";
    echo "<tr>";

    //Buddiespalte

    // Nutzer hat gar keine buddies
    if (!GetNumberOfBuddies()) { ?>
        <td width="50%" valign="top">
            <table width="100%" cellspacing="0" cellpadding="1" border="0">
                <tr>
                    <td class="steel1" width="50%" align="center">
                            <?= _("Sie haben keine Buddies ausgew&auml;hlt.") ?>
                            <br>
                            <? printf(_("Zum Adressbuch (%d Eintr&auml;ge) klicken Sie %shier%s"),
                                      GetSizeofBook(),
                                      "<a href=\"contact.php\">", "</a>") ?>
                    </td>
                </tr>
            </table>
        </td>

    <? } else { // nutzer hat prinzipiell buddies ?>

        <td width="50%" valign="top">
            <table width="100%" cellspacing="0" cellpadding="1" border="0">
                <? if ($group_buddies || $non_group_buddies) { ?>
                    <tr>
                        <th colspan="3" width="65%">
                            <?= _("Name") ?>
                        </th>
                        <th width="20%" colspan="4">
                            <?= _("letztes Lebenszeichen") ?>
                        </th>
                    </tr>
                <? } else { // gar keine Buddies online ?>
                    <tr>
                        <td class="steel1" width="50%" align="center" colspan="7">
                            <?= _("Es sind keine Ihrer Buddies online.") ?>
                        </td>
                    </tr>
                <? } ?>


                <? if (sizeof($group_buddies)) {
                    reset ($group_buddies);
                    $lastgroup = "";
                    $groupcount = 0;
                    $template = $GLOBALS['template_factory']->open('online/user');
                    while (list($index)=each($group_buddies)) {
                        list($position,$gruppe,$fullname,$zeit,$tmp_online_uname,$statusgruppe_id,$tmp_user_id)=$group_buddies[$index];
                        //list($fullname, $zeit, $tmp_online_uname, $tmp_user_id) = $n_buddies[$index];
                        if ($gruppe != $lastgroup) {// Ueberschrift fuer andere Gruppe
                            printf("\n<tr><td colspan=\"7\" align=\"middle\" class=\"steelkante\"><a href=\"contact.php?view=gruppen&filter=%s\"><font size=\"2\" color=\"#555555\">%s</font></a></td></tr>",$statusgruppe_id, htmlready($gruppe));
                            $groupcount++;
                            if ($groupcount > 10) //irgendwann gehen uns die Farben aus
                                $groupcount = 1;
                        }
                        $lastgroup = $gruppe;
                        $args = compact('fullname', 'zeit', 'tmp_online_uname', 'tmp_user_id');
                        $args['gruppe'] = "gruppe$groupcount";
                        $args['is_buddy'] = TRUE;
                        $template->clear_attributes();
                        echo $template->render($args);
                        $cssSw->switchClass();
                    }
                }

                if (sizeof($non_group_buddies)) {
                    echo "\n<tr><td colspan=7 class=\"steelkante\" align=\"center\"><font size=-1 color=\"#555555\"><a href=\"contact.php?view=gruppen&filter=all\"><font size=-1 color=\"#555555\">"._("Buddies ohne Gruppenzuordnung").":</font></a></font></td></tr>";
                    reset ($non_group_buddies);
                    $template = $GLOBALS['template_factory']->open('online/user');
                    while (list($index)=each($non_group_buddies)) {
                        list($fullname,$zeit,$tmp_online_uname,$tmp_user_id)=$non_group_buddies[$index];
                        $args = compact('fullname', 'zeit', 'tmp_online_uname', 'tmp_user_id');
                        $args['is_buddy'] = TRUE;
                        $template->clear_attributes();
                        echo $template->render($args);
                    }
                } ?>

                <tr>
                    <td class="blank" width="50%" align="center" colspan="7">
                    <? printf(_("Zum Adressbuch (%d Eintr&auml;ge) klicken Sie %shier%s"),
                                      GetSizeofBook(),
                                      "<a href=\"contact.php\">", "</a>") ?>
                    </td>
                </tr>
            </table>
        </td>

    <? }

    ob_end_flush();
    ob_start();

    //Spalte anderer Benutzer
    if (!$my_messaging_settings["show_only_buddys"])
    {
        echo "\n<td width=\"50%\" valign=\"top\">";
        echo "\n<table width=\"100%\" cellspacing=0 cellpadding=1 border=0>\n";

        if (is_array($n_buddies)) {
            echo "<tr>\n<th colspan=3>" . _("Name") . "</th><th colspan=4>" . _("letztes Lebenszeichen") . "</th></tr>\n";
            reset($n_buddies);
            $template = $GLOBALS['template_factory']->open('online/user');
            while (list($index)=each($n_buddies)) {
                list($fullname, $zeit, $tmp_online_uname, $tmp_user_id) = $n_buddies[$index];
                $args = compact('fullname', 'zeit', 'tmp_online_uname', 'tmp_user_id');
                $args['background'] = $cssSw->getClass();
                $args['is_buddy'] = FALSE;
                $template->clear_attributes();
                echo $template->render($args);
                $cssSw->switchClass();
            }

        } else {
            // if we previously found unvisible users who are online
            if ($weitere > 0) {
            ?>
            <tr>
                <td class="steel1" align="center">
                    <?=_("Keine sichtbaren Nutzer online.")?>
                </td>
            </tr>
            <?
            } else {
            ?>
                <td class="steel1" width="50%" align="center">
                    <?=_("Kein anderer Nutzer ist online.")?>
                </td>
            </tr>
            <?
            }
        }
        echo "</table>\n";
    }
?>
        <? if ($user_count > 25) : ?>
            <div style="text-align:right; padding-top: 2px; padding-bottom: 2px" class="steelgraudunkel">
            <?
            $pagination = $GLOBALS['template_factory']->open('shared/pagechooser');
            $pagination->clear_attributes();
            $pagination->set_attribute('perPage', 25);
            $pagination->set_attribute('num_postings', $user_count);
            $pagination->set_attribute('page', $page);
            $pagination->set_attribute('pagelink', 'online.php?page=%s');
            echo $pagination->render("shared/pagechooser");
            ?>
            </div>
        <? endif; ?>
        <? if ($weitere > 0) : ?>
            <div align="center"><font size="-1" align="center"><br><?=sprintf(_("+ %s unsichtbare NutzerInnen"), $weitere)?></font></div>
        <? endif; ?>
        </td>
        </tr>
        </table>
    </div>
    <div class="clear"></div>
</div>
<?php
    ob_end_flush();
    include ('lib/include/html_end.inc.php');
    page_close();
