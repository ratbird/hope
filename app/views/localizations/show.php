<?

$translations = array(
    "suchen" => _("suchen")
  , "Sonntag" => _("Sonntag")
  , "Montag" => _("Montag")
  , "Dienstag" => _("Dienstag")
  , "Mittwoch" => _("Mittwoch")
  , "Donnerstag" => _("Donnerstag")
  , "Freitag" => _("Freitag")
  , "Samstag" => _("Samstag")
  // add your translations here
);

// translations have to be UTF8 for #json_encode
$translations = $plugin->utf8EncodeArray($translations);

?>
String.toLocaleString({
  "<?= strtr($language, "_", "-") ?>": <?= json_encode($translations) ?>

});
