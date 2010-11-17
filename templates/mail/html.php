<html>
<head>
  <?= Assets::stylesheet('style.css') ?>
</head>
<body style="background: none;">
  <?= Assets::img("locale/$lang/LC_PICTURES/mail_header.png") ?>
  <p style="padding: 10px;">
    <?= formatReady($message, true, true) ?>
  </p>
  <hr>
  <?= sprintf(_("Diese E-Mail ist eine Kopie einer systeminternen Nachricht, die in Stud.IP an %s versendet wurde."), htmlReady($rec_fullname)) ?><br>
  <?= sprintf(_("Sie erreichen Stud.IP unter %s"), "<a href=\"" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "\">" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "</a>") ?>
</body>
</html>
