<?php
/**
 * StudipLog.php
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * Usage:
 * @code
 * //instant logging to $GLOBALS['TMP_PATH'] . '/studip.log'
 * StudipLog::_('log this');
 * //use other file for default logging
 * StudipLog::get()->setHandler('/tmp/anotherlog.txt');
 * //create additional log
 * StudipLog::set('my', '/tmp/mylog.txt');
 * StudipLog::_my('log to my', StudipLog::DEBUG);
 * //use self defined log handler
 * StudipLog::get('my')
 * ->setHandler(function ($m) {
 *   return mail( mail('noack@data-quest.de', '['.$m['level_name'].']', $m['formatted']););
 *   });
 * StudipLog::_my('log via mail');
 * @endcode
 * 
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class StudipLog
{

    const EMERGENCY = 0; // All is lost
    const ALERT = 1; // Immediate action required
    const CRITICAL = 2; // Critical conditions
    const ERROR = 3; // An error occurred
    const WARNING = 4; // Something unexpected happening
    const NOTICE = 5; // Something worth noting
    const INFO = 6; // Information, not an error
    const DEBUG = 7; // Debugging messages

    /**
     * if string then complete path to logfile
     * if not schould be callable
     * 
     * @var mixed
     */
    private $log_handler = null;

    /**
     * maximum log level
     * 
     * @var integer
     */
    private $log_level = 6;
    /**
     * an array with log levels, taken from contants
     * .
     * @var array
     */
    private $log_level_names = array();

    /**
     * if log_handler is a string
     * the file pointer
     * 
     * @var resource
     */
    private $file = null;

    /**
     * array of used log instances
     * 
     * @var array
     */
    private static $instances = array();

    /**
     * returns a log instance, identified by given name
     * if name is omitted, the default logger is returned
     * 
     * @param string $name name of log instance
     * @throws InvalidArgumentException
     * @return StudipLog
     */
    public static function get($name = '')
    {
        $name = strlen($name) ? $name : 0;
        if ($name === 0 && !isset(self::$instances[$name])) {
            self::set();
        }
        if (!isset(self::$instances[$name])) {
            throw new InvalidArgumentException('Unknown logger: ' . $name);
        }
        return self::$instances[$name];
    }

    /**
     * sets a log handler for the named log instance
     * returns the old handler
     * 
     * @param string $name
     * @param mixed $log_handler
     * @return mixed
     */
    public static function set($name = '', $log_handler = null)
    {
        $name = strlen($name) ? $name : 0;
        if (isset(self::$instances[$name])) {
            $old = self::$instances[$name];
        }
        self::$instances[$name] = new StudipLog($log_handler);
        return $old;
    }

    /**
     * magic log getter, intercepts all static method calls if the
     * method name begins with an underscore
     * 
     * @param string $name
     * @param array $arguments
     * @return StudipLog
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name[0] === '_') {
            $log_name = substr($name, 1);
            $message = $arguments[0];
            $level = isset($arguments[1]) ? $arguments[1] : self::ERROR;
            return self::get($log_name)->log($message, $level);
        }
    }

    /**
     * create new log instance with given handler
     * 
     * @param mixed $log_handler
     */
    function __construct($log_handler = null)
    {
        $this->setHandler($log_handler);
        $r = new ReflectionClass($this);
        $this->log_level_names = array_flip($r->getConstants());
    }

    /**
     * set the maximum log level
     * 
     * @param integer $level
     * @return integer
     */
    public function setLogLevel($level)
    {
        $old = $this->log_level;
        $this->log_level = $level;
        return $old;
    }

    /**
     * returns the current maximum log level
     * 
     * @return integer
     */
    public function getLogLevel()
    {
        return $this->log_level;
    }

    /**
     * set the log handler
     * returns the old handler
     * 
     * @param mixed $log_handler
     * @return mixed
     */
    public function setHandler($log_handler)
    {
        $old = $this->log_handler;
        $this->log_handler = $log_handler;
        if (is_resource($this->file)) {
            fclose($this->file);
        }
        return $old;
    }

    /**
     * returns the current log handler
     * 
     * @return mixed
     */
    public function getHandler()
    {
        return $this->log_handler;
    }

    /**
     * log a message
     * 
     * @param string $message the log message
     * @param integer $level log level, see constants
     * @return mixed number of written bytes or return value from callable handler
     */
    public function log($message, $level = 3)
    {
        if ($level <= $this->log_level) {
            $log_level_name = $this->log_level_names[$level];
            $formatted_message = date('c') . ' ['.$this->log_level_names[$level].'] ' . $message;
            if (is_callable($this->log_handler)) {
                $log_handler = $this->log_handler;
                return $log_handler(array('formatted' => $formatted_message,
                                            'message' => $message,
                                            'level' => $level,
                                            'level_name' => $this->log_level_names[$level],
                                            'timestamp' => time()
                                            ));
            } else {
                $logfile = $this->log_handler ? $this->log_handler : $GLOBALS['TMP_PATH'] . '/studip.log';
                $this->file = is_resource($this->file) ? $this->file : @fopen($logfile, 'ab');
                if ($this->file && flock($this->file , LOCK_EX)) {
                    $ret = fwrite($this->file, date('c') . ' ['.$this->log_level_names[$level].'] ' . $message . "\n");
                    flock($this->file, LOCK_UN);
                    return $ret;
                } else {
                    trigger_error(sprintf('Logfile %s could not be opened.', $logfile), E_USER_WARNING);
                }
            }
        }
    }
}