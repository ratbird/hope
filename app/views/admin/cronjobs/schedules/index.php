<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('admin/cronjobs/schedules/filter') ?>"
      method="post" class="cronjob-filters">
<table class="default">
    <colgroup>
        <col width="33.3%">
        <col width="33.4%">
        <col width="33.3%">
    </colgroup>
    <thead>
        <tr>
            <th>
                <?= _('Darstellung einschränken') ?>
            </th>
            <th colspan="2">
            <? if ($total_filtered != $total): ?>
                <?= sprintf(_('Passend: %u von %u Cronjobs'), $total_filtered, $total) ?>
            <? endif; ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <label for="type"><?= _('Typ') ?></label>
                <select name="filter[type]" id="type">
                    <option value=""><?= _('Alle Cronjobs anzeigen') ?></option>
                    <option value="once" <? if ($filter['type'] === 'once') echo 'selected'; ?>>
                        <?= _('Nur einmalige Cronjobs anzeigen') ?>
                    </option>
                    <option value="periodic" <? if ($filter['type'] === 'periodic') echo 'selected'; ?>>
                        <?= _('Nur regelmässige Cronjobs anzeigen') ?>
                    </option>
                </select>
            </td>
            <td>
                <label for="task_id"><?= _('Aufgabe') ?></label>
                <select name="filter[task_id]" id="task_id">
                    <option value=""><?= _('Alle Cronjobs anzeigen') ?></option>
                <? foreach ($tasks as $task): ?>
                    <option value="<?= $task->task_id ?>" <? if ($filter['task_id'] === $task->task_id) echo 'selected'; ?>>
                        <?= htmlReady($task->name) ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
            <td>
                <label for="status"><?= _('Status') ?></label>
                <select name="filter[status]" id="status">
                    <option value=""><?= _('Alle Cronjobs anzeigen') ?></option>
                    <option value="active" <? if ($filter['status'] === 'active') echo 'selected'; ?>>
                        <?= _('Nur aktive Cronjobs anzeigen') ?>
                    </option>
                    <option value="inactive" <? if ($filter['status'] === 'inactive') echo 'selected'; ?>>
                        <?= _('Nur deaktivierte Cronjobs anzeigen') ?>
                    </option>
                </select>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">
                <noscript>
                    <?= Button::create(_('Filtern')) ?>
                </noscript>

            <? if (!empty($filter)): ?>
                <?= LinkButton::createCancel(_('Zurücksetzen'),
                                             $controller->url_for('admin/cronjobs/schedules/filter'),
                                             array('title' => _('Filter zurücksetzen'))) ?>
            <? endif; ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>

<form action="<?= $controller->url_for('admin/cronjobs/schedules/bulk', $page) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

<table class="default cronjobs">
    <colgroup>
        <col width="20px">
        <col>
        <col width="40px">
        <col width="100px">
        <col width="30px">
        <col width="30px">
        <col width="30px">
        <col width="30px">
        <col width="30px">
        <col width="90px">
    </colgroup>
    <thead>
        <tr>
            <th>
                <input type="checkbox" name="all" value="1"
                       data-proxyfor=":checkbox[name='ids[]']"
                       data-activates=".cronjobs select[name=action]">
            </th>
            <th><?= _('Cronjob') ?></th>
            <th><?= _('Aktiv') ?></th>
            <th><?= _('Typ') ?></th>
            <th colspan="5"><?= _('Ausführung') ?></th>
            <th><?= _('Optionen') ?></th>
        </tr>
    </thead>
    <tbody>
<? for ($i = 0; $i < $max_per_page; $i += 1): ?>
    <? if (!isset($schedules[$i])): ?>
        <tr class="empty">
            <td colspan="10">&nbsp;</td>
        </tr>
    <? else: ?>
        <tr id="job-<?= $schedules[$i]->schedule_id ?>" <? if (!$schedules[$i]->task->active) echo 'class="inactivatible"'; ?>>
            <td style="text-align: center">
                <input type="checkbox" name="ids[]" value="<?= $schedules[$i]->schedule_id ?>">
            </td>
            <td><?= htmlReady($schedules[$i]->title ?: $schedules[$i]->task->name) ?></td>
            <td style="text-align: center;">
            <? if (!$schedules[$i]->task->active): ?>
                <?= Assets::img('icons/16/grey/checkbox-unchecked',
                                tooltip2(_('Cronjob kann nicht aktiviert werden, da die zugehörige ' .
                                           'Aufgabe deaktiviert ist.'))) ?>
            <? elseif ($schedules[$i]->active): ?>
                <a href="<?= $controller->url_for('admin/cronjobs/schedules/deactivate', $schedules[$i]->schedule_id, $page) ?>" data-behaviour="ajax-toggle">
                    <?= Assets::img('icons/16/blue/checkbox-checked', tooltip2(_('Cronjob deaktivieren'))) ?>
                </a>
            <? else: ?>
                <a href="<?= $controller->url_for('admin/cronjobs/schedules/activate', $schedules[$i]->schedule_id, $page) ?>" data-behaviour="ajax-toggle">
                    <?= Assets::img('icons/16/blue/checkbox-unchecked', tooltip2(_('Cronjob aktivieren'))) ?>
                </a>
            <? endif; ?>
            </td>
            <td><?= ($schedules[$i]->type === 'once') ? _('Einmalig') : _('Regelmässig') ?></td>
        <? if ($schedules[$i]->type === 'once'): ?>
            <td colspan="5">
                <?= date('d.m.Y H:i', $schedules[$i]->next_execution) ?>
            </td>
        <? else: ?>
            <?= $this->render_partial('admin/cronjobs/schedules/periodic-schedule', $schedules[$i]->toArray() + array('display' => 'table-cells')) ?>
        <? endif; ?>
            <td style="text-align: right">
                <a data-dialog href="<?= $controller->url_for('admin/cronjobs/schedules/display', $schedules[$i]->schedule_id) ?>">
                    <?= Assets::img('icons/16/blue/admin', tooltip2(_('Cronjob anzeigen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/schedules/edit', $schedules[$i]->schedule_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/edit', tooltip2(_('Cronjob bearbeiten'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/logs/schedule', $schedules[$i]->schedule_id) ?>">
                    <?= Assets::img('icons/16/blue/log', tooltip2(_('Log anzeigen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/schedules/cancel', $schedules[$i]->schedule_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/trash', tooltip2(_('Cronjob löschen'))) ?>
                </a>
            </td>
        </tr>
    <? endif; ?>
<? endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="printhead">
                <select name="action" data-activates=".cronjobs button[name=bulk]">
                    <option value="">- <?= _('Aktion auswählen') ?></option>
                    <option value="activate"><?= _('Aktivieren') ?></option>
                    <option value="deactivate"><?= _('Deaktivieren') ?></option>
                    <option value="cancel"><?= _('Löschen') ?></option>
                </select>
                <?= Button::createAccept(_('Ausführen'), 'bulk') ?>
            </td>
            <td colspan="8" class="printhead" style="text-align: right; vertical-align: middle;">
                <?
                    $pagination = $GLOBALS['template_factory']->open('shared/pagechooser');
                    $pagination->set_attributes(array(
                        'perPage'      => $max_per_page,
                        'num_postings' => $total_filtered,
                        'page'         => $page,
                        'pagelink'     => $controller->url_for('admin/cronjobs/schedules/index/%u')
                    ));
                    echo $pagination->render();
                ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
