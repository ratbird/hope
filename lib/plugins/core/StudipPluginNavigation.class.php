<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * StudipPluginNavigation.class.php - menus for plugins
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


class StudipPluginNavigation extends AutoNavigation {

  protected $icon;

  /**
   * @deprecated
   */
  public function __construct($title = '', $url = NULL)
  {
      parent::__construct($title, $url);
  }

  /**
   * Returns the displayname, usually used for creating a link
   *
   * @deprecated
   */
  function getDisplayname(){
    return $this->getTitle();
  }


  /**
   * @deprecated
   */
  function setDisplayname($title){
    $this->setTitle($title);
  }


  /**
   * @deprecated
   */
  function getLink(){
    return $this->getURL();
  }


  /**
   * @deprecated
   */
  function setLink($url){
    $this->setURL($url);
  }


  /**
   * @deprecated
   */
  function getIcon(){
    return $this->icon;
  }


  /**
   * @deprecated
   */
  function setIcon($icon){
    $this->icon = $icon;
  }


  /**
   * @deprecated
   */
  function hasIcon(){
    return isset($this->icon);
  }


  /**
   * @deprecated
   */
  function getSubmenu(){
    return $this->getSubNavigation();
  }


  /**
   * @deprecated
   */
  function addSubmenu(StudipPluginNavigation $subnavigation){
    $this->addSubNavigation(uniqid(), $subnavigation);
  }


  /**
   * @deprecated
   */
  function removeSubmenu(StudipPluginNavigation $subnavigation){
    foreach ($this->getSubNavigation() as $name => $nav) {
      if ($nav === $subnavigation) {
        $this->removeSubNavigation($name);
      }
    }
  }

  /**
   * clears the submenu
   *
   * @deprecated
   */
  function clearSubmenu(){
    foreach ($this->getSubNavigation() as $name => $nav) {
      $this->removeSubNavigation($name);
    }
  }
}
