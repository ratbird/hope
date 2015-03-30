<?php
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

/*
 * Copyright (C) 2010 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

$sidebar = Sidebar::get();
$sidebar->setImage("sidebar/admin-sidebar.png");

$widget = new ActionsWidget();
$widget->addLink(_('Bild ändern'),
    $controller->url_for('course/avatar/update', $course_id),
    'icons/16/blue/edit.png');
$sidebar->addWidget($widget);

if ($adminList) {
    $list = new SelectorWidget();
    $list->setUrl("?#admin_top_links");
    $list->setSelectParameterName("cid");
    foreach ($adminList->adminList as $seminar) {
        $list->addElement(new SelectElement($seminar['Seminar_id'], $seminar['Name']), 'select-' . $seminar['Seminar_id']);
    }
    $list->setSelection($adminList->course_id);
    $sidebar->addWidget($list);
}

$width_column1    = 20;
$width_namecolumn = 60;

$message_types = array('msg' => "success", 'error' => "error", 'info' => "info");
?>

<? if (is_array($flash['msg'])) foreach ($flash['msg'] as $msg) : ?>
    <?= MessageBox::$message_types[$msg[0]]($msg[1]) ?>
<? endforeach ?>

<div style="min-width: 600px">

    <form name="details" method="post" action="<?= $controller->url_for('course/basicdata/set', $course_id) ?>" <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?>>
        <?= CSRFProtection::tokenTag() ?>
        <div style="text-align:center" id="settings" class="table_row_even">

            <h2 id="bd_basicsettings" class="table_row_odd"><?= _("Grundeinstellungen") ?></h2>

            <div>
                <table width="100%">
                    <?php
                    if (!$attributes) {
                        ?>
                        <tr>
                            <td colspan="2"><?= _("Fehlende Datenzeilen") ?></td>
                        </tr>
                    <?php
                    } else {
                        foreach ($attributes as $attribute) : ?>
                            <tr>
                                <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;">
                                    <?= $attribute['title'] ?>
                                    <?= $attribute['must'] ? "<span style=\"color: red; font-size: 1.6em\">*</span>" : "" ?>
                                </td>
                                <td style="text-align: left" width="<?= 100 - $width_column1 ?>%"><?=
                                    $this->render_partial("course/basicdata/_input", array('input' => $attribute))
                                    ?></td>
                            </tr>
                        <? endforeach;
                    }
                    ?>
                    <tr>
                        <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;"><?= _('Erstellt') ?>
                            :
                        </td>
                        <td style="text-align: left"><?= htmlReady($mkstring) ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;"><?= _('Letzte Änderung') ?>
                            :
                        </td>
                        <td style="text-align: left"><?= htmlReady($chstring) ?></td>
                    </tr>
                </table>
            </div>

            <h2 id="bd_inst" class="table_row_odd"><?= _("Einrichtungen") ?></h2>

            <div>
                <table width="100%">
                    <?php
                    if (!$institutional) {
                        ?>
                        <tr>
                            <td colspan="2"><?= _("Fehlende Datenzeilen") ?></td>
                        </tr>
                    <?php
                    } else {
                        foreach ($institutional as $inst) : ?>
                            <tr>
                                <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;">
                                    <?= $inst['title'] ?>
                                    <?= $inst['must'] ? "<span style=\"color: red; font-size: 1.6em\">*</span>" : "" ?>
                                </td>
                                <td style="text-align: left" width="<?= 100 - $width_column1 ?>%"><?
                                    if ($inst['type'] !== "select" || $inst['choices'][$inst['value']]) {
                                        echo $this->render_partial("course/basicdata/_input", array('input' => $inst));
                                    } else {
                                        $name = get_object_name($inst['value'], "inst");
                                        echo htmlReady($name['name']);
                                    }
                                    ?></td>
                            </tr>
                        <? endforeach;
                    }
                    ?>
                </table>
            </div>

            <h2 id="bd_personal" class="table_row_odd"><?= _("Personal") ?></h2>

            <div>

                <style>
                    #leiterinnen_tabelle > tbody > tr > td {
                        vertical-align: top;
                    }
                </style>

                <table class="default" id="leiterinnen_tabelle">
                    <caption>
                        <?= $dozenten_title ?>

                        <? if ($perm_dozent && !$dozent_is_locked) : ?>
                            <span class="actions">
                                <?
                                $mps_dozent = MultiPersonSearch::get("add_member_dozent" . $course_id)
                                    ->setTitle(_('Mehrere DozentInnen hinzufügen'))
                                    ->setSearchObject($dozentUserSearch)
                                    ->setDefaultSelectedUser(array_keys($dozenten))
                                    ->setDataDialogStatus(Request::isXhr())
                                    ->setJSFunctionOnSubmit('jQuery(this).closest(".ui-dialog-content").dialog("close");')
                                    ->setExecuteURL(URLHelper::getLink('dispatch.php/course/basicdata/add_member/' . $course_id));
                                echo $mps_dozent->render();

                                ?>
                                </span>
                        <? endif ?>

                    </caption>
                    <thead>
                    <tr>
                        <th></th>
                        <th><?= _('Name') ?></th>
                        <th><?= _('Funktion') ?></th>
                        <th><?= _('Aktion') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <? if (count($dozenten) > 0) : ?>
                        <? $num = 0;
                        foreach ($dozenten as $dozent) : ?>
                            <tr>
                                <td>
                                    <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => $dozent['username'])) ?>">
                                        <?= Avatar::getAvatar($dozent["user_id"], $dozent['username'])->getImageTag(Avatar::SMALL) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= get_fullname($dozent["user_id"], 'full_rev', true) . " (" . $dozent["username"] . ")" ?>
                                </td>
                                <td>
                                    <? if ($perm_dozent && !$dozent_is_locked) : ?>
                                        <input value="<?= htmlReady($dozent["label"]) ?>" type="text" name="label[<?= htmlReady($dozent["user_id"]) ?>]" title="<?= _("Die Funktion, die die Person in der Veranstaltung erfüllt.") ?>">
                                    <? else : ?>
                                        <?= $dozent["label"] ? htmlReady($dozent["label"]) : '' ?>
                                    <? endif ?>
                                </td>
                                <td style="text-align: right">
                                    <? if ($perm_dozent && !$dozent_is_locked) : ?>
                                        <? if ($num > 0) : ?>
                                            <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?> href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $dozent["user_id"], "dozent") ?>">
                                                <?= Assets::img("icons/16/yellow/arr_2up.png", array('class' => 'middle')) ?></a>
                                        <? endif;
                                        if ($num < count($dozenten) - 1) : ?>
                                            <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?> href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $dozent["user_id"], "dozent") ?>">
                                                <?= Assets::img("icons/16/yellow/arr_2down.png", array('class' => 'middle')) ?></a>
                                        <? endif; ?>
                                        <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?> href="<?= $controller->url_for('course/basicdata/deletedozent', $course_id, $dozent["user_id"]) ?>">
                                            <?= Assets::img("icons/16/blue/trash.png") ?>
                                        </a>
                                    <? endif ?>
                                </td>
                            </tr>
                            <? $num++; endforeach ?>
                    <? else : ?>
                        <tr>
                            <td colspan="6" style="text-align: center"><?= _('Keine DozentInnen eingetragen') ?></td>
                        </tr>
                    <? endif ?>
                    </tbody>
                </table>


                <? if ($deputies_enabled && ($perm_dozent || count($deputies))) : ?>
                    <!-- Stellvertreter -->
                    <table class="default">
                        <caption>
                            <?= $deputy_title ?>
                            <? if ($perm_dozent && !$dozent_is_locked) : ?>
                                <span class="actions">
                                <?
                                $mps_deputy = MultiPersonSearch::get("add_member_deputy" . $course_id)
                                    ->setTitle(_('Mehrere Vertretungen hinzufügen'))
                                    ->setSearchObject($deputySearch)
                                    ->setDefaultSelectedUser(array_keys($deputies))
                                    ->setDataDialogStatus(Request::isXhr())
                                    ->setJSFunctionOnSubmit('jQuery(this).closest(".ui-dialog-content").dialog("close");')
                                    ->setExecuteURL(URLHelper::getLink('dispatch.php/course/basicdata/add_member/' . $course_id . '/deputy'));
                                echo $mps_deputy->render();
                                ?>
                            </span>
                            <? endif ?>
                        </caption>
                        <thead>
                        <tr>
                            <th></th>
                            <th><?= _('Name') ?></th>
                            <th></th>
                            <th><?= _('Aktion') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <? if (count($deputies) > 0) : ?>
                            <? foreach ($deputies as $deputy) : ?>
                                <tr>
                                    <td>
                                        <?= Avatar::getAvatar($deputy["user_id"], $deputy["username"])->getImageTag(Avatar::SMALL) ?>
                                    </td>
                                    <td>
                                        <?= get_fullname($deputy["user_id"], 'full_rev', true) . " (" . $deputy["username"] . ", " . _("Status") . ": " . $deputy['perms'] . ")" ?>
                                    </td>
                                    <td></td>
                                    <td style="text-align: right">
                                        <? if ($perm_dozent && !$dozent_is_locked) : ?>
                                            <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?> href="<?= $controller->url_for('course/basicdata/deletedeputy', $course_id, $deputy["user_id"]) ?>">
                                                <?= Assets::img("icons/16/blue/trash.png") ?>
                                            </a>
                                        <? endif ?>
                                    </td>
                                </tr>
                            <? endforeach ?>
                        <? else : ?>
                            <tr>
                                <td colspan="4" style="text-align: center"><?= _('Keine Vertretung eingetragen') ?></td>
                            </tr>
                        <? endif ?>
                    </table>
                <? endif ?>

                <!-- Tutoren -->
                <table class="default">
                    <caption>
                        <?= $tutor_title ?>
                        <? if ($perm_dozent && !$tutor_is_locked) : ?>
                            <span class="actions">
                        <?
                        $mps_tutor = MultiPersonSearch::get("add_member_tutor" . $course_id)
                            ->setTitle(_('Mehrere TutorInnen hinzufügen'))
                            ->setSearchObject($tutorUserSearch)
                            ->setDefaultSelectedUser(array_keys($tutoren))
                            ->setDataDialogStatus(Request::isXhr())
                            ->setJSFunctionOnSubmit('jQuery(this).closest(".ui-dialog-content").dialog("close");')
                            ->setExecuteURL(URLHelper::getLink('dispatch.php/course/basicdata/add_member/' . $course_id . '/tutor'));
                        echo $mps_tutor->render();
                        ?>
                    </span>
                        <? endif ?>
                    </caption>
                    <thead>
                    <tr>
                        <th></th>
                        <th><?= _('Name') ?></th>
                        <th><?= _('Funktion') ?></th>
                        <th><?= _('Aktion') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <? if (count($tutoren) > 0) : ?>
                        <? $num = 0;
                        foreach ($tutoren as $tutor) : ?>
                            <tr>
                                <td>
                                    <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => $tutor['username'])) ?>">
                                        <?= Avatar::getAvatar($tutor["user_id"], $tutor['username'])->getImageTag(Avatar::SMALL) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= get_fullname($tutor["user_id"], 'full_rev', true) . " (" . $tutor["username"] . ")" ?>
                                </td>
                                <td>
                                    <? if ($perm_dozent && !$tutor_is_locked) : ?>
                                        <input value="<?= htmlReady($tutor["label"]) ?>" type="text" name="label[<?= htmlReady($tutor["user_id"]) ?>]" title="<?= _("Die Funktion, die die Person in der Veranstaltung erfüllt.") ?>">
                                    <? else : ?>
                                        <?= $tutor["label"] ? htmlReady($tutor["label"]) : '' ?>
                                    <? endif ?>
                                </td>
                                <td style="text-align: right">
                                    <? if ($perm_dozent && !$tutor_is_locked) : ?>
                                        <? if ($num > 0) : ?>
                                            <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?>href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $tutor["user_id"], "tutor") ?>">
                                                <?= Assets::img("icons/16/yellow/arr_2up.png", array('class' => 'middle')) ?></a>
                                        <? endif;
                                        if ($num < count($tutoren) - 1) : ?>
                                            <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?> href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $tutor["user_id"], "tutor") ?>">
                                                <?= Assets::img("icons/16/yellow/arr_2down.png", array('class' => 'middle')) ?></a>
                                        <? endif; ?>
                                        <a <?= Request::isXhr() ? 'data-dialog="size=50%"' : '' ?> href="<?= $controller->url_for('course/basicdata/deletetutor', $course_id, $tutor["user_id"]) ?>">
                                            <?= Assets::img("icons/16/blue/trash.png") ?>
                                        </a>
                                    <? endif ?>
                                </td>
                            </tr>
                            <? $num++; endforeach ?>
                    <? else : ?>
                        <tr>
                            <td colspan="4" style="text-align: center"><?= _('Keine TutorInnen eingetragen') ?></td>
                        </tr>
                    <? endif ?>
                    </tbody>
                </table>
                <? if (!$perm_dozent) : ?>
                    <span style="color: #ff0000"><?= _("Die Personendaten können Sie mit Ihrem Status nicht bearbeiten!") ?></span>
                <? endif; ?>

                <script>
                    STUDIP.MultiPersonSearch.init();
                </script>
            </div>


            <h2 id="bd_description" class="table_row_odd"><?= _("Beschreibungen") ?></h2>

            <div>
                <table style="width: 100%">
                    <?php
                    if (!$descriptions) {
                        ?>
                        <tr>
                            <td colspan="2"><?= _("Fehlende Datenzeilen") ?></td>
                        </tr>
                    <?php
                    } else {
                        foreach ($descriptions as $description) : ?>
                            <tr>
                                <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;">
                                    <?= $description['title'] ?>
                                    <?= $description['must'] ? "<span style=\"color: red; font-size: 1.6em\">*</span>" : "" ?>
                                </td>
                                <td style="text-align: left; width: <?= 100 - $width_column1 ?>%"><?=
                                    $this->render_partial("course/basicdata/_input", array('input' => $description))
                                    ?></td>
                            </tr>
                        <? endforeach;
                    }
                    ?>
                </table>
            </div>

        </div>

        <div style="text-align:center; padding: 15px" data-dialog-button>
            <div class="button-group">
                <?= Button::create(_('Übernehmen')) ?>
                <input id="open_variable" type="hidden" name="open" value="<?= $flash['open'] ?>">
            </div>
        </div>
    </form>
    <script>
        jQuery("#settings").accordion({
            <?= $flash['open'] ? "active: '#".$flash['open']."',\n" : "" ?>
            collapsible: true,
            autoHeight: false,
            change: function (event, ui) {
                jQuery('#open_variable').attr('value', ui.newHeader.attr('id'));
            }
        });
        jQuery(function () {
            jQuery("input[name^=label]").autocomplete({
                source: <?=
        json_encode(preg_split("/[\s,;]+/", studip_utf8encode(Config::get()->getValue("PROPOSED_TEACHER_LABELS")), -1, PREG_SPLIT_NO_EMPTY));
        ?>
            });
        });
    </script>
</div>
