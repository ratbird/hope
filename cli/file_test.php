#!/usr/bin/php -q
<?php
/**
 * file_test.php - tests for the File class
 *
 * Copyright (c) 2013  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'studip_cli_env.inc.php';
//require_once 'lib/files/File.php';
//require_once 'lib/files/StudipDirectory.php';

//$root = StudipDirectory::getRootDirectory(md5('foo'));

// create a test folder and file
$folder_entry = $root->mkdir('folder', 0);
$folder_entry->setDescription('test folder');
$folder = $folder_entry->getFile();

$file_entry = $folder->create('file');
$file_entry->setDescription('test file');
$file = $file_entry->getFile();

$stream = $file->open('wb');
fputs($stream, "Hello, world!\n");
fclose($stream);

// create a copy of the file
$folder->copy($file, 'copy');

// print content of the test file
$folder_entry = $root->getEntry('folder');
var_dump($folder_entry);
$folder = $folder_entry->getFile();

$file_entry = $folder->getEntry('file');
var_dump($file_entry);
$file = $file_entry->getFile();

$stream = $file->open('rb');
fpassthru($stream);
fclose($stream);

// print content of the copy
$file_entry = $folder->getEntry('copy');
var_dump($file_entry);
$file = $file_entry->getFile();

$stream = $file->open('rb');
fpassthru($stream);
fclose($stream);

// remove the test folder and file
$entries = $root->listFiles();

foreach ($entries as $entry) {
    $root->unlink($entry->getName());
}
