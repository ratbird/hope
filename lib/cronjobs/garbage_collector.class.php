<?php
/**
* garbage_collector.class.php
*
* @author André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access public
* @since  2.4
*/
require_once 'lib/classes/CronJob.class.php';

class GarbageCollectorJob extends CronJob
{

    public static function getName()
    {
        return _('Datenbank bereinigen');
    }

    public static function getDescription()
    {
        return _('Entfernt endgültig gelöschte Nachrichten, nicht zugehörige Dateianhänge und abgelaufene Ankündigungen');
    }

    public static function getParameters()
    {
        return array(
                'verbose' => array(
                        'type'        => 'boolean',
                        'default'     => false,
                        'status'      => 'optional',
                        'description' => _('Sollen Ausgaben erzeugt werden (sind später im Log des Cronjobs sichtbar)'),
                ),
        );
    }

    public function setUp()
    {
        require_once 'lib/datei.inc.php';
    }

    public function execute($last_result, $parameters = array())
    {
        $db = DBManager::get();
        $dd_func = function($d) {
            delete_document($d);
        };

        //abgelaufenen News löschen
        $deleted_news = StudipNews::DoGarbageCollect();
        //messages aufräumen
        $to_delete = $db->query("SELECT message_id, count( message_id ) AS gesamt, count(IF (deleted =0, NULL , 1) ) AS geloescht
                FROM message_user GROUP BY message_id HAVING gesamt = geloescht")->fetchAll(PDO::FETCH_COLUMN,0);
        if (count($to_delete)) {
            $db->exec("DELETE FROM message_user WHERE message_id IN(" . $db->quote($to_delete) . ")");
            $db->exec("DELETE FROM message WHERE message_id IN(" . $db->quote($to_delete) . ")");
            $to_delete_attach = $db->query("SELECT dokument_id FROM dokumente WHERE range_id IN(" . $db->quote($to_delete) . ")")->fetchAll(PDO::FETCH_COLUMN,0);
            array_walk($to_delete_attach, $dd_func);
        }
        //Attachments von nicht versendeten Messages aufräumen
        $to_delete_attach = $db->query("SELECT dokument_id FROM dokumente WHERE range_id = 'provisional' AND chdate < UNIX_TIMESTAMP(DATE_ADD(NOW(),INTERVAL -2 HOUR))")->fetchAll(PDO::FETCH_COLUMN,0);
        array_walk($to_delete_attach, $dd_func);
        if ($parameters['verbose']) {
            printf(_("Gelöschte Ankündigungen: %u") . "\n", (int)$deleted_news);
            printf(_("Gelöschte Nachrichten: %u") . "\n", count($to_delete));
            printf(_("Gelöschte Dateianhänge: %u") . "\n", count($to_delete_attach));
        }

        PersonalNotifications::doGarbageCollect();
    }
}
