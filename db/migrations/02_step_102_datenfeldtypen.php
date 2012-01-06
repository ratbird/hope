<?php

/*
 * 02_step_102_datenfeldtypen.php - migration for StEP00102
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


class Step102Datenfeldtypen extends DBMigration {

  function description() {
    return 'modify db schema for StEP00102 to provide typed datafields';
  }

  function up() {
    $this->db->query("ALTER TABLE `datafields` CHANGE `object_type` `object_type` enum('sem','inst','user','userinstrole','usersemdata','roleinstdata') default NULL;");
    $this->db->query("ALTER TABLE `datafields` CHANGE `view_perms` `view_perms` enum('all','user','autor','tutor','dozent','admin','root') default NULL;");
    $this->db->query("ALTER TABLE `datafields` ADD `type` enum('bool','textline','textarea','selectbox','date','time','email','url','phone', 'radio', 'combo') NOT NULL default 'textline';");
    $this->db->query("ALTER TABLE `datafields` ADD `typeparam` text NOT NULL;");
    $this->db->query("ALTER TABLE `datafields_entries` ADD `sec_range_id` varchar(32) NOT NULL default '';");
    $this->db->query("ALTER TABLE `datafields_entries` DROP PRIMARY KEY , ADD PRIMARY KEY ( `datafield_id` , `range_id` , `sec_range_id` );");
    $this->db->query("ALTER TABLE `datafields_entries` ADD INDEX `range_id` ( `range_id` , `datafield_id` );");
    $this->db->query("ALTER TABLE `datafields_entries` ADD INDEX `datafield_id_2` (`datafield_id`,`sec_range_id`);");
    $this->db->query("ALTER TABLE `statusgruppe_user` ADD `visible` tinyint(4) NOT NULL default '1';");
    $this->db->query("ALTER TABLE `statusgruppe_user` ADD `inherit` tinyint(4) NOT NULL default '1';");
    $this->db->query("CREATE TABLE `aux_lock_rules` (`lock_id` varchar( 32 ) NOT NULL default '', `name` varchar( 255 ) NOT NULL default '', `description` text NOT NULL , `attributes` text NOT NULL , `sorting` text NOT NULL , PRIMARY KEY ( `lock_id` )) ENGINE=MyISAM;");
    $this->db->query("ALTER TABLE `seminare` ADD `aux_lock_rule` varchar(32) default NULL;");

    $this->migrate_datafields();
  }

  function down() {
    $this->db->query("ALTER TABLE `seminare` DROP `aux_lock_rule`;");
    $this->db->query("DROP TABLE `aux_lock_rules`;");
    $this->db->query("ALTER TABLE `statusgruppe_user` DROP `inherit`;");
    $this->db->query("ALTER TABLE `statusgruppe_user` DROP `visible`;");
    $this->db->query("ALTER TABLE `datafields_entries` DROP INDEX `datafield_id_2`;");
    $this->db->query("ALTER TABLE `datafields_entries` DROP INDEX `range_id`;");
    $this->db->query("ALTER TABLE `datafields_entries` DROP PRIMARY KEY , ADD PRIMARY KEY ( `datafield_id` , `range_id` );");
    $this->db->query("ALTER TABLE `datafields_entries` DROP `sec_range_id`;");
    $this->db->query("ALTER TABLE `datafields` DROP `type`;");
    $this->db->query("ALTER TABLE `datafields` DROP `typeparam`;");
    $this->db->query("ALTER TABLE `datafields` CHANGE `view_perms` `view_perms` enum('all','user','autor','tutor','dozent','admin','root') NOT NULL default 'all';");
    $this->db->query("ALTER TABLE `datafields` CHANGE `object_type` `object_type` ENUM('sem','inst','user') default NULL;");
  }

  function migrate_datafields() {

    # only require, if exists
    if (!file_exists($GLOBALS['STUDIP_BASE_PATH']
                     . '/config/config_datafields.inc.php')) {
      return;
    }
    require_once 'config/config_datafields.inc.php';

    if (!isset($DATAFIELDS)) {
      return;
    }

    require_once 'lib/classes/DataFieldStructure.class.php';

    $ids = array_keys(DataFieldStructure::getDataFieldStructures());

    foreach ($DATAFIELDS as $id => $field) {

      if (!in_array($id, $ids)) {
        $this->write('Not existent: ' . $id);
        continue;
      }

      $struct = new DataFieldStructure(array('datafield_id' => $id));

      $mapping = array('text'     => 'textline',
                        'textarea' => 'textarea',
                        'checkbox' => 'bool',
                        'select'   => 'selectbox',
                        'combo'    => 'combo',
                        'radio'    => 'radio',
                        'date'     => 'date');

      if (!isset($mapping[$field['type']])) {
        # TODO (mlunzena) what to do?
      }

      $type = $mapping[$field['type']];
      $type_param = '';

      if (in_array($type, array('selectbox', 'combo', 'radio'))) {
        $type_param = $this->get_type_param($field['options']);
      }

      $struct->setType($type);
      $struct->setTypeParam($type_param);
      $struct->store();
    }
  }

  function get_type_param($options) {
    $new_options = array();
    foreach ((array)$options as $key => $value) {
      if (is_string($value)) {
        $new_options[] = $value;
      }
      else {
        $new_options[] = $value['name'];
      }
    }
    return join("\n", $new_options);
  }
}
