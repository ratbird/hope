<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter005: TODO - studipim
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
about.php - Anzeige der persoenlichen Userseiten von Stud.IP
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>, Niclas Nohlen <nnohlen@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/


require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($_REQUEST['again'] && ($auth->auth["uid"] == "nobody"));
$perm->check("user");



include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- hier muessen Seiten-Initialisierungen passieren --

require_once 'lib/functions.php';
require_once('config.inc.php');
require_once('lib/dates.inc.php');
require_once('lib/messaging.inc.php');
require_once('lib/msg.inc.php');
require_once('lib/statusgruppe.inc.php');
require_once('lib/showNews.inc.php');
require_once('lib/show_dates.inc.php');
require_once('lib/classes/DbView.class.php');
require_once('lib/classes/DbSnapshot.class.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/guestbook.class.php');
require_once('lib/object.inc.php');
require_once('lib/classes/score.class.php');
require_once('lib/classes/SemesterData.class.php');
require_once('lib/user_visible.inc.php');
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/Avatar.class.php');
require_once('lib/classes/StudipKing.class.php');

DbView::addView('sem_tree');

function print_kings($username) {

    $uid = get_userid($username);
    $is_king = StudipKing::is_king($uid, TRUE);

    $result = '';
    foreach ($is_king as $type => $text) {
        $alt =
        $result .= Assets::img("crowns/crown-$type.png", array(
            'alt'   => $text,
            'title' => $text
        ));
    }

    if ($result !== '') {
    ?>
        <p>
            <?= $result ?>
        </p>
    <?
    }
}

function prettyViewPermString ($viewPerms) {
    switch ($viewPerms) {
        case 'all'   : return _('alle');
        case 'root'  : return _('SystemadministratorInnen');
        case 'admin' : return _('AdministratorInnen');
        case 'dozent': return _('DozentInnen');
        case 'tutor' : return _('TutorInnen');
        case 'autor' : return _('Studierenden');
        case 'user'  : return _('NutzerInnen');
    }
    return '';
}


function isDataFieldArrayEmpty ($array) {
    foreach ($array as $v)
        if (trim($v->getValue()) != '')
            return false;
    return true;
}


unregister_globals();

UrlHelper::bindLinkParam('about_data', $about_data);

$username = $auth->auth["uname"];

if (isset($_REQUEST['username']) && $_REQUEST['username'] !== '') {
    $username = $_REQUEST['username'];
}


if (get_config('CHAT_ENABLE')){
    include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
    if ($_REQUEST['kill_chat']){
        chat_kill_chat($_REQUEST['kill_chat']);
    }
}

if (get_config('VOTE_ENABLE')) {
    include_once ("lib/vote/vote_show.inc.php");
}

if (get_config('NEWS_RSS_EXPORT_ENABLE')){
    $news_author_id = StudipNews::GetRssIdFromUserId(get_userid($_REQUEST['username']));
    if ($news_author_id) {
        PageLayout::addHeadElement('link', array('rel'   => 'alternate',
                                                 'type'  => 'application/rss+xml',
                                                 'title' => 'RSS',
                                                 'href'  => 'rss.php?id='.$news_author_id));
    }
}


$db = new DB_Seminar;
$db2 = new DB_Seminar;
$db3 = new DB_Seminar;
$semester = new SemesterData;

$msging = new messaging;

//Buddie hinzufuegen
if ($_GET['cmd'] == "add_user") {
    $msging->add_buddy ($_GET['add_uname'], 0);
}


//Auf und Zuklappen Termine
if ($_GET['dopen'])
    $about_data["dopen"]=$_GET['dopen'];

if ($_GET['dclose'])
    $about_data["dopen"]='';

//Auf und Zuklappen News
process_news_commands($about_data);

$msg = "";
if ($_SESSION['sms_msg']) {
    $msg = $_SESSION['sms_msg'];
    unset($_SESSION['sms_msg']);
}

// 3 zeilen wegen username statt id zum aufruf...
// in $user_id steht jetzt die user_id (sic)
$db->query("SELECT * FROM auth_user_md5  WHERE username ='$username'");
$db->next_record();

// Help
PageLayout::setHelpKeyword("Basis.Homepage");
if($db->f('user_id') == $user->id && !$db->f('locked')){
    PageLayout::setTitle(_("Mein Profil"));
    $user_id = $db->f("user_id");
} elseif ($db->f('user_id') && ($perm->have_perm("root") || (!$db->f('locked') && get_visibility_by_id($db->f("user_id"))))) {
    PageLayout::setTitle(_("Profil")  . ' - ' . get_fullname($db->f('user_id')));
    $user_id = $db->f("user_id");
} else {
    PageLayout::setTitle(_("Profil"));
    unset($user_id);
}
# and start the output buffering
ob_start();
if ($user_id){

// count views of Page
if ($auth->auth["uid"]!=$user_id) {
    object_add_view($user_id);
}

if ($auth->auth["uid"]==$user_id)
    $GLOBALS['homepage_cache_own'] = time();

//Wenn er noch nicht in user_info eingetragen ist, kommt er ohne Werte rein
$db->query("SELECT user_id FROM user_info WHERE user_id ='$user_id'");
if ($db->num_rows()==0) {
    $db->query("INSERT INTO user_info (user_id) VALUES ('$user_id')");
}

//Bin ich ein Inst_admin, und ist der user in meinem Inst Tutor oder Dozent?
$admin_darf = FALSE;
$db->query("SELECT b.inst_perms FROM user_inst AS a ".
           "LEFT JOIN user_inst AS b USING (Institut_id) ".
           "WHERE (b.user_id = '$user_id') AND ".
           "(b.inst_perms = 'autor' OR b.inst_perms = 'tutor' OR ".
           "b.inst_perms = 'dozent') AND (a.user_id = '$user->id') AND ".
           "(a.inst_perms = 'admin')");
if ($db->num_rows())
    $admin_darf = TRUE;
if ($perm->is_fak_admin()){
    $db->query("SELECT c.user_id FROM user_inst a LEFT JOIN Institute b ON(a.Institut_id=b.fakultaets_id)  LEFT JOIN user_inst c ON(b.Institut_id=c.Institut_id) WHERE a.user_id='$user->id' AND a.inst_perms='admin' AND c.user_id='$user_id'");
    if ($db->next_record())
    $admin_darf = TRUE;
}
if ($perm->have_perm("root")) {
    $admin_darf=TRUE;
}


//Her mit den Daten...
$db->query("SELECT user_info.* , auth_user_md5.*,".
           $_fullname_sql['full'] . " AS fullname ".
           "FROM auth_user_md5 ".
           "LEFT JOIN user_info USING (user_id) ".
           "WHERE auth_user_md5.user_id = '$user_id'");
$db->next_record();

// generische Datenfelder aufsammeln
$short_datafields = array();
$long_datafields  = array();
foreach (DataFieldEntry::getDataFieldEntries($user_id) as $entry) {
    if ($entry->structure->accessAllowed($perm, $auth->auth["uid"], $user_id) &&
        $entry->getDisplayValue()) {
        if ($entry instanceof DataFieldTextareaEntry) {
            $long_datafields[] = $entry;
        }
        else {
            $short_datafields[] = $entry;
        }
    }
}



$show_tabs = ($user_id == $user->id && $perm->have_perm("autor"))
             || (isDeputyEditAboutActivated()
                && isDeputy($user->id, $user_id, true))
             || $perm->have_perm("root")
             || $admin_darf;

// FIXME these tabs should not have been added anyway
if (!$show_tabs) {
    foreach (Navigation::getItem('/profile') as $key => $nav) {
        if ($key != 'view') {
            Navigation::removeItem('/profile/'.$key);
        }
    }
}

Navigation::activateItem('/profile/view');

// TODO this can be removed when page output is moved to a template
URLHelper::addLinkParam('username', $username);

// add skip link
SkipLinks::addIndex(_("Benutzerprofil"), 'user_profile', 100);

$visibilities = get_local_visibility_by_username($username, 'homepage');
if (is_array(json_decode($visibilities, true))) {
    $visibilities = json_decode($visibilities, true);
} else {
    $visibilities = array();
}

?>
<script language="Javascript">
function open_im() {
  fenster = window.open("<?= URLHelper::getURL('studipim.php') ?>",
                        "im_<?= $GLOBALS['user']->id ?>",
                        "scrollbars=yes,width=400,height=300",
                        "resizable=no");
}
</script>

<table id="user_profile" width="100%" border="0" cellpadding="1" cellspacing="0">
    <? if ($msg) : ?>
        <?= parse_msg($msg) ?>
    <? endif ?>
    <tr>
        <td class="steel1" valign="top">
            <br>
            <?php $avatar_user_id = is_element_visible_for_user($user->id, $user_id, $visibilities['picture']) ? $user_id : 'nobody'; ?>
            <?= Avatar::getAvatar($avatar_user_id)->getImageTag(Avatar::NORMAL) ?>

            <br>
            <br>

            <font size="-1">&nbsp;<?= _("Besucher dieses Profils:") ?>&nbsp;<?= object_return_views($user_id) ?></font>
            <br>

            <?
            // Die Anzeige der Stud.Ip-Punkte
            $score = new Score(get_userid($username));


            if ($score->IsMyScore()) {
                echo "&nbsp;<a href=\"". URLhelper::getLink("score.php") ."\" " . tooltip(_("Zur Rangliste")) . "><font size=\"-1\">"
                     . _("Ihre Stud.IP-Punkte:") . " ".$score->ReturnMyScore()."<br>&nbsp;"
                     . _("Ihr Rang:") . " ".$score->ReturnMyTitle()."</a></font><br>";
            }
            elseif ($score->ReturnPublik()) {
                $scoretmp = $score->GetScore(get_userid($username));
                $title = $score->gettitel($scoretmp, $score->GetGender(get_userid($username)));
                echo "&nbsp;<a href=\"". URLhelper::getLink("score.php") ."\"><font size=\"-1\">"
                     . _("Stud.IP-Punkte:") . " ".$scoretmp."<br>&nbsp;"
                     . _("Rang:") . " ".$title."</a></font><br>";
            }

            if ($username==$auth->auth["uname"]) {
                if ($auth->auth["jscript"]) {
                    echo "<br><a href='javascript:open_im();'>" . _("Stud.IP Messenger starten") . "</a>";
                }
            } else {
                if (CheckBuddy($username)==FALSE) {
                    echo "<br><a href=\"". URLHelper::getLink("?cmd=add_user&add_uname=".$username) ."\">"
                         . Assets::img('icons/16/blue/person.png', array('title' =>_("zu den Kontakten hinzufügen"), 'class' => 'middle'))
                         . " " . _("zu den Kontakten hinzufügen") . " </a>";
                }
                echo "<br><a href=\"". URLHelper::getLink("sms_send.php?sms_source_page=about.php&rec_uname=".$db->f("username")) ."\">"
                     . Assets::img('icons/16/blue/mail.png', array('title' => _("Nachricht an Nutzer verschicken"), 'class' => 'middle'))
                     . " " . _("Nachricht an Nutzer") . "</a>";

            }

            // Export dieses Users als Vcard
            echo "<br><a href=\"". URLHelper::getLink("contact_export.php") ."\">"
                 . Assets::img('icons/16/blue/vcard.png', array('title' => _("vCard herunterladen"), 'class' => 'middle'))
                 . " " . _("vCard herunterladen") ."</a>";

            ?>

            <br>
            <br>
        </td>

        <td class="steel1" width="99%" valign="top" style="padding: 10px;">
            <h1><?= htmlReady($db->f("fullname")) ?></h1>
                <? if ($db->f('motto') &&
                        is_element_visible_for_user($user->id, $user_id, $visibilities['motto'])) : ?>
                    <h3><?= htmlReady($db->f('motto')) ?></h3>
                <? endif ?>

                <? if (!get_visibility_by_id($user_id)) : ?>
                    <? if ($user_id != $user->id) : ?>
                        <p>
                            <font color="red"><?= _("(Dieser Nutzer ist unsichtbar.)") ?></font>
                        </p>
                    <? else : ?>
                        <p>
                            <font color="red"><?= _("(Sie sind unsichtbar. Deshalb können nur Sie diese Seite sehen.)") ?></font>
                        </p>
                    <? endif ?>
                <? endif ?>

                <br>

                <? if (($email = get_visible_email($user_id)) != '') : ?>
                    <b>&nbsp;<?= _("E-mail:") ?></b>
                    <a href="mailto:<?= htmlReady($email) ?>"><?= htmlReady($email) ?></a>
                    <br>
                <? endif ?>

                <? if ($db->f("privatnr") != "" &&
                        is_element_visible_for_user($user->id, $user_id, $visibilities['private_phone'])) : ?>
                    <b>&nbsp;<?= _("Telefon (privat):") ?></b>
                    <?= htmlReady($db->f("privatnr")) ?>
                    <br>
                <? endif ?>

                <? if ($db->f("privatcell") != "" &&
                        is_element_visible_for_user($user->id, $user_id, $visibilities['private_cell'])) : ?>
                    <b>&nbsp;<?= _("Mobiltelefon:") ?></b>
                    <?= htmlReady($db->f("privatcell")) ?>
                    <br>
                <? endif ?>

                <? if (get_config("ENABLE_SKYPE_INFO") &&
                       UserConfig::get($user_id)->SKYPE_NAME &&
                       is_element_visible_for_user($user->id, $user_id, $visibilities['skype_name'])) : ?>
                    <?php $skype_name = UserConfig::get($user_id)->SKYPE_NAME ?>
                    <b>&nbsp;<?= _("Skype:") ?></b>
                    <a href="skype:<?= htmlReady($skype_name) ?>?call">
                        <? if (UserConfig::get($user_id)->SKYPE_ONLINE_STATUS &&
                       is_element_visible_for_user($user->id, $user_id, $visibilities['skype_online_status'])) : ?>
                            <img src="http://mystatus.skype.com/smallicon/<?= htmlReady($skype_name) ?>" style="vertical-align:middle;" width="16" height="16" alt="My status">
                        <? else : ?>
                            <?= Assets::img('icon_small_skype.gif', array('style' => 'vertical-align:middle;')) ?>
                        <? endif ?>
                        <?= htmlReady($skype_name) ?>
                    </a>
                    <br>
                <? endif ?>

                <? if ($db->f("privadr") != "" &&
                        is_element_visible_for_user($user->id, $user_id, $visibilities['privadr'])) : ?>
                    <b>&nbsp;<?= _("Adresse (privat):") ?></b>
                    <?= htmlReady($db->f("privadr")) ?>
                    <br>
                <? endif ?>

                <? if ($db->f("Home") != "" &&
                        is_element_visible_for_user($user->id, $user_id, $visibilities['homepage'])) : ?>
                    <b>&nbsp;<?= _("Homepage:") ?></b>
                    <?= FixLinks(htmlReady($db->f("Home"))) ?>
                    <br>
                <? endif ?>

                <? if ($perm->have_perm("root") && $db->f('locked')) : ?>
                    <br>
                    <b>
                        <font color="red" size="+1"><?= _("BENUTZER IST GESPERRT!") ?></font>
                    </b>
                    <br>
                <? endif ?>

                <?
                // Anzeige der Institute an denen (hoffentlich) studiert wird:

                if($db->f('perms') != 'dozent'){
                $db3->query("SELECT Institute.* FROM user_inst LEFT JOIN Institute  USING (Institut_id) WHERE user_id = '$user_id' AND inst_perms = 'user'");
                IF ($db3->num_rows() && is_element_visible_for_user($user->id, $user_id, $visibilities['studying'])) {
                    echo "<br><b>&nbsp;" . _("Wo ich studiere:") . "&nbsp;&nbsp;</b><br>";
                    while ($db3->next_record()) {
                        echo "&nbsp; &nbsp; &nbsp; &nbsp;<a href=\"". URLHelper::getLink("institut_main.php?auswahl=".$db3->f("Institut_id")) ."\">".htmlReady($db3->f("Name"))."</a><br>";
                    }
                }
                }

                // Anzeige der Institute an denen gearbeitet wird

                $query = "SELECT a.*,b.Name FROM user_inst a LEFT JOIN Institute b USING (Institut_id) ";
                $query .= "WHERE user_id = '$user_id' AND inst_perms != 'user' AND visible = 1 ORDER BY priority ASC";
                $db3->query($query);
                IF ($db3->num_rows()) {
                    echo "<br><b>&nbsp;" . _("Wo ich arbeite:") . "&nbsp;&nbsp;</b><br>";
                }

                //schleife weil evtl. mehrere sprechzeiten und institut nicht gesetzt...

                while ($db3->next_record()) {
                    $institut=$db3->f("Institut_id");
                    echo "&nbsp; &nbsp; &nbsp; &nbsp;<a href=\"". URLHelper::getLink("institut_main.php?auswahl=".$institut) ."\">".htmlReady($db3->f("Name"))."</a>";

                    echo "<font size=-1>";
                    IF ($db3->f("raum")!="")
                        echo "<b><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; " . _("Raum:") . " </b>", htmlReady($db3->f("raum"));
                    IF ($db3->f("sprechzeiten")!="")
                        echo "<b><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; " . _("Sprechzeit:") . " </b>", htmlReady($db3->f("sprechzeiten"));
                    IF ($db3->f("Telefon")!="")
                        echo "<b><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; " . _("Telefon:") . " </b>", htmlReady($db3->f("Telefon"));
                    IF ($db3->f("Fax")!="")
                        echo "<b><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; " . _("Fax:") . " </b>", htmlReady($db3->f("Fax"));

                    echo '<table cellspacing="0" cellpadding="0" border="0">';
                    $entries = DataFieldEntry::getDataFieldEntries(array($user_id, $institut));
                    if (!isDataFieldArrayEmpty($entries)) {
                        foreach ($entries as $entry) {
                            $view = DataFieldStructure::permMask($auth->auth['perm']) >= DataFieldStructure::permMask($entry->structure->getViewPerms());
                            $show_star = false;
                            if (!$view && ($user_id == $user->id)) {
                                $view = true;
                                $show_star = true;
                            }

                            if (trim($entry->getValue()) && $view) {
                                echo '<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>' . htmlReady($entry->getName()) . ": " .'&nbsp;&nbsp;</td><td>'. $entry->getDisplayValue();
                                if ($show_star) echo ' *';
                            }
                        }
                    }

                    echo '</table>';

                    if ($groups = GetAllStatusgruppen($institut, $user_id)) {
                        $default_entries = DataFieldEntry::getDataFieldEntries(array($user_id, $institut));
                        $data = get_role_data_recursive($groups, $user_id, $default_entries);
                        echo '<table cellpadding="0" cellspacing="0" border="0">';
                        echo $data['standard'];
                        echo '</table>';
                    } else {
                        echo '<br>';
                    }

                    echo "</font>";
                    echo '<br>';
                }

                if (($user_id == $user->id) && $GLOBALS['has_denoted_fields']) {
                    echo '<br>';
                    echo '<font size="-1">';
                    echo ' * Diese Felder sind nur für Sie und AdministratorInnen sichtbar.<br>';
                    echo '</font>';
                }

                if ($score->IsMyScore() || $score->ReturnPublik()) {
                    echo "<p>";
                    print_kings($username);
                }

                ?>


                <br>

                <? foreach ($short_datafields as $entry) : ?>

                    <?
                    $vperms = $entry->structure->getViewPerms();
                    $visible = 'all' == $vperms
                               ? _("sichtbar für alle")
                               : sprintf(_("sichtbar für Sie und alle %s"),
                                         prettyViewPermString($vperms));
                    ?>

                    &nbsp;<strong><?= htmlReady($entry->getName()) ?>:</strong>
                    <?= $entry->getDisplayValue() ?>
                    <span class="minor">(<?= $visible ?>)</span>
                    <br>
                <? endforeach ?>
        </td>
</tr>
</table>

<br>

<?

// News zur person anzeigen!!!
$show_admin = ($perm->have_perm("autor") && $auth->auth["uid"] == $user_id) || 
    (isDeputyEditAboutActivated() && isDeputy($auth->auth["uid"], $user_id, true));
if (is_element_visible_for_user($user->id, $user_id, $visibilities['news'])) {
    show_news($user_id, $show_admin, 0, $about_data["nopen"], "100%", 0, $about_data);
}

// alle persoenlichen Termine anzeigen, aber keine privaten
if (get_config('CALENDAR_ENABLE')) {
    $temp_user_perm = get_global_perm($user_id);
    if ($temp_user_perm != "root" && $temp_user_perm != "admin") {
        $start_zeit = time();
        $show_admin = ($perm->have_perm("autor") && 
            $auth->auth["uid"] == $user_id);
        if (is_element_visible_for_user($user->id, $user_id, $visibilities['termine']))
            show_personal_dates($user_id, $start_zeit, -1, FALSE, $show_admin, $about_data["dopen"]);
    }
}

// include and show friend-of-a-friend list
// (direct/indirect connection via buddy list)
if ($GLOBALS['FOAF_ENABLE']
    && ($auth->auth['uid']!=$user_id)
    && UserConfig::get($user_id)->FOAF_SHOW_IDENTITY) {
        include("lib/classes/FoafDisplay.class.php");
        $foaf=new FoafDisplay($auth->auth['uid'], $user_id, $username);
        $foaf->show($_REQUEST['foaf_open']);
}

// include and show votes and tests
if (get_config('VOTE_ENABLE') && is_element_visible_for_user($user->id, $user_id, $visibilities['votes'])) {
    show_votes($username, $auth->auth["uid"], $perm, YES);
}


// show Guestbook
$guest = new Guestbook($user_id,$admin_darf, Request::int('guestpage', 0));

if ($_REQUEST['guestbook'] && $perm->have_perm('autor'))
    $guest->actionsGuestbook($_REQUEST['guestbook'],$_REQUEST['post'],$_REQUEST['deletepost'],$_REQUEST['studipticket']);

if ($guest->active == TRUE || $guest->rights == TRUE && is_element_visible_for_user($user->id, $user_id, $visibilities['guestbook'])) {
    $guest->showGuestbook();
}

// show chat info
if (get_config('CHAT_ENABLE')) {
    chat_show_info($user_id);
}

$layout = $GLOBALS['template_factory']->open('shared/index_box');

// show literature info
if (get_config('LITERATURE_ENABLE')) {
    // Ausgabe von Literaturlisten
    $lit_list = StudipLitList::GetFormattedListsByRange($user_id);
    if ($user_id == $user->id){
        $layout->admin_url = 'admin_lit_list.php?_range_id=self';
        $layout->admin_title = _('Literaturlisten bearbeiten');
    }

    if (is_element_visible_for_user($user->id, $user_id, $visibilities['literature'])) {
        echo $layout->render(array('title' => _('Literaturlisten'), 'content_for_layout' => $lit_list));
        $layout->clear_attributes();
    }
}

// Hier werden Lebenslauf, Hobbys, Publikationen und Arbeitsschwerpunkte ausgegeben:
$ausgabe_felder = array('lebenslauf' => _("Lebenslauf"),
            'hobby' => _("Hobbys"),
            'publi' => _("Publikationen"),
            'schwerp' => _("Arbeitsschwerpunkte")
            );

foreach ($ausgabe_felder as $key => $value) {
    if (is_element_visible_for_user($user->id, $user_id, $visibilities[$key]))
        echo $layout->render(array('title' => $value, 'content_for_layout' => formatReady($db->f($key))));
}

$layout->clear_attributes();

// add the free administrable datafields (these field are system categories -
// the user is not allowed to change the categories)
foreach ($long_datafields as $entry) {
    if (is_element_visible_for_user($user->id, $user_id, $visibilities[$entry->getName()])) {
        $vperms = $entry->structure->getViewPerms();
        $visible = 'all' == $vperms
                   ? _("sichtbar für alle")
                   : sprintf(_("sichtbar für Sie und alle %s"),
                             prettyViewPermString($vperms));
        echo $layout->render(array('title' => $entry->getName() . "($visible)", 'content_for_layout' => $entry->getDisplayValue()));
    }
}

$layout->clear_attributes();

// Prüfen, ob HomepagePlugins vorhanden sind.
$homepageplugins = PluginEngine::getPlugins('HomepagePlugin');

foreach ($homepageplugins as $homepageplugin){
    // hier nun die HomepagePlugins anzeigen
    $template = $homepageplugin->getHomepageTemplate($user_id);

    if ($template) {
        echo $template->render(NULL, $layout);
        $layout->clear_attributes();
    }
}

//add the own categories - this ones are self created by the user
$categories = DBManager::get()->query("SELECT * FROM kategorien WHERE range_id = '$user_id' ORDER BY priority");
while ($category = $categories->fetch())  {
    $head=$category["name"];
    $body=$category["content"];
    if ($user->id == $user_id) {
        switch ($visibilities['kat_'.$category['kategorie_id']]) {
            case VISIBILITY_ME:
                $vis_text = _("nur für mich sichtbar");
                break;
            case VISIBILITY_BUDDIES:
                $vis_text = _("nur für meine Buddies sichtbar");
                break;
            case VISIBILITY_DOMAIN:
                $vis_text = _("nur für meine Nutzerdomäne sichtbar");
                break;
            case VISIBILITY_EXTERN:
                $vis_text = _("auf externen Seiten sichtbar");
                break;
            default:
            case VISIBILITY_STUDIP:
                $vis_text = _("für alle Stud.IP-Nutzer sichtbar");
                break;
        }
        $head .= ' ('.$vis_text.')';
    }
    // oeffentliche Rubrik oder eigene Homepage
    if (is_element_visible_for_user($user->id, $user_id, $visibilities['kat_'.$category['kategorie_id']])) {
        echo $layout->render(array('title' => $head, 'content_for_layout' => formatReady($body)));
    }
}

// Anzeige der Seminare
if ($perm->get_perm($user_id) == 'dozent'){
    $all_semester = SemesterData::GetSemesterArray();
    $view = new DbView();
    $output = '';
    for ($i = count($all_semester)-1; $i >= 0; --$i){
        $view->params[0] = $user_id;
        $view->params[1] = "dozent";
        $view->params[2] = " HAVING (sem_number <= $i AND (sem_number_end >= $i OR sem_number_end = -1)) ";
        $snap = new DbSnapshot($view->get_query("view:SEM_USER_GET_SEM"));
        if ($snap->numRows){
            $sem_name = $all_semester[$i]['name'];
            if ($output) $output .= '<br>';
            $output .= "<font size=\"+1\"><b>$sem_name</b></font><br><br>";
            $snap->sortRows("Name");
            while ($snap->nextRow()) {
                $ver_name = $snap->getField("Name");
                $sem_number_start = $snap->getField("sem_number");
                $sem_number_end = $snap->getField("sem_number_end");
                if ($sem_number_start != $sem_number_end){
                    $ver_name .= " (" . $all_semester[$sem_number_start]['name'] . " - ";
                    $ver_name .= (($sem_number_end == -1) ? _("unbegrenzt") : $all_semester[$sem_number_end]['name']) . ")";
                }
                $output .= '<b><a href="'. URLHelper::getLink("details.php?sem_id=".$snap->getField('Seminar_id')). '">' . htmlReady($ver_name) . '</a></b><br>';
            }
        }
    }

    echo $layout->render(array('title' => _('Veranstaltungen'), 'content_for_layout' => $output));
}
} else {
    echo MessageBox::error(_("Dieses Profil ist nicht verfügbar."), array(_("Der Benutzer hat sich unsichtbar geschaltet oder ist im System nicht vorhanden.")));
}

# get the layout template
$layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');

$layout->content_for_layout = ob_get_clean();

echo $layout->render();

// Save data back to database.
page_close();
