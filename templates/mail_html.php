<html>
<head>
<?= Assets::stylesheet('style.css') ?>
</head>
<body>
<center>
<table style="text-align:left; width:700px; min-width:700px; max-width:700px; background-color:white;">
  <tr>
    <td style="height:90px; min-height:90px; max-height:90px;">
      <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/locale/<?= $lang ?>/LC_PICTURES/mail_header.png">
    </td>
  </tr>
  <tr>
    <td style="padding:10px;">
<?= $message ?>
    </td>
  </tr>
  <tr>
    <td>
      <hr>
      <span style="font-size:10px;"><?= sprintf(_("Diese E-Mail ist eine Kopie einer systeminternen Nachricht, die in Stud.IP an %s versendet wurde."), $rec_fullname) ?></span><br/>
<? $studip = "<a href=\"". $GLOBALS['ABSOLUTE_URI_STUDIP'] . "\">" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "</a>"; ?>
      <span style="font-size:10px;"><?= sprintf(_("Sie erreichen Stud.IP unter %s"), "<a href=\"" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "\">" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "</a>") ?></span>
    </td>
  </tr>
</table>
</center>
</body>
</html>
