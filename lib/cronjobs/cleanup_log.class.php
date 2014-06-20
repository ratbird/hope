<?php
/**
 * cleanup_log.php - Clean up event and cronjob logs
 *
 * @author Tobias Thelen <tobias.thelen@uni-osnabrueck.de>
 * @author Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @access public
 * @since  2.4
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// cleanup_log.class.php
//
// Copyright (C) 2013 Jan-Hendrik Willms <tleilax+studip@gmail.com>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once 'lib/classes/CronJob.class.php';

class CleanupLogJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('Logs aufr�umen');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Entfernt abgelaufene Log-Eintr�ge sowohl f�r das '
                .'Eventsystem als auch f�r die Cronjobs');
    }

    /**
     * Return the paremeters for this cronjob.
     *
     * @return Array Parameters.
     */
    public static function getParameters()
    {
        return array(
            'cronjobs' => array(
                'type'        => 'boolean',
                'default'     => false,
                'status'      => 'optional',
                'description' => _('Sollen die Logeintr�ge f�r Cronjobs auch gel�scht werden'),
            ),
            'cronjobs-success' => array(
                'type'        => 'integer',
                'default'     => 1,
                'status'      => 'optional',
                'description' => _('Nach wievielen Tagen sollen Logeintr�ge f�r '
                                  .'erfolgreiche Cronjobs gel�scht werden (0 f�r nie)'),
            ),
            'cronjobs-error' => array(
                'type'        => 'integer',
                'default'     => 28,
                'status'      => 'optional',
                'description' => _('Nach wievielen Tagen sollen Logeintr�ge f�r '
                                  .'fehlgeschlagene Cronjobs gel�scht werden (0 f�r nie)'),
            ),
        );
    }

    /**
     * Setup method. Loads the neccessary classes.
     */
    public function setUp()
    {
        require_once 'app/models/event_log.php';
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     */
    public function execute($last_result, $parameters = array())
    {
        $event_log = new EventLog();
        $event_log->cleanup_log_events();

        if (!empty($parameters['cronjobs'])) {
            $delete = function($l) {$l->delete();};
            if ($parameters['cronjobs-error'] > 0) {
                CronjobLog::findEachBySql($delete, "exception != 'N;' AND executed + ? < UNIX_TIMESTAMP()",
                                              array($parameters['cronjobs-error'] * 24 * 60 * 60));
            }
            if ($parameters['cronjobs-success'] > 0) {
                CronjobLog::findEachBySql($delete, "exception = 'N;' AND executed + ? < UNIX_TIMESTAMP()",
                                              array($parameters['cronjobs-success'] * 24 * 60 * 60));
            }
        }
    }
}

