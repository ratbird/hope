<?php
/**
 * clean_object_user_visits.php.
 *
 * Core cronjob that removes old entries from the table "object_user_visits".
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since  3.1
 */

class CleanObjectUserVisits extends CronJob
{
    /**
     * Return the name of this cronjob.
     *
     * @return String containing the name
     */
    public static function getName()
    {
        return _('Entfernt alte Einträge aus der Tabelle "object_user_visits"');
    }

    /**
     * Returns the description for this cronjob.
     *
     * @return String containing the description
     */
    public static function getDescription()
    {
        return _('Um die Tabelle "object_user_visits" schmal zu halten, werden alte Einträge nach einem definierten Zeitraum entfernt.');
    }

    /**
     * Returns the defined configuration parameters for this cronjob.
     *
     * @return Array with defined parameters
     */
    public static function getParameters()
    {
        return array(
            'expires' => array(
                'type'        => 'integer',
                'default'     => 365,
                'status'      => 'mandatory',
                'description' => _('Anzahl an Tagen, nach denen ein Eintrag aus der Tabelle gelöscht wird.'),
            ),
        );
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result Result returned from the last execution
     * @param Array $parameters  Defined parameters
     */
    public function execute($last_result, $parameters = array())
    {
        $expires = $parameters['expires'];

        if ($expires) {
            $query = "DELETE FROM `object_user_visits`
                      WHERE GREATEST(`visitdate`, `last_visitdate`) < UNIX_TIMESTAMP(NOW() - INTERVAL :expires DAY)";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':expires', (int) $expires, PDO::PARAM_INT);
            $statement->execute();
        }
    }
}
