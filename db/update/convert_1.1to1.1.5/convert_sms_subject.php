<?

// page_open
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");

// initialise session
include ("$ABSOLUTE_PATH_STUDIP/seminar_open.php");

// -- here you have to put initialisations for the current page
require_once ("$ABSOLUTE_PATH_STUDIP/functions.php");
require_once ("$ABSOLUTE_PATH_STUDIP/msg.inc.php");
require_once ("$ABSOLUTE_PATH_STUDIP/visual.inc.php");
require_once ("$ABSOLUTE_PATH_STUDIP/statusgruppe.inc.php");
require_once ("$ABSOLUTE_PATH_STUDIP/reiter.inc.php");

// need kontact to mothership
$db = new DB_Seminar;
$db2 = new DB_Seminar;

// Output of html head and Stud.IP head
include ("$ABSOLUTE_PATH_STUDIP/html_head.inc.php");
include ("$ABSOLUTE_PATH_STUDIP/header.php");


// do we use javascript?
if ($auth->auth["jscript"]) {
    echo "<script language=\"JavaScript\">var ol_textfont = \"Arial\"</script>";
    echo "<DIV ID=\"overDiv\" STYLE=\"position:absolute; visibility:hidden; z-index:1000;\"></DIV>";
    echo "<SCRIPT LANGUAGE=\"JavaScript\" SRC=\"overlib.js\"></SCRIPT>";
}

if (($change_view) || ($delete_user) || ($view=="Messaging")) {
    $my_messaging_settings = json_decode(UserConfig::get($user->id)->__get('my_messaging_settings'),true);
    $my_messaging_settings = check_messaging_default($my_messaging_settings);
    change_messaging_view($my_messaging_settings);
    echo "</td></tr></table>";
    page_close();
    die;
} 

?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank">
            <br><br><center><font style="font-weight: bold">Convert-Script</font> um messages pre1.1.5 ein Betreff zu verpassen...</center><br><hr noshade size="9">
            <?
            $db=new DB_Seminar;
            $count = "0";
            $db->query("SELECT * FROM message WHERE subject = ''");
            while ($db->next_record()) {
                $new_subject = $db->f("message");
                $new_subject = kill_format($new_subject); // entfernt die meisten Formatierungen
                $new_subject = ereg_replace("\n", "", $new_subject); // hier entfernen wir alle dreckigen zeilenumbrueche :headhammer:
                $new_subject = preg_replace("#\[quote\=(.*)\](.*)\[\/quote\]#i", "", $new_subject); // entfernt das erste quote
                $new_subject = preg_replace("#\[(/?(pre|latex|code)\])|img.*?\]#i", "", $new_subject); // entfernt alle uebrigen Auszeichnungen
                $new_subject = substr($new_subject, 0, 51); // text kuerzen
                if (strlen($new_subject) > 50) $new_subject .= "..."; // punkte anhaengen wenn titel laenger als 50 zeichen
                if ($new_subject == "") $new_subject = "Ohne Betreff"; // "ohne betreff", wenn nichts von message uebrig
                $new_subject = addslashes($new_subject); // noch ein bisschen slashes
                $db2->query("UPDATE message SET subject = '".$new_subject."' WHERE message_id = '".$db->f("message_id")."'"); // hier den neuen betreff in die db schreiben
                $count = $count+1; // zaehle
            }
            echo $count." Nachrichten bearbeitet.";
            ?>
        </td>
    </tr>
</table>

<?

// Save data back to database.
page_close() ?>

</body>
</html>
