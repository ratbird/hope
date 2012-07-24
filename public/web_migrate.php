<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * web_migrate.php - web-based migrator for Stud.IP
 * Copyright (C) 2007  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require '../lib/bootstrap.php';

unregister_globals();
require_once 'lib/migrations/db_migration.php';
require_once 'lib/migrations/db_schema_version.php';
require_once 'lib/migrations/migrator.php';
require_once 'lib/visual.inc.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth",
                "perm" => "Seminar_Perm", "user" => "Seminar_User"));

$auth->login_if(!$perm->have_perm("root"));
$perm->check("root");

if (empty($_SESSION['_language'])) {
    $_SESSION['_language'] = get_accepted_languages();
}

$_language_path = init_i18n($_SESSION['_language']);

include 'lib/include/html_head.inc.php';

$path = $GLOBALS['STUDIP_BASE_PATH'].'/db/migrations';
$verbose = false;
$target = NULL;

if (Request::int('target')) {
    $target = (int) Request::int('target');
}

$version = new DBSchemaVersion('studip');
$migrator = new Migrator($path, $version, $verbose);

if (Request::submitted('start')) {
    set_time_limit(0);
    $migrator->migrate_to($target);
}

$current = $version->get();
$migrations = $migrator->relevant_migrations($target);

$template = $template_factory->open('web_migrate');
$template->set_attribute('current_page', _('Datenbank-Migration'));
$template->set_attribute('current', $current);
$template->set_attribute('target', $target);
$template->set_attribute('migrations', $migrations);

echo $template->render();

include 'lib/include/html_end.inc.php';
?>
