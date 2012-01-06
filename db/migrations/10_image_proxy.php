<?php
class ImageProxy extends DBMigration {

    function description () {
        return 'creates table image_proxy_cache and config entry EXTERNAL_IMAGE_EMBEDDING';
    }

    function up () {
        $this->announce(" creating table `image_proxy_cache`...");
        
        $this->db->query( "CREATE TABLE `image_proxy_cache` (
                          `id` char(32) NOT NULL,
                          `type` char(10) NOT NULL,
                          `length` int(10) unsigned NOT NULL,
                          `error` char(15) NOT NULL,
                          `chdate` timestamp NOT NULL,
                          PRIMARY KEY  (`id`),
                          KEY `chdate` (`chdate`,`id`)
                        ) ENGINE=MyISAM;");
        $this->announce(" config entry EXTERNAL_IMAGE_EMBEDDING ...");
        $this->db->query("INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES 
                        ('0c81083086adc66714864b1abcff650a', '', 'EXTERNAL_IMAGE_EMBEDDING', 'deny', 1, 'string', 'global', '', 0, 0, 0, 'Sollen externe Bilder über [img] eingebunden werden? deny=nicht erlaubt, allow=erlaubt, proxy=image proxy benutzen', '', '');
                        ");
        $this->announce("done.");
    }
    
    function down () {
        $this->announce(" removing table `admission_group`...");
        $this->db->query("DROP TABLE IF EXISTS `image_proxy_cache` ");
        $this->announce("done.");
    }
}
?>
