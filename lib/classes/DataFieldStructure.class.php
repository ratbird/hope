<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * DataFieldStructure.class.php - <short-description>
 *
 * Copyright (C) 2005 - Martin Gieseking  <mgieseki@uos.de>
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * <ClassDescription>
 *
 * @package     studip
 * @subpackage  datafields
 *
 * @author    mgieseki, mlunzena
 * @copyright (c) Authors
 */
class DataFieldStructure {


  /**
   * <FieldDescription>
   *
   * @access private
   * @var <type>
   */
  var $data;


  /**
   * <FieldDescription>
   *
   * @access private
   * @var <type>
   */
  var $numEntries;


  function DataFieldStructure($data='') {
    $this->data = $data ? $data : array();
    if (!$this->data['datafield_id'])
      $this->data['datafield_id'] = !$this->data['datafield_id'] ? md5(uniqid('fdhdgg')) : $id;  # we always need a valid unique ID
    /*
     * Performance-Tuning: mdiedric
     * numEntries wird zur Zeit nicht direkt verwendet
     */
     # $this->numEntries = $this->numberOfUsedEntries();
  }


  function getID()               {return $this->data['datafield_id'];}
  function getName()             {return $this->data['name'];}
  function getType()             {return $this->data['type'];}
  function getTypeParam()        {return $this->data['typeparam'];}
  function getObjectClass()      {return $this->data['object_class'];}
  function getObjectType()       {return $this->data['object_type'];}
  function getPriority()         {return $this->data['priority'];}
  function getEditPerms()        {return $this->data['edit_perms'];}
  function getViewPerms()        {return $this->data['view_perms'];}

  function getCachedNumEntries() {
    if (is_null($this->numEntries)) {
      $this->numEntries = $this->numberOfUsedEntries();
    }
    return $this->numEntries;
  }

  function setID($v)               {$this->data['datafield_id'] = $v;}
  function setName($v)             {$this->data['name'] = $v;}
  function setTypeParam($v)        {$this->data['typeparam'] = $v;}
  function setObjectClass($v)      {$this->data['object_class'] = $v;}
  function setObjectType($v)       {$this->data['object_type'] = $v;}
  function setPriority($v)         {$this->data['priority'] = $v;}
  function setEditPerms($v)        {$this->data['edit_perms'] = $v;}
  function setViewPerms($v)        {$this->data['view_perms'] = $v;}

  function setType($v) {
    $this->data['type'] = $v;
    if (!in_array($v, array('selectbox', 'radio', 'combo')))
      $this->setTypeParam('');
  }


  /**
   * Returns an HTML fragment used for editing select boxes
   *
   * @param  string  the name of this datafield
   *
   * @return string  the HTML fragment
   */
  function getHTMLEditor($name) {
    $ret = '';
    if (in_array($this->getType(), array('selectbox', 'radio', 'combo'))) {
      $content = $this->getTypeParam();
      $ret = "<textarea name=\"$name\" cols=\"20\" rows=\"8\" wrap=\"off\">$content</textarea>";
    }
    return $ret;
  }


  /**
   * Returns the count of entries for this datafield.
   *
   * @return integer  the count of entries for this datafield
   */
  function numberOfUsedEntries() {
    $db = new DB_Seminar;
    $id = $this->data['datafield_id'];
    $query = "SELECT count(range_id) AS count FROM datafields_entries WHERE datafield_id = '$id'";
    $db->query($query);
    $db->next_record();
    return $this->numEntries = $db->f('count');
  }


  /**
   * Return the mask for the given permission
   *
   * @static
   *
   * @param  string   the name of the permission
   *
   * @return integer  the mask for the permission
   */
  function permMask($perm) {
    static $masks = array("user" => 1, "autor" => 2, "tutor" => 4, "dozent" => 8, "admin" => 16, "root" => 32, "self" => 64,);
    return $masks[$perm];
  }


  /**
   * liefert String zu gegebener user_class-Maske
   *
   * @static
   *
   * @param  integer  the user class mask
   *
   * @return string   a string consisting of a comma separated list of
   *                  permissions
   */
  function getReadableUserClass($class) {
    static $classes = array(1 => "user", 2 => "autor", 4 => "tutor", 8 => "dozent", 16 => "admin", 32 => "root", 64 => "self");
    foreach ($classes as $key=>$val) {
      if ($class & $key) {
        if ($ret)
          $ret .= ", ";
        $ret .= $val;
      }
    }
    return $ret;
  }


  /**
   * Returns a collection of structures of datafields filtered by objectType,
   * objectClass and unassigned objectClasses.
   *
   * @static
   *
   * @param  type     <description>
   * @param  type     <description>
   * @param  boolean  <description>
   *
   * @return array    <description>
   */
  function getDataFieldStructures($objectType=NULL, $objectClass='', $includeNullClass=false) {

    $ret = array();

    $db = new DB_Seminar();

    $expr = array();

    if (isset($objectType))
      $expr[] = "object_type='$objectType'";

    if ($objectClass)
      $expr[] = "(object_class=$objectClass" .
                ($includeNullClass ? ' OR object_class IS NULL)' : ')');


    $expr = empty($expr) ? '' : 'WHERE ' . join(' AND ', $expr);

    $query = "SELECT * FROM datafields $expr ".
             "ORDER BY object_class, priority, name";
    $db->query($query);

    while ($db->next_record())
      $ret[$db->f("datafield_id")] = new DataFieldStructure($db->Record);

    return $ret;
  }


  # load structure information from database
  function load() {
    if ($this->getID()) {
      $db = new DB_Seminar;
      $query = sprintf("SELECT * FROM datafields WHERE datafield_id='%s'", $this->getID());
      $db->query($query);
      $db->next_record();
      $this->data = $db->Record;
    }
  }


  function store() {
    $data = &$this->data;
    $db = new DB_Seminar;
    $query = "SELECT * FROM datafields WHERE datafield_id = '$data[datafield_id]'";

    $db->query($query);
    $db->next_record();

    if (!$data['name'])
      $data['name'] = $db->f("name");

    if (!$data['type'])
      $data['type'] = $db->f('type');

    if (in_array($data['type'], array('selectbox', 'radio', 'combo')))
      $data['typeparam'] = $data['typeparam'] ? $data['typeparam'] : $db->f('typeparam');
    else
      $data['typeparam'] = '';

    if (!$data['object_type'])
      $data['object_type'] = $db->f("object_type");

    if (!$data['object_class'])
      $data['object_class'] = $db->f('object_class') ? $db->f('object_class') : 'NULL';

    if (!$data['edit_perms'])
      $data['edit_perms'] = $db->f("edit_perms");

    if (!$data['view_perms'])
      $data['view_perms'] = $db->f('view_perms');

    if (!$data['priority'])
      $data['priority'] = $db->f("priority");

    $db->queryf("REPLACE INTO datafields ".
                "SET datafield_id='%s', name='%s', object_type='%s', ".
                "object_class=%s, edit_perms='%s', priority='%s', ".
                "view_perms='%s', type='%s', typeparam='%s'",
                $data['datafield_id'], $data['name'],       $data['object_type'],
                $data['object_class'], $data['edit_perms'], $data['priority'],
                $data['view_perms'],   $data['type'],       $data['typeparam']);

    return $db->affected_rows() > 0;
  }


  function remove($id='') {
    if (!$id)
      $id = $this->getID();
    $db = new DB_Seminar;
    $query = "DELETE FROM datafields WHERE datafield_id = '$id'";
    $db->query($query);
    return $db->affected_rows() > 0;
  }


  function accessAllowed($perm, $watcher = "", $user = "") {

    # everybody may see the information
    if ($this->getViewPerms() == "all")
      return TRUE;

    # permission ist high enough
    if ($perm->have_perm($this->getViewPerms()))
      return TRUE;

    # user may see his own data
    if ($watcher != "" && $user != "" && $user == $watcher)
      return TRUE;

    # nothing matched...
    return FALSE;
  }


  function editAllowed($userPerms) {
    if (!$this->getEditPerms())
      $this->load();
    return DataFieldStructure::permMask($userPerms)
           >=
           DataFieldStructure::permMask($this->getEditPerms());
  }
}
