<?
# Lifter010: TODO
?>

<div style="width: 750px; margin: auto;">
    <?= MessageBox::error(_("Cookies sind nicht aktiviert!"), array(
        _("Die Anmeldung f�r Stud.IP ist nur m�glich, wenn Sie das Setzen von Cookies erlauben!"),
        sprintf(_("Bitte �ndern Sie die Einstellungen Ihres Browsers und wiederholen Sie den %sLogin%s"), "<a href=\"".$_SERVER['REQUEST_URI']."\">", "</a>")
    )) ?>
</div>
<table class="blank" width="750" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td class="table_header_bold">
        <?= Icon::create('door-enter', 'info_alt')->asImg() ?>
        <b><?=_("Stud.IP - Login")?></b>
    </td>
</tr>
<tr>
    <td>
        <p class="info">
        <p><b><?=_("Was sind Cookies?")?></b></p>
        <?= _("Cookies werden normalerweise benutzt, damit der Browser BenutzerInnen
        der Seiten beim wiederholten Aufrufen dieser Seiten wiedererkennen kann.
        Innerhalb von Stud.IP werden Cookies benutzt, um den Namen und die Daten
        der Nutzenden auf jeder Seite neu verf�gbar zu machen. Ohne Cookies
        m�ssten Sie sich beim Betreten jeder Seite neu anmelden!") ?>
        <p><b><?=_("Sind Cookies gef�hrlich?")?></b></p>
        <?= _("Das Zulassen von Cookies stellt - leider - ein geringf�giges
        Sicherheitsrisiko dar. Jedoch sollten Sie Ihr Augenmerk darauf richten,
        dass in den meisten Browsern Techniken integriert sind, die ein deutlich
        gr��eres Sicherheitsrisiko darstellen (etwa AktiveX-Elemente).
        <br>Au�erdem gibt es zwei verschiedene Arten von Cookies: Jene,
        die auf der Festplatte gespeichert werden und auf dem Rechner verbleiben,
        und solche, die nur solange aktiv sind, wie Sie die entsprechenden Seiten
        benutzen.<br>Das Stud.IP-System nutzt nur die ungef�hrlicheren Cookies,
        die sich nur f�r die Dauer der Sitzung im Speicher des Browser befinden
        und danach automatisch verschwinden.<br>Sie brauchen sich also keine
        Sorgen zu machen!")?>
        <p><b><?=_("Hinweise f�r BenutzerInnen des Netscape Navigator 4.x")?></b></p>
        <?= _("Wenn Sie den Netscape Navigator 4.x benutzen, k�nnen Sie das
        Annehmen von Cookies auf folgende Weise einschalten:")?>
        <ol type="1" start="1">
            <li><?=_("�ffnen Sie das Menu \"Bearbeiten\" in der Kopfzeile des Browsers")?>
            <li><?=_("Gehen Sie auf \"Einstellungen\"")?>
            <li><?=_("W�hlen Sie jetzt auf der linken Seite der Einstellungsseite den Men�punkt \"Erweitert\"")?>
            <li><?=_("Aktivieren Sie jetzt auf der rechten Seite die Punkte:")?>
                <ul type="disc">
                <li><?=_("\"Benutze JavaScript\"")?>
                <li><?=_("\"Benutze Style Sheets\"")?>
                <li><?=_("\"Erlaube Cookies nur von dem Server, von dem die aktuelle Seite stammt\"")?>
                </ul>
        </ol>
        </p>
    </td>
</tr>
</table>