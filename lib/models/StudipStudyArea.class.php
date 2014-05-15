<?php
/**
 * Studienbereich... TODO
 *
 * Copyright (C) 2008 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @package     studip
 *
 * @author    mlunzena
 * @author    André Noack <noack@data-quest.de>
 * @copyright (c) Authors
 *
 * @property string sem_tree_id database column
 * @property string id alias column for sem_tree_id
 * @property string parent_id database column
 * @property string priority database column
 * @property string info database column
 * @property string name database column
 * @property string studip_object_id database column
 * @property string type database column
 * @property SimpleORMapCollection _children has_many StudipStudyArea
 * @property Institute institute belongs_to Institute
 * @property StudipStudyArea _parent belongs_to StudipStudyArea
 * @property SimpleORMapCollection courses has_and_belongs_to_many Course
 */

class StudipStudyArea extends SimpleORMap
{

    /**
     * This constant represents the key of the root area.
     */
    const ROOT = 'root';


    protected static function configure($config = array())
    {
        $config['db_table'] = 'sem_tree';
        $config['default_values']['info'] = '';
        $config['default_values']['type'] = 0;

        $config['has_many']['_children'] = array(
            'class_name' => 'StudipStudyArea',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func' => 'findByParent',
            'on_delete' => 'delete',
            'on_store' => 'store',
        );
        $config['has_and_belongs_to_many']['courses'] = array(
            'class_name' => 'Course',
            'thru_table' => 'seminar_sem_tree',
        );
        $config['belongs_to']['institute'] = array(
            'class_name' => 'Institute',
            'foreign_key' => 'studip_object_id',
        );
        $config['belongs_to']['_parent'] = array(
            'class_name' => 'StudipStudyArea',
            'foreign_key' => 'parent_id',
        );
        parent::configure($config);
    }

    /**
     * Returns the children of the study area with the specified ID.
     */
    static function findByParent($parent_id)
    {
        return self::findByparent_id($parent_id, "ORDER BY priority,name");
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
            $result = parent::find($id);
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
     * Get the comment of this study area.
     */
    function getInfo() {
        return $this->content['info'];
    }


    /**
     * Set the comment of this study area.
     */
    function setInfo($info) {
        $this->content['info'] = (string) $info;
        return $this;
    }


    /**
     * Get the display name of this study area.
     */
    function getName() {
        if ($this->studip_object_id) {
            return $this->institute->name;
        }
        return $this->content['name'];
    }

    /**
     * Set the display name of this study area.
     */
    function setName($name) {
        $this->content['name'] = (string) $name;
        return $this;
    }


    /**
     * Get the parent ID of this study area.
     */
    function getParentId() {
        return $this->content['parent_id'];
    }


    /**
     * Get the parent.
     */
    function getParent() {
        $result = NULL;
        if ($this->getID() !== self::ROOT) {
            $result = $this->_parent;
        }
        return $result;
    }


    /**
     * Set the parent of this study area.
     */
    function setParentId($parent_id) {
        $this->content['parent_id'] = (string) $parent_id;
        $this->resetRelation('parent');
        return $this;
    }

    /**
     * get the type of this study area.
     */
    function getType(){
        return $this->content['type'];
    }

    /**
     * set the type of this study area.
     */
    function setType($type){
        $this->content['type'] = (int) $type;
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

        $path = array();

        $area = $this;
        while ($area) {
            if ($area->getName() != '') {
                $path[] = $area->getName();
            }
            if ($area->getParentId() == self::ROOT) {
                break;
            }
            $area = $area->getParent();
        }

        $path = array_reverse($path);

        return isset($separator)
        ? join($separator, $path)
        : $path;
    }


    /**
     * Get the priority of this study area.
     */
    function getPriority() {
        return $this->content['priority'];
    }


    /**
     * Set the priority of this study area.
     */
    function setPriority($priority) {
        $this->content['priority'] = (int) $priority;
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
        $this->resetRelation('institute');
        return $this;
    }


    /**
     * Returns the children of this study area.
     */
    function getChildren() {
        return $this->_children;
    }

    /**
     * Returns1 TRUE if the area has children.
     */
    function hasChildren() {
        return sizeof($this->_children) > 0;
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
        if ($this->isModule() && $plugin = PluginEngine::getPlugin('StudienmodulManagement')) {
            return $plugin->getModuleDescription($this->getID(), $semester_id);
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
        if ($this->isModule() && $plugin = PluginEngine::getPlugin('StudienmodulManagement')) {
            $nav = $plugin->getModuleInfoNavigation($this->getID(), $semester_id);
            if ($nav->isVisible(true)) {
                $ret = '<a class="module-info" href="' . URLHelper::getLink($nav->getURL()) . '">';
                $ret .= '<img ';
                foreach ($nav->getImage() as $key => $value) $ret .= sprintf('%s="%s" ', $key, htmlReady($value));
                $ret .= '>';
                $ret .= '<span>' .htmlready($nav->getTitle()) . '</span>';
                $ret .= '</a>';
            }
        }
        return $ret;
    }


    /**
     * Get an associative array of all study areas of a course. The array
     * contains StudipStudyArea instances
     *
     * @param  id         the course's ID
     *
     * @return SimpleCollection      a SimpleORMapCollection of that course's study areas
     */
    static function getStudyAreasForCourse($id) {
        $course = Course::find($id);
        return $course ? $course->study_areas : new SimpleCollection();
    }


    /**
     * Returns the not really existing root study area.
     *
     * @return object     the root study area object
     */
    static function getRootArea() {
        $root = new StudipStudyArea();
        $root->setID(self::ROOT);
        $root->setName($GLOBALS['UNI_NAME_CLEAN']);
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

        $query =
        "sem_tree_id IN (
        SELECT sem_tree_id FROM sem_tree st1 WHERE name LIKE :searchTerm
        UNION DISTINCT
        SELECT sem_tree_id FROM Institute i
        INNER JOIN sem_tree st2 ON st2.studip_object_id = i.Institut_id
        WHERE i.Name LIKE :searchTerm )
        ORDER BY priority";
        return self::findBySql($query, array('searchTerm' => "%$searchTerm%"));
    }
}
