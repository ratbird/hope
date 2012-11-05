<?
/*
* Copyright (C) 2008 - André Noack <noack@data-quest.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*/

require_once "lib/classes/Log.php";
@include "FirePHPCore/fb.php";

class StudipDebug {

    static private $logger;
    static private $total_query_time;

    public static function log($msg) {
        if(is_null(self::$logger)){
            $logger = function($m)
            {
                return file_put_contents($GLOBALS['TMP_PATH'] . '/studip-debug.sql', $m['message'], FILE_APPEND);
            };
            file_put_contents($GLOBALS['TMP_PATH'] . '/studip-debug.sql', '');
            Log::set('query', $logger);
            self::$logger = Log::get('query');
            self::log('-- Logging started: ' . realpath($_SERVER['SCRIPT_FILENAME']));
            self::log('-- $_REQUEST:'. join("\n-- ", explode("\n",print_r($_REQUEST,1))));
        }
        self::$logger->info($msg."\n");
    }

    public static function log_query($query_string, $starttime) {
        $query_time = microtime(true) - $starttime;
        self::$total_query_time += $query_time;
        StudipDebug::log($query_string."\n-- query time: ".round($query_time, 4)."; total query time: " .round(self::$total_query_time, 4) . "; memory: ". (int)(memory_get_usage(true) / 1024) ." KB; total memory: ". (int)(memory_get_peak_usage(true) / 1024) ." KB");
    }

    public static function log_time($msg, $starttime) {
        $query_time = microtime(true) - $starttime;
        StudipDebug::log($msg."\n-- query time: ".round($query_time, 4)."; memory: ". (int)(memory_get_usage(true) / 1024) ." KB; total memory: ". (int)(memory_get_peak_usage(true) / 1024) ." KB");
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
