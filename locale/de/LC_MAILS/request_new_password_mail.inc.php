<?
    $subject = _("[Stud.IP - " . $GLOBALS['UNI_NAME_CLEAN'] . "] Neues Passwort zusenden (Schritt 3 von 5)");

    $mailbody="Dies ist eine Best�tigungsmail des Stud.IP-Systems\n"
    ."(Studienbegleitender Internetsupport von Pr�senzlehre)\n"
    ."- {$GLOBALS['UNI_NAME_CLEAN']} -\n\n"
    ."Sie haben um die Zusendung eines neuen Passwortes gebeten.\n\n"
    ."Benutzername: {$username}\n"
    ."Vorname: {$vorname}\n"
    ."Nachname: {$nachname}\n"
    ."E-Mail-Adresse: {$email}\n\n"
    ."Diese E-Mail wurde Ihnen zugesandt um sicherzustellen,\n"
    ."dass die angegebene E-Mail-Adresse tats�chlich Ihnen geh�rt.\n\n"
    ."Wenn diese Angaben korrekt sind, dann �ffnen Sie bitte den Link\n\n"
    ."{$GLOBALS['ABSOLUTE_URI_STUDIP']}request_new_password.php?uname={$username}&id={$id}&cancel_login=1\n\n"
    ."in Ihrem Browser. Das System wird Ihnen anschlie�end eine E-Mail mit Ihrem neuen\n"
    ."Passwort an diese E-Mail-Adresse senden.\n\n"
    ."Wahrscheinlich unterst�tzt Ihr E-Mail-Programm ein einfaches Anklicken des Links.\n"
    ."Ansonsten m�ssen Sie Ihren Browser �ffnen und den Link komplett in die Zeile\n"
    ."\"Location\" oder \"URL\" kopieren.\n\n"
    ."Falls Sie sich nicht als Benutzer \"{$username}\" angemeldet haben\n"
    ."oder �berhaupt nicht wissen, wovon hier die Rede ist,\n"
    ."dann hat jemand Ihre E-Mail-Adresse missbraucht!\n"
    ."Ignorieren Sie in diesem Fall diese E-Mail. Es werden dann keine �nderungen an\n"
    ."Ihren Zugangsdaten vorgenommen.\n\n";
?>

