<?php
/**
* check_admission.class.php
*
* @author Andr� Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access public
* @since  2.4
*/

class CheckAdmissionJob extends CronJob
{

     public static function getName()
     {
        return _('Losverfahren �berpr�fen');
     }

      public static function getDescription()
      {
          return _('�berpr�ft, ob Losverfahren anstehen und f�hrt diese aus');
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
                  @mkdir($GLOBALS['TMP_PATH'] . '/seat_distribution_logs');
                  $logfile = $GLOBALS['TMP_PATH'] . '/seat_distribution_logs/' .  date('Y-m-d-H-i') . '_seat_distribution.log';
                  if (is_dir($GLOBALS['TMP_PATH'] . '/seat_distribution_logs')) {
                      Log::get()->setHandler($logfile);
                      Log::get()->setLogLevel(Log::DEBUG);
                      echo 'logging to ' . $logfile . chr(10);
                  } else {
                      echo 'could not create directory ' . $GLOBALS['TMP_PATH'] . '/seat_distribution_logs' . chr(10);
                  }
              }
              foreach($sets as $set_id) {
                  $courseset = new CourseSet($set_id);
                  if ($courseset->isSeatDistributionEnabled() && !$courseset->hasAlgorithmRun() && $courseset->getSeatDistributionTime() < time()) {
                      if ($verbose) {
                          echo ++$i . ' ' . $courseset->getId() . ' : ' . $courseset->getName() . chr(10);
                          $applicants = AdmissionPriority::getPriorities($set_id);
                          $courses = SimpleCollection::createFromArray(Course::findMany($courseset->getCourses()))->toGroupedArray('seminar_id', words('name veranstaltungsnummer'));
                          $captions = array(_("Nachname"), _("Vorname"), _("Nutzername"),_('Nutzer-ID'), _('Veranstaltung-ID'), _("Veranstaltung"), _("Nummer"), _("Priorit�t"));
                          $data = array();
                          $users = User::findEachMany(function($user) use ($courses,$applicants,&$data) {
                                  $app_courses = $applicants[$user->id];
                                  asort($app_courses);
                                  foreach ($app_courses as $course_id => $prio) {
                                      $row = array();
                                      $row[] = $user->nachname;
                                      $row[] = $user->vorname;
                                      $row[] = $user->username;
                                      $row[] = $user->id;
                                      $row[] = $course_id;
                                      $row[] = $courses[$course_id]['name'];
                                      $row[] = $courses[$course_id]['veranstaltungsnummer'];
                                      $row[] = $prio;
                                      $data[] = $row;
                                  }
                          }, array_keys($applicants), 'ORDER BY Nachname');
                          $applicants_file = $GLOBALS['TMP_PATH'] . '/seat_distribution_logs/applicants_' . $set_id . '.csv';
                          if (array_to_csv($data, $applicants_file, $captions)) {
                              echo 'applicants written to ' . $applicants_file . chr(10);
                          }
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
