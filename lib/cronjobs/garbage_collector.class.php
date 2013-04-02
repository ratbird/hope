<?php
/**
* garbage_collector.class.php
*
* @author Andr� Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
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
        return _('Entfernt endg�ltig gel�schte Nachrichten, nicht zugeh�rige Dateianh�nge und abgelaufene Ank�ndigungen');
    }

    public static function getParameters()
    {
        return array(
                'verbose' => array(
                        'type'        => 'boolean',
                        'default'     => false,
                        'status'      => 'optional',
                        'description' => _('Sollen Ausgaben erzeugt werden (sind sp�ter im Log des Cronjobs sichtbar)'),
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

        //abgelaufenen News l�schen
        $deleted_news = StudipNews::DoGarbageCollect();
        //messages aufr�umen
        $to_delete = $db->query("SELECT message_id, count( message_id ) AS gesamt, count(IF (deleted =0, NULL , 1) ) AS geloescht
                FROM message_user GROUP BY message_id HAVING gesamt = geloescht")->fetchAll(PDO::FETCH_COLUMN,0);
        if (count($to_delete)) {
            $db->exec("DELETE FROM message_user WHERE message_id IN(" . $db->quote($to_delete) . ")");
            $db->exec("DELETE FROM message WHERE message_id IN(" . $db->quote($to_delete) . ")");
            $to_delete_attach = $db->query("SELECT dokument_id FROM dokumente WHERE range_id IN(" . $db->quote($to_delete) . ")")->fetchAll(PDO::FETCH_COLUMN,0);
            array_walk($to_delete_attach, $dd_func);
        }
        //Attachments von nicht versendeten Messages aufr�umen
        $to_delete_attach = $db->query("SELECT dokument_id FROM dokumente WHERE range_id = 'provisional' AND chdate < UNIX_TIMESTAMP(DATE_ADD(NOW(),INTERVAL -2 HOUR))")->fetchAll(PDO::FETCH_COLUMN,0);
        array_walk($to_delete_attach, $dd_func);
        if ($parameters['verbose']) {
            printf(_("Gel�schte Ank�ndigungen: %u") . "\n", (int)$deleted_news);
            printf(_("Gel�schte Nachrichten: %u") . "\n", count($to_delete));
            printf(_("Gel�schte Dateianh�nge: %u") . "\n", count($to_delete_attach));
        }
    }
}
