<?php
$result = array (
  0 => 
  array (
    'Field' => 'schedule_id',
    'Type' => 'char(32)',
    'Null' => 'NO',
    'Key' => 'PRI',
    'Default' => '',
    'Extra' => '',
  ),
  1 => 
  array (
    'Field' => 'task_id',
    'Type' => 'char(32)',
    'Null' => 'NO',
    'Key' => 'MUL',
    'Default' => '',
    'Extra' => '',
  ),
  2 => 
  array (
    'Field' => 'active',
    'Type' => 'tinyint(1)',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '0',
    'Extra' => '',
  ),
  3 => 
  array (
    'Field' => 'title',
    'Type' => 'varchar(255)',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '',
    'Extra' => '',
  ),
  4 => 
  array (
    'Field' => 'description',
    'Type' => 'varchar(4096)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  5 => 
  array (
    'Field' => 'parameters',
    'Type' => 'text',
    'Null' => 'YES',
    'Key' => '',
    'Default' => '',
    'Extra' => '',
  ),
  6 => 
  array (
    'Field' => 'priority',
    'Type' => 'enum(\'low\',\'normal\',\'high\')',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 'normal',
    'Extra' => '',
  ),
  7 => 
  array (
    'Field' => 'type',
    'Type' => 'enum(\'periodic\',\'once\')',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 'periodic',
    'Extra' => '',
  ),
  8 => 
  array (
    'Field' => 'minute',
    'Type' => 'tinyint(2)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  9 => 
  array (
    'Field' => 'hour',
    'Type' => 'tinyint(2)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  10 => 
  array (
    'Field' => 'day',
    'Type' => 'tinyint(2)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  11 => 
  array (
    'Field' => 'month',
    'Type' => 'tinyint(2)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  12 => 
  array (
    'Field' => 'day_of_week',
    'Type' => 'tinyint(1) unsigned',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  13 => 
  array (
    'Field' => 'next_execution',
    'Type' => 'int(11) unsigned',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  14 => 
  array (
    'Field' => 'last_execution',
    'Type' => 'int(11) unsigned',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  15 => 
  array (
    'Field' => 'last_result',
    'Type' => 'text',
    'Null' => 'YES',
    'Key' => '',
    'Default' => '',
    'Extra' => '',
  ),
  16 => 
  array (
    'Field' => 'execution_count',
    'Type' => 'bigint(20) unsigned',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 0,
    'Extra' => '',
  ),
  17 => 
  array (
    'Field' => 'mkdate',
    'Type' => 'int(11) unsigned',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 0,
    'Extra' => '',
  ),
  18 => 
  array (
    'Field' => 'chdate',
    'Type' => 'int(11) unsigned',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 0,
    'Extra' => '',
  ),
);