<?php
/**
* check_admission.class.php
*
* @author André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access public
* @since  2.4
*/
require_once 'lib/classes/CronJob.class.php';

class CheckAdmissionJob extends CronJob
{

     public static function getName()
     {
        return _('Losverfahren überprüfen');
     }

      public static function getDescription()
      {
          return _('Überprüft, ob Losverfahren anstehen und führt diese aus');
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
                  'send_messages' => array(
                          'type'        => 'boolean',
                          'default'     => true,
                          'status'      => 'optional',
                          'description' => _('Sollen interne Nachrichten an alle betroffenen Nutzer gesendet werden)'),
                  ),
          );
      }

      public function setUp()
      {
          require_once 'lib/language.inc.php';
          require_once 'lib/admission.inc.php';
          if (empty($GLOBALS['ABSOLUTE_URI_STUDIP'])) {
            throw new Exception('To use check_admission job you MUST set correct values for $ABSOLUTE_URI_STUDIP in config_local.inc.php!');
        }
      }

      public function execute($last_result, $parameters = array())
      {
          $verbose = $parameters['verbose'];
          $seminars = DbManager::get()
                  ->query("SELECT Seminar_id,Name FROM seminare
                  WHERE admission_endtime != -1
                  AND admission_endtime < UNIX_TIMESTAMP()
                  AND admission_type IN(1,2)
                  AND (admission_selection_take_place = '0' OR admission_selection_take_place IS NULL)
                  AND visible='1'")
                  ->fetchAll();
          if (count($seminars)) {
              if ($verbose) echo date('r') . ' - Assigning participants to this courses:' . chr(10);
              foreach($seminars as $sem) {
                  if ($verbose) echo ++$i . ' ' . $sem['Seminar_id'] . ' : ' . $sem['Name'] . chr(10);
              }
              check_admission($parameters['send_messages']);
          } else {
              if ($verbose) echo date('r') . ' - Nothing to do' . chr(10);
          }
      }
}
