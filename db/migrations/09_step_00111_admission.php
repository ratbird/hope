<?php
class StEP00111Admission extends DBMigration {

	function description () {
		return 'creates table admission groups';
	}

	function up () {
		$this->announce(" creating table `admission_group`...");
		
		$this->db->query( "CREATE TABLE IF NOT EXISTS `admission_group` (
						  `group_id` varchar(32) NOT NULL,
						  `name` varchar(255) NOT NULL,
						  `status` tinyint(3) unsigned NOT NULL,
						  `chdate` int(10) unsigned NOT NULL,
						  `mkdate` int(10) unsigned NOT NULL,
						  PRIMARY KEY  (`group_id`)
						) TYPE=MyISAM");
		$this->announce(" fill table with existing groups...");
		$this->db->query("INSERT IGNORE INTO admission_group 
						(group_id, status, chdate,mkdate)
						SELECT DISTINCT admission_group,0, UNIX_TIMESTAMP(),UNIX_TIMESTAMP() FROM seminare WHERE admission_group <> ''");
		$this->announce("done.");
	}
	
	function down () {
		$this->announce(" removing table `admission_group`...");
		$this->db->query("DROP TABLE IF EXISTS `admission_group` ");
		$this->announce("done.");
	}
}
?>
