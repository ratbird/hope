<?php
class SetupApi extends Migration
{
    function description()
    {
        return 'Creates api tables in database and according config entries';
    }

    function up()
    {
        $sql = file_get_contents(__DIR__ . '/../../vendor/oauth-php/library/store/mysql/mysql.sql');
        $chunks = explode('#--SPLIT--', $sql);
        $chunks = array_filter($chunks);
        foreach ($chunks as $chunk) {
            $chunk = preg_replace('/^#.*/m', '', $chunk);
            $chunk = implode("\n", array_filter(explode("\n", $chunk)));
            DBManager::get()->exec($chunk);
        }

        // TODO: InnoDB?
        $query = "CREATE TABLE IF NOT EXISTS `api_oauth_mapping` (
            `oauth_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` CHAR(32) NOT NULL,
            `mkdate` INT(11) UNSIGNED NOT NULL,
            `access_granted` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (`oauth_id`),
            UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB";
        DBManager::get()->exec($query);

        $query = "CREATE TABLE IF NOT EXISTS `api_permissions` (
            `route_id` CHAR(32) NOT NULL,
            `consumer_id` CHAR(32) NOT NULL,
            `method` CHAR(6) NOT NULL,
            `granted` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY `route_id` (`route_id`,`consumer_id`,`method`)
        )";
        DBManager::get()->exec($query);

        // Add config entries
        $query = "INSERT IGNORE INTO `config`
                    (`config_id`, `field`, `value`, `is_default`, `type`, `range`, `section`,
                     `mkdate`, `chdate`, `description`)
                  VALUES (MD5(:field), :field, :value, 1, :type, 'global', 'global',
                          UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)";
        $statement = DBManager::get()->prepare($query);

        $statement->execute(array(
            ':field' => 'API_ENABLED',
            ':value' => (int)false,
            ':type'  => 'boolean',
            ':description' => 'Schaltet die REST-API an',
        ));

        $statement->execute(array(
            ':field'       => 'API_OAUTH_AUTH_PLUGIN',
            ':value'       => 'Standard',
            ':type'        => 'string',
            ':description' => 'Definiert das für OAuth verwendete Authentifizierungsverfahren',
        ));
    }

    function down()
    {
        DBManager::get()->exec("DELETE FROM config WHERE field IN ('API_ENABLED', 'API_OAUTH_AUTH_PLUGIN')");
        DBManager::get()->exec("DROP TABLE IF EXISTS `api_permissions`, `api_oauth_mapping`");

        // Delete all tables that belong to oauth
        $tables = DBManager::get()
            ->query("SHOW TABLES LIKE 'oauth_%'")
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            DBManager::get()->exec("DROP TABLE {$table}");
        }
    }
}
