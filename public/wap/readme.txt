==============================================================================
                                 Stud.IP WAP Modul
                                 Suchi & Berg GmbH
                            Florian Hansen, 12.09.2003
==============================================================================

Installation
------------

-	Sämtliche Dateien in ein beliebiges StudIP-Unterverzeichnis kopieren.

-	Tabelle für die WAP-Sessionverwaltung in die Datenbank einfügen:
	create table wap_sessions (user_id CHAR(32) NOT NULL, session_id CHAR(32) NOT NULL, creation_time DATETIME);


Benutzung
---------

-	Mit einem WAP-Browser die index.php aufrufen.


Wap-Emulatoren
--------------

-	http://www.yourwap.com/			(verschiedene Handytypen)
-	http://www.pyweb.com/tools/		(nur ein Nokia)



Bemerkungen
-----------

-	Zum WAP-Emulator von yourwap.com:
	Falls man eine lokale StudIP-Installation mit eigenem Apache-Server nutzt,
	muss die (leider implementierte) WebServer-Funktionalität des
	yourwap.com-Browsers z.B. mittels Firewall unterbunden werden.
	Es gibt natürlich auch noch viele weitere on- und off-line WAP-Emulatoren
	im Netz, jedoch unterstützt der yourwap.com-Browser gleich mehrere
	Handy-Typen.
	
-	Zum WAP-Emulator von pyweb.com:
	Emuliert zwar nur ein Nokia-Handy, zeigt dafür jedoch den Source-Code sowie
	Fehler an. Zudem ist das Handling durch die Tastatur sehr angenehm.
