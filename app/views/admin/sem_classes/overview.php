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
<table class="default">
    <thead>
        <tr>
            <th><?= _("ID") ?></th>
            <th><?= _("Veranstaltungskategorie") ?></th>
            <th><?= _("Anzahl Veranstaltungstypen") ?></th>
            <th><?= _("Anzahl Veranstaltungen") ?></th>
            <th><?= _("Zuletzt ge�ndert") ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($GLOBALS['SEM_CLASS'] as $id => $sem_class) : ?>
        <tr>
            <td class="id"><?= htmlReady($id) ?></td>
            <td><?= htmlReady($sem_class['name']) ?></td>
            <td><?= count($sem_class->getSemTypes()) ?></td>
            <td><?= $sem_class->countSeminars() ?></td>
            <td><?= date("j.n.Y G:i", $sem_class['chdate']) ?> <?= _("Uhr") ?></td>
            <td class="actions">
                <a href="<?= URLHelper::getLink("dispatch.php/admin/sem_classes/details", array('id' => $id)) ?>" title="<?= _("Editieren dieser Veranstaltungskategorie") ?>">
                <?= Icon::create('edit', 'clickable')->asImg(['class' => "text-bottom"]) ?>
                </a>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>

<div id="add_sem_class_window_title" style="display: none;"><?= _("Neue Veranstaltungskategorie") ?></div>
<div id="add_sem_class_window" style="display: none;">
    <form action="?" method="post">
    <table>
        <tbody>
            <tr>
                <td><label for="add_name"><?= _("Name") ?></label></td>
                <td><input type="text" name="add_name" id="add_name" required></td>
            </tr>
            <tr>
                <td><label for="add_like"><?= _("Attribute kopieren von Veranstaltungskategorie") ?></label></td>
                <td>
                    <select name="add_like" id="add_like">
                        <option value=""><?= _("keine") ?></option>
                        <? foreach ($GLOBALS['SEM_CLASS'] as $id => $sem_class) : ?>
                        <option value="<?= $id ?>"><?= htmlReady($sem_class['name']) ?></option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><?= Studip\Button::create(_("Erstellen")) ?></td>
            </tr>
        </tbody>
    </table>
    </form>
</div>

<script>
STUDIP.sem_classes = {
    'add': function () {
        jQuery("#add_sem_class_window").dialog({
            'modal': true,
            'title': jQuery("#add_sem_class_window_title").text(),
            'show': "fade",
            'hide': "fade",
            'width': "400px"
        });
    }
};
</script>
<?
$sidebar = Sidebar::Get();
$sidebar->setTitle(PageLayout::getTitle());
$sidebar->setImage('sidebar/plugin-sidebar.png');
$links = new ActionsWidget();
$links->addLink(_('Neue Kategorie anlegen'), '#', Icon::create('add', 'clickable'), array('onClick' => 'STUDIP.sem_classes.add(); return false;'));
$sidebar->addWidget($links);

