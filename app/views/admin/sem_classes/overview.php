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
<style>
    table.selecttable {
        border-collapse: collapse;
        width: 100%;
    }
    table.selecttable > thead > tr > td {
        text-align: center;
        font-weight: bold;
        background-color: #dddddd;
    }
    table.selecttable > thead > tr > td, table.selecttable > tbody > tr > td {
        padding: 5px;
        border: thin solid #aaaaaa;
    }
    table.selecttable > tbody > tr:hover > td {
        cursor: pointer;
        background-color: #eeeeee;
    } 
</style>
<table class="selecttable">
    <thead>
        <tr>
            <td><?= _("ID") ?></td>
            <td><?= _("Seminarklasse") ?></td>
            <td><?= _("Anzahl Veranstaltungen") ?></td>
            <td><?= _("Zuletzt geändert") ?></td>
        </tr>
    </thead>
    <tbody>
        <? foreach ($GLOBALS['SEM_CLASS'] as $id => $sem_class) : ?>
        <tr>
            <td class="id"><?= htmlReady($id) ?></td>
            <td><?= htmlReady($sem_class['name']) ?></td>
            <td><?= $sem_class->countSeminars() ?></td>
            <td><?= date("j.n.Y G:i", $sem_class['chdate']) ?> <?= _("Uhr") ?></td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>
<script>
jQuery(function () {
    jQuery("table.selecttable > tbody > tr").bind("click", function () {
        var id = jQuery.trim(jQuery(this).find("td:first-child").text());
        location.href = STUDIP.URLHelper.getURL("dispatch.php/admin/sem_classes/details", { 'id': id });
    });
})
</script>
<? 
$infobox = array(
    array(
        'kategorie' => _('Informationen:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/exclaim.png',
                'text' => _("ACHTUNG! Änderungen an dieser Seite können alle Veranstaltungen in Stud.IP verändern. Alle Änderungen sind zwar rückgängig machbar, aber bitte ändern sie nur, wenn sie wissen, was Sie tun.")
            )
        )
    )
);
$infobox = array('picture' => "infobox/hoersaal.jpg", 'content' => $infobox);