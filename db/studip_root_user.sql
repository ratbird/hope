# Anlegen des Benutzers root

# Benutzer: root@studip ; Password: testing
INSERT IGNORE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `auth_plugin`) VALUES ('76ed43ef286fb55cf9e41beadb484a9f', 'root@studip', 'ae2b1fca515949e5d54fb22b8ed95575', 'root', 'Root', 'Studip', 'root@localhost', 'standard');
INSERT IGNORE INTO user_info SET user_id ='76ed43ef286fb55cf9e41beadb484a9f';

#
# wichtige News
#

INSERT IGNORE INTO `news` (`news_id`, `topic`, `body`, `author`, `date`, `user_id`, `expire`, `allow_comments`, `chdate`, `chdate_uid`, `mkdate`) VALUES ('29f2932ce32be989022c6f43b866e744', 'Herzlich Willkommen!', 'Das Stud.IP-Team heisst sie herzlich willkommen. \r\nBitte schauen Sie sich ruhig um!\r\n\r\nWenn Sie das System selbst installiert haben und diese News sehen, haben Sie die Demonstrationsdaten in die Datenbank eingef�gt. Wenn Sie produktiv mit dem System arbeiten wollen, sollten Sie diese Daten sp�ter wieder l�schen, da die Passw�rter der Accounts (vor allem des root-Accounts) �ffentlich bekannt sind.', 'Root Studip', UNIX_TIMESTAMP(), '76ed43ef286fb55cf9e41beadb484a9f', 14562502, 1, UNIX_TIMESTAMP(), '', UNIX_TIMESTAMP());
#
# Daten f�r Tabelle `news_range`
#

INSERT IGNORE INTO news_range VALUES ('29f2932ce32be989022c6f43b866e744', '76ed43ef286fb55cf9e41beadb484a9f');
INSERT IGNORE INTO news_range VALUES ('29f2932ce32be989022c6f43b866e744', 'studip');

