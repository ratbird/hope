<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("autor");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once ('lib/visual.inc.php');

if (!$_POST["pass"])
   {
    ?>
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html><head><title><?=_("Autologin Datei erzeugen")?></title>
        <script type="text/javascript" src="<?= $GLOBALS['ASSETS_URL'] ?>javascripts/md5.js"></script>
    <script type="text/javascript">
    function doSubmit(){
        if (document.forms[0].pass.value!="") document.forms[0].submit();
        else document.forms[0].pass.focus();
    }
    </script></head>
    <body style="background-image: url('<?= $GLOBALS['ASSETS_URL'] ?>images/steel1.jpg');font-family: Arial, Helvetica, sans-serif;">
    <?
    echo "<div align=\"center\"><form action=\"$PHP_SELF\" method=\"post\" >";
    printf(_("Bitte Passwort eingeben für User: <b>%s</b>"), $auth->auth["uname"]);
        echo "<br><br>";
    echo "<input type=\"password\" size=\"15\" name=\"pass\"><br><br><a href=\"javascript:doSubmit();\"><img " . makeButton("herunterladen", "src") . " border=\"0\" " . tooltip(_("Die heruntergeladene Datei bitte mit der Endung .html speichern!")) . "></a>";
    echo "&nbsp;&nbsp;<a href=\"javascript:window.close()\"><img " . makeButton("abbrechen", "src") . " border=\"0\" " . tooltip(_("Fenster schließen")) . "></a></form></div>";
    ?><script type="text/javascript">document.forms[0].pass.focus();</script><?php
    include ('lib/include/html_end.inc.php');
    page_close();
    die;
    }

ob_start();
$link = "http" . ($_SERVER['HTTPS'] ? 's' : '') . "://" . $_SERVER["HTTP_HOST"].$CANONICAL_RELATIVE_PATH_STUDIP;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title><?=_("Autologin")?></title>
<script src="<? echo $link;?>get_key.php" type="text/javascript">
</script>
<script type="text/javascript">

function convert(x, n, m, d)
   {
      if (x == 0) return "00";
      var r = "";
      while (x != 0)
      {
         r = d.charAt((x & m)) + r;
         x = x >>> n;
      }
      return (r.length%2) ? "0" + r : r;
   }

function toHexString(x){
    return convert(x, 4, 15, "0123456789abcdef");
    }

function one_time_pad(text,key)
{
var geheim=""
 for(var i = 0; i < text.length; i++)
         {
         k=((text.charCodeAt(i))+(key.charCodeAt(i)))%256;
         geheim=geheim + toHexString(k);
         }
 return(geheim);
}

/* Hier gehts los... */
var password = "<?=$_POST["pass"];?>";
var username = "<?=$auth->auth["uname"];?>";
if (auto_key)
   {
    var response = one_time_pad(password,auto_key);
    var autourl="<? echo $link;?>index.php?again=yes&auto_user=" + username + "&auto_response=" + response + "&auto_id=" + auto_id + "&resolution=" + screen.width+"x"+screen.height;
    location.href=autourl;
    }
//-->
</script>
</head>
<body>
</body>
</html>
<?

$data = ob_get_clean();
header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"autologin_".$auth->auth["uname"].".html\"");
header("Content-Length: " . strlen($data));
echo $data;
die();
?>
