<?
class LockRulez extends DBMigration {

    function description () {
        return 'creates table for lock rules';
    }

    function up () {
        $this->announce(" creating table...");
        
        $this->db->query( " 
            CREATE TABLE `lock_rules` (
                `lock_id` varchar(32) NOT NULL default '',
                `name` varchar(255) NOT NULL default '',
                `description` text NOT NULL,
                `attributes` text NOT NULL,
                PRIMARY KEY  (`lock_id`)
            ) ENGINE=MyISAM");

        $this->db->query( "
            ALTER TABLE `seminare` ADD `lock_rule` VARCHAR( 32 ) NULL ; ");

        $this->announce("done.");
        
    }
    
    function down () {
        $this->announce(" removing table...");
        $this->db->query("
      DROP TABLE `lock_rules` 
        ");

        $this->db->query("
            ALTER TABLE `seminare` DROP `lock_rule`");

        $this->announce("done.");
        
    }
}
?>
