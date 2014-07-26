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
  , 'Die beiden Werte "$1" und "$2" stimmen nicht überein. Bitte überprüfen Sie Ihre Eingabe.' => _('Die beiden Werte "$1" und "$2" stimmen nicht überein. Bitte überprüfen Sie Ihre Eingabe.')
  , "Bitte geben Sie Ihren tatsächlichen Vornamen an." => _("Bitte geben Sie Ihren tatsächlichen Vornamen an.")
  , "Bitte geben Sie Ihren tatsächlichen Nachnamen an." => _("Bitte geben Sie Ihren tatsächlichen Nachnamen an.")
  , "Blenden Sie die restlichen Termine ein" => _("Blenden Sie die restlichen Termine ein")
  , "Blenden Sie die restlichen Termine aus" => _("Blenden Sie die restlichen Termine aus")
  , 'Alle Räume anzeigen' => _('Alle Räume anzeigen')
  , 'Nur buchbare Räume anzeigen' => _('Nur buchbare Räume anzeigen')
  , 'Jeder Termin muss mindestens eine Person haben, die ihn durchführt!' => _('Jeder Termin muss mindestens eine Person haben, die ihn durchführt!')
  , 'Übernehmen' => _('Übernehmen')
  , 'Abbrechen' => _('Abbrechen')

  // public/assets/javascripts/register.js
  , 'Der Benutzername ist zu kurz, er sollte mindestens 4 Zeichen lang sein.' => _('Der Benutzername ist zu kurz, er sollte mindestens 4 Zeichen lang sein.')
  , 'Der Benutzername enthält unzulässige Zeichen, er darf keine Sonderzeichen oder Leerzeichen enthalten.' => _('Der Benutzername enthält unzulässige Zeichen, er darf keine Sonderzeichen oder Leerzeichen enthalten.')
  , 'Das Passwort ist zu kurz, es sollte mindestens 4 Zeichen lang sein.' => _('Das Passwort ist zu kurz, es sollte mindestens 4 Zeichen lang sein.')
  , 'Das Passwort stimmt nicht mit dem Bestätigungspasswort überein!' => _('Das Passwort stimmt nicht mit dem Bestätigungspasswort überein!')
  , 'Bitte geben Sie Ihren tatsächlichen Vornamen an.' => _('Bitte geben Sie Ihren tatsächlichen Vornamen an.')
  , 'Bitte geben Sie Ihren tatsächlichen Nachnamen an.' => _('Bitte geben Sie Ihren tatsächlichen Nachnamen an.')
  , 'Die E-Mail-Adresse ist nicht korrekt!' => _('Die E-Mail-Adresse ist nicht korrekt!')

  // public/assets/javascripts/messages.js
  , 'Sie haben nicht angegeben, wer die Nachricht empfangen soll!' => _('Sie haben nicht angegeben, wer die Nachricht empfangen soll!')

  // add your translations here
);

// translations have to be UTF8 for #json_encode
$translations = $plugin->utf8EncodeArray($translations);

?>
String.toLocaleString({
  "<?= strtr($language, "_", "-") ?>": <?= json_encode($translations) ?>

});
