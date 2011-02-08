<?
# Lifter010: TODO
?>
<html>
<head>
  <?= Assets::stylesheet('style.css') ?>
</head>
<body>
  <div style="background-color: white; margin: auto; max-width: 700px; padding: 4px;">
    <?= Assets::img("locale/$lang/LC_PICTURES/mail_header.png") ?>
    <p>
      <?= formatReady($message, true, true) ?>
    </p>
    <hr>
    <span class="minor">
      <?= sprintf(_("Diese E-Mail ist eine Kopie einer systeminternen Nachricht, die in Stud.IP an %s versendet wurde."), htmlReady($rec_fullname)) ?><br>
      <?= sprintf(_("Sie erreichen Stud.IP unter %s"), "<a href=\"" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "\">" . $GLOBALS['ABSOLUTE_URI_STUDIP'] . "</a>") ?>
    </span>
  </div>
</body>
</html>
