<?php

/*
* Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*/
require "StudipDebug.class.php";

class DebugPDO extends PDO {

    public function query($query_string){
        $time = microtime(true);
        $ret = parent::query($query_string);
        StudipDebug::log_query($query_string, $time);
        return $ret;
    }
    public function exec($query_string){
        $time = microtime(true);
        $ret = parent::exec($query_string);
        StudipDebug::log_query($query_string, $time);
        return $ret;
    }
}

class DebugPDOStatement extends PDOStatement
{
    public $query_params = NULL;
    public $dbh;
    protected function __construct($dbh)
    {
        $this->dbh = $dbh;
    }

    public function execute($arr = null)
    {
        if (is_array($arr)) $this->queryParams = $arr;
        $time = microtime(true);
        $ret =  parent::execute($arr);
        StudipDebug::log_query($this->getActualQuery(), $time);
        return $ret;
    }

    public function bindValue($n, $value, $data_type = PDO::PARAM_STR)
    {
        $this->queryParams[$n] = $value;
        return parent::bindValue($n, $value, $data_type) ;
    }

    public function bindParam($parameter , &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        $this->queryParams[$parameter] =& $variable;
        return parent::bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    public function getActualQuery()
    {
        $rv = $this->queryString;
        if (preg_match('/\?/', $rv)) {
            while (preg_match('/\?/', $rv)) $rv = preg_replace('/\?/','%'.(++$i).'$s',$rv,1);
            foreach ((array)$this->queryParams as $k=>$v) $arr[$k]= $this->dbh->quote($v);
            return vsprintf($rv, $arr);
        }
        if (is_array($this->queryParams)) {
            foreach($this->queryParams as $key => $value) {
                $search = $key;
                $replace = $this->dbh->quote($value);
            }
            return str_replace($search, $replace, $rv);
        }
        return $rv;
    }
}

$_debug_pdo = new DebugPDO( 'mysql:host='.$GLOBALS['DB_STUDIP_HOST'].
                  ';dbname='.$GLOBALS['DB_STUDIP_DATABASE'],
                  $GLOBALS['DB_STUDIP_USER'],
                  $GLOBALS['DB_STUDIP_PASSWORD']);
$_debug_pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DebugPDOStatement',array($_debug_pdo)));
DBManager::getInstance()->setConnection('studip',$_debug_pdo);

