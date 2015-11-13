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

$dialog_attr = Request::isXhr() ? ' data-dialog="size=50%"' : '';

$message_types = array('msg' => "success", 'error' => "error", 'info' => "info");
?>

<? if (is_array($flash['msg'])) foreach ($flash['msg'] as $msg) : ?>
    <?= MessageBox::$message_types[$msg[0]]($msg[1]) ?>
<? endforeach ?>

<form name="details" method="post" action="<?= $controller->url_for('course/basicdata/set', $course_id) ?>" <?= $dialog_attr ?> class="default collapsable">
    <?= CSRFProtection::tokenTag() ?>
    <input id="open_variable" type="hidden" name="open" value="<?= $flash['open'] ?>">

    <fieldset>
        <legend><?= _('Grundeinstellungen') ?></legend>

<? if (!$attributes): ?>
        <?= MessageBox::info(_('Fehlende Datenzeilen')) ?>
<? else: ?>
    <? foreach ($attributes as $attribute): ?>
        <label>
            <?= htmlReady($attribute['title']) ?>
        <? if ($attribute['must']): ?>
            <em class="required"></em>
        <? endif; ?>
            <?= $this->render_partial("course/basicdata/_input", array('input' => $attribute)) ?>
        </label>
    <? endforeach; ?>
<? endif; ?>

        <label>
            <?= _('Erstellt') ?>
            <br>
            <?= htmlReady($mkstring) ?>
        </label>

        <label>
            <?= _('Letzte �nderung') ?>
            <br>
            <?= htmlReady($chstring) ?>
        </label>
    </fieldset>

    <fieldset class="collapsed">
        <legend><?= _('Einrichtungen') ?></legend>

<? if (!$institutional): ?>
        <?= MessageBox::info(_('Fehlende Datenzeilen')) ?>
<? else: ?>
    <? foreach ($institutional as $inst): ?>
        <label>
            <?= htmlReady($inst['title']) ?>
        <? if ($inst['must']): ?>
            <em class="required"></em>
        <? endif; ?>
        <? if ($inst['type'] === 'select' && !$inst['choices'][$inst['value']]): ?>
            <? $name = get_object_name($inst['value'], 'inst'); ?>
             <?= htmlReady($name['name']) ?>
        <? else: ?>
            <?= $this->render_partial('course/basicdata/_input', array('input' => $inst)) ?>
        <? endif; ?>
        </label>
    <? endforeach; ?>
<? endif; ?>
    </fieldset>

    <fieldset class="collapsed">
        <legend><?= _('Personal') ?></legend>

        <table class="default">
            <caption>
                <?= htmlReady($dozenten_title) ?>

            <? if ($perm_dozent && !$dozent_is_locked): ?>
                <span class="actions">
                    <?= MultiPersonSearch::get('add_member_dozent' . $course_id)
                            ->setTitle(_('Mehrere Lehrende hinzuf�gen'))
                            ->setSearchObject($dozentUserSearch)
                            ->setDefaultSelectedUser(array_keys($dozenten))
                            ->setDataDialogStatus(Request::isXhr())
                            ->setJSFunctionOnSubmit(Request::isXhr() ? 'jQuery(this).closest(".ui-dialog-content").dialog("close");' : false)
                            ->setExecuteURL($controller->url_for('course/basicdata/add_member/' . $course_id))
                            ->addQuickfilter(sprintf(_('%s der Einrichtung'), get_title_for_status('dozent', 2)), $membersOfInstitute)
                            ->render() ?>
                    </span>
            <? endif; ?>
            </caption>
            <thead>
                <tr>
                    <th></th>
                    <th><?= _('Name') ?></th>
                    <th><?= _('Funktion') ?></th>
                    <th class="actions"><?= _('Aktion') ?></th>
                </tr>
            </thead>
            <tbody>
        <? if (count($dozenten) === 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center">
                        <?= _('Keine DozentInnen eingetragen') ?>
                    </td>
                </tr>
        <? else: ?>
            <? foreach (array_values($dozenten) as $num => $dozent) : ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $dozent['username']) ?>">
                            <?= Avatar::getAvatar($dozent['user_id'], $dozent['username'])->getImageTag(Avatar::SMALL) ?>
                        </a>
                    </td>
                    <td>
                        <?= get_fullname($dozent['user_id'], 'full_rev', true) . ' (' . $dozent['username'] . ')' ?>
                    </td>
                    <td>
                    <? if ($perm_dozent && !$dozent_is_locked): ?>
                        <input value="<?= htmlReady($dozent['label']) ?>" type="text" name="label[<?= htmlReady($dozent['user_id']) ?>]" title="<?= _('Die Funktion, die die Person in der Veranstaltung erf�llt.') ?>">
                    <? else : ?>
                        <?= htmlReady($dozent['label']) ?>
                    <? endif ?>
                    </td>
                    <td class="actions">
                <? if ($perm_dozent && !$dozent_is_locked): ?>
                    <? if ($num > 0) : ?>
                        <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $dozent['user_id'], 'dozent') ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/yellow/arr_2up.svg', array('class' => 'middle')) ?>
                        </a>
                    <? endif; ?>
                    <? if ($num < count($dozenten) - 1): ?>
                        <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $dozent['user_id'], 'dozent') ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/yellow/arr_2down.svg', array('class' => 'middle')) ?>
                        </a>
                    <? endif; ?>
                        <a href="<?= $controller->url_for('course/basicdata/deletedozent', $course_id, $dozent['user_id']) ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/blue/trash.svg') ?>
                        </a>
                <? endif; ?>
                    </td>
                </tr>
            <? endforeach; ?>
        <? endif; ?>
            </tbody>
        </table>

    <!-- Stellvertreter -->
    <? if ($deputies_enabled && ($perm_dozent || count($deputies) > 0)): ?>
        <table class="default">
            <caption>
                <?= htmlReady($deputy_title) ?>
            <? if ($perm_dozent && !$dozent_is_locked) : ?>
                <span class="actions">
                    <?= MultiPersonSearch::get('add_member_deputy' . $course_id)
                            ->setTitle(_('Mehrere Vertretungen hinzuf�gen'))
                            ->setSearchObject($deputySearch)
                            ->setDefaultSelectedUser(array_keys($deputies))
                            ->setDataDialogStatus(Request::isXhr())
                            ->setJSFunctionOnSubmit(Request::isXhr() ? 'jQuery(this).closest(".ui-dialog-content").dialog("close");' : false)
                            ->setExecuteURL($controller->url_for('course/basicdata/add_member/' . $course_id . '/deputy'))
                            ->render() ?>
                </span>
            <? endif; ?>
            </caption>
            <thead>
                <tr>
                    <th></th>
                    <th><?= _('Name') ?></th>
                    <th></th>
                    <th class="actions"><?= _('Aktion') ?></th>
                </tr>
            </thead>
            <tbody>
        <? if (count($deputies) === 0): ?>
            <tr>
                <td colspan="4" style="text-align: center">
                    <?= _('Keine Vertretung eingetragen') ?>
                </td>
            </tr>
        <? else: ?>
            <? foreach ($deputies as $deputy) : ?>
                <tr>
                    <td>
                        <?= Avatar::getAvatar($deputy['user_id'], $deputy['username'])->getImageTag(Avatar::SMALL) ?>
                    </td>
                    <td>
                        <?= get_fullname($deputy['user_id'], 'full_rev', true) ?>
                        (<?= $deputy['username']  ?>,
                         <?= _('Status') ?>:
                         <?= $deputy['perms'] ?>)
                    </td>
                    <td></td>
                    <td class="actions">
                    <? if ($perm_dozent && !$dozent_is_locked): ?>
                        <a href="<?= $controller->url_for('course/basicdata/deletedeputy', $course_id, $deputy["user_id"]) ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/blue/trash.svg') ?>
                        </a>
                    <? endif; ?>
                    </td>
                </tr>
            <? endforeach ?>
        <? endif ?>
            </tbody>
        </table>
    <? endif ?>

        <!-- Tutoren -->
        <table class="default">
            <caption>
                <?= htmlReady($tutor_title) ?>
            <? if ($perm_dozent && !$tutor_is_locked): ?>
                <span class="actions">
                <?= MultiPersonSearch::get('add_member_tutor' . $course_id)
                        ->setTitle(_('Mehrere TutorInnen hinzuf�gen'))
                        ->setSearchObject($tutorUserSearch)
                        ->setDefaultSelectedUser(array_keys($tutoren))
                        ->setDataDialogStatus(Request::isXhr())
                        ->setJSFunctionOnSubmit(Request::isXhr() ? 'jQuery(this).closest(".ui-dialog-content").dialog("close");' : false)
                        ->setExecuteURL($controller->url_for('course/basicdata/add_member/' . $course_id . '/tutor'))
                        ->render() ?>
                </span>
            <? endif; ?>
            </caption>
            <thead>
                <tr>
                    <th></th>
                    <th><?= _('Name') ?></th>
                    <th><?= _('Funktion') ?></th>
                    <th class="actions"><?= _('Aktion') ?></th>
                </tr>
            </thead>
            <tbody>
        <? if (count($tutoren) === 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center">
                        <?= _('Keine TutorInnen eingetragen') ?>
                    </td>
                </tr>
        <? else: ?>
            <? foreach ($tutoren as $num => $tutor): ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $tutor['username']) ?>">
                            <?= Avatar::getAvatar($tutor['user_id'], $tutor['username'])->getImageTag(Avatar::SMALL) ?>
                        </a>
                    </td>
                    <td>
                        <?= get_fullname($tutor['user_id'], 'full_rev', true) . ' (' . $tutor['username'] . ')' ?>
                    </td>
                    <td>
                    <? if ($perm_dozent && !$tutor_is_locked): ?>
                        <input value="<?= htmlReady($tutor['label']) ?>" type="text" name="label[<?= htmlReady($tutor['user_id']) ?>]" title="<?= _('Die Funktion, die die Person in der Veranstaltung erf�llt.') ?>">
                    <? else: ?>
                        <?= htmlReady($tutor['label']) ?>
                    <? endif; ?>
                    </td>
                    <td class="actions">
                <? if ($perm_dozent && !$tutor_is_locked): ?>
                    <? if ($num > 0) : ?>
                        <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $tutor['user_id'], 'tutor') ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/yellow/arr_2up.svg', array('class' => 'middle')) ?>
                        </a>
                    <? endif; ?>
                    <? if ($num < count($tutoren) - 1) : ?>
                        <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $tutor['user_id'], 'tutor') ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/yellow/arr_2down.svg', array('class' => 'middle')) ?>
                        </a>
                    <? endif; ?>
                        <a href="<?= $controller->url_for('course/basicdata/deletetutor', $course_id, $tutor['user_id']) ?>" <?= $dialog_attr ?>>
                            <?= Assets::img('icons/blue/trash.svg') ?>
                        </a>
                <? endif; ?>
                    </td>
                </tr>
            <? endforeach; ?>
        <? endif; ?>
            </tbody>
        </table>
    </fieldset>

    <fieldset class="collapsed">
        <legend><?= _('Beschreibungen') ?></legend>

<? if (!$descriptions): ?>
        <?= MessageBox::info(_('Fehlende Datenzeilen')) ?>
<? else: ?>
    <? foreach ($descriptions as $description): ?>
        <label>
            <?= $description['title'] ?>
        <? if ($description['must']): ?>
            <em class="required"></em>
        <? endif; ?>
            <?= $this->render_partial('course/basicdata/_input', array('input' => $description)) ?>
        </label>
    <? endforeach; ?>
<? endif; ?>
    </fieldset>

    <footer data-dialog-button>
        <?= Button::create(_('�bernehmen')) ?>
    </footer>
</form>

<script>
jQuery(function ($) {
    $('input[name^=label]').autocomplete({
        source: <?=
json_encode(preg_split('/[\s,;]+/', studip_utf8encode(Config::get()->PROPOSED_TEACHER_LABELS), -1, PREG_SPLIT_NO_EMPTY));
?>
    });
});
</script>
