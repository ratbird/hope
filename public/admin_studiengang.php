<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
admin_studiengang.php - Studiengang-Verwaltung von Stud.IP.
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

// Set this to something, just something different...
$hash_secret = "dudeldoe";

// If is set 'cancel', we leave the adminstration form...
if (isset($cancel_x)) unset ($i_view);

$CURRENT_PAGE = _("Verwaltung der Studiengänge");
Navigation::activateItem('/admin/config/study_programs');

// Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head

    require_once ('lib/msg.inc.php'); //Funktionen fuer Nachrichtenmeldungen
    require_once ('lib/visual.inc.php');

    $cssSw=new cssClassSwitcher;
?>
<table border=0 bgcolor="#000000" align="center" cellspacing=0 cellpadding=0 width=100%>
<tr><td class="blank" colspan=2>&nbsp;</td></tr>


<?php


// Get a database connection
$db = new DB_Seminar;
$db2 = new DB_Seminar;

// Check if there was a submission

reset($_POST);


while ( is_array($_POST)
     && list($key, $val) = each($_POST)) {
  switch ($key) {


  // Neuer Studiengang
  case "create_x":
    // Do we have all necessary data?
    if (empty($Name)) {
      my_error("<b>" . _("Bitte geben Sie eine Bezeichnung f&uuml;r das Fach ein!") . "</b>");
      break;
    }

    // Does the Studiengang already exist?
    // NOTE: This should be a transaction, but it isn't...
    $db->query("SELECT * FROM studiengaenge WHERE name='$Name'");
    if ($db->nf()>0) {
      my_error("<b>" . sprintf(_("Der Studiengang \"%s\" existiert bereits!"), htmlReady(stripslashes($Name))) . "</b>");
      break;
    }

    // Create an id
    $i_id=md5(uniqid($hash_secret));
    $query = "INSERT INTO studiengaenge VALUES('$i_id','$Name','$Beschreibung', '".time()."', '".time()."') ";
    $db->query($query);
    if ($db->affected_rows() == 0) {
      my_error("<b>" . _("Datenbankoperation gescheitert:") . " $query </b>");
      break;
    }
    ELSE {
         unset($i_view);  // gibt keine Detailansicht
         my_msg("<b>" . sprintf(_("Der Studiengang \"%s\" wurde angelegt!"), htmlReady(stripslashes($Name))) . "</b>");
         break;
    }

  ## Change Studiengangname
  case "i_edit_x":

    // Do we have all necessary data?
    if (empty($Name)) {
      my_error("<b>" . _("Bitte geben Sie eine Bezeichnung f&uuml;r den Studiengang ein!") . "</b>");
      break;
    }

    // Update Studiengang information.
    $query = "UPDATE studiengaenge SET name='$Name', beschreibung='$Beschreibung' WHERE studiengang_id = '$i_id'";
    $db->query($query);
    if ($db->affected_rows() == 0) {
        unset($i_view);  // wurde nix verändert...
        my_msg("<b>" . sprintf(_("Keine Änderungen am Studiengang \"%s\" durchgeführt."), htmlReady(stripslashes($Name))) . "</b>");
        break;
    }
    else
        $db->query("UPDATE studiengaenge SET chdate='".time()."' ");

    my_msg("<b>" . sprintf(_("Die Daten des Studiengangs \"%s\" wurden ver&auml;ndert."), htmlReady(stripslashes($Name))) . "</b>");
    unset($i_view);  // gibt keine Detailansicht
  break;

  // Delete the Studiengang

  // diese Passage wäre zu diskutieren. Darf man Studiengänge löschen, denen sich Studis bereits zugeordnet haben?
  // Zur Vorsicht erst mal dringelassen.

  case "i_kill_x":
    // sind dem Studengang noch veranstaltungen zugeordnet?
    $db->query("SELECT * FROM admission_seminar_studiengang WHERE studiengang_id = '$i_id'");
        if ($db->next_record()) {
            my_error("<b>" . _("Dieser Studiengang kann nicht gel&ouml;scht werden, da noch Veranstaltungen zugeordnet sind!") . "</b>");
            break;
        }

// Loeschen des Studiengangs und eventuell noch daranhaengender user

    $query = "DELETE FROM studiengaenge WHERE studiengang_id='$i_id'";
    $db->query($query);
    if ($db->affected_rows() == 0) {
      my_error("<b>" . _("Datenbankoperation gescheitert:") . " </b> $query</b>");
      break;
    }
    if (!$move_user_stdg_id) {
        $query = "DELETE FROM user_studiengang WHERE studiengang_id='$i_id'";
        $db->query($query);
        if ($db->affected_rows() == 0) {
                my_msg("<b>" . _("Keine Nutzenden betroffen") . "</b>");
        } else {
                my_msg(sprintf("<b>" . _("%s Zuordnungen von Nutzenden zu Studieng&auml;ngen gel&ouml;scht.") . "</b>", $db->affected_rows()));
        }
    } else {
        $query = "UPDATE IGNORE user_studiengang SET studiengang_id = '$move_user_stdg_id' WHERE studiengang_id='$i_id'";
        $db->query($query);
        if ($db->affected_rows() == 0) {
                my_msg("<b>" . _("Keine Nutzenden betroffen") . "</b>");
        } else {
                my_msg(sprintf("<b>" . _("%s Zuordnungen von Nutzenden zu Studieng&auml;ngen ge&auml;ndert.") . "</b>", $db->affected_rows()));
        }
            $query = "DELETE FROM user_studiengang WHERE studiengang_id='$i_id'";
        $db->query($query);
    }

    unset($i_view);  // gibt keine Detailansicht
    my_msg("<b>" . sprintf(_("Der Studiengang \"%s\" wurde gel&ouml;scht!"), htmlReady(stripslashes($Name))) . "</b>");
    break;

    default:
    break;
    }
}


//Anzeige der Studiengangdaten; das tatseachliche Aenderungsmodul

if ($i_view) {
    if ($i_view <> "new") {
        $db->cache_query("SELECT studiengaenge.*, count(admission_seminar_studiengang.seminar_id) AS number FROM studiengaenge LEFT JOIN admission_seminar_studiengang USING(studiengang_id) WHERE studiengaenge.studiengang_id = '$i_view' GROUP BY studiengang_id");
        $db->next_record();
    }
    $i_id= $db->f("studiengang_id");

  ?>
    <tr><td class="blank" colspan=2>
    <table border=0 bgcolor="#eeeeee" align="center" width="75%" cellspacing=0 cellpadding=2>
    <form method="POST" name="edit" action="<? echo $PHP_SELF?>">
    <tr><td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>"><?=_("Name des Studienganges:")?> </td><td class="<? echo $cssSw->getClass() ?>"><input type="text" name="Name" size=60 maxlength=254 value="<?php echo htmlReady($db->f("name")) ?>"></td></tr>
    <tr><td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>"><?=_("Beschreibung:")?> </td><td class="<? echo $cssSw->getClass() ?>"><textarea cols=50 ROWS=4 name="Beschreibung" value="<?php $db->p("beschreibung") ?>"><?php echo htmlReady($db->f("beschreibung")) ?></textarea></td></tr>
    <?
    if ($i_view <> "new") {
        if ($db->f("number") < 1) {
            ?>
        <tr>
            <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" align="center">
                &nbsp;
            </td>
            <td class="<? echo $cssSw->getClass() ?>">
                <font size="-1"><?=_("Wenn die dem Studiengang zugeordneten Studierenden beim L&ouml;schen einem anderen Studiengang zugeordnet werden sollen, w&auml;hlen Sie ihn bitte hier aus:") ?></font><br><br>          <?
                $db2->query("SELECT * FROM studiengaenge WHERE studiengang_id  != '".$db->f("studiengang_id")."' ORDER BY name");
                print "<select name=\"move_user_stdg_id\">";
                print "<option value=\"\">"._("&lt;keinem anderen Studiengang zuordnen - direkt l&ouml;schen&gt;")."</option>";
                while ($db2->next_record()) {
                    printf ("<option value=\"%s\">%s</option>", $db2->f("studiengang_id"), my_substr($db2->f("name"), 0, 50));
                }
                print "</select><br>";
                ?>
            </td>
        </tr>
            <?
        }
        ?>
        <tr>
            <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>">
                &nbsp;
            </td>
            <td class="<?= $cssSw->getClass()?>">
            <?
            if (!$db->f("number"))
                print "<input type=\"IMAGE\"  border=\"0\" name=\"i_kill\" value=\""._("L&ouml;schen")."\" ".makeButton("loeschen", "src").">";
            ?>
                <input type="IMAGE"  border="0" name="i_edit" value=" <?=_("Ver&auml;ndern")?> " <?=makeButton("uebernehmen", "src")?>>
                <input type="hidden" name="i_id"   value="<?php $db->p("studiengang_id") ?>">
        <?
    } else {
        echo "<input type=\"IMAGE\" name=\"create\" value=\" " . _("Anlegen") . " \" " . makeButton("anlegen", "src") . ">";
    }
    ?>
        <input type="IMAGE" name="cancel" border="0" value=" <?=_("abbrechen")?> " <?=makeButton("abbrechen", "src")?>>
        <input type="hidden" name="i_view" value="<? echo $i_view; ?>">
        </td>
    </tr>
    </form></table>
    <?
    if ($i_view<>"new") {
        $db->query("SELECT Name, seminare.seminar_id FROM admission_seminar_studiengang INNER JOIN seminare USING (seminar_id) WHERE studiengang_id = '$i_id'");
        ?>
        <table border=0 align="center" width="75%" cellspacing=0 cellpadding=2>
        <?
        if ($db->affected_rows() > 0) {
            ?><tr><td width="100%" colspan=2><br><?=_("Diesem Studiengang sind folgende teilnahmebeschr&auml;nkte Veranstaltungen zugeordnet:")?><br><br></th></tr>
            <tr><th width="100%" align="center"><?=_("Name")?></th><tr><?
        } else {
            ?><tr><td width="100%" colspan=2><br><?=_("Diesem Bereich sind noch keine Veranstaltungen zugeordnet!")?><br><br></th></tr><?}
        while ($db->next_record()) {
            printf ("<tr><td class=\"%s\"><a href=\"admin_admission.php?seminar_id=%s\">&nbsp; %s</a></td></tr>", $cssSw->getClass(), $db->f("seminar_id"), htmlReady($db->f("Name")));
            $cssSw->switchClass();
    }
    echo "</table><br><br>";
    }
}

// Output Studiengang administration forms, including all updated
// information, if we come here after a submission...

if (!$i_view) {
    ?>
  <tr><td class="blank" colspan=2>
  <?
  printf("&nbsp;&nbsp;"._("Neuen Studiengang %s")."<br><br>", "<a href=" . $PHP_SELF . "?i_view=new><img ".makeButton("anlegen", "src")." align=\"absmiddle\"></a>");
  ?>
  <tr><td class="blank" colspan=2>
  <table align=center bg="#ffffff" width="80%" border=0 cellpadding=2 cellspacing=0>
  <tr valign=top align=middle>
  <th width="60%"><?=_("Name des Studienganges")?></th>
  <th width="20%"><?=_("Veranstaltungen")?></th>
  <th width="20%"><?=_("Nutzer")?></th>
  </tr>
    <?

  // Traverse the result set
  $db->cache_query("SELECT studiengaenge.*, count(admission_seminar_studiengang.seminar_id) AS count_sem FROM studiengaenge LEFT JOIN admission_seminar_studiengang USING(studiengang_id) GROUP BY studiengang_id ORDER BY name");
  $db2->cache_query("SELECT studiengaenge.*, count(user_studiengang.studiengang_id) AS count_user FROM studiengaenge LEFT JOIN user_studiengang USING(studiengang_id) GROUP BY studiengang_id ORDER BY name");
  while ($db->next_record() && $db2->next_record()) {        //Aufbauen der &Uuml;bersichtstabelle
        $cssSw->switchClass();
        print("<tr valign=\"middle\" align=\"left\">");
        printf("<td class=\"%s\"><a href=\"%s?i_view=%s\">&nbsp;%s</a></td>", $cssSw->getClass(), $PHP_SELF, $db->f("studiengang_id"), htmlReady($db->f("name")));
        printf("<td class=\"%s\" align=\"center\">&nbsp;%s</td>", $cssSw->getClass(), $db->f("count_sem"));
        printf("<td class=\"%s\" align=\"center\">&nbsp;%s</td>", $cssSw->getClass(), $db2->f("count_user"));
        print("</tr>");
  }
  print("</table><br><br>\n");
  print("</td></tr>\n");
}

echo '</table>';
include ('lib/include/html_end.inc.php');
page_close();

?>
