# mysql migration script
# base version: 0.8.15
# update version: 0.9

PLEASE NOTE: Since a migration-tool is not written yet, please use this script MANUEL to convert your old installtion.
Dont't paste this script direct into your SQL-Server, because you have to start the convert scripts manuel at the 
right time!

# For detailed informations, please take a look at the update protocol from our installation in goettingen!
# (Should be located in the same folder)
#
#
# #1
# changes for new chatsystem
#

ALTER TABLE `globalmessages` ADD `chat_id` VARCHAR(32); ALTER TABLE `globalmessages` ADD INDEX (`chat_id`);

# #2
# delete old field (change in 0.8.15)
#

ALTER TABLE `user_inst` DROP `Funktion`

# #3
# changes to save the preferred language
#

ALTER TABLE `user_info` ADD `preferred_language` VARCHAR( 6 ) ;

# #4
# changes for titles
#

ALTER TABLE `user_info` ADD `title_front` VARCHAR(64) NOT NULL;
ALTER TABLE `user_info` ADD `title_rear` VARCHAR(64) NOT NULL;

# #5
# >>>please use the script convert_title_front at this point!
#

# #6
# >>>please use the script convert_title_rear at this point!
#

# #7
# structure for range tree
#

CREATE TABLE range_tree (
item_id varchar(32) NOT NULL default '',
parent_id varchar(32) NOT NULL default '',
priority int(11) NOT NULL default '0',
name varchar(255) NOT NULL default '',
studip_object varchar(10) default NULL,
studip_object_id varchar(32) default NULL,
PRIMARY KEY (item_id),
KEY parent_id (parent_id),
KEY priority (priority),
KEY studip_object_id (studip_object_id)
) TYPE=MyISAM;

# #8
# changes for table 'kategorien' (for range tree)
#

ALTER TABLE `kategorien` ADD `priority` INT DEFAULT '0' NOT NULL;
ALTER TABLE `kategorien` ADD INDEX (`priority`);
ALTER TABLE `kategorien` ADD INDEX (`range_id`);

# #9
# >>>please use the script convert_fakultaeten at this point!
#

# #10
# >>>please use the script import_tree at this point!
#

# #11
# delete no more used tables
#

DROP TABLE `Fakultaeten`
DROP TABLE `fakultaet_user`

# #12
# structure for contacts
#

CREATE TABLE contact (
contact_id varchar(32) NOT NULL default '',
owner_id varchar(32) NOT NULL default '',
user_id varchar(32) NOT NULL default '',
buddy smallint(6) NOT NULL default '1',
PRIMARY KEY (contact_id),
KEY owner_id (owner_id),
KEY user_id (user_id)
) TYPE=MyISAM;

CREATE TABLE contact_userinfo (
userinfo_id varchar(32) NOT NULL default '',
contact_id varchar(32) NOT NULL default '',
name varchar(255) NOT NULL default '',
content text NOT NULL,
priority int(11) NOT NULL default '0',
PRIMARY KEY (userinfo_id),
KEY contact_id (contact_id)
) TYPE=MyISAM;

# #13
# >>>please use the cript convert_buddies at this point!
#

# #14
# structure for the sri-interface modul (only if installed)
# 

CREATE TABLE extern_config(
config_id varchar( 32 ) NOT NULL ,
range_id varchar( 32 ) NOT NULL ,
config_type int( 4 ) DEFAULT '0' NOT NULL ,
name varchar( 255 ) NOT NULL ,
is_standard int( 4 ) DEFAULT '0' NOT NULL ,
mkdate int( 20 ) DEFAULT '0' NOT NULL ,
chdate int( 20 ) DEFAULT '0' NOT NULL ,
PRIMARY KEY ( config_id, range_id )
)

# #15
# structure for the ILIAS connection modul (only if installed)
#

CREATE TABLE seminar_lernmodul (
seminar_id varchar(32) NOT NULL default '',
co_inst bigint(20) NOT NULL default '0',
co_id bigint(20) NOT NULL default '0',
PRIMARY KEY  (seminar_id,co_id),
KEY seminar_id (seminar_id)
) TYPE=MyISAM;

CREATE TABLE studip_ilias (
studip_user_id varchar(32) NOT NULL default '',
ilias_user_id bigint(20) NOT NULL default '0',
is_created tinyint(4) NOT NULL default '0',
PRIMARY KEY (studip_user_id,ilias_user_id),
KEY is_created (is_created)
) TYPE=MyISAM;

# #16
# structure for the sem_tree
#

CREATE TABLE seminar_sem_tree (
  seminar_id varchar(32) NOT NULL default '',  
  sem_tree_id varchar(32) NOT NULL default '',  
  PRIMARY KEY  (seminar_id,sem_tree_id),  
  KEY seminar_id (seminar_id),  
  KEY sem_tree_id (sem_tree_id)
) TYPE=MyISAM;

CREATE TABLE sem_tree (
  sem_tree_id varchar(32) NOT NULL default '',  
  parent_id varchar(32) NOT NULL default '',  
  priority tinyint(4) NOT NULL default '0',  
  info text NOT NULL,  
  name varchar(255) NOT NULL default '',  
  studip_object_id varchar(32) default NULL,  
  PRIMARY KEY  (sem_tree_id),  
  KEY parent_id (parent_id),  
  KEY priority (priority),  
  KEY studip_object_id (studip_object_id)
) TYPE=MyISAM;

# #17
# changes to the archive
#

ALTER TABLE `archiv` ADD `studienbereiche` TEXT NOT NULL;
UPDATE archiv SET `studienbereiche`=`bereich`;
ALTER TABLE `archiv` DROP `fach`, DROP `bereich`;

# #18
# >>>please use the script import_sem_tree at this point!
#

# #19
# drop no more used tables after converting in sem_tree
#

DROP TABLE `faecher`
DROP TABLE `bereiche`
DROP TABLE `bereich_fach`
DROP TABLE `seminar_bereich`
DROP TABLE `fach_inst`

# #20
# add download counter for documents
# 

ALTER TABLE `dokumente` ADD `downloads` INT( 20 ) DEFAULT '0' NOT NULL ;

# #21
# structure for the resource management modulul (only if installed)
#

CREATE TABLE resources_assign (
assign_id varchar(32) NOT NULL default '',
resource_id varchar(32) NOT NULL default '',
assign_user_id varchar(32) NOT NULL default '',
user_free_name varchar(255) default NULL,
begin int(20) NOT NULL default '0',
end int(20) NOT NULL default '0',
repeat_end int(20) default NULL,
repeat_quantity int(2) default NULL,
repeat_interval int(2) default NULL,
repeat_month_of_year int(2) default NULL,
repeat_day_of_month int(2) default NULL,
repeat_month int(2) default NULL,
repeat_week_of_month int(2) default NULL,
repeat_day_of_week int(2) default NULL,
repeat_week int(2) default NULL,
mkdate int(20) NOT NULL default '0',
chdate int(20) NOT NULL default '0',
PRIMARY KEY (assign_id)
) TYPE=MyISAM;

CREATE TABLE resources_categories (
category_id varchar(32) NOT NULL default '',
name varchar(255) NOT NULL default '',
description text NOT NULL,
system tinyint(4) NOT NULL default '0',
iconnr int(3) unsigned default '1',
PRIMARY KEY (category_id)
) TYPE=MyISAM;

CREATE TABLE resources_categories_properties (
category_id varchar(32) NOT NULL default '',
property_id varchar(32) NOT NULL default '',
system tinyint(4) NOT NULL default '0',
PRIMARY KEY (category_id,property_id)
) TYPE=MyISAM;

CREATE TABLE resources_objects (
resource_id varchar(32) NOT NULL default '',
root_id varchar(32) NOT NULL default '',
parent_id varchar(32) NOT NULL default '',
category_id varchar(32) NOT NULL default '',
owner_id varchar(32) NOT NULL default '',
level varchar(4) default NULL,
name varchar(255) NOT NULL default '',
description text NOT NULL,
inventar_num varchar(255) NOT NULL default '',
parent_bind tinyint(4) default NULL,
mkdate int(20) NOT NULL default '0',
chdate int(20) NOT NULL default '0',
PRIMARY KEY (resource_id),
KEY categorie_id (category_id,owner_id),
KEY root_id (root_id,parent_id)
) TYPE=MyISAM;

CREATE TABLE resources_objects_properties (
resource_id varchar(32) NOT NULL default '',
property_id varchar(32) NOT NULL default '',
state text NOT NULL,
PRIMARY KEY (resource_id,property_id)
) TYPE=MyISAM;

CREATE TABLE resources_properties (
property_id varchar(32) NOT NULL default '',
name varchar(255) NOT NULL default '',
description text NOT NULL,
type set('bool','text','num','select') NOT NULL default 'bool',
options text NOT NULL,
system tinyint(4) NOT NULL default '0',
PRIMARY KEY (property_id)
) TYPE=MyISAM;

CREATE TABLE resources_user_resources (
user_id varchar(32) NOT NULL default '',
resource_id varchar(32) NOT NULL default '',
perms varchar(10) NOT NULL default '',
PRIMARY KEY (user_id,resource_id)
) TYPE=MyISAM; 

# #22
# default data for resources management
#

INSERT INTO resources_properties VALUES("ef4ba565e635b45c3f43ecdc69fb4aca", "Sitzplätze", "", "num", "", "1");
INSERT INTO resources_properties VALUES("8772d6757457c8b4a05b180e1c2eba9c", "Adresse", "", "text", "", "0");
INSERT INTO resources_properties VALUES("0ef8a73d95f335cdfbaec50cae92762a", "Ausstattung", "", "text", "", "0");
INSERT INTO resources_properties VALUES("7bff1a7d45bc37280e988f6e8d007bad", "Seriennummer", "", "num", "", "0");
INSERT INTO resources_properties VALUES("31abad810703df361d793361bf6b16e5", "Raumtyp", "", "select", "Hörsaal;Übungsraum;Sitzungszimmer", "0");
INSERT INTO resources_properties VALUES("5753ab43945ae787f983f5c8a036712d", "behindertengerecht", "", "bool", "", "0");
INSERT INTO resources_properties VALUES("648b8579ffca64a565459fd6ea0313c5", "Verdunklung", "", "bool", "vorhanden", "0");
INSERT INTO resources_properties VALUES("9c0658891b95fe962d013f1308feb80d", "Hersteller", "", "num", "", "0");
INSERT INTO resources_properties VALUES("1b86b5026052fd3d8624fead31204cba", "Kaufdatum", "", "num", "", "0");

INSERT INTO resources_categories_properties VALUES("82bdd20907e914de72bbfc8043dd3a46", "8772d6757457c8b4a05b180e1c2eba9c", "0");
INSERT INTO resources_categories_properties VALUES("82bdd20907e914de72bbfc8043dd3a46", "5753ab43945ae787f983f5c8a036712d", "0");
INSERT INTO resources_categories_properties VALUES("891662c701078186c857fca25d34ade6", "1b86b5026052fd3d8624fead31204cba", "0");
INSERT INTO resources_categories_properties VALUES("891662c701078186c857fca25d34ade6", "9c0658891b95fe962d013f1308feb80d", "0");
INSERT INTO resources_categories_properties VALUES("891662c701078186c857fca25d34ade6", "7bff1a7d45bc37280e988f6e8d007bad", "0");
INSERT INTO resources_categories_properties VALUES("1cf2a34de92c06137ecdfcef4a29e4bc", "0ef8a73d95f335cdfbaec50cae92762a", "0");
INSERT INTO resources_categories_properties VALUES("1cf2a34de92c06137ecdfcef4a29e4bc", "5753ab43945ae787f983f5c8a036712d", "0");
INSERT INTO resources_categories_properties VALUES("1cf2a34de92c06137ecdfcef4a29e4bc", "31abad810703df361d793361bf6b16e5", "0");
INSERT INTO resources_categories_properties VALUES("1cf2a34de92c06137ecdfcef4a29e4bc", "ef4ba565e635b45c3f43ecdc69fb4aca", "1");
INSERT INTO resources_categories_properties VALUES("1cf2a34de92c06137ecdfcef4a29e4bc", "648b8579ffca64a565459fd6ea0313c5", "0");

INSERT INTO resources_categories VALUES("1cf2a34de92c06137ecdfcef4a29e4bc", "Raum", "", "1", "3");
INSERT INTO resources_categories VALUES("82bdd20907e914de72bbfc8043dd3a46", "Gebäude", "", "0", "1");
INSERT INTO resources_categories VALUES("891662c701078186c857fca25d34ade6", "Gerät", "", "0", "2");

# #23
# >>>please use the script convert_rooms at this point!
# 

# #24
# changes to the field Veranstaltungsnummer
# Please note: This change wasn't part of the 0.9.0-beta distribution,
# so convert only this field, of you used the 0.9.0-beta.

ALTER TABLE Seminare CHANGE VeranstaltungsNummer eranstaltungsNummer VARCHAR( 32 ) DEFAULT NULL