<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
admin_institut.php - Einrichtungs-Verwaltung von Stud.IP.
Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';
unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("admin");

if (Request::option('admin_inst_id')) {
    Request::set('cid', Request::option('admin_inst_id'));
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

## Set this to something, just something different...
  $hash_secret = "hgeisgczwgebt";
$i_view = Request::option('i_view');
## If is set 'cancel', we leave the adminstration form...
if (Request::option('cancel')) unset ($i_view);

require_once('lib/msg.inc.php'); //Funktionen f&uuml;r Nachrichtenmeldungen
require_once('lib/visual.inc.php');
require_once('config.inc.php');
require_once('lib/forum.inc.php');
require_once('lib/datei.inc.php');
require_once('lib/statusgruppe.inc.php');
require_once 'lib/functions.php';
require_once('lib/classes/Modules.class.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/StudipLitSearch.class.php');
require_once('lib/classes/StudipNews.class.php');
require_once('lib/log_events.inc.php');
require_once 'lib/classes/InstituteAvatar.class.php';
require_once 'lib/classes/LockRules.class.php';
require_once 'lib/classes/Institute.class.php';

if (get_config('RESOURCES_ENABLE')) {
    include_once($RELATIVE_PATH_RESOURCES."/lib/DeleteResourcesUser.class.php");
}

if (get_config('EXTERN_ENABLE')) {
    require_once($RELATIVE_PATH_EXTERN . "/lib/ExternConfig.class.php");
}


// Get a database connection
$cssSw = new cssClassSwitcher;
$Modules = new Modules;


//needed to build this to not break following switch strucure
$test_tasks = array('create', 'i_edit', 'i_kill', 'i_trykill');
$submitted_task = '';
foreach($test_tasks as $val) {
    if(Request::submitted($val)) {
        $submitted_task = $val;
    }
}

// Check if there was a submission
switch ($submitted_task) {

    // Create a new Institut
    case "create":
        if (!$perm->have_perm("root") && !($perm->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') != 'none'))  {
            $msg = "error§<b>" . _("Sie haben nicht die Berechtigung, um neue Einrichtungen zu erstellen!") . "</b>";
            break;
        }
        // Do we have all necessary data?
        if (!Request::quoted('Name')) {
            $msg="error§<b>" . _("Bitte geben Sie eine Bezeichnung f&uuml;r die Einrichtung ein!") . "</b>";
            $i_view="new";
            break;
        }

        // Does the Institut already exist?
        // NOTE: This should be a transaction, but it is not...
        $query      = "SELECT 1 FROM Institute WHERE Name = ?";
        $parameters = array(Request::get('Name'));

        $Fakultaet = Request::option('Fakultaet');
        if ($Fakultaet) {
            $query .= " AND fakultaets_id = ?";
            $parameters[] = $Fakultaet;
        }

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        
        if ($statement->fetchColumn()) {
            $msg="error§<b>" . sprintf(_("Die Einrichtung \"%s\" existiert bereits!"), htmlReady(Request::get('Name')));
            break;
        }

        // Create an id
        $i_id=md5(uniqid($hash_secret));
        if (!$Fakultaet) {
            if ($perm->have_perm("root")) {
                $Fakultaet = $i_id;
            } else {
                $msg = "error§<b>" . _("Sie haben nicht die Berechtigung, neue Fakult&auml;ten zu erstellen");
                break;
            }
        }

        $query = "INSERT INTO Institute
                      (Institut_id, Name, fakultaets_id, Strasse, Plz, url, telefon, email,
                       fax, type, lit_plugin_name, lock_rule, mkdate, chdate)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $i_id,
            Request::get('Name'),
            $Fakultaet,
            Request::get('strasse'),
            Request::get('plz'), // Beware: Despite the name, this contains both zip code AND city name
            Request::get('home'),
            Request::get('telefon'),
            Request::get('email'),
            Request::get('fax'),
            Request::int('type'),
            Request::get('lit_plugin_name'),
            Request::option('lock_rule'),
        ));

        if ($statement->rowCount() == 0) {
            $msg="error§<b>" . _("Datenbankoperation gescheitert:") . " " . $query . "</b>";
            break;
        }

        log_event("INST_CREATE",$i_id,NULL,NULL,$query); // logging

        // Set the default list of modules
        $Modules->writeDefaultStatus($i_id);

        // Create default folder and discussion
        CreateTopic(_("Allgemeine Diskussionen"), " ", _("Hier ist Raum für allgemeine Diskussionen"), 0, 0, $i_id, 0);
        
        $query = "INSERT INTO folder (folder_id, range_id, name, description, mkdate, chdate)
                  VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            md5(uniqid('folder')),
            $i_id,
            _('Allgemeiner Dateiordner'),
            _('Ablage für allgemeine Ordner und Dokumente der Einrichtung'),
        ));

        $msg="msg§" . sprintf(_("Die Einrichtung \"%s\" wurde erfolgreich angelegt."), htmlReady(Request::get('Name')));

        $i_view = $i_id;

        //This will select the new institute later for navigation (=>admin_search_form.inc.php)
        $admin_inst_id = $i_id;
        openInst($i_id);
      break;

    //change institut's data
    case "i_edit":

        if (!$perm->have_studip_perm("admin",Request::option('i_id'))){
            $msg = "error§<b>" . _("Sie haben nicht die Berechtigung diese Einrichtungen zu ver&auml;ndern!") . "</b>";
            break;
        }

        //do we have all necessary data?
        if (!(Request::quoted('Name'))) {
            $msg="error§<b>" . _("Bitte geben Sie einen Namen f&uuml;r die Einrichtung ein!") . "</b>";
            break;
        }

        //update Institut information.
        $query = "UPDATE Institute
                  SET Name = ?, fakultaets_id = ?, Strasse = ?, Plz = ?, url = ?, telefon = ?, fax = ?, 
                      email = ?, type = ?, lit_plugin_name = ?, lock_rule = ?, chdate = UNIX_TIMESTAMP()
                  WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            Request::get('Name'),
            Request::option('Fakultaet'),
            Request::get('strasse'),
            Request::get('plz'),
            Request::get('home'),
            Request::get('telefon'),
            Request::get('fax'),
            Request::get('email'),
            Request::int('type'),
            Request::get('lit_plugin_name'),
            Request::option('lock_rule'),
            Request::option('i_id'),
        ));
        if ($statement->rowCount() == 0) {
            $msg="error§<b>" . _("Datenbankoperation gescheitert:") . " " . $query . "</b>";
            break;
        } else {
            $msg="msg§" . sprintf(_("Die Änderung der Einrichtung \"%s\" wurde erfolgreich gespeichert." . '§'), htmlReady(Request::get('Name')));
        }
        // update additional datafields
        if (is_array($_REQUEST['datafields'])) {
            $invalidEntries = array();
            foreach (DataFieldEntry::getDataFieldEntries(Request::option('i_id'), 'inst') as $entry) {
                if(isset($_REQUEST['datafields'][$entry->getId()])){
                    $entry->setValueFromSubmit($_REQUEST['datafields'][$entry->getId()]);
                    if ($entry->isValid())
                        $entry->store();
                    else
                        $invalidEntries[$entry->getId()] = $entry;
                }
            }
            if (count($invalidEntries)  > 0)
                $msg='error§<b>' . _('ung&uuml;ltige Eingaben (s.u.) wurden nicht gespeichert') .'</b>§';
            else
                $msg="msg§<b>" . sprintf(_("Die Daten der Einrichtung \"%s\" wurden ver&auml;ndert."),htmlReady(Request::get('Name'))) . "</b>§";
        }
        break;

    // Delete the Institut
    case "i_kill":
        if (!check_ticket($_GET['studipticket']))
        {
            $msg="error§<b>" . _("Ihr Ticket ist abgelaufen. Versuchen Sie die letzte Aktion erneut.") . "</b>";
            break;
        }

        if (!$perm->have_perm("root") && !($perm->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') == 'all'))
        {
            $msg="error§<b>" . _("Sie haben nicht die Berechtigung Fakult&auml;ten zu l&ouml;schen!") . "</b>";
            break;
        }
        $i_id=Request::option('i_id');
        // Institut in use?
        $query = "SELECT 1 FROM seminare WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if ($statement->fetchColumn()) {
            $msg="error§<b>" . _("Diese Einrichtung kann nicht gel&ouml;scht werden, da noch Veranstaltungen an dieser Einrichtung existieren!") . "</b>";
            break;
        }

        $query = "SELECT a.Institut_id, a.Name, a.Institut_id = a.fakultaets_id AS is_fak, COUNT(b.Institut_id) AS num_inst
                  FROM Institute AS a
                    LEFT JOIN Institute AS b ON (a.Institut_id=b.fakultaets_id)
                  WHERE a.Institut_id = ? AND b.Institut_id != ? AND a.Institut_id = a.fakultaets_id
                  GROUP BY a.Institut_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id, $i_id));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($temp['num_inst']) {
            $msg="error§<b>" . _("Diese Einrichtung kann nicht gel&ouml;scht werden, da sie den Status Fakult&auml;t hat, und noch andere Einrichtungen zugeordnet sind!") . "</b>";
            break;
        }

        if ($temp['is_fak'] && !$perm->have_perm("root")) {
            $msg="error§<b>" . _("Sie haben nicht die Berechtigung Fakult&auml;ten zu l&ouml;schen!") . "</b>";
            break;
        }

        // delete users in user_inst
        $query = "SELECT user_id FROM user_inst WHERE institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        while ($user_id = $statement->fetchColumn()) {
            log_event('INST_USER_DEL', $i_id, $user_id);
        }

        $query = "DELETE FROM user_inst WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));

        if (($db_ar = $statement->rowCount()) > 0) {
            $msg.="msg§" . sprintf(_("%s Mitarbeiter gel&ouml;scht."), $db_ar) . "§";
        }

        // delete participations in seminar_inst
        $query = "DELETE FROM seminar_inst WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if (($db_ar = $statement->rowCount()) > 0) {
            $msg.="msg§" . sprintf(_("%s Beteiligungen an Veranstaltungen gel&ouml;scht"), $db_ar) . "§";
        }


        // delete literatur
        $del_lit = StudipLitList::DeleteListsByRange($i_id);
        if ($del_lit) {
            $msg.="msg§" . sprintf(_("%s Literaturlisten gel&ouml;scht."),$del_lit['list'])  . "§";
        }

        // SCM löschen
        $query = "DELETE FROM scm WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if (($db_ar = $statement->rowCount()) > 0) {
            $msg .= "msg§" . _("Freie Seite der Einrichtung gel&ouml;scht") . "§";
        }

        // delete news-links
        StudipNews::DeleteNewsRanges($i_id);

        //delete entry in news_rss_range
        StudipNews::UnsetRssId($i_id);

        //updating range_tree
        $query = "UPDATE range_tree SET name = ?, studip_object = '', studip_object_id = '' WHERE studip_object_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            _('(in Stud.IP gelöscht)'),
            $i_id,
        ));
        
        if (($db_ar = $statement->rowCount()) > 0) {
            $msg.="msg§" . sprintf(_("%s Bereiche im Einrichtungsbaum angepasst."), $db_ar) . "§";
        }

        // Statusgruppen entfernen
        if ($db_ar = DeleteAllStatusgruppen($i_id) > 0) {
            $msg .= "msg§" . sprintf(_("%s Funktionen/Gruppen gel&ouml;scht"), $db_ar) . ".§";
        }

        //kill the datafields
        DataFieldEntry::removeAll($i_id);

        //kill all wiki-pages
        foreach (array('', '_links', '_locks') as $area) {
            $query = "DELETE FROM wiki{$area} WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($i_id));
        }

        // kill all the ressources that are assigned to the Veranstaltung (and all the linked or subordinated stuff!)
        if (get_config('RESOURCES_ENABLE')) {
            $killAssign = new DeleteResourcesUser($i_id);
            $killAssign->delete();
        }

        // delete all configuration files for the "extern modules"
        if (get_config('EXTERN_ENABLE')) {
            $counts = ExternConfig::DeleteAllConfigurations($i_id);
            if ($counts) {
                $msg .= "msg§" . sprintf(_("%s Konfigurationsdateien f&uuml;r externe Seiten gel&ouml;scht."), $counts);
                $msg .= "§";
            }
        }

        // delete folders and discussions
        $query = "DELETE FROM px_topics WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if (($db_ar = $statement->rowCount()) > 0) {
            $msg.="msg§" . sprintf(_("%s Postings aus dem Forum der Einrichtung gel&ouml;scht."), $db_ar) . "§";
        }

        $db_ar = delete_all_documents($i_id);
        if ($db_ar > 0)
            $msg.="msg§" . sprintf(_("%s Dokumente gel&ouml;scht."), $db_ar) . "§";

        //kill the object_user_vists for this institut

        object_kill_visits(null, $i_id);

        // Delete that Institut.
        $query ="DELETE FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if ($statement->rowCount() == 0) {
            $msg="error§<b>" . _("Datenbankoperation gescheitert:") . "</b> " . $query;
            break;
        } else {

            $msg.="msg§" . sprintf(_("Die Einrichtung \"%s\" wurde gel&ouml;scht!"), htmlReady(Request::get('Name'))) . "§";
            $i_view="delete";
            log_event("INST_DEL",$i_id,NULL,Request::quoted('Name')); // logging - put institute's name in info - it's no longer derivable from id afterwards
        }

        // We deleted that intitute, so we have to unset the selection
        closeObject();
        break;
    case 'i_trykill':
        $message = _("Sind Sie sicher, dass Sie diese Einrichtung löschen wollen?");
        $post['i_id'] = Request::option('i_id');
        $post['i_kill'] = 1;
        $post['Name'] = Request::quoted('Name');
        $post['studipticket'] = get_ticket();
        echo createQuestion($message, $post);
        break;

    default:

}
//workaround
if ($i_view == "new")
    closeObject();

require_once 'lib/admin_search.inc.php';

PageLayout::setTitle(_("Verwaltung der Grunddaten"));

Navigation::activateItem('/admin/institute/details');
//$i_view=Request::option('i_view');
//get ID from a open Institut
if ($SessSemName[1])
    $i_view=$SessSemName[1];

$header_line = getHeaderLine($i_view);
if ($header_line)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

$lockrule = LockRules::getObjectRule($i_view);
if ($lockrule->description && LockRules::CheckLockRulePermission($i_view)) {
    $msg .= 'info§' . formatLinks($lockrule->description);
}
?>
<table class="blank" width="100%" cellpadding="2" cellspacing="0">
<? if (isset($msg)) : ?><? parse_msg($msg) ?><? endif ?>

<? if ($i_view=="delete") {
    echo "<tr><td class=\"blank\" colspan=\"2\"><table width=\"70%\" align=\"center\" class=\"steelgraulight\" >";
    echo "<tr><td><br>" . _("Die ausgewählte Einrichtung wurde gel&ouml;scht.") . "<br>";
    printf(_("Bitte wählen Sie über den Reiter %s eine andere Einrichtung aus."), "<a href=\"admin_institut.php?list=TRUE\"><b>"._('Einrichtungen')."</b></a>");
    echo '<br><br></td></tr></table><br><br></td></tr></table>';
    include ('lib/include/html_end.inc.php');
    page_close();
    die;
}

if ($perm->have_studip_perm("admin",$i_view) || $i_view == "new") {
    $institute = array();
    if ($i_view != "new") {
        $query = "SELECT a.*, b.Name AS fak_name, COUNT(Seminar_id) AS number
                  FROM Institute AS a
                  LEFT JOIN Institute AS b ON (b.Institut_id = a.fakultaets_id)
                  LEFT JOIN seminare AS c ON (a.Institut_id = c.Institut_id)
                  WHERE a.Institut_id = ?
                  GROUP BY a.Institut_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_view));
        $institute = $statement->fetch(PDO::FETCH_ASSOC);

        $query = "SELECT COUNT(b.Institut_id)
                  FROM Institute AS a
                  LEFT JOIN Institute AS b ON (a.Institut_id = b.fakultaets_id)
                  WHERE a.Institut_id = ? AND b.Institut_id != ? AND a.Institut_id = a.fakultaets_id
                  GROUP BY a.Institut_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_view, $i_view));
        $_num_inst = $statement->fetchColumn();
    }

    $lockCheck = function ($field, $raw = false) use ($institute) {
        $check = LockRules::Check($institute['Institut_id'], $field);
        if ($raw) {
            return $check;
        }
        return $check ? 'readonly' : '';
    };
    ?>
<tr>
    <td class="blank" valign="top">
    <form method="POST" name="edit" action="<?= UrlHelper::getLink() ?>">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Name:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="text" <?= $lockCheck('name') ?> name="Name" size=50 maxlength=254 value="<?php echo htmlReady(Request::get('Name', $institute['Name'])) ?>"></td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Fakult&auml;t:")?></td>
        <td class="<? echo $cssSw->getClass() ?>" align=left>
        <?php
        if ($perm->is_fak_admin() && !$lockCheck('fakultaets_id', true) && ($perm->have_studip_perm("admin", $institute['fakultaets_id']) || $i_view == "new")) {
            if ($_num_inst) {
                echo "\n<font size=\"-1\"><b>" . _("Diese Einrichtung hat den Status einer Fakult&auml;t.") . "<br>";
                printf(_("Es wurden bereits %s andere Einrichtungen zugeordnet."), $_num_inst) . "</b></font>";
                echo "\n<input type=\"hidden\" name=\"Fakultaet\" value=\"{$institute['Institut_id']}\">";
            } else {
                echo "\n<select name=\"Fakultaet\" style=\"width: 98%\">";
                if ($perm->have_perm("root")) {
                    printf ("<option %s value=\"%s\">" . _("Diese Einrichtung hat den Status einer Fakult&auml;t.") . "</option>", ($institute['fakultaets_id'] == Request::option('Fakultaet', $institute['Institut_id'])) ? "selected" : "", $institute['Institut_id']);
                    $query = "SELECT Institut_id, Name
                              FROM Institute
                              WHERE Institut_id = fakultaets_id AND fakultaets_id != ?
                              ORDER BY Name";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($institute['Institut_id'] ?: ''));
                } else {
                    $query = "SELECT a.Institut_id, Name
                              FROM user_inst AS a
                              LEFT JOIN Institute USING (Institut_id)
                              WHERE user_id = ? AND inst_perms = 'admin' AND fakultaets_id = ?
                              ORDER BY Name";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($user->id, $institute['Institut_id'] ?: ''));
                }
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    printf ("<option %s value=\"%s\"> %s</option>", ($row['Institut_id'] == Request::option('Fakultaet', $institute['fakultaets_id']))  ? "selected" : "", $row['Institut_id'], htmlReady($row['Name']));
                }
                echo "</select>";
            }
        } else {
            echo htmlReady($institute['fak_name']) . "\n<input type=\"hidden\" name=\"Fakultaet\" value=\"" . $institute['fakultaets_id'] . "\">";
        }

        ?>
        </td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Bezeichnung:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" >
        <? if (!$lockCheck('type', true)) : ?>
        <select style="width: 98%" name="type">
        <?
        $i=0;
        foreach ($GLOBALS['INST_TYPE'] as $i => $inst_type) {
            if ($i == Request::int('type', $institute['type']))
                echo "<option selected value=\"$i\">".htmlready($inst_type['name'])."</option>";
            else
                echo "<option value=\"$i\">".htmlready($inst_type['name'])."</option>";
        }
        ?></select>
        <? else :?>
            <?=htmlReady($GLOBALS['INST_TYPE'][$institute['type']]["name"])?><input type="hidden" name="type" value="<?=(int)$institute['type'] ?>">
        <? endif;?>
        </td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Straße:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="text" <?= $lockCheck('strasse') ?> name="strasse" size=32 maxlength=254 value="<?php echo htmlReady(Request::get('strasse', $institute['Strasse'])) ?>"></td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Ort:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="text" <?= $lockCheck('plz') ?> name="plz" size=32 maxlength=254 value="<?php echo htmlReady(Request::get('plz', $institute['Plz'])) ?>"></td>
        </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Telefonnummer:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="tel" <?= $lockCheck('telefon') ?> name="telefon" size=32 maxlength=254 value="<?php echo htmlReady(Request::get('telefon', $institute['telefon'])) ?>"></td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Faxnummer:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="tel" <?= $lockCheck('fax') ?> name="fax" size=32 maxlength=254 value="<?php echo htmlReady(Request::get('fax', $institute['fax'])) ?>"></td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("E-Mail-Adresse:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="email" <?= $lockCheck('email') ?> name="email" size=32 maxlength=254 value="<?php echo htmlReady(Request::get('email', $institute['email'])) ?>"></td>
    </tr>
    <tr <? $cssSw->switchClass() ?>>
        <td class="<? echo $cssSw->getClass() ?>" ><?=_("Homepage:")?> </td>
        <td class="<? echo $cssSw->getClass() ?>" ><input style="width: 98%" type="url" <?= $lockCheck('url') ?> name="home" size=32 maxlength=254 value="<?php echo htmlReady(Request::get('home', $institute['url'])) ?>"></td>
    </tr>
    <?
    //choose preferred lit plugin
    if (get_config('LITERATURE_ENABLE') && $institute['Institut_id'] == $institute['fakultaets_id']){
        ?><tr <? $cssSw->switchClass() ?>><td class="<? echo $cssSw->getClass() ?>" ><?=_("Bevorzugter Bibliothekskatalog:")?></td>
        <td class="<? echo $cssSw->getClass() ?>" >
        <select name="lit_plugin_name" style="width: 98%">
        <?
        foreach (StudipLitSearch::GetAvailablePlugins() as $plugin_name => $plugin_display_name){
            echo '<option value="'.$plugin_name.'" ' . (Request::get('lit_plugin_name', $institute['lit_plugin_name']) == $plugin_name ? 'selected' : '') .' >' . htmlReady($plugin_display_name) . '</option>';
        }
        ?>
        </select>
        </td></tr>
        <?
    }
    if ($perm->have_perm('root')) {
        ?>
        <tr <? $cssSw->switchClass(); echo $cssSw->getFullClass()  ?>>
        <td>
        <?=_("Sperrebene")?>
        </td>
        <td>
        <select name="lock_rule" style="width: 98%">
            <option value=""></option>
            <? foreach(LockRule::findAllByType('inst') as $rule) :?>
            <option value="<?=$rule->getId()?>" <?=($rule->getId() == Request::option('lock_rule', $institute['lock_rule']) ? 'selected' : '')?>><?=htmlReady($rule->name)?></option>
            <? endforeach;?>
        </select>
        </td>
        </tr>
        <?
    }
    //add the free administrable datafields
    $localEntries = DataFieldEntry::getDataFieldEntries($institute['Institut_id'], "inst");
    if ($localEntries) {
      foreach ($localEntries as $entry) {
        $value = $entry->getValue();
        $color = '#000000';
        $id = $entry->structure->getID();
        if ($invalidEntries[$id]) {
            $entry = $invalidEntries[$id];
            $color = '#ff0000';
        }
        if ($entry->structure->accessAllowed($perm)) {
            ?>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass(); ?>" >
                   <font color="<?=$color?>"><?=htmlReady($entry->getName())?>:</font>
               </td>
                <td class="<? echo $cssSw->getClass() ?>" >
                    <?
                    if ($perm->have_perm($entry->structure->getEditPerms()) && !$lockCheck($entry->getId(), true)) {
                        print $entry->getHTML("datafields");
                    }
                    else
                        print $entry->getDisplayValue();
                        
                    ?>
                </td>
            </tr>
            <?
        }
      }
    }
    ?>
    <tr <? $cssSw->switchClass() ?>>
        <td class="steel2" colspan="2" align="center">

    <?
    if ($i_view != "new" && isset($institute['Institut_id'])) {
        ?>
        <input type="hidden" name="i_id" value="<?= $institute['Institut_id'] ?>">
        <?
        echo Button::create(_('Übernehmen'), 'i_edit');
        if ($db->f("number") < 1 && !$_num_inst && ($perm->have_perm("root") || ($perm->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') == 'all'))) {
            echo '&nbsp;'.Button::create(_('Löschen'), 'i_trykill');
        }
    } else {
        echo Button::create(_('Anlegen'), 'create');
    }
    ?>
        <input type="hidden" name="i_view" value="<? printf ("%s", ($i_view=="new") ? "create" : $i_view);  ?>">
        </td>
    </tr>
    </table>
    </form>
    </td>
    <td width="270" class="blank" align="right" valign="top">
            <?
            $aktionen = array();
            if ($i_view != "new") {
                $aktionen[] = array(
                  "icon" => "icons/16/black/edit.png",
                  "text" => '<a href="' .
                            URLHelper::getLink('dispatch.php/institute/avatar/update/' . $institute['Institut_id']) .
                            '">' . _("Bild ändern") . '</a>');
                $aktionen[] = array(
                  "icon" => "icons/16/black/trash.png",
                  "text" => '<a href="' .
                            URLHelper::getLink('dispatch.php/institute/avatar/delete/'. $institute['Institut_id']) .
                            '">' . _("Bild löschen") . '</a>');
            }
            $infobox = array(
                array("kategorie" => _("Aktionen:"),
                      "eintrag"   => $aktionen
            ));
            ?>
            <?= $template_factory->render('infobox/infobox_avatar',
            array('content' => $infobox,
                  'picture' => InstituteAvatar::getAvatar($institute['Institut_id'])->getUrl(Avatar::NORMAL)
            )) ?>
    </td>
    </tr>
    <?
}
echo '</table>';
include ('lib/include/html_end.inc.php');
page_close();
