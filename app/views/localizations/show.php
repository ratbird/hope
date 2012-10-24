<?
# Lifter010: TODO

$translations = array(
    "suchen" => _("suchen")
  , "Sonntag" => _("Sonntag")
  , "Montag" => _("Montag")
  , "Dienstag" => _("Dienstag")
  , "Mittwoch" => _("Mittwoch")
  , "Donnerstag" => _("Donnerstag")
  , "Freitag" => _("Freitag")
  , "Samstag" => _("Samstag")
  , "Bitte ändern Sie ihre Eingabe" => _("Bitte ändern Sie ihre Eingabe")
  , "Bitte wählen Sie einen Wert aus" => _("Bitte wählen Sie einen Wert aus")
  , "Bitte geben Sie eine gültige E-Mail-Adresse ein" => _("Bitte geben Sie eine gültige E-Mail-Adresse ein")
  , "Bitte geben Sie eine Zahl ein" => _("Bitte geben Sie eine Zahl ein")
  , "Bitte geben Sie eine gültige Web-Adresse ein" => _("Bitte geben Sie eine gültige Web-Adresse ein")
  , "Der eingegebene Wert darf nicht größer als $1 sein" => _("Der eingegebene Wert darf nicht größer als $1 sein")
  , "Der eingegebene Wert darf nicht kleiner als $1 sein" => _("Der eingegebene Wert darf nicht kleiner als $1 sein")
  , "Dies ist ein erforderliches Feld" => _("Dies ist ein erforderliches Feld")
  , "Nicht buchbare Räume:" => _("Nicht buchbare Räume:")
  // add your translations here
);

// translations have to be UTF8 for #json_encode
$translations = $plugin->utf8EncodeArray($translations);

?>
String.toLocaleString({
  "<?= strtr($language, "_", "-") ?>": <?= json_encode($translations) ?>

});
