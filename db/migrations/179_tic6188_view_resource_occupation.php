<?php
/**
 * Migration for TIC #6188
 *
 * @author  Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.4
 *
 * @see https://develop.studip.de/trac/ticket/6188
 */
class Tic6188ViewResourceOccupation extends Migration
{
    function description()
    {
        return 'Creates a config switch for restricting who may view resource occupation schedules';
    }

    public function up()
    {
        // Add config entry.
        $query = "INSERT IGNORE INTO `config`
                    (`config_id`, `field`, `value`, `is_default`, `type`, `range`, `section`,
                     `mkdate`, `chdate`, `description`)
                  VALUES (MD5(:field), :field, :value, 1, :type, 'global', 'resources',
                          UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)";
        $statement = DBManager::get()->prepare($query);

        $statement->execute(array(
            ':field' => 'RESOURCES_ALLOW_VIEW_RESOURCE_OCCUPATION',
            ':value' => '1',
            ':type'  => 'boolean',
            ':description' => 'D�rfen alle Nutzer Ressourcenbelegungen einsehen?',
        ));
    }

    public function down()
    {
        DBManager::get()->exec("DELETE FROM `config` WHERE `field`='RESOURCES_ALLOW_VIEW_RESOURCE_OCCUPATION'");
    }
}
