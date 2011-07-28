<?
/*
* Copyright (C) 2008 - André Noack <noack@data-quest.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*/

require_once "Log.php";
require_once "FirePHPCore/fb.php";

class StudipDebug {

    static private $logger;
    static private $total_query_time;

    public static function log($msg) {
        if(is_null(self::$logger)){
            self::$logger = Log::factory('file', $GLOBALS['TMP_PATH'] . '/studip-debug.log', '', array('append' => false));
            self::$logger->log('Logging started: ' . realpath($_SERVER['SCRIPT_FILENAME']));
            self::$logger->log('$_REQUEST:'.print_r($_REQUEST,1));
        }
        self::$logger->log($msg);
    }

    public static function log_query($query_string, $starttime) {
        $query_time = microtime(true) - $starttime;
        self::$total_query_time += $query_time;
        StudipDebug::log($query_string."\nquery time: ".round($query_time, 4)."; total query time: " .round(self::$total_query_time, 4) . "; memory: ". (int)(memory_get_usage() / 1024) ." KB");
    }

    public static function log_time($msg, $starttime) {
        $query_time = microtime(true) - $starttime;
        StudipDebug::log($msg."\nquery time: ".round($query_time, 4)."; memory: ". (int)(memory_get_usage() / 1024) ." KB");
    }

    public static function get_backtrace($NL = "\n") {
        $dbgTrace = debug_backtrace();
        $dbgMsg .= $NL."Debug backtrace begin:$NL";
        foreach($dbgTrace as $dbgIndex => $dbgInfo) {
            $dbgMsg .= "\t at $dbgIndex  ".$dbgInfo['file']." (line {$dbgInfo['line']}) -> {$dbgInfo['function']}(".join(",",$dbgInfo['args']).")$NL";
        }
        $dbgMsg .= "Debug backtrace end".$NL;
        return $dbgMsg;
    }
}
?>
