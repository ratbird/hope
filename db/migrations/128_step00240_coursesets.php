<?php
require_once 'vendor/phpass/PasswordHash.php';

class Step00240CourseSets extends Migration
{

    function description()
    {
        return 'add tables needed for storing new admission related data';
    }

    function up()
    {
        $db = DBManager::get();

        //check for future admission
        $future_admissions = $db->fetchColumn("SELECT COUNT(*) FROM seminare WHERE admission_type IN (1,2) AND
         (admission_starttime > UNIX_TIMESTAMP() OR start_time > UNIX_TIMESTAMP())");

        if ($future_admissions && !Request::submitted('i_accept_the_risk')) {
            throw new Exception(sprintf("Es gibt %s Veranstaltungen mit Anmeldeverfahren, die in der Zukunft starten.
                Diese Anmeldeverfahren k�nnen nicht migriert werden. Wenn sie auch diese zuk�nftigen Verfahren in gesperrt umwandeln wollen,
                rufen sie manuell web_migrate.php?i_accept_the_risk auf, und klicken sie erneut auf Starten", $future_admissions ));
        }

        // assign conditions to admission rules
        $db->exec("CREATE TABLE IF NOT EXISTS `admission_condition` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `filter_id` VARCHAR(32) NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`rule_id`, `filter_id`) )
            ENGINE = MyISAM");

        // "chance adjustment" in seat distribution
        $db->exec("CREATE TABLE IF NOT EXISTS `admissionfactor` (
                `list_id` VARCHAR(32) NOT NULL ,
                `name` VARCHAR(255) NOT NULL ,
                `factor` DECIMAL(5,2) NOT NULL DEFAULT 1,
                `owner_id` VARCHAR(32) NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`list_id`) )
            ENGINE = MyISAM");

        // available admission rules.
        $db->exec("CREATE TABLE IF NOT EXISTS `admissionrules` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ruletype` VARCHAR(255) UNIQUE NOT NULL,
          `active` TINYINT(1) NOT NULL DEFAULT 0,
          `mkdate` INT(11) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`)
        ) ENGINE = MyISAM");
        // Create entries for default admission rule types.
        $db->exec("INSERT IGNORE INTO `admissionrules`
            (`ruletype`, `active`, `mkdate`) VALUES
                ('ConditionalAdmission', 1, UNIX_TIMESTAMP()),
                ('LimitedAdmission', 1, UNIX_TIMESTAMP()),
                ('LockedAdmission', 1, UNIX_TIMESTAMP()),
                ('PasswordAdmission', 1, UNIX_TIMESTAMP()),
                ('TimedAdmission', 1, UNIX_TIMESTAMP()),
                ('ParticipantRestrictedAdmission', 1, UNIX_TIMESTAMP());");

        // Admission rules can be available globally or only at selected institutes.
        $db->exec("CREATE TABLE IF NOT EXISTS `admissionrule_inst` (
          `rule_id` VARCHAR(32) NOT NULL,
          `institute_id` VARCHAR(32) NOT NULL,
          `mkdate` INT(11) NOT NULL DEFAULT 0,
          PRIMARY KEY (`rule_id`, `institute_id`)
        ) ENGINE = MyISAM");

        // admission rules specifying conditions for access
        $db->exec("CREATE TABLE IF NOT EXISTS `conditionaladmissions` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `message` TEXT NULL ,
                `start_time` INT NOT NULL DEFAULT 0,
                `end_time` INT NOT NULL DEFAULT 0,
                `mkdate` INT NOT NULL DEFAULT 0,
                `conditions_stopped` TINYINT(1) NOT NULL DEFAULT 0 ,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`rule_id`) )
            ENGINE = MyISAM");

        // assign course sets to factor lists
        $db->exec("CREATE TABLE IF NOT EXISTS `courseset_factorlist` (
                `set_id` VARCHAR(32) NOT NULL ,
                `factorlist_id` VARCHAR(32) NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0 ,
            PRIMARY KEY (`set_id`, `factorlist_id`) )
            ENGINE = MyISAM");

        // assign course sets to institutes
        $db->exec("CREATE TABLE IF NOT EXISTS `courseset_institute` (
                `set_id` VARCHAR(32) NOT NULL ,
                `institute_id` VARCHAR(32) NOT NULL ,
                `mkdate` INT NULL ,
                `chdate` INT NULL ,
            PRIMARY KEY (`set_id`, `institute_id`),
            INDEX `institute_id` (`institute_id`,`set_id`))
            ENGINE = MyISAM");

        // assign admission rules to course sets
        $db->exec("CREATE TABLE IF NOT EXISTS `courseset_rule` (
                `set_id` VARCHAR(32) NOT NULL ,
                `rule_id` VARCHAR(32) NOT NULL ,
                `type` VARCHAR(255) NULL ,
                `mkdate` INT NULL ,
            PRIMARY KEY (`set_id`, `rule_id`),
            INDEX `type` (`set_id`,`type`))
            ENGINE = MyISAM");

        // sets of courses with common admission rules
        $db->exec("CREATE TABLE IF NOT EXISTS `coursesets` (
                `set_id` VARCHAR(32) NOT NULL ,
                `user_id` VARCHAR(32) NOT NULL ,
                `name` VARCHAR(255) NOT NULL ,
                `infotext` TEXT NOT NULL ,
                `algorithm` VARCHAR(255) NOT NULL ,
                `algorithm_run` TINYINT(1) NOT NULL DEFAULT 0 ,
                `private` TINYINT(1) NOT NULL DEFAULT 0,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`set_id`) ,
            INDEX `set_user` (`user_id`, `set_id`) )
            ENGINE = MyISAM");

        // admission rules with max number of courses to register for
        $db->exec("CREATE TABLE IF NOT EXISTS `limitedadmissions` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `message` TEXT NOT NULL ,
                `start_time` INT NOT NULL DEFAULT 0,
                `end_time` INT NOT NULL DEFAULT 0,
                `maxnumber` INT NOT NULL DEFAULT 0,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`rule_id`) )
            ENGINE = MyISAM");

        // admission rules that completely lock access to courses
        $db->exec("CREATE TABLE IF NOT EXISTS `lockedadmissions` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `message` TEXT NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`rule_id`) )
            ENGINE = MyISAM");

        // admission rules that specify a password for course access
        $db->exec("CREATE TABLE IF NOT EXISTS `passwordadmissions` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `message` TEXT NULL ,
                `start_time` INT NOT NULL DEFAULT 0,
                `end_time` INT NOT NULL DEFAULT 0,
                `password` VARCHAR(255) NULL ,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`rule_id`) )
            ENGINE = MyISAM");

        // priorities for course assignment
        $db->exec("CREATE TABLE IF NOT EXISTS `priorities` (
                `user_id` VARCHAR(32) NOT NULL ,
                `set_id` VARCHAR(32) NOT NULL ,
                `seminar_id` VARCHAR(32) NOT NULL ,
                `priority` INT NOT NULL DEFAULT 0,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`user_id`, `set_id`, `seminar_id`) ,
            INDEX `user_rule_priority` (`user_id` ASC, `priority` ASC, `set_id` ASC) )
            ENGINE = MyISAM");

        // assign courses to course sets
        $db->exec("CREATE TABLE IF NOT EXISTS `seminar_courseset` (
                `set_id` VARCHAR(32) NOT NULL ,
                `seminar_id` VARCHAR(32) NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0 ,
            PRIMARY KEY (`set_id`, `seminar_id`),
            INDEX `seminar_id` (`seminar_id`, `set_id` ) )
            ENGINE = MyISAM");

        // admission rules concerning time
        $db->exec("CREATE TABLE IF NOT EXISTS `timedadmissions` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `message` TEXT NOT NULL ,
                `start_time` INT NOT NULL DEFAULT 0,
                `end_time` INT NOT NULL DEFAULT 0,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`rule_id`) ,
            INDEX `start_time` (`start_time` ASC) ,
            INDEX `end_time` (`end_time` ASC) ,
            INDEX `start_end` (`start_time` ASC, `end_time` ASC) )
            ENGINE = MyISAM");

        $db->exec("CREATE TABLE IF NOT EXISTS `participantrestrictedadmissions` (
        `rule_id` varchar(32),
        `message` text NOT NULL,
        `distribution_time` int(11) NOT NULL DEFAULT 0,
        `mkdate` int(11) NOT NULL DEFAULT 0,
        `chdate` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`rule_id`)
        ) ENGINE=MyISAM");

        // assign users to lists with different factor in seat distribution
        $db->exec("CREATE TABLE IF NOT EXISTS `user_factorlist` (
                `list_id` VARCHAR(32) NULL ,
                `user_id` VARCHAR(32) NULL ,
                `mkdate` INT NULL ,
            PRIMARY KEY (`list_id`, `user_id`) )
            ENGINE = MyISAM");

        // filters for users
        $db->exec("CREATE TABLE IF NOT EXISTS `userfilter` (
                `filter_id` VARCHAR(32) NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`filter_id`) )
            ENGINE = MyISAM");

        // several fields form a user filter
        $db->exec("CREATE TABLE IF NOT EXISTS `userfilter_fields` (
                `field_id` VARCHAR(32) NOT NULL ,
                `filter_id` VARCHAR(32) NOT NULL ,
                `type` VARCHAR(255) NOT NULL ,
                `value` VARCHAR(255) NOT NULL ,
                `compare_op` VARCHAR(255) NOT NULL ,
                `mkdate` INT NOT NULL DEFAULT 0,
                `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`field_id`) )
            ENGINE = MyISAM");

        // user defined max number of courses to register for
        $db->exec("CREATE TABLE IF NOT EXISTS `userlimits` (
                `rule_id` VARCHAR(32) NOT NULL ,
                `user_id` VARCHAR(32) NOT NULL ,
                `maxnumber` INT NULL ,
                `mkdate` INT NULL ,
                `chdate` INT NULL ,
            PRIMARY KEY (`rule_id`, `user_id`) )
            ENGINE = MyISAM");

        $cs_insert = $db->prepare("INSERT INTO coursesets (set_id,user_id,name,infotext,algorithm,mkdate,chdate)
                                   VALUES (?,?,?,?,'',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
        $cs_i_insert = $db->prepare("INSERT INTO courseset_institute (set_id,institute_id,mkdate,chdate) VALUES (?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
        $cs_r_insert = $db->prepare("INSERT INTO courseset_rule (set_id,rule_id,type,mkdate) VALUES (?,?,?,UNIX_TIMESTAMP())");
        $s_cs_insert = $db->prepare("INSERT INTO seminar_courseset (set_id,seminar_id,mkdate) VALUES (?,?,UNIX_TIMESTAMP())");
        $password_insert = $db->prepare("INSERT INTO passwordadmissions (rule_id,message,password,mkdate,chdate) VALUES (?,'Das Passwort ist falsch',?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
        $locked_insert = $db->prepare("INSERT INTO lockedadmissions (rule_id,message,mkdate,chdate) VALUES (?,'Die Anmeldung ist gesperrt',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
        $hasher = new PasswordHash(8, false);

        //mit pw wandeln
        $pw_admission = $db->fetchAll("SELECT seminar_id,name,passwort,institut_id FROM seminare WHERE Lesezugriff=2");
        foreach ($pw_admission as $course) {
            $new_pwd = $hasher->HashPassword($course['passwort']);
            $rule_id = md5(uniqid('passwordadmissions',1));
            $password_insert->execute(array($rule_id, $new_pwd));
            $set_id = md5(uniqid('coursesets',1));
            $name = 'Anmeldung mit Passwort: ' . $course['name'];
            $info = 'Erzeugt durch Migration 128 ' . strftime('%X %x');
            $cs_insert->execute(array($set_id,$GLOBALS['user']->id,$name,$info));
            $cs_i_insert->execute(array($set_id,$course['institut_id']));
            $cs_r_insert->execute(array($set_id,$rule_id,'PasswordAdmission'));
            $s_cs_insert->execute(array($set_id, $course['seminar_id']));
        }

        //locked wandeln
        $locked_admission = $db->fetchAll("SELECT seminar_id,name,institut_id FROM seminare WHERE admission_type=3");
        foreach ($locked_admission as $course) {
            $rule_id = md5(uniqid('lockedadmissions',1));
            $locked_insert->execute(array($rule_id));
            $set_id = md5(uniqid('coursesets',1));
            $name = 'Anmeldung gesperrt: ' . $course['name'];
            $info = 'Erzeugt durch Migration 128 ' . strftime('%X %x');
            $cs_insert->execute(array($set_id,$GLOBALS['user']->id,$name,$info));
            $cs_i_insert->execute(array($set_id,$course['institut_id']));
            $cs_r_insert->execute(array($set_id,$rule_id,'LockedAdmission'));
            $s_cs_insert->execute(array($set_id, $course['seminar_id']));
        }

        //gruppierte wandeln
        $grouped_admission = $db->fetchAll("SELECT seminar_id,seminare.name,admission_group.name as a_name,institut_id,admission_group
            FROM seminare inner join admission_group on(group_id=admission_group)
            WHERE admission_type in (1,2) ORDER BY admission_group");
        foreach ($grouped_admission as $course) {
            if ($group_id != $course['admission_group']) {
                $group_id = $course['admission_group'];
                $group_name = $course['a_name'];
                if (!$group_name) {
                    $group_name = " " . ++$g;
                }
                $rule_id = md5(uniqid('lockedadmissions',1));
                $locked_insert->execute(array($rule_id));
                $set_id = md5(uniqid('coursesets',1));
                $name = 'Anmeldung gesperrt: Gruppe ' . $group_name;
                $info = 'Erzeugt durch Migration 128 ' . strftime('%X %x');
                $cs_insert->execute(array($set_id,$GLOBALS['user']->id,$name,$info));
                $cs_i_insert->execute(array($set_id,$course['institut_id']));
                $cs_r_insert->execute(array($set_id,$rule_id,'LockedAdmission'));
            }
            $s_cs_insert->execute(array($set_id, $course['seminar_id']));
        }

        $admission = $db->fetchAll("SELECT seminar_id,seminare.name,institut_id,admission_turnout
            FROM seminare left join admission_group on(group_id=admission_group) WHERE admission_type in (1,2) AND group_id is null");
        $migrated_per_institute = array();
        foreach ($admission as $course) {
            if (!isset($migrated_per_institute[$course['institut_id']])) {
                $rule_id = md5(uniqid('lockedadmissions',1));
                $locked_insert->execute(array($rule_id));
                $set_id = md5(uniqid('coursesets',1));
                $inst_name = $db->fetchColumn("SELECT name FROM Institute WHERE Institut_id=?", array($course['institut_id']));
                $name = 'Anmeldung gesperrt: Einrichtung ' . $inst_name;
                $info = 'Erzeugt durch Migration 128 ' . strftime('%X %x');
                $cs_insert->execute(array($set_id,$GLOBALS['user']->id,$name,$info));
                $cs_i_insert->execute(array($set_id,$course['institut_id']));
                $cs_r_insert->execute(array($set_id,$rule_id,'LockedAdmission'));
                $migrated_per_institute[$course['institut_id']] = $set_id;
            }
            $s_cs_insert->execute(array($migrated_per_institute[$course['institut_id']], $course['seminar_id']));
        }

        $db->exec("UPDATE seminare SET Lesezugriff=1,Schreibzugriff=1 WHERE Lesezugriff=3");
        $db->exec("UPDATE seminare SET Lesezugriff=1,Schreibzugriff=1 WHERE Lesezugriff=2");

        //Warte und Anmeldelisten l�schen
        $db->exec("DELETE FROM admission_seminar_user WHERE status <> 'accepted'");
        $db->exec("DROP TABLE admission_seminar_studiengang");
        $db->exec("DROP TABLE admission_group");
        $db->exec("ALTER TABLE `seminare` DROP `Passwort`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_endtime`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_type`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_selection_take_place`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_group`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_starttime`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_endtime_sem`");
        $db->exec("ALTER TABLE `seminare` DROP `admission_enable_quota`");

        $db->exec("ALTER TABLE  `seminare` ADD  `admission_waitlist_max` INT UNSIGNED NOT NULL DEFAULT  '0'");
        $db->exec("ALTER TABLE  `seminare` ADD  `admission_disable_waitlist_move` TINYINT UNSIGNED NOT NULL DEFAULT '0'");

        $db->exec("ALTER TABLE `seminar_user` DROP `admission_studiengang_id`");
        $db->exec("ALTER TABLE `admission_seminar_user` DROP `studiengang_id`");
        try {
            $db->exec("ALTER TABLE `seminar_user` DROP INDEX `Seminar_id`");
            $db->exec("ALTER TABLE `seminar_user` DROP INDEX `user_id`");
        } catch (PDOException $e) {
        }
        $db->exec("ALTER TABLE `seminar_user` ADD INDEX (`user_id`, `Seminar_id`, `status`)");

        SimpleORMap::expireTableScheme();

        // Insert global configuration: who may edit course sets?
        $db->exec("INSERT IGNORE INTO `config`
            (`config_id`, `parent_id`, `field`, `value`, `is_default`,
             `type`, `range`, `section`, `position`, `mkdate`, `chdate`,
             `description`, `comment`, `message_template`)
        VALUES
            (MD5('ALLOW_DOZENT_COURSESET_ADMIN'), '',
            'ALLOW_DOZENT_COURSESET_ADMIN', '0', '1', 'boolean', 'global',
            'coursesets', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
            'Sollen Lehrende einrichtungsweite Anmeldesets anlegen und bearbeiten d�rfen?',
            '', '')");
    }

    function down()
    {
        $db = DBManager::get();
        // delete all tables related with new admission structure
        $db->exec("DROP TABLE `admission_condition`, `admissionfactor`,
            `admissionrules`, `conditionaladmissions`, `courseset_factorlist`,
            `courseset_rule`, `coursesets`, `limitedadmissions`,
            `lockedadmissions`, `priorities`, `seminar_courseset`,
            `timedadmissions`, `userfilter_fields`, `userfilter`,
            `user_factorlist`, `userlimits`");
    }
}
?>
