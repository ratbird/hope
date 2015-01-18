 
<?
class Step00284HelpEditor extends DBMigration {

    function description () {
        return 'Adds fields for extended help functions and inserts help administrator role.';
    }

    function up () {
        $this->announce("add new fields to table help_content");
        
        $this->db->query("ALTER TABLE `help_content` ADD `global_content_id` varchar(32) NOT NULL FIRST");
        $this->db->query("UPDATE `help_content` SET `global_content_id` = `content_id`");
        $this->db->query("ALTER TABLE `help_content` DROP PRIMARY KEY , ADD PRIMARY KEY ( `content_id` )"); 
        $this->db->query("ALTER TABLE `help_content` ADD `author_email` varchar(255) NOT NULL AFTER `author_id`");
        $this->db->query("ALTER TABLE `help_content` ADD `chdate` int(11) NOT NULL AFTER `mkdate`");
        $this->db->query("ALTER TABLE `help_content` DROP COLUMN `author_id`, DROP COLUMN `label`, DROP COLUMN `icon`");
        
        $this->announce("add new fields to table help_tours");
        
        $this->db->query("ALTER TABLE `help_tours` ADD `global_tour_id` varchar(32) NOT NULL FIRST");
        $this->db->query("UPDATE `help_tours` SET `global_tour_id` = `tour_id`");
        $this->db->query("ALTER TABLE `help_tours` ADD `author_email` varchar(255) NOT NULL AFTER `installation_id`");
        $this->db->query("ALTER TABLE `help_tours` ADD `chdate` int(11) NOT NULL AFTER `mkdate`");
        
        $this->announce("add new fields to table help_tour_steps");
        
        $this->db->query("ALTER TABLE `help_tour_steps` ADD `chdate` int(11) NOT NULL AFTER `mkdate`");
        $this->db->query("ALTER TABLE `help_tour_steps` ADD `author_email` varchar(255) NOT NULL AFTER `route`");
        $this->db->query("ALTER TABLE `help_tour_steps` DROP COLUMN `author_id`");
        $this->db->query("ALTER TABLE `help_tour_steps` ADD `action_next` varchar(255) NOT NULL AFTER `route`");
        $this->db->query("ALTER TABLE `help_tour_steps` ADD `action_prev` varchar(255) NOT NULL AFTER `route`");
        
        $this->db->query("INSERT INTO `roles` (`rolename`, `system`) VALUES ('Hilfe-Administrator(in)', 'n')");
        
        $this->announce("done.");
        
    }
    
    function down () {
        $this->db->query("DELETE `roles_user`.*, `roles`.* FROM `roles_user` LEFT JOIN `roles` USING (`roleid`) WHERE `roles`.`rolename` = 'Hilfe-Administrator(in)'");
        
        $this->announce("remove fields from table help_content");
        $this->db->query("ALTER TABLE `help_content` ADD `author_id` varchar(255) NOT NULL AFTER `author_email`");
        $this->db->query("ALTER TABLE `help_content` ADD `label` varchar(255) NOT NULL AFTER `language`");
        $this->db->query("ALTER TABLE `help_content` ADD `icon` varchar(255) NOT NULL AFTER `language`");
        $this->db->query("ALTER TABLE `help_content` DROP COLUMN `author_email`, DROP COLUMN `global_content_id`");
        $this->db->query("ALTER TABLE `help_content` DROP PRIMARY KEY , ADD PRIMARY KEY ( `content_id`, `language`, `studip_version`, `installation_id` )"); 
        
        $this->announce("remove fields from table help_tours");
        $this->db->query("ALTER TABLE `help_tours` DROP COLUMN `author_email`, DROP COLUMN `chdate`, DROP COLUMN `global_tour_id`");
        
        $this->announce("remove fields from table help_tour_steps");
        $this->db->query("ALTER TABLE `help_tour_steps` ADD `author_id` varchar(255) NOT NULL AFTER `author_email`");
        $this->db->query("ALTER TABLE `help_tour_steps` DROP COLUMN `author_email`,  DROP COLUMN `chdate`,  DROP COLUMN `action_prev`,  DROP COLUMN `action_next`");
        
        $this->announce("done.");
    }
}
?>