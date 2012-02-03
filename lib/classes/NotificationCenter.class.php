<?php
# Lifter010: TODO
/*
 * NotificationCenter.class.php - NotificationCenter class
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// ########################################################################
//  MODULE DEFINITION
/** @defgroup notifications Notifications */

/**
 * Special Exception that can be thrown to veto an announced change.
 * Only some types of events support this (see documentation).
 */
class NotificationVetoException extends Exception
{
}

/**
 * The NotificationCenter class is the central event dispatcher
 * for Stud.IP. Objects interested in receiving notifications for
 * particular events need to register with the NotificationCenter:
 *
 * NotificationCenter::addObserver($this, 'update', 'shutdown');
 *
 * Event notifications are sent via the postNotification() method:
 *
 * NotificationCenter::postNotification('shutdown', $sender);
 */
class NotificationCenter
{
    /**
     * array of registered notification observers
     */
    private static $observers = array();

    /**
     * Register an object to be notified. The same object may be
     * registered several times (e.g. for different notifications).
     *
     * @param object $observer  object to be notified
     * @param string $method    method that will be called
     * @param string $event     name of event (may be NULL)
     * @param mixed  $object    subject to observe (may be NULL)
     */
    public static function addObserver($observer, $method, $event, $object = NULL)
    {
        if ($event === NULL) {
            $event = '';
        }

        if ($object) {
            $predicate = is_callable($object)
                ? $object
                : function ($other) use ($object) {
                    return $object === $other;
                  };
        }

        self::$observers[$event][] =
            array('predicate' => $predicate ?: NULL,
                  'observer'  => array($observer, $method));
    }

    /**
     * Remove an object registered with the NotificationCenter.
     * Trying to remove an observer that was not registered is
     * allowed and has no effect.
     *
     * @param object $observer  object to be removed
     * @param string $event     name of event (may be NULL)
     * @param mixed  $object    subject to observe (may be NULL)
     */
    public static function removeObserver($observer, $event = NULL, $object = NULL)
    {
        if ($event === NULL) {
            $events = array_keys(self::$observers);
        } else if (isset(self::$observers[$event])) {
            $events = array($event);
        } else {
            return;
        }

        foreach ($events as $event) {
            foreach (self::$observers[$event] as $index => $list) {
                if ($object === NULL
                    || $list['predicate'] && $list['predicate']($object)) {

                    if ($list['observer'][0] === $observer) {
                        unset(self::$observers[$event][$index]);
                    }
                }
            }
        }
    }

    /**
     * Post an event notification to all registered observers.
     * Only observers registered for this event type and subject
     * are notified.
     *
     * @param string $event     name of this notification
     * @param mixed  $object    subject of this notification
     * @param mixed  $user_data additional information (optional)
     *
     * @throws NotificationVetoException  on observer veto
     */
    public static function postNotification($event, $object, $user_data = NULL)
    {
        foreach (array('', $event) as $e) {
            if (isset(self::$observers[$e])) {
                foreach (self::$observers[$e] as $list) {
                    if (!$list['predicate'] || $list['predicate']($object)) {
                        call_user_func($list['observer'], $event, $object, $user_data);
                    }
                }
            }
        }
    }
}
