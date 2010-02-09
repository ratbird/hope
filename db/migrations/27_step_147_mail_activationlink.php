<?php

/*
 * 02_step_102_datenfeldtypen.php - migration for StEP00102
 *
 * Copyright (C) 2008 - Florian Ludwig (dino@phidev.org)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


class Step147MailActivationLink extends DBMigration {

  function description() {
    return 'modify db schema for StEP00147 to provide validation key attribute';
  }

  function up() {
    $this->db->query("ALTER TABLE `auth_user_md5` ADD `validation_key` VARCHAR(10) NOT NULL AFTER `Email`;");
  }

  function down() {
    $this->db->query("ALTER TABLE `auth_user_md5` DROP `validation_key`;");
  }
}
