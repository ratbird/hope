
--
-- Daten für Tabelle `auth_user_md5`
--

REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', 'root@studip', 'ae2b1fca515949e5d54fb22b8ed95575', 'root', 'Root', 'Studip', 'root@localhost', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('205f3efb7997a0fc9755da2b535038da', 'test_dozent', 'ae2b1fca515949e5d54fb22b8ed95575', 'dozent', 'Testaccount', 'Dozent', 'dozent@studip.de', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('6235c46eb9e962866ebdceece739ace5', 'test_admin', 'ae2b1fca515949e5d54fb22b8ed95575', 'admin', 'Testaccount', 'Admin', 'admin@studip.de', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('7e81ec247c151c02ffd479511e24cc03', 'test_tutor', 'ae2b1fca515949e5d54fb22b8ed95575', 'tutor', 'Testaccount', 'Tutor', 'tutor@studip.de', '', NULL, 0, NULL, NULL, 'unknown');
REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', 'test_autor', 'ae2b1fca515949e5d54fb22b8ed95575', 'autor', 'Testaccount', 'Autor', 'autor@studip.de', '', NULL, 0, NULL, NULL, 'unknown');

--
-- Daten für Tabelle `aux_lock_rules`
--

REPLACE INTO `aux_lock_rules` (`lock_id`, `name`, `description`, `attributes`, `sorting`) VALUES('d34f75dbb9936ba300086e096b718242', 'Standard', '', 'a:5:{s:10:"vasemester";s:1:"1";s:4:"vanr";s:1:"1";s:7:"vatitle";s:1:"0";s:8:"vadozent";s:1:"0";s:32:"ce73a10d07b3bb13c0132d363549efda";s:1:"1";}', 'a:5:{s:10:"vasemester";s:1:"0";s:4:"vanr";s:1:"0";s:7:"vatitle";s:1:"0";s:8:"vadozent";s:1:"0";s:32:"ce73a10d07b3bb13c0132d363549efda";s:1:"0";}');

--
-- Daten für Tabelle `config`
--

REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('48f849a4927f8ac5231da5352076f16a', '', 'STUDYGROUPS_ENABLE', '1', 0, 'boolean', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', 'Studiengruppen', '');
REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('5175463a0ef7d17507716907c7491561', '', 'STUDYGROUP_SETTINGS', 'forum:1|documents:1|literature:0|chat:1|wiki:1|scm:0|documents_folder_permissions:0|participants:1|schedule:0', 0, 'string', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', '', '');
REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('9f1c998d46f55ac38da3a53072a4086b', '', 'STUDYGROUP_DEFAULT_INST', 'ec2e364b28357106c0f8c282733dbe56', 0, 'string', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', '', '');
REPLACE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES('bcd4820eebd8e027cef91bc761ab9a75', '', 'STUDYGROUP_TERMS', 'Mir ist bekannt, dass ich die Gruppe nicht zu rechtswidrigen Zwecken nutzen darf. Dazu zählen u.a. Urheberrechtsverletzungen, Beleidigungen und andere Persönlichkeitsdelikte.\r\n\r\nIch erkläre mich damit einverstanden, dass AdministratorInnen die Inhalte der Gruppe zu Kontrollzwecken einsehen dürfen.', 0, 'string', 'global', '', 0, 1268739461, 1268739461, 'Studiengruppen', '', '');

--
-- Daten für Tabelle `datafields`
--

REPLACE INTO `datafields` (`datafield_id`, `name`, `object_type`, `object_class`, `edit_perms`, `view_perms`, `priority`, `mkdate`, `chdate`, `type`, `typeparam`) VALUES('ce73a10d07b3bb13c0132d363549efda', 'Matrikelnummer', 'user', '7', 'user', 'dozent', 0, NULL, NULL, 'textline', '');

--
-- Daten für Tabelle `dokumente`
--

REPLACE INTO `dokumente` (`dokument_id`, `range_id`, `user_id`, `seminar_id`, `name`, `description`, `filename`, `mkdate`, `chdate`, `filesize`, `autor_host`, `downloads`, `url`, `protected`, `priority`, `author_name`) VALUES('c51a12e44c667b370fe2c497fbfc3c21', '823b5c771f17d4103b1828251c29a7cb', '76ed43ef286fb55cf9e41beadb484a9f', '834499e2b8a2cd71637890e5de31cba3', 'Stud.IP-Produktbroschüre im PDF-Format', '', 'studip_broschuere.pdf', 1156516698, 1156516698, 295294, '217.94.188.5', 3, 'http://www.studip.de/download/studip_broschuere.pdf', 0, 0, 'Root Studip');

--
-- Daten für Tabelle `folder`
--

REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('dad53cd0f0d9f36817c3c9c7c124bda3', 'ec2e364b28357106c0f8c282733dbe56', '', 'Allgemeiner Dateiordner', 'Ablage für allgemeine Ordner und Dokumente der Einrichtung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('b58081c411c76814bc8f78425fb2ab81', '7a4f19a0a2c321ab2b8f7b798881af7c', '', 'Allgemeiner Dateiordner', 'Ablage für allgemeine Ordner und Dokumente der Einrichtung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('694cdcef09c2b8e70a7313b028e36fb6', '110ce78ffefaf1e5f167cd7019b728bf', '', 'Allgemeiner Dateiordner', 'Ablage für allgemeine Ordner und Dokumente der Einrichtung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('823b5c771f17d4103b1828251c29a7cb', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', 'Allgemeiner Dateiordner', 'Ablage für allgemeine Ordner und Dokumente der Veranstaltung', 7, 1156516698, 1156516698, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('c996fa8545b9ed2ca0c6772cca784019', 'ab10d788d28787ea00ca39770a2516d9', '76ed43ef286fb55cf9e41beadb484a9f', 'nur lesbarer Ordner', '', 5, 1176474403, 1176474408, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('963084e86963b5cd2d086eafd5f04eb5', 'ab10d788d28787ea00ca39770a2516d9', '76ed43ef286fb55cf9e41beadb484a9f', 'unsichtbarer Ordner', '', 0, 1176474417, 1176474422, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('17b75cc079bdad8e54d2f7ce3ce29f2e', 'ab10d788d28787ea00ca39770a2516d9', '76ed43ef286fb55cf9e41beadb484a9f', 'Hausaufgabenordner', '', 3, 1176474443, 1176474449, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('aa15759a75c167e38425d17539e7a7be', '41ad59c9b6cdafca50e42fe6bc68af4f', '205f3efb7997a0fc9755da2b535038da', 'Dateiordner der Gruppe: Thema 1', 'Ablage für Ordner und Dokumente dieser Gruppe', 15, 1194628738, 1194628738, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('5b1b53b48c487a639ec493afbb270d4c', '151c33059a90b6138d280862f5d4b3c2', '205f3efb7997a0fc9755da2b535038da', 'Dateiordner der Gruppe: Thema 2', 'Ablage für Ordner und Dokumente dieser Gruppe', 15, 1194628768, 1194628768, 0);
REPLACE INTO `folder` (`folder_id`, `range_id`, `user_id`, `name`, `description`, `permission`, `mkdate`, `chdate`, `priority`) VALUES('17534632a6a9145f21c9fc99b7557bf9', 'a5061826bf8db7487a774f92ce2a4d23', '205f3efb7997a0fc9755da2b535038da', 'Dateiordner der Gruppe: Thema 3', 'Ablage für Ordner und Dokumente dieser Gruppe', 15, 1194628789, 1194628789, 0);

--
-- Daten für Tabelle `Institute`
--

REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('1535795b0d6ddecac6813f5f6ac47ef2', 'Test Fakultät', '1535795b0d6ddecac6813f5f6ac47ef2', 'Geismar Landstr. 17b', '37083 Göttingen', 'http://www.studip.de', '0551 / 381 985 0', 'testfakultaet@studip.de', '0551 / 381 985 3', 1, 16, 1156516698, 1156516698, 'Studip', 0);
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('2560f7c7674942a7dce8eeb238e15d93', 'Test Einrichtung', '1535795b0d6ddecac6813f5f6ac47ef2', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0);
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('536249daa596905f433e1f73578019db', 'Test Lehrstuhl', '1535795b0d6ddecac6813f5f6ac47ef2', '', '', '', '', '', '', 3, 16, 1156516698, 1156516698, 'Studip', 0);
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('f02e2b17bc0e99fc885da6ac4c2532dc', 'Test Abteilung', '1535795b0d6ddecac6813f5f6ac47ef2', '', '', '', '', '', '', 4, 16, 1156516698, 1156516698, 'Studip', 0);
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('ec2e364b28357106c0f8c282733dbe56', 'externe Bildungseinrichtungen', 'ec2e364b28357106c0f8c282733dbe56', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0);
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('7a4f19a0a2c321ab2b8f7b798881af7c', 'externe Einrichtung A', 'ec2e364b28357106c0f8c282733dbe56', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0);
REPLACE INTO `Institute` (`Institut_id`, `Name`, `fakultaets_id`, `Strasse`, `Plz`, `url`, `telefon`, `email`, `fax`, `type`, `modules`, `mkdate`, `chdate`, `lit_plugin_name`, `srienabled`) VALUES('110ce78ffefaf1e5f167cd7019b728bf', 'externe Einrichtung B', 'ec2e364b28357106c0f8c282733dbe56', '', '', '', '', '', '', 1, 16, 1156516698, 1156516698, 'Studip', 0);

--
-- Daten für Tabelle `lit_catalog`
--

REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('54181f281faa777941acc252aebaf26d', 'studip', 1156516698, 1156516698, 'Gvk', '387042768', 'Quickguide Strahlenschutz : [Aufgaben, Organisation, Schutzmaßnahmen].', 'Wolf, Heike', '', '', 'Kissing : WEKA Media', '', '2004-01-01', '', '74 S : Ill.', '', '', 'ger', '[Der Strahlenschutzbeauftragte in Medizin und Technik / Heike Wolf] Praxislösungen', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('d6623a3c2b8285fb472aa759150148ad', 'studip', 1156516698, 1156516698, 'Gvk', '387042253', 'Röntgenverordnung : (RÖV) ; Verordnung über den Schutz vor Schäden durch Röntgenstrahlen.', 'Wolf, Heike', '', '', 'Kissing : WEKA Media', '', '2004-01-01', '', '50 S.', '', '', 'ger', '[Der Strahlenschutzbeauftragte in Medizin und Technik / Heike Wolf] Praxislösungen', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('15074ad4f2bd2c57cbc9dfb343c1355b', 'studip', 1156516698, 1156516698, 'Gvk', '384065813', 'Der Kater mit Hut', 'Geisel, Theodor Seuss', '', '', 'München [u.a.] : Piper', '', '2004-01-01', '', '75 S : zahlr. Ill ; 19 cm.', 'ISBN: 349224078X (kart.)', '', 'ger', 'Serie Piper ;, 4078', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('ce704bbc9453994daa05d76d2d04aba0', 'studip', 1156516698, 1156516698, 'Gvk', '379252104', 'Die volkswirtschaftliche Perspektive', 'Heise, Michael', '', '', 'In: Zeitschrift für das gesamte Kreditwesen, Vol. 57, No. 4 (2004), p. 211-217, Frankfurt, M. : Knapp', 'Kater, Ulrich;', '2004-01-01', '', 'graph. Darst.', '', '', 'ger', '', '', '');
REPLACE INTO `lit_catalog` (`catalog_id`, `user_id`, `mkdate`, `chdate`, `lit_plugin`, `accession_number`, `dc_title`, `dc_creator`, `dc_subject`, `dc_description`, `dc_publisher`, `dc_contributor`, `dc_date`, `dc_type`, `dc_format`, `dc_identifier`, `dc_source`, `dc_language`, `dc_relation`, `dc_coverage`, `dc_rights`) VALUES('b5d115a7f7cad02b4535fb3090bf18da', 'studip', 1156516698, 1156516698, 'Gvk', '386883831', 'E-Learning: Qualität und Nutzerakzeptanz sichern : Beiträge zur Planung, Umsetzung und Evaluation multimedialer und netzgestützter Anwendungen', 'Zinke, Gert', '', '', 'Bielefeld : Bertelsmann', 'Härtel, Michael; Bundesinstitut für Berufsbildung, ;', '2004-01-01', '', '159 S : graph. Darst ; 225 mm x 155 mm.', 'ISBN: 3763910204', '', 'ger', 'Berichte zur beruflichen Bildung ;, 265', '', '');

--
-- Daten für Tabelle `lit_list`
--

REPLACE INTO `lit_list` (`list_id`, `range_id`, `name`, `format`, `user_id`, `mkdate`, `chdate`, `priority`, `visibility`) VALUES('3332f270b96fb23cdd2463cef8220b29', '834499e2b8a2cd71637890e5de31cba3', 'Basisliteratur der Veranstaltung', '**{dc_creator}** |({dc_contributor})||\r\n{dc_title}||\r\n{dc_identifier}||\r\n%%{published}%%||\r\n{note}||\r\n[{lit_plugin}]{external_link}|\r\n', '76ed43ef286fb55cf9e41beadb484a9f', 1156516698, 1156516698, 1, 1);

--
-- Daten für Tabelle `lit_list_content`
--

REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('1e6d6e6f179986f8c2be5b1c2ed37631', '3332f270b96fb23cdd2463cef8220b29', '15074ad4f2bd2c57cbc9dfb343c1355b', '76ed43ef286fb55cf9e41beadb484a9f', 1156516698, 1156516698, '', 1);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('4bd3001d8260001914e9ab8716a4fe70', '3332f270b96fb23cdd2463cef8220b29', 'ce704bbc9453994daa05d76d2d04aba0', '76ed43ef286fb55cf9e41beadb484a9f', 1156516698, 1156516698, '', 2);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('ce226125c3cf579cf28e5c96a8dea7a9', '3332f270b96fb23cdd2463cef8220b29', '54181f281faa777941acc252aebaf26d', '76ed43ef286fb55cf9e41beadb484a9f', 1156516698, 1156516698, '', 3);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('1d4ff2d55489dd9284f6a83dfc69149e', '3332f270b96fb23cdd2463cef8220b29', 'd6623a3c2b8285fb472aa759150148ad', '76ed43ef286fb55cf9e41beadb484a9f', 1156516698, 1156516698, '', 4);
REPLACE INTO `lit_list_content` (`list_element_id`, `list_id`, `catalog_id`, `user_id`, `mkdate`, `chdate`, `note`, `priority`) VALUES('293e90c3c6511d2c8e1d4ba7b51daa98', '3332f270b96fb23cdd2463cef8220b29', 'b5d115a7f7cad02b4535fb3090bf18da', '76ed43ef286fb55cf9e41beadb484a9f', 1156516698, 1156516698, '', 5);

--
-- Daten für Tabelle `news_rss_range`
--

REPLACE INTO `news_rss_range` (`range_id`, `rss_id`, `range_type`) VALUES('studip', '70cefd1e80398bb20ff599636546cdff', 'global');

--
-- Daten für Tabelle `px_topics`
--

REPLACE INTO `px_topics` (`topic_id`, `parent_id`, `root_id`, `name`, `description`, `mkdate`, `chdate`, `author`, `author_host`, `Seminar_id`, `user_id`) VALUES('5260172c3d6f9d56d21b06bf4c278b52', '0', '5260172c3d6f9d56d21b06bf4c278b52', 'Allgemeine Diskussionen', 'Hier ist Raum für allgemeine Diskussionen', 1084723039, 1084723039, '', '134.76.62.67', 'ec2e364b28357106c0f8c282733dbe56', '76ed43ef286fb55cf9e41beadb484a9f');
REPLACE INTO `px_topics` (`topic_id`, `parent_id`, `root_id`, `name`, `description`, `mkdate`, `chdate`, `author`, `author_host`, `Seminar_id`, `user_id`) VALUES('b30ec732ee1c69a275b2d6adaae49cdc', '0', 'b30ec732ee1c69a275b2d6adaae49cdc', 'Allgemeine Diskussionen', 'Hier ist Raum für allgemeine Diskussionen', 1084723053, 1084723053, '', '134.76.62.67', '7a4f19a0a2c321ab2b8f7b798881af7c', '76ed43ef286fb55cf9e41beadb484a9f');
REPLACE INTO `px_topics` (`topic_id`, `parent_id`, `root_id`, `name`, `description`, `mkdate`, `chdate`, `author`, `author_host`, `Seminar_id`, `user_id`) VALUES('9f394dffd08043f13cc65ffff65bfa05', '0', '9f394dffd08043f13cc65ffff65bfa05', 'Allgemeine Diskussionen', 'Hier ist Raum für allgemeine Diskussionen', 1084723061, 1084723061, '', '134.76.62.67', '110ce78ffefaf1e5f167cd7019b728bf', '76ed43ef286fb55cf9e41beadb484a9f');
REPLACE INTO `px_topics` (`topic_id`, `parent_id`, `root_id`, `name`, `description`, `mkdate`, `chdate`, `author`, `author_host`, `Seminar_id`, `user_id`) VALUES('515b5485c3c72065df1c8980725e14ca', '0', '515b5485c3c72065df1c8980725e14ca', 'Allgemeine Diskussionen', '', 1176472544, 1176472551, 'Root Studip', '81.20.112.44', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f');

--
-- Daten für Tabelle `range_tree`
--

REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('3f93863e3d37ba0df286a6e7e26974ef', 'root', 0, 0, 'Einrichtungen der Universität', '', '');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('1323254564871354786157481484621', '3f93863e3d37ba0df286a6e7e26974ef', 1, 0, '', 'inst', '1535795b0d6ddecac6813f5f6ac47ef2');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('ce6c87bbf759b4cfd6f92d0c5560da5c', '1323254564871354786157481484621', 0, 0, 'Test Einrichtung', 'inst', '2560f7c7674942a7dce8eeb238e15d93');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('2f4f90ac9d8d832cc8c8a95910fde4eb', '1323254564871354786157481484621', 0, 1, 'Test Lehrstuhl', 'inst', '536249daa596905f433e1f73578019db');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('5d032f70c255f3e57cf8aa85a429ad4e', '1323254564871354786157481484621', 0, 2, 'Test Abteilung', 'inst', 'f02e2b17bc0e99fc885da6ac4c2532dc');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('a3d977a66f0010fa8e15c27dd71aff63', 'root', 0, 1, 'externe Bildungseinrichtungen', 'fak', 'ec2e364b28357106c0f8c282733dbe56');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('e0ff0ead6a8c5191078ed787cd7c0c1f', 'a3d977a66f0010fa8e15c27dd71aff63', 0, 0, 'externe Einrichtung A', 'inst', '7a4f19a0a2c321ab2b8f7b798881af7c');
REPLACE INTO `range_tree` (`item_id`, `parent_id`, `level`, `priority`, `name`, `studip_object`, `studip_object_id`) VALUES('105b70b72dc1908ce2925e057c4a8daa', 'a3d977a66f0010fa8e15c27dd71aff63', 0, 1, 'externe Einrichtung B', 'inst', '110ce78ffefaf1e5f167cd7019b728bf');

--
-- Daten für Tabelle `rss_feeds`
--

REPLACE INTO `rss_feeds` (`feed_id`, `user_id`, `name`, `url`, `mkdate`, `chdate`, `priority`, `hidden`, `fetch_title`) VALUES('486d7fe04aa150a05c259b5ce95bcbbb', '76ed43ef286fb55cf9e41beadb484a9f', 'Stud.IP-Projekt (Stud.IP - Entwicklungsserver der Stud.IP Core Group)', 'http://develop.studip.de/studip/rss.php?id=51fdeef0efc6e3dd72d29eeb0cac2a16', 1156518361, 1240431662, 0, 0, 1);
REPLACE INTO `rss_feeds` (`feed_id`, `user_id`, `name`, `url`, `mkdate`, `chdate`, `priority`, `hidden`, `fetch_title`) VALUES('7fbdfba36eab17be85d35fbb21a2423f', '205f3efb7997a0fc9755da2b535038da', 'Stud.IP-Blog', 'http://blog.studip.de/feed', 1194629881, 1194629896, 0, 0, 1);

--
-- Daten für Tabelle `scm`
--

REPLACE INTO `scm` (`scm_id`, `range_id`, `user_id`, `tab_name`, `content`, `mkdate`, `chdate`) VALUES('63863907e672f85e804de69a04d947c1', '834499e2b8a2cd71637890e5de31cba3', '205f3efb7997a0fc9755da2b535038da', 'Informationen', 'Wenn sie sich für ein Referatsthema anmelden möchten, ordnen Sie sich bitte selbst einer Referatsgruppe zu.\r\n\r\nSie finden diese Gruppen unter\r\n\r\n%%TeilnehmerInnen%%\r\n\r\nund dann \r\n\r\n%%Funktionen / Gruppen%%', 1194628681, 1194628711);
REPLACE INTO `scm` (`scm_id`, `range_id`, `user_id`, `tab_name`, `content`, `mkdate`, `chdate`) VALUES('1e6c94cdd7033ea745467df9fdfc5083', '834499e2b8a2cd71637890e5de31cba3', '205f3efb7997a0fc9755da2b535038da', 'Pfefferminz', 'Die Pfefferminze (Mentha x piperita) ist eine Heil- und beliebte Gewürzpflanze aus der Gattung der Minzen. Es ist eine Kreuzung zwischen M. aquatica x (und) M. spicata. 2004 wurde sie zur Arzneipflanze des Jahres gewählt.', 1194629051, 1194629136);

--
-- Daten für Tabelle `seminare`
--

REPLACE INTO `seminare` (`Seminar_id`, `VeranstaltungsNummer`, `Institut_id`, `Name`, `Untertitel`, `status`, `Beschreibung`, `Ort`, `Sonstiges`, `Passwort`, `Lesezugriff`, `Schreibzugriff`, `start_time`, `duration_time`, `art`, `teilnehmer`, `vorrausetzungen`, `lernorga`, `leistungsnachweis`, `mkdate`, `chdate`, `ects`, `admission_endtime`, `admission_turnout`, `admission_binding`, `admission_type`, `admission_selection_take_place`, `admission_group`, `admission_prelim`, `admission_prelim_txt`, `admission_starttime`, `admission_endtime_sem`, `admission_disable_waitlist`, `admission_enable_quota`, `visible`, `showscore`, `modules`, `aux_lock_rule`, `lock_rule`) VALUES('834499e2b8a2cd71637890e5de31cba3', '12345', '2560f7c7674942a7dce8eeb238e15d93', 'Test Lehrveranstaltung', 'eine normale Lehrveranstaltung', 1, '', '', '', '', 1, 1, 1285884000, 0, '', 'für alle Studierenden', 'abgeschlossenes Grundstudium', 'Referate in Gruppenarbeit', 'Klausur', 1240429345, 1293119028, '4', -1, 0, 0, 0, 0, '', 0, '', -1, -1, 0, 0, 1, 0, 20911, 'd34f75dbb9936ba300086e096b718242', NULL);
REPLACE INTO `seminare` (`Seminar_id`, `VeranstaltungsNummer`, `Institut_id`, `Name`, `Untertitel`, `status`, `Beschreibung`, `Ort`, `Sonstiges`, `Passwort`, `Lesezugriff`, `Schreibzugriff`, `start_time`, `duration_time`, `art`, `teilnehmer`, `vorrausetzungen`, `lernorga`, `leistungsnachweis`, `mkdate`, `chdate`, `ects`, `admission_endtime`, `admission_turnout`, `admission_binding`, `admission_type`, `admission_selection_take_place`, `admission_group`, `admission_prelim`, `admission_prelim_txt`, `admission_starttime`, `admission_endtime_sem`, `admission_disable_waitlist`, `admission_enable_quota`, `visible`, `showscore`, `modules`, `aux_lock_rule`, `lock_rule`) VALUES('7cb72dab1bf896a0b55c6aa7a70a3a86', '', 'ec2e364b28357106c0f8c282733dbe56', 'Test Studiengruppe', '', 99, 'Studiengruppen sind eine einfache Möglichkeit, mit KommilitonInnen, KollegInnen und anderen zusammenzuarbeiten. ', '', '', '', 1, 1, 1254348000, -1, '', '', '', '', '', 1268739824, 1268739824, '', -1, 0, NULL, 0, 0, NULL, 0, '', -1, -1, 0, 0, 1, 0, 395, NULL, NULL);

--
-- Daten für Tabelle `seminare`
--

REPLACE INTO `seminare` (`Seminar_id`, `VeranstaltungsNummer`, `Institut_id`, `Name`, `Untertitel`, `status`, `Beschreibung`, `Ort`, `Sonstiges`, `Passwort`, `Lesezugriff`, `Schreibzugriff`, `start_time`, `duration_time`, `art`, `teilnehmer`, `vorrausetzungen`, `lernorga`, `leistungsnachweis`, `mkdate`, `chdate`, `ects`, `admission_endtime`, `admission_turnout`, `admission_binding`, `admission_type`, `admission_selection_take_place`, `admission_group`, `admission_prelim`, `admission_prelim_txt`, `admission_starttime`, `admission_endtime_sem`, `admission_disable_waitlist`, `admission_enable_quota`, `visible`, `showscore`, `modules`, `aux_lock_rule`, `lock_rule`) VALUES('834499e2b8a2cd71637890e5de31cba3', '12345', '2560f7c7674942a7dce8eeb238e15d93', 'Test Lehrveranstaltung', 'eine normale Lehrveranstaltung', 1, '', '', '', '', 1, 1, 1317420000, 0, '', 'für alle Studierenden', 'abgeschlossenes Grundstudium', 'Referate in Gruppenarbeit', 'Klausur', 1240429345, 1311416105, '4', -1, 0, 0, 0, 0, '', 0, '', -1, -1, 0, 0, 1, 0, 20911, 'd34f75dbb9936ba300086e096b718242', NULL);
REPLACE INTO `seminare` (`Seminar_id`, `VeranstaltungsNummer`, `Institut_id`, `Name`, `Untertitel`, `status`, `Beschreibung`, `Ort`, `Sonstiges`, `Passwort`, `Lesezugriff`, `Schreibzugriff`, `start_time`, `duration_time`, `art`, `teilnehmer`, `vorrausetzungen`, `lernorga`, `leistungsnachweis`, `mkdate`, `chdate`, `ects`, `admission_endtime`, `admission_turnout`, `admission_binding`, `admission_type`, `admission_selection_take_place`, `admission_group`, `admission_prelim`, `admission_prelim_txt`, `admission_starttime`, `admission_endtime_sem`, `admission_disable_waitlist`, `admission_enable_quota`, `visible`, `showscore`, `modules`, `aux_lock_rule`, `lock_rule`) VALUES('7cb72dab1bf896a0b55c6aa7a70a3a86', '', 'ec2e364b28357106c0f8c282733dbe56', 'Test Studiengruppe', '', 99, 'Studiengruppen sind eine einfache Möglichkeit, mit KommilitonInnen, KollegInnen und anderen zusammenzuarbeiten. ', '', '', '', 1, 1, 1254348000, -1, '', '', '', '', '', 1268739824, 1268739824, '', -1, 0, NULL, 0, 0, NULL, 0, '', -1, -1, 0, 0, 1, 0, 395, NULL, NULL);

--
-- Daten für Tabelle `seminar_cycle_dates`
--

REPLACE INTO `seminar_cycle_dates` (`metadate_id`, `seminar_id`, `start_time`, `end_time`, `weekday`, `description`, `sws`, `cycle`, `week_offset`, `sorter`, `mkdate`, `chdate`) VALUES('6e8564a271f9abd46a8f8e69acc7b89a', '834499e2b8a2cd71637890e5de31cba3', '10:00:00', '12:00:00', 1, 'Vorlesung', '0.0', 0, 0, 0, 1240429345, 1268739499);
REPLACE INTO `seminar_cycle_dates` (`metadate_id`, `seminar_id`, `start_time`, `end_time`, `weekday`, `description`, `sws`, `cycle`, `week_offset`, `sorter`, `mkdate`, `chdate`) VALUES('c6f297af47815b47d027a4403f9f67d0', '834499e2b8a2cd71637890e5de31cba3', '09:00:00', '11:00:00', 3, 'Übung', '0.0', 1, 1, 0, 1240429345, 1293119933);

--
-- Daten für Tabelle `seminar_sem_tree`
--

REPLACE INTO `seminar_sem_tree` (`seminar_id`, `sem_tree_id`) VALUES('834499e2b8a2cd71637890e5de31cba3', '3d39528c1d560441fd4a8cb0b7717285');
REPLACE INTO `seminar_sem_tree` (`seminar_id`, `sem_tree_id`) VALUES('834499e2b8a2cd71637890e5de31cba3', '5c41d2b4a5a8338e069dda987a624b74');
REPLACE INTO `seminar_sem_tree` (`seminar_id`, `sem_tree_id`) VALUES('834499e2b8a2cd71637890e5de31cba3', 'dd7fff9151e85e7130cdb684edf0c370');

--
-- Daten für Tabelle `seminar_user`
--

REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`) VALUES('834499e2b8a2cd71637890e5de31cba3', '205f3efb7997a0fc9755da2b535038da', 'dozent', 0, 2, '', 0, 1156516698, '', 'yes');
REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`) VALUES('834499e2b8a2cd71637890e5de31cba3', '7e81ec247c151c02ffd479511e24cc03', 'tutor', 0, 2, '', 0, 1156516698, '', 'yes');
REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`) VALUES('834499e2b8a2cd71637890e5de31cba3', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 'autor', 0, 2, '', 0, 1156516698, '', 'yes');
REPLACE INTO `seminar_user` (`Seminar_id`, `user_id`, `status`, `position`, `gruppe`, `admission_studiengang_id`, `notification`, `mkdate`, `comment`, `visible`) VALUES('7cb72dab1bf896a0b55c6aa7a70a3a86', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 'dozent', 0, 8, '', 0, 0, '', 'unknown');

--
-- Daten für Tabelle `sem_tree`
--

REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('5b73e28644a3e259a6e0bc1e1499773c', 'root', 1, '', '', '1535795b0d6ddecac6813f5f6ac47ef2', 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('439618ae57d8c10dcaabcf7e21bcc1d9', '5b73e28644a3e259a6e0bc1e1499773c', 0, '', 'Test Studienbereich A', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('5c41d2b4a5a8338e069dda987a624b74', '5b73e28644a3e259a6e0bc1e1499773c', 1, '', 'Test Studienbereich B', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('3d39528c1d560441fd4a8cb0b7717285', '439618ae57d8c10dcaabcf7e21bcc1d9', 0, '', 'Test Studienbereich A-1', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('dd7fff9151e85e7130cdb684edf0c370', '439618ae57d8c10dcaabcf7e21bcc1d9', 1, '', 'Test Studienbereich A-2', NULL, 0);
REPLACE INTO `sem_tree` (`sem_tree_id`, `parent_id`, `priority`, `info`, `name`, `studip_object_id`, `type`) VALUES('01c8b1d188be40c5ac64b54a01aae294', '5b73e28644a3e259a6e0bc1e1499773c', 2, '', 'Test Studienbereich C', NULL, 0);

--
-- Daten für Tabelle `statusgruppen`
--

REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('86498c641ccf4f4d4e02f4961ccc3829', 'Lehrbeauftragte', '2560f7c7674942a7dce8eeb238e15d93', 3, 0, 0, 1156516698, 1156516698);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('600403561c21a50ae8b4d41655bd2191', 'HochschullehrerIn', '2560f7c7674942a7dce8eeb238e15d93', 4, 0, 0, 1156516698, 1156516698);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('efb56e092f33cb78a8766676042dc1c5', 'wiss. MitarbeiterIn', '2560f7c7674942a7dce8eeb238e15d93', 2, 0, 0, 1156516698, 1156516698);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('5d40b1fc0434e6589d7341a3ee742baf', 'DirektorIn', '2560f7c7674942a7dce8eeb238e15d93', 1, 0, 0, 1156516698, 1156516698);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('41ad59c9b6cdafca50e42fe6bc68af4f', 'Thema 1', '834499e2b8a2cd71637890e5de31cba3', 2, 3, 2, 1194628738, 1194629392);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('151c33059a90b6138d280862f5d4b3c2', 'Thema 2', '834499e2b8a2cd71637890e5de31cba3', 3, 3, 2, 1194628768, 1194628768);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('a5061826bf8db7487a774f92ce2a4d23', 'Thema 3', '834499e2b8a2cd71637890e5de31cba3', 4, 3, 2, 1194628789, 1194628789);
REPLACE INTO `statusgruppen` (`statusgruppe_id`, `name`, `range_id`, `position`, `size`, `selfassign`, `mkdate`, `chdate`) VALUES('ee5764d68c795815c9dd8b2448313fb6', 'DozentInnen', '834499e2b8a2cd71637890e5de31cba3', 1, 0, 0, 1194628816, 1194628816);

--
-- Daten für Tabelle `statusgruppe_user`
--

REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('efb56e092f33cb78a8766676042dc1c5', '7e81ec247c151c02ffd479511e24cc03', 1, 1, 1);
REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('5d40b1fc0434e6589d7341a3ee742baf', '205f3efb7997a0fc9755da2b535038da', 1, 1, 1);
REPLACE INTO `statusgruppe_user` (`statusgruppe_id`, `user_id`, `position`, `visible`, `inherit`) VALUES('ee5764d68c795815c9dd8b2448313fb6', '205f3efb7997a0fc9755da2b535038da', 1, 1, 1);

--
-- Daten für Tabelle `termine`
--

REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('cc04c7bd4b0259b8968402d8ce85b4cc', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1322035200, 1322042400, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('103a7c9a1a9433494f979dcd1c654742', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1323244800, 1323252000, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('914976ace65274dabe4a3812167fe37e', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1324454400, 1324461600, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('26125eb9fa17efa3947f4c0b1a7a8600', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1325664000, 1325671200, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('157f8605ad389c7b59d8437bcd7a5a54', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1326873600, 1326880800, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('67f1c5cc5a38779c1f09ab60699452d7', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1329120000, 1329134400, 1258026662, 1326810622, 3, NULL, '', NULL);
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('2c9a2628f2ba40c3fef4542c871da912', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1328083200, 1328090400, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('e41ef4b756e5d12c2fc41148fa234a10', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1319443200, 1319450400, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('a9997a4cb099f2af24d7f511dc50141b', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1320051600, 1320058800, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('eeeb9bf048703cedeb3b939608bd4404', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1320656400, 1320663600, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('df52bb52be086a5e53131fa4cb4ce283', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1321261200, 1321268400, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('739c57d3acd8fb6aa04b58518b070260', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1321866000, 1321873200, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('fdb2712dedb8a2aa1b06ede975688352', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1322470800, 1322478000, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('edc6d7bf127f8b18f70208f39194c3e9', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1323075600, 1323082800, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('56ff28275c6527a8132f9dabee3350a9', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1323680400, 1323687600, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('ffe9e79ed927ca1a2133386c43ec7d3d', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1324285200, 1324292400, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('1944327eb9b4a904b9e9792685bbe995', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1326099600, 1326106800, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('5c7581d917d87ed05ee971ad3c5051bd', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1326704400, 1326711600, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('d79c261e0e25ec52931b69c213439cfd', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1327309200, 1327316400, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('0d5b5638eb568c5cd7c89ca90352c400', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1327914000, 1327921200, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('0e686bda235739042215c437f8324a3d', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1328518800, 1328526000, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('5dc85e7b934206c2e7bae0d139ec1a4e', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1319612400, 1319619600, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('7ac48037d6103c29e29abea65c9e6c85', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1320825600, 1320832800, 1311416105, 1311416105, 1, NULL, '', 'c6f297af47815b47d027a4403f9f67d0');
REPLACE INTO `termine` (`termin_id`, `range_id`, `autor_id`, `content`, `description`, `date`, `end_time`, `mkdate`, `chdate`, `date_typ`, `topic_id`, `raum`, `metadate_id`) VALUES('cdb0b2f7c3977a78c780b03d3f189bc9', '834499e2b8a2cd71637890e5de31cba3', '76ed43ef286fb55cf9e41beadb484a9f', '', NULL, 1318838400, 1318845600, 1311416105, 1311416105, 1, NULL, '', '6e8564a271f9abd46a8f8e69acc7b89a');

--
-- Daten für Tabelle `themen`
--


--
-- Daten für Tabelle `themen_termine`
--


--
-- Daten für Tabelle `user_info`
--

REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `smiley_favorite_publish`, `motto`) VALUES('76ed43ef286fb55cf9e41beadb484a9f', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', 0, '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `smiley_favorite_publish`, `motto`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', 0, '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `smiley_favorite_publish`, `motto`) VALUES('205f3efb7997a0fc9755da2b535038da', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', 0, '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `smiley_favorite_publish`, `motto`) VALUES('6235c46eb9e962866ebdceece739ace5', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', 0, '');
REPLACE INTO `user_info` (`user_id`, `hobby`, `lebenslauf`, `publi`, `schwerp`, `Home`, `privatnr`, `privatcell`, `privadr`, `score`, `geschlecht`, `mkdate`, `chdate`, `title_front`, `title_rear`, `preferred_language`, `smsforward_copy`, `smsforward_rec`, `guestbook`, `email_forward`, `smiley_favorite`, `smiley_favorite_publish`, `motto`) VALUES('7e81ec247c151c02ffd479511e24cc03', '', NULL, '', '', '', '', '', '', 0, 0, 0, 0, '', '', NULL, 1, '', 0, 0, '', 0, '');

--
-- Daten für Tabelle `user_inst`
--

REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('205f3efb7997a0fc9755da2b535038da', '2560f7c7674942a7dce8eeb238e15d93', 'dozent', '', '', '', '', 0, 0, 1);
REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('6235c46eb9e962866ebdceece739ace5', '2560f7c7674942a7dce8eeb238e15d93', 'admin', '', '', '', '', 0, 0, 1);
REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('7e81ec247c151c02ffd479511e24cc03', '2560f7c7674942a7dce8eeb238e15d93', 'tutor', '', '', '', '', 0, 0, 1);
REPLACE INTO `user_inst` (`user_id`, `Institut_id`, `inst_perms`, `sprechzeiten`, `raum`, `Telefon`, `Fax`, `externdefault`, `priority`, `visible`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', '2560f7c7674942a7dce8eeb238e15d93', 'user', '', '', '', '', 1, 0, 1);

--
-- Daten für Tabelle `vote`
--

REPLACE INTO `vote` (`vote_id`, `author_id`, `range_id`, `type`, `title`, `question`, `state`, `startdate`, `stopdate`, `timespan`, `mkdate`, `chdate`, `resultvisibility`, `multiplechoice`, `anonymous`, `changeable`, `co_visibility`, `namesvisibility`) VALUES('b5329b23b7f865c62028e226715e1914', '76ed43ef286fb55cf9e41beadb484a9f', 'studip', 'vote', 'Nutzen Sie bereits Stud.IP?', 'Haben Sie Stud.IP bereits im Einsatz oder planen Sie, es einzusetzen?', 'active', 1293120882, NULL, NULL, 1142525062, 1293120883, 'delivery', 1, 0, 1, NULL, 0);

--
-- Daten für Tabelle `voteanswers`
--

REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('2ea4169a90dbcc56be1610f75d86d460', 'b5329b23b7f865c62028e226715e1914', 'Ich plane, es demnächst einzusetzen', 18, 0, 0);
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
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('3c065ec2b3037c39991cc5d99eca185c', 'b5329b23b7f865c62028e226715e1914', 'Ich schaue mich nur mal um', 19, 0, 0);
REPLACE INTO `voteanswers` (`answer_id`, `vote_id`, `answer`, `position`, `counter`, `correct`) VALUES('56991b7ad13aa8f5315e9bc412c6a199', 'b5329b23b7f865c62028e226715e1914', 'Ich bin nicht interessiert', 20, 0, 0);

--
-- Daten für Tabelle `abschluss`
--

REPLACE INTO `abschluss` (`abschluss_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('228234544820cdf75db55b42d1ea3ecc', 'Bachelor', '', 1311416359, 1311416359);
REPLACE INTO `abschluss` (`abschluss_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('c7f569e815a35cf24a515a0e67928072', 'Master', '', 1311416385, 1311416385);

--
-- Daten für Tabelle `studiengaenge`
--

REPLACE INTO `studiengaenge` (`studiengang_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('f981c9b42ca72788a09da4a45794a737', 'Informatik', '', 1311416397, 1311416397);
REPLACE INTO `studiengaenge` (`studiengang_id`, `name`, `beschreibung`, `mkdate`, `chdate`) VALUES('6b9ac09535885ca55e29dd011e377c0a', 'Geschichte', '', 1311416418, 1311416418);

--
-- Daten für Tabelle `user_studiengang`
--

REPLACE INTO `user_studiengang` (`user_id`, `studiengang_id`, `semester`, `abschluss_id`) VALUES('e7a0a84b161f3e8c09b4a0a2e8a58147', '6b9ac09535885ca55e29dd011e377c0a', 2, '228234544820cdf75db55b42d1ea3ecc');
REPLACE INTO `user_studiengang` (`user_id`, `studiengang_id`, `semester`, `abschluss_id`) VALUES('7e81ec247c151c02ffd479511e24cc03', 'f981c9b42ca72788a09da4a45794a737', 1, '228234544820cdf75db55b42d1ea3ecc');
