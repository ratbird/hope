<?php

set_time_limit(7200);

class Visibilityapi extends Migration {

    function description() {
        return 'Copy visibilitydata from old json compress';
    }

    function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `user_visibility_settings` (
  `user_id` varchar(32)  NOT NULL DEFAULT '',
  `visibilityid` int(32) NOT NULL AUTO_INCREMENT,
  `parent_id` int(32) NOT NULL,
  `category` varchar(128)  NOT NULL,
  `name` varchar(128)  NOT NULL,
  `state` int(2) NULL,
  `plugin` int(11),
  `identifier` varchar(64)  NOT NULL,
  PRIMARY KEY (`visibilityid`),
  KEY `parent_id` (`parent_id`),
  KEY `identifier` (`identifier`),
  KEY `userid` (`user_id`)
) ENGINE=MyISAM";
        $db = DBManager::get();
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $category = array('Studien-/Einrichtungsdaten' => 'studdata',
            'Private Daten' => 'privatedata',
            'Zus�tzliche Datenfelder' => 'additionaldata',
            'Eigene Kategorien' => 'owncategory',
            'Allgemeine Daten' => 'commondata');


        $sql = "SELECT `username` FROM `auth_user_md5`";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $about = new about($result['username'], "i have no idea what msg is for...");
            Visibility::createDefaultCategories($about->auth_user['user_id']);

            //copy all homepage visibility
            $elements = $about->get_homepage_elements();
            if (is_array($elements)) {
                foreach ($elements as $key => $state) {
                    //echo $key;
                    $pr = print_r($state, true);
                    Log::debug("Processing: $key => $pr");
                    Visibility::addPrivacySetting($state['name'], $key, $category[$state['category']], 1, $about->auth_user['user_id'], $state['visibility']);
                }
            }
        }
    }

    function down() {

    }

}

?>
