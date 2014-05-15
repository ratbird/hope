<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('admin/cronjobs/logs/filter') ?>"
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
                <?= sprintf(_('Passend: %u von %u Logeinträgen'), $total_filtered, $total) ?>
            <? endif; ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <label for="status"><?= _('Status') ?></label>
                <select name="filter[status]" id="status">
                    <option value=""><?= _('Alle Logeinträge anzeigen') ?></option>
                    <option value="passed" <? if ($filter['status'] === 'passed') echo 'selected'; ?>>
                        <?= _('Nur fehlerfreie Logeinträge anzeigen') ?>
                    </option>
                    <option value="failed" <? if ($filter['status'] === 'failed') echo 'selected'; ?>>
                        <?= _('Nur fehlerhafte Logeinträge anzeigen') ?>
                    </option>
                </select>
            </td>
            <td>
                <label for="schedule_id"><?= _('Cronjob') ?></label>
                <select name="filter[schedule_id]" id="schedule_id">
                    <option value=""><?= _('Alle Logeinträge anzeigen') ?></option>
                <? foreach ($schedules as $schedule): ?>
                    <option value="<?= $schedule->schedule_id ?>" <? if ($filter['schedule_id'] === $schedule->schedule_id) echo 'selected'; ?>>
                        <?= htmlReady($schedule->title) ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
            <td>
                <label for="task_id"><?= _('Aufgabe') ?></label>
                <select name="filter[task_id]" id="task_id">
                    <option value=""><?= _('Alle Aufgaben anzeigen') ?></option>
                <? foreach ($tasks as $task): ?>
                    <option value="<?= $task->task_id ?>" <? if ($filter['task_id'] === $task->task_id) echo 'selected'; ?>>
                        <?= htmlReady($task->name) ?>
                    </option>
                <? endforeach; ?>
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
                                             $controller->url_for('admin/cronjobs/logs/filter'),
                                             array('title' => _('Filter zurücksetzen'))) ?>
            <? endif; ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>


<form action="<?= $controller->url_for('admin/cronjobs/logs/bulk', $page) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

<table class="default cronjobs">
    <colgroup>
        <col width="20px">
        <col width="150px">
        <col width="150px">
        <col>
        <col width="50px">
        <col width="50px">
    </colgroup>
    <thead>
        <tr>
            <th>
                <input type="checkbox" name="all" value="1"
                       data-proxyfor=":checkbox[name='ids[]']"
                       data-activates=".cronjobs select[name=action]">
            </th>
            <th><?= _('Ausgeführt') ?></th>
            <th><?= _('Geplant') ?></th>
            <th><?= _('Cronjob') ?></th>
            <th><?= _('Ok?') ?></th>
            <th><?= _('Optionen') ?></th>
        </tr>
    </thead>
    <tbody>
<? for ($i = 0; $i < $max_per_page; $i += 1): ?>
    <? if (!isset($logs[$i])): ?>
        <tr class="empty">
            <td colspan="6">&nbsp;</td>
        </tr>
    <? else: ?>
        <tr id="log-<?= $logs[$i]->log_id ?>">
            <td style="text-align: center">
                <input type="checkbox" name="ids[]" value="<?= $logs[$i]->log_id ?>">
            </td>
            <td><?= date('d.m.Y H:i:s', $logs[$i]->executed) ?></td>
            <td><?= date('d.m.Y H:i:s', $logs[$i]->scheduled) ?></td>
            <td><?= htmlReady($logs[$i]->schedule->title ?: $logs[$i]->schedule->task->name) ?></td>
            <td>
            <? if ($logs[$i]->duration == -1): ?>
                <?= Assets::img('icons/16/grey/question', tooltip2(_('Läuft noch'))) ?>
            <? elseif ($logs[$i]->exception === null): ?>
                <?= Assets::img('icons/16/green/accept', tooltip2(_('Ja'))) ?>
            <? else: ?>
                <?= Assets::img('icons/16/red/decline', tooltip2(_('Nein'))) ?>
            <? endif; ?>
            </td>
            <td style="text-align: right">
                <a data-lightbox href="<?= $controller->url_for('admin/cronjobs/logs/display', $logs[$i]->log_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/admin', tooltip2(_('Logeintrag anzeigen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/logs/delete', $logs[$i]->log_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/trash', tooltip2(_('Logeintrag löschen'))) ?>
                </a>
            </td>
        </tr>
    <? endif; ?>
<? endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="printhead">
                <select name="action" data-activates="button[name=bulk]">
                    <option value="">- <?= _('Aktion auswählen') ?></option>
                    <option value="delete"><?= _('Löschen') ?></option>
                </select>
                <?= Button::createAccept(_('Ausführen'), 'bulk') ?>
            </td>
            <td colspan="3" class="printhead" style="text-align: right; vertical-align: middle;">
            <?
                $pagination = $GLOBALS['template_factory']->open('shared/pagechooser');
                $pagination->set_attributes(array(
                    'perPage'      => $max_per_page,
                    'num_postings' => $total,
                    'page'         => $page,
                    'pagelink'     => $controller->url_for('admin/cronjobs/logs/index/%u')
                ));
                echo $pagination->render();
            ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
