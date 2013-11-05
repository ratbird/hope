--
-- Daten f�r Tabelle `abschluss`
--

REPLACE INTO `abschluss` (`abschluss_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('228234544820cdf75db55b42d1ea3ecc', 'Bachelor', '', 1311416359, 1311416359);
REPLACE INTO `abschluss` (`abschluss_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('c7f569e815a35cf24a515a0e67928072', 'Master', '', 1311416385, 1311416385);

--
-- Daten f�r Tabelle `auth_user_md5`
--

REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 'root@studip', 'ae2b1fca515949e5d54fb22b8ed95575', 'root', 'Root', 'Studip', 'root@localhost', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('205f3efb7997a0fc9755da2b535038da', 'test_dozent', 'ae2b1fca515949e5d54fb22b8ed95575', 'dozent', 'Testaccount', 'Dozent', 'dozent@studip.de', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('6235c46eb9e962866ebdceece739ace5', 'test_admin', 'ae2b1fca515949e5d54fb22b8ed95575', 'admin', 'Testaccount', 'Admin', 'admin@studip.de', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('7e81ec247c151c02ffd479511e24cc03', 'test_tutor', 'ae2b1fca515949e5d54fb22b8ed95575', 'tutor', 'Testaccount', 'Tutor', 'tutor@studip.de', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 'test_autor', 'ae2b1fca515949e5d54fb22b8ed95575', 'autor', 'Testaccount', 'Autor', 'autor@studip.de', '', NULL, 0, NULL, NULL, 'unknown');

--
-- Daten f�r Tabelle `config`
--

REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('48f849a4927f8ac5231da5352076f16a', '', 'STUDYGROUPS_ENABLE', '1', 0, 'boolean', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', 'Studiengruppen', '');
REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('9f1c998d46f55ac38da3a53072a4086b', '', 'STUDYGROUP_DEFAULT_INST', 'ec2e364b28357106c0f8c282733dbe56', 0, 'string', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', '', '');
REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('bcd4820eebd8e027cef91bc761ab9a75', '', 'STUDYGROUP_TERMS', 'Mir ist bekannt, dass ich die Gruppe nicht zu rechtswidrigen Zwecken nutzen darf. Dazu z�hlen u.a. Urheberrechtsverletzungen, Beleidigungen und andere Pers�nlichkeitsdelikte.\r\n\r\nIch erkl�re mich damit einverstanden, dass AdministratorInnen die Inhalte der Gruppe zu Kontrollzwecken einsehen d�rfen.', 0, 'string', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', '', '');

--
-- Daten f�r Tabelle `datafields`
--

REPLACE INTO `datafields` (`datafield_id`, `name`, `object_type`, `object_class`, `edit_perms`, `view_perms`, `priority`, `mkdate`, `chdate`, `type`, `typeparam`) VALUES('ce73a10d07b3bb13c0132d363549efda', 'Matrikelnummer', 'user', '7', 'user', 'dozent', 0, NULL, NULL, 'textline', '');

--
-- Daten f�r Tabelle `dokumente`
--

REPLACE INTO `dokumente` (`dokument_id`, `range_id`, `user_id`, `seminar_id`, `name`, `description`, `filename`, `mkdate`, `chdate`, `filesize`, `autor_host`, `downloads`, `url`, `protected`, `priority`, `author_name`) VALUES('6b606bd3d6d6cda829200385fa79fcbf', 'ca002fbae136b07e4df29e0136e3bd32', '76ed43ef286fb55cf9e41beadb484a9f', 'a07535cf2f8a72df33c12ddfa4b53dde', 'Stud.IP-Produktbrosch�re im PDF-Format', '', 'mappe_studip-el.pdf', 1343924827, 1343924841, 314146, '127.0.0.1', 0, 'http://www.studip.de/download/mappe_studip-el.pdf', 0, 0, 'Root Studip');

--
-- Daten f�r Tabelle `folder`
--

REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('dad53cd0f0d9f36817c3c9c7c124bda3', 'ec2e364b28357106c0f8c282733dbe56', '', 'Allgemeiner Dateiordner', 'Ablage f�r allgemeine Ordner und Dokumente der Einrichtung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('b58081c411c76814bc8f78425fb2ab81', '7a4f19a0a2c321ab2b8f7b798881af7c', '', 'Allgemeiner Dateiordner', 'Ablage f�r allgemeine Ordner und Dokumente der Einrichtung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('694cdcef09c2b8e70a7313b028e36fb6', '110ce78ffefaf1e5f167cd7019b728bf', '', 'Allgemeiner Dateiordner', 'Ablage f�r allgemeine Ordner und Dokumente der Einrichtung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('ad8dc6a6162fb0fe022af4a62a15e309', '373a72966cf45c484b4b0b07dba69a64', '76ed43ef286fb55cf9e41beadb484a9f', 'Hausaufgaben', '', 3, 1343924873, 1343924877, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('df122112a21812ff4ffcf1965cb48fc3', '2f597139a049a768dbf8345a0a0af3de', '76ed43ef286fb55cf9e41beadb484a9f', 'Dateiordner der Gruppe: Studierende', 'Ablage f�r Ordner und Dokumente dieser Gruppe', 15, 1343924860, 1343924860, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('aa15759a75c167e38425d17539e7a7be', '41ad59c9b6cdafca50e42fe6bc68af4f', '205f3efb7997a0fc9755da2b535038da', 'Dateiordner der Gruppe: Thema 1', 'Ablage f�r Ordner und Dokumente dieser Gruppe', 15, 1194628738, 1194628738, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('5b1b53b48c487a639ec493afbb270d4c', '151c33059a90b6138d280862f5d4b3c2', '205f3efb7997a0fc9755da2b535038da', 'Dateiordner der Gruppe: Thema 2', 'Ablage f�r Ordner und Dokumente dieser Gruppe', 15, 1194628768, 1194628768, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('17534632a6a9145f21c9fc99b7557bf9', 'a5061826bf8db7487a774f92ce2a4d23', '205f3efb7997a0fc9755da2b535038da', 'Dateiordner der Gruppe: Thema 3', 'Ablage f�r Ordner und Dokumente dieser Gruppe', 15, 1194628789, 1194628789, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('1af61dbdcfca1b394290c5d4283371d7', '7cb72dab1bf896a0b55c6aa7a70a3a86', '76ed43ef286fb55cf9e41beadb484a9f', 'Allgemeiner Dateiordner', 'Ablage f�r allgemeine Ordner und Dokumente der Veranstaltung', 7, 1343924088, 1343924088, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('ca002fbae136b07e4df29e0136e3bd32', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', 'Allgemeiner Dateiordner', 'Ablage f�r allgemeine Ordner und Dokumente der Veranstaltung', 5, 1343924407, 1343924894, 0);

--
-- Daten f�r Tabelle `Institute`
--

REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('1535795b0d6ddecac6813f5f6ac47ef2', 'Test Fakult�t', '1535795b0d6ddecac6813f5f6ac47ef2', 'Geismar Landstr. 17b', '37083 G�ttingen', 'http://www.studip.de', '0551 / 381 985 0', 'testfakultaet@studip.de', '0551 / 381 985 3', 1, 16, 1156516698, 1156516698, 'Studip', 0, '');
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('2560f7c7674942a7dce8eeb238e15d93', 'Test Einrichtung', '1535795b0d6ddecac6813f5f6ac47ef2', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0, '');
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('536249daa596905f433e1f73578019db', 'Test Lehrstuhl', '1535795b0d6ddecac6813f5f6ac47ef2', '', '', '', '', '', '', 3, 16, 1156516698, 1156516698, 'Studip', 0, '');
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('f02e2b17bc0e99fc885da6ac4c2532dc', 'Test Abteilung', '1535795b0d6ddecac6813f5f6ac47ef2', '', '', '', '', '', '', 4, 16, 1156516698, 1156516698, 'Studip', 0, '');
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('ec2e364b28357106c0f8c282733dbe56', 'externe Bildungseinrichtungen', 'ec2e364b28357106c0f8c282733dbe56', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0, '');
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('7a4f19a0a2c321ab2b8f7b798881af7c', 'externe Einrichtung A', 'ec2e364b28357106c0f8c282733dbe56', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0, '');
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`, `lock_rule`) VALUES('110ce78ffefaf1e5f167cd7019b728bf', 'externe Einrichtung B', 'ec2e364b28357106c0f8c282733dbe56', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0, '');

--
-- Daten f�r Tabelle `lit_catalog`
--

REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('54181f281faa777941acc252aebaf26d', 'studip', 1156516698, 1156516698, 'Gvk', '387042768', 'Quickguide Strahlenschutz : [Aufgaben, Organisation, Schutzma�nahmen].', 'Wolf, Heike', '', '', 'Kissing : WEKA Media', '', '2004-01-01', '', '74 S : Ill.', '', '', 'ger', '[Der Strahlenschutzbeauftragte in Medizin und Technik / Heike Wolf] Praxisl�sungen', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('d6623a3c2b8285fb472aa759150148ad', 'studip', 1156516698, 1156516698, 'Gvk', '387042253', 'R�ntgenverordnung : (R�V) ; Verordnung �ber den Schutz vor Sch�den durch R�ntgenstrahlen.', 'Wolf, Heike', '', '', 'Kissing : WEKA Media', '', '2004-01-01', '', '50 S.', '', '', 'ger', '[Der Strahlenschutzbeauftragte in Medizin und Technik / Heike Wolf] Praxisl�sungen', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('15074ad4f2bd2c57cbc9dfb343c1355b', 'studip', 1156516698, 1156516698, 'Gvk', '384065813', 'Der Kater mit Hut', 'Geisel, Theodor Seuss', '', '', 'M�nchen [u.a.] : Piper', '', '2004-01-01', '', '75 S : zahlr. Ill ; 19 cm.', 'ISBN: 349224078X (kart.)', '', 'ger', 'Serie Piper ;, 4078', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('ce704bbc9453994daa05d76d2d04aba0', 'studip', 1156516698, 1156516698, 'Gvk', '379252104', 'Die volkswirtschaftliche Perspektive', 'Heise, Michael', '', '', 'In: Zeitschrift f�r das gesamte Kreditwesen, Vol. 57, No. 4 (2004), p. 211-217, Frankfurt, M. : Knapp', 'Kater, Ulrich;', '2004-01-01', '', 'graph. Darst.', '', '', 'ger', '', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('b5d115a7f7cad02b4535fb3090bf18da', 'studip', 1156516698, 1156516698, 'Gvk', '386883831', 'E-Learning: Qualit�t und Nutzerakzeptanz sichern : Beitr�ge zur Planung, Umsetzung und Evaluation multimedialer und netzgest�tzter Anwendungen', 'Zinke, Gert', '', '', 'Bielefeld : Bertelsmann', 'H�rtel, Michael; Bundesinstitut f�r Berufsbildung, ;', '2004-01-01', '', '159 S : graph. Darst ; 225 mm x 155 mm.', 'ISBN: 3763910204', '', 'ger', 'Berichte zur beruflichen Bildung ;, 265', '', '');

--
-- Daten f�r Tabelle `lit_list`
--

REPLACE INTO `lit_list` (`list_id`, `range_id`, `name`, `format`, `user_id`, `mkdate`, `chdate`, `priority`, `visibility`) VALUES('0b4d8c94244a1a571e3cc2afeeb15c5f', 'a07535cf2f8a72df33c12ddfa4b53dde', 'Basisliteratur der Veranstaltung', '**{dc_creator}** |({dc_contributor})||\r\n{dc_title}||\r\n{dc_identifier}||\r\n%%{published}%%||\r\n{note}||\r\n[{lit_plugin_display_name}]{external_link}|', '76ed43ef286fb55cf9e41beadb484a9f', 1343924971, 1343925058, 1, 1);

--
-- Daten f�r Tabelle `lit_list_content`
--

REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('48acf3d39374f46876d46df0f56203cd', '0b4d8c94244a1a571e3cc2afeeb15c5f', 'b5d115a7f7cad02b4535fb3090bf18da', '76ed43ef286fb55cf9e41beadb484a9f', 1343925058, 1343925058, '', 5);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('0cf7e4622ddbcc145b5792519979116f', '0b4d8c94244a1a571e3cc2afeeb15c5f', 'd6623a3c2b8285fb472aa759150148ad', '76ed43ef286fb55cf9e41beadb484a9f', 1343925058, 1343925058, '', 4);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('28de3cab6e36758b96ba757b65512cd2', '0b4d8c94244a1a571e3cc2afeeb15c5f', '54181f281faa777941acc252aebaf26d', '76ed43ef286fb55cf9e41beadb484a9f', 1343925058, 1343925058, '', 3);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('03e0d3910e15fd7ae2826ed6baf2b59d', '0b4d8c94244a1a571e3cc2afeeb15c5f', 'ce704bbc9453994daa05d76d2d04aba0', '76ed43ef286fb55cf9e41beadb484a9f', 1343925058, 1343925058, '', 2);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('7e129b140176dfc1a4c53e065fa5e8b1', '0b4d8c94244a1a571e3cc2afeeb15c5f', '15074ad4f2bd2c57cbc9dfb343c1355b', '76ed43ef286fb55cf9e41beadb484a9f', 1343925058, 1343925058, '', 1);

--
-- Daten f�r Tabelle `news_rss_range`
--

REPLACE INTO `news_rss_range` (`range_id`, `rss_id`, `range_type`) VALUES('studip', '70cefd1e80398bb20ff599636546cdff', 'global');

--
-- Daten f�r Tabelle `range_tree`
--

REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('3f93863e3d37ba0df286a6e7e26974ef', 'root', 0, 0, 'Einrichtungen der Universit�t', '', '');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('1323254564871354786157481484621', '3f93863e3d37ba0df286a6e7e26974ef', 1, 0, '', 'inst', '1535795b0d6ddecac6813f5f6ac47ef2');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('ce6c87bbf759b4cfd6f92d0c5560da5c', '1323254564871354786157481484621', 0, 0, 'Test Einrichtung', 'inst', '2560f7c7674942a7dce8eeb238e15d93');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('2f4f90ac9d8d832cc8c8a95910fde4eb', '1323254564871354786157481484621', 0, 1, 'Test Lehrstuhl', 'inst', '536249daa596905f433e1f73578019db');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('5d032f70c255f3e57cf8aa85a429ad4e', '1323254564871354786157481484621', 0, 2, 'Test Abteilung', 'inst', 'f02e2b17bc0e99fc885da6ac4c2532dc');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('a3d977a66f0010fa8e15c27dd71aff63', 'root', 0, 1, 'externe Bildungseinrichtungen', 'fak', 'ec2e364b28357106c0f8c282733dbe56');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('e0ff0ead6a8c5191078ed787cd7c0c1f', 'a3d977a66f0010fa8e15c27dd71aff63', 0, 0, 'externe Einrichtung A', 'inst', '7a4f19a0a2c321ab2b8f7b798881af7c');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('105b70b72dc1908ce2925e057c4a8daa', 'a3d977a66f0010fa8e15c27dd71aff63', 0, 1, 'externe Einrichtung B', 'inst', '110ce78ffefaf1e5f167cd7019b728bf');

--
-- Daten f�r Tabelle `rss_feeds`
--

REPLACE INTO `rss_feeds` (`feed_id`, `user_id`, `name`, `url`, `mkdate`, `chdate`, `priority`, `hidden`, `fetch_title`) VALUES('486d7fe04aa150a05c259b5ce95bcbbb', '76ed43ef286fb55cf9e41beadb484a9f', 'Stud.IP-Projekt (Stud.IP - Entwicklungsserver der Stud.IP Core Group)', 'http://develop.studip.de/studip/rss.php?id=51fdeef0efc6e3dd72d29eeb0cac2a16', 1156518361, 1240431662, 0, 0, 1);
REPLACE INTO `rss_feeds` (`feed_id`, `user_id`, `name`, `url`, `mkdate`, `chdate`, `priority`, `hidden`, `fetch_title`) VALUES('7fbdfba36eab17be85d35fbb21a2423f', '205f3efb7997a0fc9755da2b535038da', 'Stud.IP-Blog', 'http://blog.studip.de/feed', 1194629881, 1194629896, 0, 0, 1);

--
-- Daten f�r Tabelle `scm`
--

REPLACE INTO `scm` (`scm_id`, `range_id`, `user_id`, `tab_name`, `content`, `mkdate`, `chdate`) VALUES('a07df31918cc8e5ca0597e959a4a5297', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', 'Informationen', '', 1343924407, 1343924407);

--
-- Daten f�r Tabelle `seminare`
--

REPLACE INTO `seminare` (`Seminar_id`, `VeranstaltungsNummer`, `Institut_id`, `Name`, `Untertitel`, `status`, `Beschreibung`, `Ort`, `Sonstiges`, `Passwort`, `Lesezugriff`, `Schreibzugriff`, `start_time`, `duration_time`, `art`, `teilnehmer`, `vorrausetzungen`, `lernorga`, `leistungsnachweis`, `mkdate`, `chdate`, `ects`, `admission_endtime`, `admission_turnout`, `admission_binding`, `admission_type`, `admission_selection_take_place`, `admission_group`, `admission_prelim`, `admission_prelim_txt`, `admission_starttime`, `admission_endtime_sem`, `admission_disable_waitlist`, `admission_enable_quota`, `visible`, `showscore`, `modules`, `aux_lock_rule`, `lock_rule`) VALUES('7cb72dab1bf896a0b55c6aa7a70a3a86', '', 'ec2e364b28357106c0f8c282733dbe56', 'Test Studiengruppe', '', 99, 'Studiengruppen sind eine einfache M�glichkeit, mit KommilitonInnen, KollegInnen und anderen zusammenzuarbeiten.', '', '', '', 1, 1, 1254348000, -1, '', '', '', '', '', 1268739824, 1343924088, '', -1, 0, 0, 0, 0, '', 0, '', -1, -1, 0, 0, 1, 0, 395, NULL, NULL);
REPLACE INTO `seminare` (`Seminar_id`, `VeranstaltungsNummer`, `Institut_id`, `Name`, `Untertitel`, `status`, `Beschreibung`, `Ort`, `Sonstiges`, `Passwort`, `Lesezugriff`, `Schreibzugriff`, `start_time`, `duration_time`, `art`, `teilnehmer`, `vorrausetzungen`, `lernorga`, `leistungsnachweis`, `mkdate`, `chdate`, `ects`, `admission_endtime`, `admission_turnout`, `admission_binding`, `admission_type`, `admission_selection_take_place`, `admission_group`, `admission_prelim`, `admission_prelim_txt`, `admission_starttime`, `admission_endtime_sem`, `admission_disable_waitlist`, `admission_enable_quota`, `visible`, `showscore`, `modules`, `aux_lock_rule`, `lock_rule`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', '12345', '2560f7c7674942a7dce8eeb238e15d93', 'Test Lehrveranstaltung', 'eine normale Lehrveranstaltung', 1, '', '', '', '', 1, 1, 1380578400, 0, '', 'f�r alle Studierenden', 'abgeschlossenes Grundstudium', 'Referate in Gruppenarbeit', 'Klausur', 1343924407, 1383667270, '4', -1, 0, 0, 0, 0, '', 0, '', -1, -1, 0, 0, 1, 0, 20911, NULL, NULL);

--
-- Daten f�r Tabelle `seminar_cycle_dates`
--

REPLACE INTO `seminar_cycle_dates` (`metadate_id`, `seminar_id`, `start_time`, `end_time`, `weekday`, `description`, `sws`, `cycle`, `week_offset`, `sorter`, `mkdate`, `chdate`) VALUES('0309c794406b96bb01662e9e02593517', 'a07535cf2f8a72df33c12ddfa4b53dde', '09:00:00', '13:00:00', 4, '', '0.0', 1, 1, 0, 1343924407, 1343924407);
REPLACE INTO `seminar_cycle_dates` (`metadate_id`, `seminar_id`, `start_time`, `end_time`, `weekday`, `description`, `sws`, `cycle`, `week_offset`, `sorter`, `mkdate`, `chdate`) VALUES('d124b42deb48ac58adbd620b7ae6cc21', 'a07535cf2f8a72df33c12ddfa4b53dde', '09:00:00', '12:00:00', 1, '', '0.0', 1, 0, 0, 1343924407, 1343924407);

--
-- Daten f�r Tabelle `seminar_inst`
--

REPLACE INTO `seminar_inst` (`seminar_id`, `institut_id`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', '2560f7c7674942a7dce8eeb238e15d93');

--
-- Daten f�r Tabelle `seminar_sem_tree`
--

REPLACE INTO `seminar_sem_tree` (`seminar_id`, `sem_tree_id`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', '3d39528c1d560441fd4a8cb0b7717285');
REPLACE INTO `seminar_sem_tree` (`seminar_id`, `sem_tree_id`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', '5c41d2b4a5a8338e069dda987a624b74');
REPLACE INTO `seminar_sem_tree` (`seminar_id`, `sem_tree_id`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', 'dd7fff9151e85e7130cdb684edf0c370');

--
-- Daten f�r Tabelle `seminar_user`
--

REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`, `label`, `bind_calendar`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 'autor', 0, 5, '', 0, 1343924589, '', 'unknown', '', 1);
REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`, `label`, `bind_calendar`) VALUES('7cb72dab1bf896a0b55c6aa7a70a3a86', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 'dozent', 0, 8, '', 0, 0, '', 'unknown', '', 1);
REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`, `label`, `bind_calendar`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', '205f3efb7997a0fc9755da2b535038da', 'dozent', 0, 5, '', 0, 1343924407, '', 'yes', '', 1);
REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`, `label`, `bind_calendar`) VALUES('a07535cf2f8a72df33c12ddfa4b53dde', '7e81ec247c151c02ffd479511e24cc03', 'tutor', 0, 5, '', 0, 1343924407, '', 'yes', '', 1);

--
-- Daten f�r Tabelle `sem_tree`
--

REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('5b73e28644a3e259a6e0bc1e1499773c', 'root', 1, '', '', '1535795b0d6ddecac6813f5f6ac47ef2', 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('439618ae57d8c10dcaabcf7e21bcc1d9', '5b73e28644a3e259a6e0bc1e1499773c', 0, '', 'Test Studienbereich A', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('5c41d2b4a5a8338e069dda987a624b74', '5b73e28644a3e259a6e0bc1e1499773c', 1, '', 'Test Studienbereich B', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('3d39528c1d560441fd4a8cb0b7717285', '439618ae57d8c10dcaabcf7e21bcc1d9', 0, '', 'Test Studienbereich A-1', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('dd7fff9151e85e7130cdb684edf0c370', '439618ae57d8c10dcaabcf7e21bcc1d9', 1, '', 'Test Studienbereich A-2', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('01c8b1d188be40c5ac64b54a01aae294', '5b73e28644a3e259a6e0bc1e1499773c', 2, '', 'Test Studienbereich C', NULL, 0);

--
-- Daten f�r Tabelle `statusgruppen`
--

REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`, `calendar_group`) VALUES('86498c641ccf4f4d4e02f4961ccc3829', 'Lehrbeauftragte', '2560f7c7674942a7dce8eeb238e15d93', 3, 0, 0, 1156516698, 1156516698, 0);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`, `calendar_group`) VALUES('600403561c21a50ae8b4d41655bd2191', 'HochschullehrerIn', '2560f7c7674942a7dce8eeb238e15d93', 4, 0, 0, 1156516698, 1156516698, 0);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`, `calendar_group`) VALUES('efb56e092f33cb78a8766676042dc1c5', 'wiss. MitarbeiterIn', '2560f7c7674942a7dce8eeb238e15d93', 2, 0, 0, 1156516698, 1156516698, 0);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`, `calendar_group`) VALUES('5d40b1fc0434e6589d7341a3ee742baf', 'DirektorIn', '2560f7c7674942a7dce8eeb238e15d93', 1, 0, 0, 1156516698, 1156516698, 0);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`, `calendar_group`) VALUES('2f597139a049a768dbf8345a0a0af3de', 'Studierende', 'a07535cf2f8a72df33c12ddfa4b53dde', 1, 0, 0, 1343924562, 1343924562, 0);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`, `calendar_group`) VALUES('f4319d9909e9f7cb4692c16771887f22', 'Lehrende', 'a07535cf2f8a72df33c12ddfa4b53dde', 0, 0, 0, 1343924551, 1343924551, 0);

--
-- Daten f�r Tabelle `statusgruppe_user`
--

REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('efb56e092f33cb78a8766676042dc1c5', '7e81ec247c151c02ffd479511e24cc03', 1, 1, 1);
REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('5d40b1fc0434e6589d7341a3ee742baf', '205f3efb7997a0fc9755da2b535038da', 1, 1, 1);
REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('f4319d9909e9f7cb4692c16771887f22', '205f3efb7997a0fc9755da2b535038da', 1, 1, 1);
REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('f4319d9909e9f7cb4692c16771887f22', '7e81ec247c151c02ffd479511e24cc03', 2, 1, 1);
REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('2f597139a049a768dbf8345a0a0af3de', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 1, 1, 1);

--
-- Daten f�r Tabelle `studiengaenge`
--

REPLACE INTO `studiengaenge` (`studiengang_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('f981c9b42ca72788a09da4a45794a737', 'Informatik', '', 1311416397, 1311416397);
REPLACE INTO `studiengaenge` (`studiengang_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('6b9ac09535885ca55e29dd011e377c0a', 'Geschichte', '', 1311416418, 1311416418);

--
-- Daten f�r Tabelle `termine`
--

REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('856f55695ecc263d78a7386cdc63e398', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1391673600, 1391688000, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('bc8dfea3b0cab70d316513f17de0d543', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1390464000, 1390478400, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('6e3d7610078f945d4f1854bf8e91f62c', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1389254400, 1389268800, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('1aeda7c8188b1cab7c138bffa8950a3b', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1386835200, 1386849600, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('48ee2f2c69495dafeb62b061e1cd3ff5', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1383552000, 1383562800, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('336336908740c15556f468a214a613eb', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1384761600, 1384772400, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('16c8c7b1cecbd79dd596f9f4354ad7af', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1385971200, 1385982000, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('3bbccfb5d151e09d6c55c87e60f44bff', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1387180800, 1387191600, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('8c9bbc5d9978123a1dbfaf3dc4911970', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1389600000, 1389610800, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('7bd71630e82b4577e6b7042931aa4177', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1390809600, 1390820400, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('b5656d6a7d9945808bb671aa57e59d8e', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1383206400, 1383220800, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('e1f1ba3ab74189c899a3db312066b619', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1384416000, 1384430400, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('0d2ebc65277500a28e9bec8eb2424ac1', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1385625600, 1385640000, 1383667270, 1383667270, 1, NULL, '', '0309c794406b96bb01662e9e02593517');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('be6aff5747fae3274c31b63a7eb80913', 'a07535cf2f8a72df33c12ddfa4b53dde', '76ed43ef286fb55cf9e41beadb484a9f', '0', NULL, 1382338800, 1382349600, 1383667270, 1383667270, 1, NULL, '', 'd124b42deb48ac58adbd620b7ae6cc21');

--
-- Daten f�r Tabelle `user_visibility_settings`
--

REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 1, 0, '0', 'Allgemeine Daten', 4, NULL, 'commondata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 2, 0, '0', 'Private Daten', 4, NULL, 'privatedata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 3, 0, '0', 'Studien-/Einrichtungsdaten', 4, NULL, 'studdata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 4, 0, '0', 'Zus�tzliche Datenfelder', 4, NULL, 'additionaldata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 5, 0, '0', 'Eigene Kategorien', 4, NULL, 'owncategory');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 6, 0, '0', 'Plugins', 4, NULL, 'plugins');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 7, 1, '1', 'Ank�ndigungen', 4, NULL, 'news');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('6235c46eb9e962866ebdceece739ace5', 8, 0, '0', 'Allgemeine Daten', 4, NULL, 'commondata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('6235c46eb9e962866ebdceece739ace5', 9, 0, '0', 'Private Daten', 4, NULL, 'privatedata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('6235c46eb9e962866ebdceece739ace5', 10, 0, '0', 'Studien-/Einrichtungsdaten', 4, NULL, 'studdata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('6235c46eb9e962866ebdceece739ace5', 11, 0, '0', 'Zus�tzliche Datenfelder', 4, NULL, 'additionaldata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('6235c46eb9e962866ebdceece739ace5', 12, 0, '0', 'Eigene Kategorien', 4, NULL, 'owncategory');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('6235c46eb9e962866ebdceece739ace5', 13, 0, '0', 'Plugins', 4, NULL, 'plugins');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 14, 0, '0', 'Allgemeine Daten', 4, NULL, 'commondata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 15, 0, '0', 'Private Daten', 4, NULL, 'privatedata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 16, 0, '0', 'Studien-/Einrichtungsdaten', 4, NULL, 'studdata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 17, 0, '0', 'Zus�tzliche Datenfelder', 4, NULL, 'additionaldata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 18, 0, '0', 'Eigene Kategorien', 4, NULL, 'owncategory');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 19, 0, '0', 'Plugins', 4, NULL, 'plugins');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 20, 16, '1', 'Wo ich studiere', 4, NULL, 'studying');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 21, 17, '1', 'Matrikelnummer', 4, NULL, 'ce73a10d07b3bb13c0132d363549efda');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('205f3efb7997a0fc9755da2b535038da', 22, 0, '0', 'Allgemeine Daten', 4, NULL, 'commondata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('205f3efb7997a0fc9755da2b535038da', 23, 0, '0', 'Private Daten', 4, NULL, 'privatedata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('205f3efb7997a0fc9755da2b535038da', 24, 0, '0', 'Studien-/Einrichtungsdaten', 4, NULL, 'studdata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('205f3efb7997a0fc9755da2b535038da', 25, 0, '0', 'Zus�tzliche Datenfelder', 4, NULL, 'additionaldata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('205f3efb7997a0fc9755da2b535038da', 26, 0, '0', 'Eigene Kategorien', 4, NULL, 'owncategory');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('205f3efb7997a0fc9755da2b535038da', 27, 0, '0', 'Plugins', 4, NULL, 'plugins');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 28, 0, '0', 'Allgemeine Daten', 4, NULL, 'commondata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 29, 0, '0', 'Private Daten', 4, NULL, 'privatedata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 30, 0, '0', 'Studien-/Einrichtungsdaten', 4, NULL, 'studdata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 31, 0, '0', 'Zus�tzliche Datenfelder', 4, NULL, 'additionaldata');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 32, 0, '0', 'Eigene Kategorien', 4, NULL, 'owncategory');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 33, 0, '0', 'Plugins', 4, NULL, 'plugins');
REPLACE INTO `user_visibility_settings` (`user_id`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`, `identifier`) VALUES('7e81ec247c151c02ffd479511e24cc03', 34, 31, '1', 'Matrikelnummer', 4, NULL, 'ce73a10d07b3bb13c0132d363549efda');

--
-- Daten f�r Tabelle `user_info`
--

REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `motto`, `lock_rule`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', '', '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `motto`, `lock_rule`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', '', '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `motto`, `lock_rule`) VALUES('205f3efb7997a0fc9755da2b535038da', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', '', '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `motto`, `lock_rule`) VALUES('6235c46eb9e962866ebdceece739ace5', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', '', '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `motto`, `lock_rule`) VALUES('7e81ec247c151c02ffd479511e24cc03', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', '', '');

--
-- Daten f�r Tabelle `user_inst`
--

REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('205f3efb7997a0fc9755da2b535038da', '2560f7c7674942a7dce8eeb238e15d93', 'dozent', '', '', '', '', 0, 0, 1);
REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('6235c46eb9e962866ebdceece739ace5', '2560f7c7674942a7dce8eeb238e15d93', 'admin', '', '', '', '', 0, 0, 1);
REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('7e81ec247c151c02ffd479511e24cc03', '2560f7c7674942a7dce8eeb238e15d93', 'tutor', '', '', '', '', 0, 0, 1);
REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', '2560f7c7674942a7dce8eeb238e15d93', 'user', '', '', '', '', 1, 0, 1);

--
-- Daten f�r Tabelle `user_studiengang`
--

REPLACE INTO `user_studiengang` (`user_id`, `studiengang_id`, `semester`, `abschluss_id`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', '6b9ac09535885ca55e29dd011e377c0a', 2, '228234544820cdf75db55b42d1ea3ecc');
REPLACE INTO `user_studiengang` (`user_id`, `studiengang_id`, `semester`, `abschluss_id`) VALUES('7e81ec247c151c02ffd479511e24cc03', 'f981c9b42ca72788a09da4a45794a737', 1, '228234544820cdf75db55b42d1ea3ecc');

--
-- Daten f�r Tabelle `vote`
--

REPLACE INTO `vote` (`vote_id`, `author_id`, `range_id`, `type`, `title`, `question`, `state`, `startdate`, `stopdate`, `timespan`, `mkdate`, `chdate`, `resultvisibility`, `multiplechoice`, `anonymous`, `changeable`, `co_visibility`, `namesvisibility`) VALUES('b5329b23b7f865c62028e226715e1914', '76ed43ef286fb55cf9e41beadb484a9f', 'studip', 'vote', 'Nutzen Sie bereits Stud.IP?', 'Haben Sie Stud.IP bereits im Einsatz oder planen Sie, es einzusetzen?', 'active', 1383667417, NULL, NULL, 1142525062, 1383667418, 'delivery', 1, 0, 1, NULL, 0);

--
-- Daten f�r Tabelle `voteanswers`
--

REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('42a47ba18ad12df72fca2898d5e27132', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 2.5', 23, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('2f05e4b581d9941a4262ed4b65914b9a', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 2.4', 22, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('5d664c7914aaf2b5fbc66ab871a0e27b', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 2.3', 21, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('56991b7ad13aa8f5315e9bc412c6a199', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 2.2', 20, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('3c065ec2b3037c39991cc5d99eca185c', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 2.1', 19, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('2ea4169a90dbcc56be1610f75d86d460', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 2.0', 18, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('ddcf45e577e20133fcc5bf65aef2a075', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.11', 17, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('71b97633448009af49c43b5a56de4c7f', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.10', 16, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('ef983352938c5714f23bc47257dd2489', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.9', 15, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('5fb01b6623c848c3bf33cce70675b91a', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.8', 14, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('03bce9c940fc76f5eb90ab7b151cf34d', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.7', 13, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('816a463bef33edcdf1ed82e94166f1ad', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.6', 12, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('dddf684fbcac58f7ffd0804b7095c71b', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.5', 11, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('b1083fbf35c8782ad35c1a0c9364f2c2', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.4', 10, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('f31fab58d15388245396dc59de346e90', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.3', 9, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('6f51e5d957aa6e7a3e8494e0e56c43aa', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.2', 8, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('8502e4b4600a12b2d5d43aefe2930be4', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.1.5', 7, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('8112e4b4600a12b2d5d43aecf2930be4', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.1.0', 6, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('8342e4b4600a12b2d5d43aecf2930be4', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 1.0', 5, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('dc1b49bf35e9cfbfcece807b21cec0ef', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 0.9.5', 4, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('ddfd889094a6cea75703728ee7b48806', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 0.9.0', 3, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('58281eda805a0fe5741c74a2c612cb05', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 0.8.15', 2, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('c8ade4c7f3bbe027f6c19016dd3e001c', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 0.8.0', 1, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('112f7c8f52b0a2a6eff9cddf93b419c7', 'b5329b23b7f865c62028e226715e1914', 'Ich nutze die Version 0.7.5', 0, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('f963fccda920be268aa116ae870a8984', 'b5329b23b7f865c62028e226715e1914', 'Ich plane, es demn�chst einzusetzen', 24, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('52390fa347c0f58b80a6f1d42a1c186c', 'b5329b23b7f865c62028e226715e1914', 'Ich schaue mich nur mal um', 25, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('157edc4f5682113c304b19295bfb5b2f', 'b5329b23b7f865c62028e226715e1914', 'Ich bin nicht interessiert', 26, 0, 0);

