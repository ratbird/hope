<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

//unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

$root_id = Request::option('root_id');
$topic_id = Request::option('topic_id');
$name = Request::quoted('name');
$parent_id = Request::option('parent_id');
// -- here you have to put initialisations for the current page

checkObject();

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head

if  ($user->id == "nobody") {  // nicht angemeldete muessen Namen angeben, dazu auch JS Check auf Name
?>
<SCRIPT language="JavaScript">
<!--
function pruefe_name(){
 var re_nachname = /^([a-zA-ZÄÖÜ][^0-9"´'`\/\\\(\)\[\]]+)$/;
 var checked = true;
 if (re_nachname.test(document.forumwrite.nobodysname.value)==false) {
    alert("<?=_("Bitte geben Sie Ihren tatsächlichen Namen an.")?>");
    document.forumwrite.nobodysname.focus();
    checked = false;
    }
  if (document.forumwrite.nobodysname.value=="unbekannt") {
    alert("<?=_("Bitte geben Sie Ihren Namen an.")?>");
    document.forumwrite.nobodysname.focus();
    checked = false;
    }
 return checked;
}
// -->
</SCRIPT>
<?
}

    require_once('lib/forum.inc.php');
    require_once('lib/visual.inc.php');
    require_once 'lib/functions.php';
    require_once('lib/msg.inc.php');

?>


<table class="blank" width="100%" cellspacing=0 border=0><tr>

<?

// Freies Seminar mit Schreibrecht fuer Nobody?

if ($user->id == "nobody"){
    $query = "SELECT 1 FROM seminare WHERE Seminar_id = ? AND Schreibzugriff = 0";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($GLOBALS['SessSemName'][1]));
    $pass = $statement->fetchColumn() > 0;
}
$Create = Request::option('Create');
if (!(have_sem_write_perm()) OR $pass==TRUE) {
    if (empty ($Create)) {  // $Create != "abschicken"
        if (!empty ($topic_id)) {
            $query = "SELECT name, description, anonymous FROM px_topics WHERE topic_id = ? AND Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($topic_id, $GLOBALS['SessSemName'][1]));
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) { // wir sind NICHT im richtigen Seminar!
                include ('lib/include/html_end.inc.php');
                page_close();
                die;
            }
            foreach ($rows as $row) {
                $name = $row['name'];
                echo"<td class=table_footer colspan=2>&nbsp; &nbsp; <b><font size=2>".htmlReady($name)."</font></b></td>";
                echo "\n</tr><tr>";
                // $parent_description = formatReady($db->f("description"));
                $parent_description = $row['description'];
                if (preg_match("/<admin_msg/",$parent_description))
                    $parent_description = forum_parse_edit($parent_description, $row['anonymous']);
                $parent_description = formatReady($parent_description);
                printcontent ("100%","",$parent_description,"");
                echo "\n</tr>";
                echo "  <tr>";
                echo "      <td colspan=2 class=table_row_even align=center>";
                echo LinkButton::create(_("Zitieren"), URLHelper::getURL("?write=1&root_id=$root_id&topic_id=$topic_id&quote=TRUE"));
                echo "      </td>";
                echo "  </tr>";
                echo "<tr><td colspan=2 class=content_title>&nbsp; </td></tr><tr><td colspan=2 class=table_row_even><blockquote>";
            
            }
            print "<br><b>" . _("Hierzu antworten:") . "</b><br><br>\n";
        } else {
            $topic_id = "0";
            $root_id = "0";
            $name = "";
        }

        if ($user->id == "nobody")
            echo "<form name=Create method=post action=\"".URLHelper::getLink("?Create=TRUE")."\" onsubmit=\"return pruefe_name()\">"; // bei nobody mit namen pruefen
        else
            echo "<form name=Create method=post action=\"".URLHelper::getLink("?Create=TRUE")."\">";

        echo CSRFProtection::tokenTag();
        echo "<input type=hidden name=\"parent_id\" value=\"$topic_id\">";
        echo "<input type=hidden name=\"root_id\" value=\"$root_id\">";
        print _("&Uuml;berschrift:") . " <br><input type=text name=name value=\"";

        if ($topic_id != "0" OR $topic_id > 0) {
            if (substr($name,0,3)=="Re:")
                print htmlReady($name);
            else
                print "Re: ".htmlReady($name);
        }
        print ("\" size=60>");
        print ("<input type=\"hidden\" name=\"author\" value = \"");
        $tmp = $auth->auth["uname"];
        echo htmlReady(get_fullname());
        print ("\" size=\"20\"><br><br>");
        if  ($user->id == "nobody") {  // nicht angemeldete muessen Namen angeben
            $description =  "<b>" . _("Ihr Name:") . "</b>&nbsp; <input type=text size=50 name=nobodysname onchange=\"return pruefe_name()\" value=\"" . _("unbekannt") . "\"><br><br><input type=hidden name=update value='".Request::int('write')."'>";
        } 
        echo $description;
        echo _("Ihr Beitrag:");
        echo "<br><textarea name=\"description\" cols=60 rows=12>";
        if (Request::option('quote')==TRUE) {  // es soll zitiert werden
            $zitat = quote($topic_id);
            echo htmlReady($zitat);
            echo "\n";
        }
        echo "</textarea><br><br>";
        echo Button::createAccept(_('Übernehmen'));
        $help_url = format_help_url("Basis.VerschiedenesFormat");
        echo "&nbsp;&nbsp;<a href=\"" . URLHelper::getURL('dispatch.php/smileys') . "\" target=\"_blank\"><font size=\"-1\">"._("Smileys")."</a>&nbsp;&nbsp;"."<a href=\"".$help_url."\" target=\"_blank\"><font size=\"-1\">"._("Formatierungshilfen")."</a>";
        echo "</form>";
        
    } else {
        if (!empty($parent_id)) {
            $query = "SELECT 1 FROM px_topics WHERE topic_id = ? AND Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($parent_id, $GLOBALS['SessSemName'][1]));
            if (!$statement->fetchColumn()) { // wir sind NICHT im richtigen Seminar!
                include ('lib/include/html_end.inc.php');
                page_close();
                die;
            }
        }
        if (Request::quoted('nobodysname')) $author = Request::quoted('nobodysname');
        $writeextern = TRUE;
        $topic_id = CreateTopic ( Request::quoted('name'), Request::quoted('author'), Request::quoted('description'), $parent_id, $root_id);
        parse_window( "msg§" . _("Ihr Beitrag wurde erfolgreich ins System &uuml;bernommen") . "§info§" . _("Sie k&ouml;nnen dieses Fenster jetzt schliessen.<br>Um Ihr neues Posting zu sehen, m&uuml;ssen Sie das Hauptfenster aktualisieren!") . "§", "§", "Schreiben erfolgreich", "&nbsp;");
    }
} else {
    $msg=have_sem_write_perm();
    parse_window($msg, "§", _("Schreiben nicht m&ouml;glich"), "&nbsp;");
}
echo '</blockquote></td></tr></table>';
  // Save data back to database.
  include ('lib/include/html_end.inc.php');
  page_close();
?>
