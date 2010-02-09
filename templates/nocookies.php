<table class="blank" width="800" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td class="topic">
	<img src="<?=$GLOBALS['ASSETS_URL']?>images/login.gif" border="0">
	<b><?=_("Stud.IP - Login")?></b>
	</td>
</tr>
<tr>
	<td>
		<table width="96%" border=0 cellpadding=0 cellspacing=0>
		<tr>
			<td>
			&nbsp;
			</td>
		</tr>
		<? parse_msg("error§<b>" . _("Cookies sind nicht aktiviert!") . "</b>§info§" . sprintf(_("Die Anmeldung für Stud.IP ist nur möglich, wenn Sie das Setzen von Cookies erlauben! Bitte &auml;ndern Sie die Einstellungen Ihres Browsers und wiederholen Sie den %sLogin%s"), "<a href=\"".$_SERVER['REQUEST_URI']."\">", "</a>"), "§", "blank", 1, TRUE);?>
		<tr>
			<td>

			<b><?=_("Was sind Cookies?")?></b><br><br>
			<blockquote>
			<font size =-1><?=_("Cookies werden normalerweise benutzt, damit der Browser BenutzerInnen der Seiten beim wiederholten Aufrufen dieser Seiten wiedererkennen kann <br>Innerhalb von Stud.IP werden Cookies benutzt, um den Namen und die Daten der Nutzenden auf jeder Seite neu verf&uuml;gbar zu machen.<br>Ohne Cookies m&uuml;ssten Sie sich beim Betreten jeder Seite neu anmelden!")?>
			<br></font></blockquote>

			<b><?=_("Sind Cookies gef&auml;hrlich?")?></b><br><br>
			<blockquote>
			<font size =-1><?=_("Das Zulassen von Cookies stellt - leider - ein geringf&uuml;giges Sicherheitsrisiko dar. Jedoch sollten Sie Ihr Augenmerk darauf richten, dass in den meisten Browsern Techniken integriert sind, die ein deutlich gr&ouml;&szlig;eres Sicherheitsrisiko darstellen (etwa AktiveX-Elemente).<br>Au&szlig;erdem gibt es zwei verschiedene Arten von Cookies: Jene, die auf der Festplatte gespeichert werden und auf dem Rechner verbleiben, und solche, die nur solange aktiv sind, wie Sie die entsprechenden Seiten benutzen.<br>Das Stud.IP-System nutzt nur die ungef&auml;hrlicheren Cookies, die sich nur f&uuml;r die Dauer der Sitzung im Speicher des Browser befinden und danach automatisch verschwinden.<br>Sie brauchen sich also keine Sorgen zu machen!")?>
			<br></font></blockquote>

			<b><?=_("Hinweise f&uuml;r BenutzerInnen des Netscape Navigator 4.x")?></b><br><br>
			<blockquote>
			<font size =-1><?=_("Wenn Sie den Netscape Navigator 4.x benutzen, k&ouml;nnen Sie das Annehmen von Cookies auf folgende Weise einschalten:")?>
			<br>
			<ol type="1" start="1">
				<li><?=_("&Ouml;ffnen Sie das Menu \"Bearbeiten\" in der Kopfzeile des Browsers")?>
				<li><?=_("Gehen Sie auf \"Einstellungen\"")?>
				<li><?=_("W&auml;hlen Sie jetzt auf der linken Seite der Einstellungsseite den Menüpunkt \"Erweitert\"")?>
				<li><?=_("Aktivieren Sie jetzt auf der rechten Seite die Punkte:")?>
					<ul type="disc">
					<li><?=_("\"Benutze JavaScript\"")?>
					<li><?=_("\"Benutze Style Sheets\"")?>
					<li><?=_("\"Erlaube Cookies nur von dem Server, von dem die aktuelle Seite stammt\"")?>
					</ul>
			</ol>
			</font></blockquote>
			</td>
		</tr>
		<tr>
			<td>
			&nbsp;
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>
<br>
