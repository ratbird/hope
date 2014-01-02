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
          require_once 'lib/classes/admission/CourseSet.class.php';
          if (empty($GLOBALS['ABSOLUTE_URI_STUDIP'])) {
            throw new Exception('To use check_admission job you MUST set correct values for $ABSOLUTE_URI_STUDIP in config_local.inc.php!');
        }
      }

      public function execute($last_result, $parameters = array())
      {
          $verbose = $parameters['verbose'];
          $sets = DbManager::get()
                  ->fetchFirst("SELECT DISTINCT cr.set_id FROM courseset_rule cr INNER JOIN coursesets USING(set_id)
                          WHERE type = 'ParticipantRestrictedAdmission' AND algorithm_run = 0");
          if (count($sets)) {
              if ($verbose) {
                  echo date('r') . ' - Starting seat distribution ' . chr(10);
                  $old_logger = Log::get()->getHandler();
                  $old_log_level = Log::get()->getLogLevel();
                  Log::get()->setHandler(function($l) {echo $l['formatted'] ."\n";});
                  Log::get()->setLogLevel(Log::DEBUG);
              }
              foreach($sets as $set_id) {
                  $courseset = new CourseSet($set_id);
                  if ($courseset->isSeatDistributionEnabled() && !$courseset->hasAlgorithmRun() && $courseset->getSeatDistributionTime() < time()) {
                      if ($verbose) {
                          echo ++$i . ' ' . $courseset->getId() . ' : ' . $courseset->getName() . chr(10);
                      }
                      $courseset->distributeSeats();
                  }
              }
              if ($verbose) {
                  Log::get()->setHandler($old_logger);
                  Log::get()->setLogLevel($old_log_level);
              }
          } else {
              if ($verbose) echo date('r') . ' - Nothing to do' . chr(10);
          }
      }
}
