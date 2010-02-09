<?php
class StEP00123Admission2 extends DBMigration {

	function description () {
		return 'modifies table seminare, adds field `admission_enable_quota`';
	}

	function up () {
		$this->announce(" modifying  table `seminare` adding field `admission_enable_quota`...");
		
		$this->db->query( "ALTER TABLE `seminare` ADD `admission_enable_quota` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `admission_disable_waitlist`");
		$this->announce(" update old entries...");
		$this->db->query("UPDATE `seminare` SET `admission_enable_quota` =1 WHERE admission_type IN ( 1, 2 )");
		$this->announce("done.");
	}
	
	function down () {
		$this->announce(" removing field `admission_enable_quota` from `seminare`...");
		$this->db->query("ALTER TABLE `seminare` DROP `admission_enable_quota` ");
		$this->announce("done.");
	}
}
?>