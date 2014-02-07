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
<input type="hidden" id="sem_class_id" value="<?= Request::int("id") ?>">
<table class="attribute_table">
    <tbody>
        <tr>
            <td width="20%">
                <?= _("Name der Veranstaltungskategorie") ?>
            </td>
            <td width="80%" class="sem_class_name">
                <div>
                    <span class="name"><?= $sem_class['name'] ?></span>
                    <a href="#" class="sem_class_edit" onClick="jQuery(this).closest('td').children().toggle().find('input:visible').focus(); return false;"><?= Assets::img("icons/16/blue/edit", array('class' => "text-bottom")) ?></a>
                </div>
                <div class="name_input" style="display: none;">
                    <input id="sem_class_name" type="text" value="<?= htmlReady($sem_class['name']) ?>" onBlur="jQuery(this).closest('td').children().toggle().find('.name').text(this.value);">
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <?= _('Beschreibungstext f�r die Suche') ?>
            </td>
            <td class="sem_class_name">
                <div>
                    <span class="description"><?= htmlReady($sem_class['description']) ?></span>
                    <a href="#" class="sem_class_edit" onClick="jQuery(this).closest('td').children().toggle().find('input:visible').focus(); return false;">
                        <?= Assets::img('icons/16/blue/edit', array('class' => 'text-bottom')) ?></a>
                </div>
                <div class="description_input" style="display: none;">
                    <input id="sem_class_description" type="text" value="<?= htmlReady($sem_class['description']) ?>" onBlur="jQuery(this).closest('td').children().toggle().find('.description').text(this.value);" style="width: 80%;">
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Veranstaltungstypen") ?>
            </td>
            <td>
                <ul id="sem_type_list">
                    <? foreach ($sem_class->getSemTypes() as $id => $sem_type) : ?>
                    <?= $this->render_partial("admin/sem_classes/_sem_type.php", array('sem_type' => $sem_type)) ?>
                    <? endforeach ?>
                </ul>
                <div class="add">
                    <div style="display: none; margin-left: 37px;">
                        <input type="text" id="new_sem_type" onBlur="if (!this.value) jQuery(this).closest('.add').children().toggle();">
                        <a href="" onClick="STUDIP.admin_sem_class.add_sem_type(); return false;"><?= Assets::img("icons/16/yellow/arr_2up", array('class' => "text-bottom", "title" => _("hinzuf�gen"))) ?></a>
                    </div>
                    <div style="margin-left: 21px;">
                        <a href="#" onClick="jQuery(this).closest('.add').children().toggle(); jQuery('#new_sem_type').focus(); return false;">
                            <?= Assets::img("icons/16/blue/add", array('class' => "text-bottom", "title" => _("Veranstaltungstyp hinzuf�gen"))) ?>
                        </a>
                    </div>
                </div>
            </td>
        </tr>
        <? foreach (array("dozent","tutor","autor") as $role) : ?>
        <tr>
            <td>
                <?= sprintf(_("Titel der %s"), $GLOBALS['DEFAULT_TITLE_FOR_STATUS'][$role][1]) ?>
            </td>
            <td>
                <label>
                    <input type="radio" id="title_<?= $role ?>_isnull" name="title_<?= $role ?>_isnull" value="1"<?= !$sem_class['title_'.$role] && !$sem_class['title_'.$role.'_plural'] ? " checked" : ""?>>
                    <?= sprintf(_("Systemdefault (%s)"), htmlReady(implode("/", $GLOBALS['DEFAULT_TITLE_FOR_STATUS'][$role]))) ?>
                </label>
                <br>
                <input type="radio" name="title_<?= $role ?>_isnull" value="0"<?= $sem_class['title_'.$role] || $sem_class['title_'.$role.'_plural'] ? " checked" : ""?>>
                <input placeholder="<?= htmlReady($GLOBALS['DEFAULT_TITLE_FOR_STATUS'][$role][0]) ?>" title="<?= _("Singular") ?>" type="text" id="title_<?= $role ?>" name="title_<?= $role ?>" value="<?= htmlReady($sem_class['title_'.$role]) ?>">
                <input placeholder="<?= htmlReady($GLOBALS['DEFAULT_TITLE_FOR_STATUS'][$role][1]) ?>" title="<?= _("Plural") ?>" type="text" id="title_<?= $role ?>_plural" name="title_<?= $role ?>_plural" value="<?= htmlReady($sem_class['title_'.$role.'_plural']) ?>">
            </td>
        </tr>
        <? endforeach ?>
        <tr>
            <td colspan="2"><h3><?= _("Voreinstellungen beim Anlegen einer Veranstaltung") ?></h3></td>
        </tr>
        <tr class="sub">
            <td><label for="compact_mode"><?= _("Kompaktmodus f�r den Veranstaltungsassistenten") ?></label></td>
            <td><input type="checkbox" id="compact_mode" value="1"<?= $sem_class['compact_mode'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="turnus_default"><?= _("Turnus") ?></label></td>
            <td>
                <select id="turnus_default">
                    <option value="0"<?= $sem_class['turnus_default'] == 0 ? " selected" : "" ?>><?= _("Regelm��ige Termine") ?></option>
                    <option value="1"<?= $sem_class['turnus_default'] == 1 ? " selected" : "" ?>><?= _("Unregelm��ige Termine") ?></option>
                    <option value="-1"<?= $sem_class['turnus_default'] == -1 ? " selected" : "" ?>><?= _("Keine Termine") ?></option>
                </select>
            </td>
        </tr>
        <tr class="sub">
            <td><label for="default_read_level"><?= _("Lesbar f�r Nutzer") ?></label></td>
            <td>
                <select id="default_read_level">
                    <option value="0"<?= $sem_class['default_read_level'] == 0 ? " selected" : "" ?>><?= _("Unangemeldet an Veranstaltung") ?></option>
                    <option value="1"<?= $sem_class['default_read_level'] == 1 ? " selected" : "" ?>><?= _("Angemeldet an Veranstaltung") ?></option>
                    <option value="2"<?= $sem_class['default_read_level'] == 2 ? " selected" : "" ?>><?= _("Nur mit Passwort") ?></option>
                </select>
            </td>
        </tr>
        <tr class="sub">
            <td><label for="default_write_level"><?= _("Schreibbar f�r Nutzer") ?></label></td>
            <td>
                <select id="default_write_level">
                    <option value="0"<?= $sem_class['default_write_level'] == 0 ? " selected" : "" ?>><?= _("Unangemeldet an Veranstaltung") ?></option>
                    <option value="1"<?= $sem_class['default_write_level'] == 1 ? " selected" : "" ?>><?= _("Angemeldet an Veranstaltung") ?></option>
                    <option value="2"<?= $sem_class['default_write_level'] == 2 ? " selected" : "" ?>><?= _("Nur mit Passwort") ?></option>
                </select>
            </td>
        </tr>
        <tr class="sub">
            <td><label for="admission_prelim_default"><?= _("Anmeldemodus") ?></label></td>
            <td>
                <select id="admission_prelim_default">
                    <option value="0"<?= $sem_class['admission_prelim_default'] == 0 ? " selected" : "" ?>><?= _("direkter Eintrag") ?></option>
                    <option value="1"<?= $sem_class['admission_prelim_default'] == 1 ? " selected" : "" ?>><?= _("vorl�ufiger Eintrag") ?></option>
                </select>
            </td>
        </tr>
        <tr class="sub">
            <td><label for="admission_type_default"><?= _("Teilnahmebeschr�nkung") ?></label></td>
            <td>
                <select id="admission_type_default">
                    <option value="0"<?= $sem_class['admission_type_default'] == 0 ? " selected" : "" ?>><?= _("keine") ?></option>
                    <option value="1"<?= $sem_class['admission_type_default'] == 1 ? " selected" : "" ?>><?= _("nach Anmeldereihenfolge") ?></option>
                    <option value="2"<?= $sem_class['admission_type_default'] == 2 ? " selected" : "" ?>><?= _("per Losverfahren") ?></option>
                    <option value="3"<?= $sem_class['admission_type_default'] == 3 ? " selected" : "" ?>><?= _("gesperrt") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2"><h3><?= _("Forum") ?></h3></td>
        </tr>
        <tr class="sub">
            <td><label for="topic_create_autor"><?= _("Autoren d�rfen Themen anlegen.") ?></label></td>
            <td><input type="checkbox" id="topic_create_autor" value="1"<?= $sem_class['topic_create_autor'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="write_access_nobody"><?= _("Unangemeldete Nutzer (nobody) d�rfen posten.") ?></label></td>
            <td><input type="checkbox" id="write_access_nobody" value="1"<?= $sem_class['write_access_nobody'] ? " checked" : "" ?>></td>
        </tr>
        
        <tr>
            <td colspan="2"><h3><?= _("Anzeige") ?></h3></td>
        </tr>
        <tr class="sub">
            <td><label for="visible"><?= _("Sichtbar") ?></label></td>
            <td><input type="checkbox" id="visible" value="1"<?= $sem_class['visible'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="show_browse"><?= _("Zeige im Veranstaltungsbaum an.") ?></label></td>
            <td><input type="checkbox" id="show_browse" value="1"<?= $sem_class['show_browse'] ? " checked" : "" ?>></td>
        </tr>
        
        <tr>
            <td colspan="2"><h3><?= _("Sonstiges") ?></h3></td>
        </tr>
        <tr class="sub">
            <td><label for="studygroup_mode"><?= _("Studentische Arbeitsgruppe") ?></label></td>
            <td><input type="checkbox" id="studygroup_mode" value="1"<?= $sem_class['studygroup_mode'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="workgroup_mode"><?= _("Neue Nutzer immer als Tutoren eintragen.") ?></label></td>
            <td><input type="checkbox" id="workgroup_mode" value="1"<?= $sem_class['workgroup_mode'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="only_inst_user"><?= _("Nur Nutzer der Einrichtungen sind erlaubt.") ?></label></td>
            <td><input type="checkbox" id="only_inst_user" value="1"<?= $sem_class['only_inst_user'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="bereiche"><?= _("Muss Studienbereiche haben (falls nein, darf es keine haben)") ?></label></td>
            <td><input type="checkbox" id="bereiche" value="1"<?= $sem_class['bereiche'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="course_creation_forbidden"><?= _("Anlegeassistent f�r diesen Typ sperren.") ?></label></td>
            <td><input type="checkbox" id="course_creation_forbidden" value="1"<?= $sem_class['course_creation_forbidden'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="sub">
            <td><label for="create_description"><?= _("Kurzer Beschreibungstext zum Anlagen einer Veranstaltung") ?></label></td>
            <td><textarea id="create_description" maxlength="200" style="width: 100%"><?= htmlReady($sem_class['create_description']) ?></textarea></td>
        </tr>
        <tr class="sub">
            <td><label for="description"><?= _("Allgemeiner Beschreibungstext") ?></label></td>
            <td><textarea id="description" style="width: 100%"><?= htmlReady($sem_class['description']) ?></textarea></td>
        </tr>
        <tr>
            <td>
                <?= _("Inhaltselemente") ?>
            </td>
            <td>
                <? $container = array(
                    'overview' => array('name' => _("�bersicht")),
                    'admin' => array('name' => _("Verwaltung")),
                    'forum' => array('name' => _("Forum")),
                    'participants' => array('name' => _("Teilnehmerseite")),
                    'documents' => array('name' => _("Dateibereich")),
                    'schedule' => array('name' => _("Terminseite")),
                    'literature' => array('name' => _("Literatur�bersicht")),
                    'scm' => array('name' => _("Freie Informationen")),
                    'wiki' => array('name' => _("Wiki")),
                    'resources' => array('name' => _("Ressourcen")),
                    'calendar' => array('name' => _("Kalender")),
                    'elearning_interface' => array('name' => _("Lernmodule"))
                );
                ?>
                <? foreach ($container as $container_id => $container_attributes) : ?>
                <div container="<?= $container_id ?>" class="core_module_slot">
                    <h2><?= htmlReady($container_attributes['name']) ?></h2>
                    <div class="droparea limited<?= $sem_class->getSlotModule($container_id) !== null ? " full" : "" ?>">
                        <? if ($sem_class->getSlotModule($container_id) !== null) : ?>
                            <?= $this->render_partial("admin/sem_classes/content_plugin.php",
                                array(
                                    'plugin' => $modules[$sem_class->getSlotModule($container_id)],
                                    'sem_class' => $sem_class,
                                    'plugin_id' => $sem_class->getSlotModule($container_id),
                                    'activated' => $sem_class['modules'][$sem_class->getSlotModule($container_id)]['activated'],
                                    'disabled' => $sem_class['modules'][$sem_class->getSlotModule($container_id)]['disabled'],
                                    'sticky' => $sem_class['modules'][$sem_class->getSlotModule($container_id)]['sticky']
                                )
                            )?>
                        <? unset($modules[$sem_class->getSlotModule($container_id)]) ?>
                        <? endif ?>
                    </div>
                </div>
                <? endforeach ?>
                <br>
                <div container="plugins" id="plugins">
                    <h2 title="<?= _("Diese Plugins sind standardm��ig bei den Veranstaltungen dieser Klasse aktiviert.") ?>"><?= _("Plugins") ?></h2>
                    <div class="droparea">
                        <? foreach ($modules as $module_name => $module_info) : ?>
                        <? $module_attribute = $sem_class->getModuleMetadata($module_name); ?>
                        <? if (is_numeric($module_info['id'])) : ?>
                            <?= $this->render_partial("admin/sem_classes/content_plugin.php",
                                array(
                                    'plugin' => $module_info,
                                    'sem_class' => $sem_class,
                                    'plugin_id' => $module_name,
                                    'activated' => $sem_class['modules'][$module_name]['activated'],
                                    'disabled' => $sem_class['modules'][$module_name]['disabled'],
                                    'sticky' => $sem_class['modules'][$module_name]['sticky']
                                )
                            )?>
                        <? endif ?>
                        <? endforeach ?>
                    </div>
                </div>
                <hr>
                <div container="deactivated" id="deactivated_modules">
                    <h2 title="<?= _("Diese Module sind standardm��ig nicht aktiviert.") ?>"><?= _("Nichtaktivierte Inhaltselemente") ?></h2>
                    <div class="droparea">
                        <? foreach ($modules as $module_name => $module_info) {
                            $module_id = $module_info['id'];
                            if (!is_numeric($module_id) && !$sem_class['modules'][$module_id]['activated']) {
                                echo $this->render_partial("admin/sem_classes/content_plugin.php",
                                    array(
                                        'plugin' => $module_info,
                                        'sem_class' => $sem_class,
                                        'plugin_id' => $module_id,
                                        'activated' => $sem_class['modules'][$module_id]['activated'],
                                        'disabled' => $sem_class['modules'][$module_id]['disabled'],
                                        'sticky' => $sem_class['modules'][$module_id]['sticky']
                                    )
                                );
                            }
                        } ?>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <div id="message_below"></div>
                <form action="<?= URLHelper::getLink($overview_url) ?>" method="post">
                <?= Studip\Button::create(_("Speichern"), "save", array('onClick' => "STUDIP.admin_sem_class.saveData(); return false;"))?>
                <? if ($sem_class->countSeminars() === 0) : ?>
                    <input type="hidden" name="delete_sem_class" value="<?= Request::int("id") ?>">
                    <?= Studip\Button::create(_("L�schen"), "delete", array('onClick' => "return window.confirm('"._("Wirklich l�schen?")."');"))?>
                <? endif ?>
                </form>
            </td>
        </tr>
    </tbody>
</table>

<div id="sem_type_delete_question_title" style="display: none;"><?= _("Sicherheitsabfrage") ?></div>
<div id="sem_type_delete_question" style="display: none;">
    <p class="info"><?= _("Wirklich den Veranstaltungstyp l�schen?") ?></p>
    <input type="hidden" id="sem_type_for_deletion">
    <?= Studip\LinkButton::create(_("l�schen"), array('onclick' => "STUDIP.admin_sem_class.delete_sem_type(); return false;")) ?>
    <?= Studip\LinkButton::create(_("abbrechen"), array('onclick' => "jQuery(this).closest('#sem_type_delete_question').dialog('close'); return false;")) ?>
</div>



<? 
$infobox = array(
    array(
        'kategorie' => _('Informationen:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/exclaim.png',
                'text' => _("�nderungen an dieser Seite k�nnen alle Veranstaltungen (auch bestehende) in Stud.IP ver�ndern.")
            )
        )
    ),
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/tools.png',
                'text' => _("Weisen Sie Inhaltselemente wie Forum oder Plugins den Inhaltselementslots per Drag & Drop zu.")
            ),
            array(
                'icon' => 'icons/16/black/group.png',
                'text' => _("Nicht �nderbare Inhaltselemente sind welche, die weder Dozent noch Admin selbst aktivieren oder deaktivieren k�nnen. Der von Ihnen hier festgelegte Zustand bleibt immer erhalten. Leere Inhaltselementslots k�nnen ebenfalls nicht vom Dozenten oder Admin bef�llt werden und bleiben leer.")
            )
        )
    )
);
$infobox = array('picture' => "infobox/hoersaal.jpg", 'content' => $infobox);