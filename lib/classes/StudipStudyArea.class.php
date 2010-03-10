<?php
# Lifter007: TODO
# Lifter003: TODO

/*
 * Copyright (C) 2008 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Studienbereich... TODO
 *
 * @package     studip
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class StudipStudyArea {


  /**
   * This constant represents the key of the root area.
   */
  const ROOT = 'root';


  /**
   * die ID; DB: sem_tree::sem_tree_id
   *
   * @access private
   * @var string
   */
  private $id;


  /**
   * Kommentartext zu einem sem_tree-Eintrag; DB: sem_tree::info
   *
   * @access private
   * @var string
   */
  private $info;


  /**
   * der Name; DB: sem_tree::name
   *
   * @access private
   * @var string
   */
  private $name;


  /**
   * die ID des Vaters; DB: sem_tree::parent_id
   *
   * @access private
   * @var string
   */
  private $parent_id;


  /**
   * der Pfad entlang des sem_trees zu diesem Studienbereich;
   *
   * @access private
   * @var array
   */
  private $path;


  /**
   * die Reihenfolge der Geschwister; DB: sem_tree::priority
   *
   * @access private
   * @var int
   */
  private $priority;


  /**
   * Verweis auf die Institute; DB: sem_tree::studip_object_id
   *
   * @access private
   * @var string
   */
  private $studip_object_id;
  
  /**
   * DB: sem_tree::type
   *
   * @access private
   * @var integer
   */
  private $type;
  
  /**
   * Kinder dieses Studienbereichs; wird zur Memoization verwendet
   * Standardwert ist NULL. Nach Aufruf von #getChildren wird es zu einem Array.
   *
   * @access private
   * @var mixed
   */
  private $children;


  /**
   * Constructor.
   *
   * @return void
   */
  function __construct() {
  }


  /**
   * Returns the study area with the specified ID.
   */
  static function find($id) {

    $result = NULL;

    if ($id === self::ROOT) {
      $result = self::getRootArea();
    }

    else {
      # TODO (mlunzena) das sollte in eine eigene methode
      $db = DBManager::get();
      $stmt = $db->prepare("SELECT * FROM sem_tree WHERE sem_tree_id = ?");
      $stmt->execute(array((string) $id));

      if (!($row = $stmt->fetch())) {
        throw new Exception(_('Ungültige ID') . ': ' . $id);
      }

      # TODO (mlunzena) das sollte in eine eigene methode
      if ($row['studip_object_id'] !== NULL) {
        $stmt = $db->prepare("SELECT Name FROM Institute WHERE Institut_id = ?");
        $stmt->execute(array($row['studip_object_id']));
        $row['name'] = $stmt->fetchColumn();
      }

      $result = new StudipStudyArea();
      $result->restore($row);
    }

    return $result;
  }

  /**
   * Get a string representation of this study area.
   */
  function __toString() {
    return $this->id;
  }


  /**
   * Get the ID of this study area.
   */
  function getID() {
    return $this->id;
  }


  /**
   * Set the ID of this study area.
   */
  function setID($id) {
    $this->id = (string) $id;
    return $this;
  }


  /**
   * Get the comment of this study area.
   */
  function getInfo() {
    return $this->info;
  }


  /**
   * Set the comment of this study area.
   */
  function setInfo($info) {
    $this->info = (string) $info;
    return $this;
  }


  /**
   * Get the display name of this study area.
   */
  function getName() {
    return $this->name;
  }


  /**
   * Set the display name of this study area.
   */
  function setName($name) {
    $this->name = (string) $name;
    return $this;
  }


  /**
   * Get the parent ID of this study area.
   */
  function getParentId() {
    return $this->parent_id;
  }


  /**
   * Get the parent.
   */
  function getParent() {
    $result = NULL;
    if ($this->getID() !== self::ROOT) {
      $result = StudipStudyArea::find($this->parent_id);
    }
    return $result;
  }


  /**
   * Set the parent of this study area.
   */
  function setParentId($parent_id) {
    $this->parent_id = (string) $parent_id;
    return $this;
  }
  
  /**
   * get the type of this study area.
   */
  function getType(){
    return $this->type;
  }
  
  /**
   * set the type of this study area.
   */
  function setType($type){
    $this->type = (int) $type;
    return $this;
  }
  
  /**
   * get the name of the type of this study area, see $SEM_TREE_TYPES in config.inc.php
   *
   * @return string
   */
  function getTypeName(){
    if(isset($GLOBALS['SEM_TREE_TYPES'][$this->getType()]['name'])){
        return $GLOBALS['SEM_TREE_TYPES'][$this->getType()]['name'];
    } else {
        return '';
    }
  }
  
  /**
   * is this study area editable, see $SEM_TREE_TYPES in config.inc.php
   *
   * @return bool
   */
  function isEditable(){
    if(isset($GLOBALS['SEM_TREE_TYPES'][$this->getType()]['editable'])){
        return (bool)$GLOBALS['SEM_TREE_TYPES'][$this->getType()]['editable'];
    } else {
        return false;
    }
  }
  
  /**
   * Get the path along the sem_tree to this study area.
   *
   * @param  string     optional; TODO
   *
   * @return mixed      TODO
   */
  function getPath($separator = NULL) {

    # cache retrieval of path
    if (is_null($this->path)) {

      $path = array();

      $area = $this;
      while (TRUE) {
        if ($area->getName() != '') {
          $path[] = $area->getName();
        }
        if ($area->getParentId() == self::ROOT) {
          break;
        }
        $area = $area->getParent();
      }

      if ($area->getName() == '') {
        $stmt = DBManager::get()->prepare('SELECT Name FROM Institute '.
                                          'WHERE Institut_id = ?');
        $stmt->execute(array($area->getStudipObjectId()));
        $row = $stmt->fetch();
        $path[] = $row['Name'];
      }

      $this->path = array_reverse($path);
    }

    return isset($separator)
      ? join($separator, $this->path)
      : $this->path;
  }


  /**
   * Get the priority of this study area.
   */
  function getPriority() {
    return $this->priority;
  }


  /**
   * Set the priority of this study area.
   */
  function setPriority($priority) {
    $this->priority = (int) $priority;
    return $this;
  }


  /**
   * Get the studip_object_id of this study area.
   */
  function getStudipObjectId() {
    return $this->studip_object_id;
  }


  /**
   * Set the studip_object_id of this study area.
   */
  function setStudipObjectId($id) {
    $this->studip_object_id = (string) $id;
    return $this;
  }


  /**
   * Returns the children of this study area.
   */
  function getChildren() {

    if ($this->children === NULL) {
      $stmt = DBManager::get()->prepare('SELECT sem_tree_id FROM sem_tree '.
                                        'WHERE parent_id = ? ORDER BY priority');
      $stmt->execute(array($this->getID()));
      $children = array();
      foreach ($stmt->fetchAll(PDO::FETCH_COLUMN, 0) as $id) {
        $children[$id] = StudipStudyArea::find($id);
      }
      $this->children = $children;
    }

    return $this->children;
  }

  /**
   * Returns TRUE if the area has children.
   */
  function hasChildren() {
    return sizeof($this->getChildren()) > 0;
  }


  /**
   * Returns TRUE if this area is the root.
   */
  function isRoot() {
    return $this->getId() === self::ROOT;
  }


  /**
   * Returns TRUE if this area can be select.
   */
  function isAssignable() {
    $cfg = Config::GetInstance();
    $leaves_too = $cfg->getValue('SEM_TREE_ALLOW_BRANCH_ASSIGN');
    if ($leaves_too) {
      return !$this->isRoot();
    } else {
      return !$this->isRoot() && !$this->hasChildren();
    }
  }


  /**
   * Store changes to the study area to the database.
   */
  function store() {
    $db = DBManager::get();

    $args = array($this->info, $this->name, $this->parent_id, $this->priority,
                  $this->studip_object_id, $this->type, $this->id);

    if ($this->id !== NULL) {
      $stmt = $db->prepare(
        "UPDATE sem_tree SET info = ?, name = ?, parent_id = ?, ".
                            "priority = ?, studip_object_id = ?, type = ? ".
        "WHERE sem_tree_id = ?");
      $result = $stmt->execute($args);
    }

    else {
      $stmt = $db->prepare(
        "INSERT INTO sem_tree ".
        "(info, name, parent_id, priority, studip_object_id, type, sem_tree_id) ".
        "VALUES (?, ?, ?, ?, ?, ?, ?)");
      $result = $stmt->execute($args);
    }

    # TODO (mlunzena) soll man irgendwas mit result anfangen?
  }


  /**
   * Delete this user domain from the database.
   */
  function delete() {
    $result = DBManager::get()->exec("DELETE FROM sem_tree ".
      "WHERE sem_tree_id= '".$this->id."'");

    # TODO (mlunzena) soll man irgendwas mit result anfangen?
  }


  function restore($fields) {
    $this->id = $fields['sem_tree_id'];
    $this->setInfo($fields['info']);
    $this->setName($fields['name']);
    $this->setParentId($fields['parent_id']);
    $this->setPriority($fields['priority']);
    $this->setStudipObjectId($fields['studip_object_id']);
    $this->setType($fields['type']);

    return $this;
  }
  
  /**
   * is this study area considered a study modul?, see $SEM_TREE_TYPES in config.inc.php
   *
   * @return bool
   */
  function isModule(){
    return isset($GLOBALS['SEM_TREE_TYPES'][$this->getType()]['is_module']);
  }
  
  /**
   * returns the modul description if this study area is a module 
   * and if there is a compatible plugin available
   *
   * @param string $semester_id
   * @return string
   */
  function getModuleDescription($semester_id = ''){
    if ($this->isModule()) {
      return PluginEngine::getPlugin('StudienmodulManagement')
             ->getModuleDescription($this->getID(), $semester_id);
    } else {
      return '';
    }
  }
  
  /**
   * returns a HTML snippet for the info icon if this study area is a module 
   * and if there is a compatible plugin available
   *
   * @param string $semester_id
   * @return string
   */
  function getModuleInfoHTML($semester_id = ''){
    $ret = '';
    if ($this->isModule()) {
      $nav = PluginEngine::getPlugin('StudienmodulManagement')->getModuleInfoNavigation($this->getID(), $semester_id);
      if($icon = $nav->getImage()){
        $ret = '<img ';
        foreach ($icon as $key => $value) $ret .= sprintf('%s="%s" ', $key, htmlReady($value));
        $ret .= '>';
      }
    }
    return $ret;
  }
  

  /**
   * Get an associative array of all study areas of a course. The array
   * contains StudipStudyArea instances and its keys correspond to the ID of
   * each entry.
   *
   * @param  id         the course's ID
   *
   * @return array      an array of that courses study areas
   */
  static function getStudyAreasForCourse($id) {
    $db = DBManager::get();
    $domains = array();
    $stmt = $db->prepare("SELECT * FROM seminar_sem_tree s ".
                         "LEFT JOIN sem_tree t USING (sem_tree_id) ".
                         "WHERE s.seminar_id =  ?");
    $stmt->execute(array($id));

    $result = array();
    foreach ($stmt->fetchAll() as $row)  {
      $area = new StudipStudyArea();
      $area->restore($row);
      $result[$area->getID()] = $area;
    }
    return $result;
  }


  /**
   * Returns the not really existing root study area.
   *
   * @return object     the root study area object
   */
  static function getRootArea() {
    $root = new StudipStudyArea();
    $root->setID(self::ROOT)->setName($GLOBALS['UNI_NAME_CLEAN']);
    return $root;
  }


  /**
   * Search for study areas whose name matches the given search term.
   *
   * @param  string     the seach term
   *
   * @return type       <description>
   */
  static function search($searchTerm) {

    $results = array();

    $stmt = DBManager::get()->prepare(
      "(SELECT sem_tree_id, priority FROM sem_tree WHERE name LIKE :searchTerm ) ".
      "UNION ".
      "(SELECT sem_tree_id, priority FROM sem_tree st ".
      "LEFT JOIN Institute i ON (st.studip_object_id = i.Institut_id) ".
      "WHERE i.Name LIKE :searchTerm ) ".
      "ORDER BY priority");
    $stmt->execute(array('searchTerm' => "%$searchTerm%"));

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN, 0) as $id) {
      $results[] = StudipStudyArea::find($id);
    }

    return $results;
  }
}
