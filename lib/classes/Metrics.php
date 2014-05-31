<?php
/*
 * Metrics.php - TODO
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      <mlunzena@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class Metrics {

    public static function increment($stat, $sampleRate = null)
    {
        self::count($stat, 1, $sampleRate);
    }

    public static function decrement($stat, $sampleRate = null)
    {
        self::count($stat, -1, $sampleRate);
    }

    public static function count($stat, $value, $sampleRate = null)
    {
        self::sendMessage('count', func_get_args());
    }

    public static function timing($stat, $time = null, $sampleRate = null)
    {
        self::sendMessage('timing', func_get_args());
    }

    public static function startTimer($stat, $sampleRate = null)
    {
        $start_time = microtime(true);
        return function () use ($stat, $start_time, $sampleRate) {
            \Metrics::timing($stat, $start_time, $sampleRate);
        };
    }

    public static function gauge($stat, $value, $sampleRate = null)
    {
        self::sendMessage('gauge', func_get_args());
    }


    private static $metricPlugins;

    private static function sendMessage($message, $args)
    {
        if (!self::$metricPlugins) {
            self::$metricPlugins = \PluginEngine::getPlugins('MetricsPlugin');
        }
        foreach (self::$metricPlugins as $plugin) {
            call_user_func_array(array($plugin, $message), $args);
        }
    }
}
