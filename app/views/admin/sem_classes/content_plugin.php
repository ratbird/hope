<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
?>
<div class="plugin<?= ($plugin['enabled'] ? "" : " deactivated").(is_numeric($plugin['id']) ? "" : " core").($sticky ? " sticky" : "") ?>" id="plugin_<?= $plugin_id ?>" <?= $plugin['enabled'] ? "" : ' title="'._("Plugin ist momentan global deaktiviert.").'"' ?>>
    <h2><?= $plugin['name'] ?></h2>
    <div>
        <select name="sticky" title="<?= _("Änderbar meint, der Dozent der Veranstaltung darf das Modul nach Wunsch auch aktivieren oder deaktivieren.") ?>">
            <option value="nonsticky"<?= !$sticky ? " selected" : "" ?>><?= _("änderbar") ?></option>
            <option value="sticky"<?= $sticky ? " selected" : "" ?>><?= _("nicht änderbar") ?></option>
        </select>
        <span class="lock"><?= Assets::img("icons/16/red/lock-locked.png", array('class' => "text-bottom")) ?></span>
    </div>
</div>