<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * event_log.php - event logging admin model
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/visual.inc.php';
require_once 'lib/show_log.inc.php';

class EventLog
{
    /*
     * clean up old log events
     */
    function cleanup_log_events ()
    {
        $db = DBManager::get();

        $sql = 'DELETE log_events FROM log_events JOIN log_actions USING(action_id)
                WHERE expires > 0 AND mkdate + expires < UNIX_TIMESTAMP()';
        return $db->exec($sql);
    }

    /**
     * get object types available for query
     */
    function get_object_types ()
    {
        return array(
            'course'    => _('Veranstaltung'),
            'institute' => _('Einrichtung'),
            'user'      => _('BenutzerIn'),
            'resource'  => _('Ressource')
        );
    }

    /**
     * find objects matching the given string
     */
    function find_objects ($type, $string)
    {
        switch ($type) {
            case 'course':
                return showlog_search_seminar(addslashes($string));
            case 'institute':
                return showlog_search_inst(addslashes($string));
            case 'user':
                return showlog_search_user(addslashes($string));
            case 'resource':
                return showlog_search_resource(addslashes($string));
        }

        return NULL;
    }

    /**
     * build SQL query filter for selected action and object
     */
    private function sql_event_filter ($action_id, $object_id)
    {
        if (isset($action_id) && $action_id != 'all') {
            $filter[] = "action_id = '".addslashes($action_id)."'";
        }

        if (isset($object_id)) {
            $filter[] = "(affected_range_id   = '".addslashes($object_id)."' OR
                          coaffected_range_id = '".addslashes($object_id)."')";
        }

        return count($filter) ? 'WHERE '.join(' AND ', $filter) : '';
    }

    /**
     * count number of log events for selected action
     */
    function count_log_events ($action_id, $object_id)
    {
        $db = DBManager::get();

        $filter = $this->sql_event_filter($action_id, $object_id);
        $result = $db->query("SELECT COUNT(*) FROM log_events $filter");
        return $result->fetchColumn(0);
    }

    /**
     * get log events (max. 50) for selected action, starting at offset
     */
    function get_log_events ($action_id, $object_id, $offset)
    {
        $offset = (int)$offset;

        $db = DBManager::get();

        $log_events = array();
        $filter = $this->sql_event_filter($action_id, $object_id);
        $sql = "SELECT * FROM log_events $filter ORDER BY mkdate DESC, event_id DESC LIMIT $offset, 50";
        $result = $db->query($sql);

        foreach ($result as $row) {
            $action = get_log_action($row['action_id']);
            $info = showlog_format_infotemplate($action, $row['user_id'], $row['affected_range_id'],
                                                $row['coaffected_range_id'], $row['info'], $row['dbg_info']);
            $log_events[] = array(
                'time'   => $row['mkdate'],
                'info'   => $info,
                'detail' => $row['info'],
                'debug'  => $row['dbg_info']
            );
        }

        return $log_events;
    }

    /**
     * get list of all available log actions
     */
    function get_log_actions ()
    {
        $db = DBManager::get();

        $sql = 'SELECT action_id, COUNT(*) FROM log_events GROUP BY action_id';
        $result = $db->query($sql);

        foreach ($result as $row) {
            $log_count[$row[0]] = $row[1];
        }

        $sql = 'SELECT * FROM log_actions ORDER BY name';
        $result = $db->query($sql);

        foreach ($result as $row) {
            $row['log_count'] = (int) $log_count[$row['action_id']];
            $log_actions[] = $row;
        }

        return $log_actions;
    }

    /**
     * get all log actions with recorded events
     */
    function get_used_log_actions ()
    {
        $db = DBManager::get();

        $sql = "SELECT action_id, description, SUBSTRING_INDEX(name, '_', 1) AS log_group
                FROM log_actions WHERE EXISTS
                (SELECT * FROM log_events WHERE log_events.action_id = log_actions.action_id)
                ORDER BY log_group, description";

        $result = $db->query($sql);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * update log action in the database
     */
    function update_log_action ($action_id, $description, $info_template, $active, $expires)
    {
        $db = DBManager::get();

        if ($description === '') {
            throw new InvalidArgumentException(_('Keine Beschreibung angegeben.'));
        } else if ($info_template === '') {
            throw new InvalidArgumentException(_('Kein Info-Template angegeben.'));
        } else if ($expires < 0) {
            throw new InvalidArgumentException(_('Ablaufzeit darf nicht negativ sein.'));
        }

        $sql = "UPDATE log_actions
                SET description = ?, info_template = ?, active = ?, expires = ?
                WHERE action_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            $description,
            $info_template,
            $active,
            $expires,
            $action_id
        ));
    }
}
?>
