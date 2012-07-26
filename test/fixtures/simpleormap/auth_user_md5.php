<?php
$result = array (
  0 => 
  array (
    'Field' => 'user_id',
    'Type' => 'varchar(32)',
    'Null' => 'NO',
    'Key' => 'PRI',
    'Default' => '',
    'Extra' => '',
  ),
  1 => 
  array (
    'Field' => 'username',
    'Type' => 'varchar(64)',
    'Null' => 'NO',
    'Key' => 'UNI',
    'Default' => '',
    'Extra' => '',
  ),
  2 => 
  array (
    'Field' => 'password',
    'Type' => 'varchar(32)',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '',
    'Extra' => '',
  ),
  3 => 
  array (
    'Field' => 'perms',
    'Type' => 'enum(\'user\',\'autor\',\'tutor\',\'dozent\',\'admin\',\'root\')',
    'Null' => 'NO',
    'Key' => 'MUL',
    'Default' => 'user',
    'Extra' => '',
  ),
  4 => 
  array (
    'Field' => 'Vorname',
    'Type' => 'varchar(64)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  5 => 
  array (
    'Field' => 'Nachname',
    'Type' => 'varchar(64)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  6 => 
  array (
    'Field' => 'Email',
    'Type' => 'varchar(64)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  7 => 
  array (
    'Field' => 'validation_key',
    'Type' => 'varchar(10)',
    'Null' => 'NO',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  8 => 
  array (
    'Field' => 'auth_plugin',
    'Type' => 'varchar(64)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  9 => 
  array (
    'Field' => 'locked',
    'Type' => 'tinyint(1) unsigned',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '0',
    'Extra' => '',
  ),
  10 => 
  array (
    'Field' => 'lock_comment',
    'Type' => 'varchar(255)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  11 => 
  array (
    'Field' => 'locked_by',
    'Type' => 'varchar(32)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  12 => 
  array (
    'Field' => 'visible',
    'Type' => 'enum(\'global\',\'always\',\'yes\',\'unknown\',\'no\',\'never\')',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 'unknown',
    'Extra' => '',
  ),
);
