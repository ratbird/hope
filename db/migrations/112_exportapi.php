<?php
class Exportapi extends Migration {

    function description() {
        return 'Prepares the database to be able to store export templates';
    }

    function up() {
        $sql = "CREATE TABLE `export_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL,
  `template` varchar(128) NOT NULL,
  `edits` text NOT NULL,
  `name` varchar(256) NOT NULL,
  `format` varchar(32) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
);";
        $db = DBManager::get();
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }

    function down() {
              $sql = "DROP TABLE `export_templates`;";
        $db = DBManager::get();
        $stmt = $db->prepare($sql);
        $stmt->execute();  
    }

}

?>
