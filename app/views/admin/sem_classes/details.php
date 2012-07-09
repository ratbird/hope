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
                <?= _("Name der Seminarklasse") ?>
            </td>
            <td width="80%">
                <div>
                    <span class="name"><?= $sem_class['name'] ?></span>
                    <a href="#" onClick="jQuery(this).closest('td').children().toggle(); return false;"><?= Assets::img("icons/16/blue/edit", array('class' => "text-bottom")) ?></a>
                </div>
                <div class="name_input" style="display: none;">
                    <input id="sem_class_name" type="text" value="<?= htmlReady($sem_class['name']) ?>" onBlur="jQuery(this).closest('td').children().toggle().find('.name').text(this.value);">
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Seminartypen") ?>
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
                        <a href="" onClick="STUDIP.admin_sem_class.add_sem_type(); return false;"><?= Assets::img("icons/16/yellow/arr_2up", array('class' => "text-bottom", "title" => _("hinzufügen"))) ?></a>
                    </div>
                    <div style="margin-left: 21px;">
                        <a href="#" onClick="jQuery(this).closest('.add').children().toggle(); jQuery('#new_sem_type').focus(); return false;">
                            <?= Assets::img("icons/16/blue/plus", array('class' => "text-bottom", "title" => _("Seminartyp hinzufügen"))) ?>
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
            <td>
                <?= _("Inhaltselemente") ?>
            </td>
            <td>
                <? $container = array(
                    'overview' => array('name' => _("Übersicht")),
                    'admin' => array('name' => _("Verwaltung")),
                    'forum' => array('name' => _("Forum")),
                    'documents' => array('name' => _("Dateibereich")),
                    'participants' => array('name' => _("Teilnehmerseite")),
                    'schedule' => array('name' => _("Terminseite")),
                    'literature' => array('name' => _("Literaturübersicht")),
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
                                    'sticky' => $sem_class['modules'][$sem_class->getSlotModule($container_id)]['sticky']
                                )
                            )?>
                        <? unset($modules[$sem_class->getSlotModule($container_id)]) ?>
                        <? endif ?>
                    </div>
                </div>
                <? endforeach ?>
                <br>
                <div container="plugins" id="activated_plugins">
                    <h2 title="<?= _("Diese Plugins sind standardmäßig bei den Veranstaltungen dieser Klasse aktiviert.") ?>"><?= _("Aktivierte Plugins") ?></h2>
                    <div class="droparea">
                        <? foreach ($modules as $module_name => $module_info) : ?>
                        <? $module_attribute = $sem_class->getModuleMetadata($module_name); ?>
                        <? if ($module_attribute['activated']) : ?>
                            <?= $this->render_partial("admin/sem_classes/content_plugin.php", 
                                array(
                                    'plugin' => $module_info,
                                    'sem_class' => $sem_class,
                                    'plugin_id' => $module_name,
                                    'sticky' => $sem_class['modules'][$module_name]['sticky']
                                )
                            )?>
                        <? endif ?>
                        <? endforeach ?>
                    </div>
                </div>
                <div container="plugins" id="nonactivated_plugins">
                    <h2 title="<?= _("Diese Plugins sind standardmäßig bei den Veranstaltungen dieser Klasse nicht aktiviert, können vom Dozenten aber aktiviert werden.") ?>"><?= _("Nicht aktivierte Plugins") ?></h2>
                    <div class="droparea">
                        <? foreach ($modules as $module_name => $module_info) : ?>
                        <? $module_attribute = $sem_class->getModuleMetadata($module_name); ?>
                        <? if (!$module_attribute['activated'] && !$module_attribute['sticky'] && is_numeric($module_info['id'])) : ?>
                            <?= $this->render_partial("admin/sem_classes/content_plugin.php", 
                                array(
                                    'plugin' => $module_info,
                                    'sem_class' => $sem_class,
                                    'plugin_id' => $module_name,
                                    'sticky' => $sem_class['modules'][$module_name]['sticky']
                                )
                            )?>
                        <? endif ?>
                        <? endforeach ?>
                    </div>
                </div>
                <hr>
                <div container="deactivated" id="deactivated_modules">
                    <h2 title="<?= _("Diese Module sind standardmäßig nicht aktiviert.") ?>"><?= _("Nichtaktivierte Module") ?></h2>
                    <div class="droparea">
                        <? foreach ($modules as $module_name => $module_info) {
                            $module_id = $module_info['id'];
                            if (!is_numeric($module_id) && !$sem_class['modules'][$module_id]['activated']) {
                                echo $this->render_partial("admin/sem_classes/content_plugin.php", 
                                    array(
                                        'plugin' => $module_info,
                                        'sem_class' => $sem_class,
                                        'plugin_id' => $module_id,
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
            <td><label for="chat"><?= _("Chat ist erlaubt") ?></label></td>
            <td><input type="checkbox" id="chat" value="1"<?= $sem_class['chat'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="compact_mode"><?= _("Kompaktmodus für den Veranstaltungsassistenten") ?></label></td>
            <td><input type="checkbox" id="compact_mode" value="1"<?= $sem_class['compact_mode'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="workgroup_mode"><?= _("Studentische Arbeitsgruppe") ?></label></td>
            <td><input type="checkbox" id="workgroup_mode" value="1"<?= $sem_class['workgroup_mode'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="only_inst_user"><?= _("Nur Nutzer der Einrichtungen sind erlaubt.") ?></label></td>
            <td><input type="checkbox" id="only_inst_user" value="1"<?= $sem_class['only_inst_user'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="turnus_default"><?= _("Turnus") ?></label></td>
            <td>
                <select id="turnus_default">
                    <option value="0"<?= $sem_class['turnus_default'] == 0 ? " selected" : "" ?>><?= _("Regelmäßige Termine") ?></option>
                    <option value="1"<?= $sem_class['turnus_default'] == 1 ? " selected" : "" ?>><?= _("Unregelmäßige Termine") ?></option>
                    <option value="2"<?= $sem_class['turnus_default'] == -1 ? " selected" : "" ?>><?= _("Keine Termine") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="default_read_level"><?= _("Lesbar für Nutzer") ?></label></td>
            <td>
                <select id="default_read_level">
                    <option value="0"<?= $sem_class['default_read_level'] == 0 ? " selected" : "" ?>><?= _("Unangemeldet an Veranstaltung") ?></option>
                    <option value="1"<?= $sem_class['default_read_level'] == 1 ? " selected" : "" ?>><?= _("Angemeldet an Veranstaltung") ?></option>
                    <option value="2"<?= $sem_class['default_read_level'] == 2 ? " selected" : "" ?>><?= _("Nur mit Passwort") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="default_write_level"><?= _("Schreibbar für Nutzer") ?></label></td>
            <td>
                <select id="default_write_level">
                    <option value="0"<?= $sem_class['default_write_level'] == 0 ? " selected" : "" ?>><?= _("Unangemeldet an Veranstaltung") ?></option>
                    <option value="1"<?= $sem_class['default_write_level'] == 1 ? " selected" : "" ?>><?= _("Angemeldet an Veranstaltung") ?></option>
                    <option value="2"<?= $sem_class['default_write_level'] == 2 ? " selected" : "" ?>><?= _("Nur mit Passwort") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="bereiche"><?= _("Muss Studienbereiche haben (falls nein, darf es keine haben)") ?></label></td>
            <td><input type="checkbox" id="bereiche" value="1"<?= $sem_class['bereiche'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="show_browse"><?= _("Zeige im Veranstaltungsbaum an.") ?></label></td>
            <td><input type="checkbox" id="show_browse" value="1"<?= $sem_class['show_browse'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="write_access_nobody"><?= _("Unangemeldete Nutzer (nobody) dürfen posten.") ?></label></td>
            <td><input type="checkbox" id="write_access_nobody" value="1"<?= $sem_class['write_access_nobody'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="topic_create_autor"><?= _("Autoren dürfen Themen anlegen.") ?></label></td>
            <td><input type="checkbox" id="topic_create_autor" value="1"<?= $sem_class['topic_create_autor'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="visible"><?= _("Sichtbar") ?></label></td>
            <td><input type="checkbox" id="visible" value="1"<?= $sem_class['visible'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td><label for="course_creation_forbidden"><?= _("Veranstaltungen dürfen nicht manuell erstellt werden.") ?></label></td>
            <td><input type="checkbox" id="course_creation_forbidden" value="1"<?= $sem_class['course_creation_forbidden'] ? " checked" : "" ?>></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <div id="message_below"></div>
                <?= Studip\Button::create(_("Speichern"), "save", array('onClick' => "STUDIP.admin_sem_class.saveData();"))?>
                <? if ($sem_class->countSeminars() === 0) : ?>
                <form action="<?= URLHelper::getLink($overview_url) ?>" method="post">
                    <input type="hidden" name="delete_sem_class" value="<?= Request::int("id") ?>">
                    <?= Studip\Button::create(_("Löschen"), "delete", array('onClick' => "return window.confirm('"._("Wirklich löschen?")."');"))?>
                </form>
                <? endif ?>
            </td>
        </tr>
    </tbody>
</table>

<div id="sem_type_delete_question_title" style="display: none;"><?= _("Sicherheitsabfrage") ?></div>
<div id="sem_type_delete_question" style="display: none;">
    <p class="info"><?= _("Wirklich den Seminartyp löschen?") ?></p>
    <input type="hidden" id="sem_type_for_deletion">
    <?= Studip\LinkButton::create(_("löschen"), array('onclick' => "STUDIP.admin_sem_class.delete_sem_type(); return false;")) ?>
    <?= Studip\LinkButton::create(_("abbrechen"), array('onclick' => "jQuery(this).closest('#sem_type_delete_question').dialog('close'); return false;")) ?>
</div>

<script>
STUDIP.admin_sem_class = {
    'make_sortable': function () {
        var after_update = function (event, ui) {
            if (jQuery(ui.item).is(".core") && jQuery(this).is("#activated_plugins .droparea, #nonactivated_plugins .droparea")) {
                jQuery('#deactivated_modules .droparea').append(jQuery(ui.item).clone().fadeIn(1500));
                jQuery(ui.item).remove();
            } 
            if (jQuery(ui.item).is(".plugin:not(.core)") && jQuery(this).is("#deactivated_modules .droparea")) {
                jQuery('#nonactivated_plugins .droparea').append(jQuery(ui.item).clone().fadeIn(1500));
                jQuery(ui.item).remove();
            }
            
            jQuery(".droparea.limited").each(function (index, droparea) {
                if (jQuery(this).children().length === 0) {
                    jQuery(this).removeClass("full");
                } else {
                    jQuery(this).addClass("full");
                }
            });
            STUDIP.admin_sem_class.make_sortable();
        };
        jQuery(".droparea").sortable({
            'connectWith': ".droparea:not(.full)",
            'revert': 200,
            'update': after_update
        });
        jQuery("#activated_plugins .droparea, #nonactivated_plugins .droparea").sortable({
            'connectWith': ".droparea:not(.full, #deactivated_modules .droparea)",
            'revert': 200,
            'update': after_update
        });
        jQuery("#deactivated_modules .droparea").sortable({
            'connectWith': ".droparea:not(.full, #activated_plugins .droparea, #nonactivated_plugins .droparea,)",
            'revert': 200,
            'update': after_update
        });
    },
    'saveData': function () {
        var core_module_slots = {};
        jQuery.each(['overview','forum','admin','documents','participants','schedule','scm','wiki','calendar','elearning_interface'], function (index, element) {
            var module = jQuery("div[container=" + element + "] .droparea > div.plugin").attr("id");
            if (module) {
                module = module.substr(module.indexOf("_") + 1);
            }
            core_module_slots[element] = module ? module : "0";
        });
        var modules = {};
        jQuery("div.plugin").each(function () {
            var activated = jQuery(this).is("#activated_plugins div.plugin, .core_module_slot div.plugin");
            var sticky = (jQuery(this).find("select").val() === "sticky" || jQuery(this).is("#deactivated_modules div.plugin"));
            if (sticky || activated) {
                var module_name = jQuery(this).attr("id");
                if (module_name) {
                    module_name = module_name.substr(module_name.indexOf("_") + 1);
                }
                modules[module_name] = {
                    'activated': activated ? 1 : 0,
                    'sticky': sticky ? 1 : 0
                };
            }
        });
        jQuery("#message_below").html("");
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/admin/sem_classes/save",
            'data': {
                'sem_class_id': jQuery("#sem_class_id").val(),
                'sem_class_name': jQuery("#sem_class_name").val(),
                'title_dozent': !jQuery("#title_dozent_isnull").is(":checked") ? jQuery("#title_dozent").val() : "",
                'title_dozent_plural': !jQuery("#title_dozent_isnull").is(":checked") ? jQuery("#title_dozent_plural").val() : "",
                'title_tutor': !jQuery("#title_tutor_isnull").is(":checked") ? jQuery("#title_tutor").val() : "",
                'title_tutor_plural': !jQuery("#title_tutor_isnull").is(":checked") ? jQuery("#title_tutor_plural").val() : "",
                'title_autor': !jQuery("#title_autor_isnull").is(":checked") ? jQuery("#title_autor").val() : "",
                'title_autor_plural': !jQuery("#title_autor_isnull").is(":checked") ? jQuery("#title_autor_plural").val() : "",
                'core_module_slots': core_module_slots,
                'modules': modules,
                'compact_mode': jQuery("#compact_mode").is(":checked") ? 1 : 0,
                'workgroup_mode': jQuery("#workgroup_mode").is(":checked") ? 1 : 0,
                'only_inst_user': jQuery("#only_inst_user").is(":checked") ? 1 : 0,
                'turnus_default': jQuery("#turnus_default").val(),
                'default_read_level': jQuery("#default_read_level").val(),
                'default_write_level': jQuery("#default_write_level").val(),
                'bereiche': jQuery("#bereiche").is(":checked") ? 1 : 0,
                'show_browse': jQuery("#show_browse").is(":checked") ? 1 : 0,
                'write_access_nobody': jQuery("#write_access_nobody").is(":checked") ? 1 : 0,
                'topic_create_autor': jQuery("#topic_create_autor").is(":checked") ? 1 : 0,
                'visible': jQuery("#visible").is(":checked") ? 1 : 0,
                'course_creation_forbidden': jQuery("#course_creation_forbidden").is(":checked") ? 1 : 0,
                'chat': jQuery("#chat").is(":checked") ? 1 : 0
            },
            'type': "POST",
            'dataType': "json",
            success: function(data) {
                jQuery("#message_below").html(data.html);
            }
        });
    },
    'delete_sem_type_question': function () {
        var sem_type = jQuery(this).closest("li").attr('id');
        sem_type = sem_type.substr(sem_type.lastIndexOf("_") + 1);
        jQuery("#sem_type_for_deletion").val(sem_type);
        jQuery("#sem_type_delete_question").dialog({
            'title': jQuery("#sem_type_delete_question_title").text()
        });
    },
    'add_sem_type': function () {
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/admin/sem_classes/add_sem_type",
            'type': "post",
            'data': {
                'sem_class': jQuery("#sem_class_id").val(),
                'name': jQuery("#new_sem_type").val()
            },
            'success': function (ret) {
                jQuery("#sem_type_list").append(jQuery(ret));
                jQuery("#new_sem_type").val('').closest("li").children().toggle();
            },
            'error': function () {
                jQuery("#new_sem_type").val('').closest("li").children().toggle();
            }
        });
    },
    'delete_sem_type': function () {
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/admin/sem_classes/delete_sem_type",
            'data': {
                'sem_type': jQuery("#sem_type_for_deletion").val()
            },
            'type': "post",
            'success': function () {
                jQuery("#sem_type_" + jQuery("#sem_type_for_deletion").val()).remove();
                jQuery("#sem_type_delete_question").dialog("close");
            }
        });
    },
    'rename_sem_type': function () {
        jQuery(this).closest('span.name_container').children().toggle();
        var name = this.value;
        var old_name = jQuery(this).closest(".name_container").find(".name_html");
        var sem_type = jQuery(this).closest("li").attr('id');
        sem_type = sem_type.substr(sem_type.lastIndexOf("_") + 1);
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/admin/sem_classes/rename_sem_type",
            'data': {
                'sem_type': sem_type,
                'name': name
            },
            'type': "post",
            'success': function () {
                old_name.text(name);
            }
        });
    }
}
jQuery(".sem_type_delete").live("click", STUDIP.admin_sem_class.delete_sem_type_question);
jQuery(".name_input > input").live("blur", STUDIP.admin_sem_class.rename_sem_type);
jQuery(STUDIP.admin_sem_class.make_sortable);
jQuery("div[container] > div.droparea > div.plugin select[name=sticky]").change(function () {
    if (this.value === "sticky") {
        jQuery(this).closest("div.plugin").addClass("sticky");
    } else {
        jQuery(this).closest("div.plugin").removeClass("sticky");
    }
});
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
    ),
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/tools.png',
                'text' => _("Weisen Sie Inhaltselemente wie Forum oder Plugins den Modulslots per Drag & Drop zu.")
            ),
            array(
                'icon' => 'icons/16/black/group.png',
                'text' => _("Nicht änderbare Inhaltselemente sind Module, die weder Dozent noch Admin selbst aktivieren oder deaktivieren können. Der von Ihnen hier festgelegte Zustand bleibt immer erhalten. Leere Modulslots können ebenfalls nicht vom Dozenten oder Admin befüllt werden und bleiben leer.")
            )
        )
    )
);
$infobox = array('picture' => "infobox/hoersaal.jpg", 'content' => $infobox);