<? use Studip\Button, Studip\LinkButton; ?>

<?
    $days_of_week = array(
        1 => _('Montag'),
        2 => _('Dienstag'),
        3 => _('Mittwoch'),
        4 => _('Donnerstag'),
        5 => _('Freitag'),
        6 => _('Samstag'),
        7 => _('Sonntag'),
    );
?>


<form action="<?= $controller->url_for('admin/cronjobs/schedules/edit', $schedule->schedule_id, $page) ?>" method="post" class="cronjobs-edit">
    <?= CSRFProtection::tokenTag() ?>

    <h1>
    <? if ($schedule->isNew()): ?>
        <?= _('Neuen Cronjob anlegen') ?>
    <? else: ?>
        <?= sprintf(_('Cronjob "%s" bearbeiten'), $schedule->title) ?>
    <? endif; ?>
    </h1>

    <h2 class="topic"><?= _('Details') ?></h2>
    <table class="default">
        <colgroup>
            <col width="20%">
            <col width="80%">
        </colgroup>
        <thead>
            <tr>
                <th><?= _('Option') ?></th>
                <th><?= _('Wert') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <label for="active"><?= _('Aktiv') ?></label>
                </td>
                <td>
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" id="active" value="1"
                           <? if ($schedule->active) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="priority"><?= _('Priorität') ?></label>
                </td>
                <td>
                    <select name="priority" id="priority">
                    <? foreach (CronjobSchedule::getPriorities() as $priority => $label): ?>
                        <option value="<?= $priority ?>" <? if ((!$schedule->priority && $priority === CronjobSchedule::PRIORITY_NORMAL) || $schedule->priority === $priority) echo 'selected'; ?>>
                            <?= htmlReady($label) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="title"><?= _('Titel') ?></label>
                </td>
                <td>
                    <input type="text" name="title" id="title" value="<?= htmlReady($schedule->title ?: '') ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="description"><?= _('Beschreibung') ?></label>
                </td>
                <td>
                    <textarea name="description"><?= htmlReady($schedule->description ?: '') ?></textarea>
                </td>
            </tr>
        </tbody>
    </table>

    <h2 class="topic"><?= _('Aufgabe') ?></h2>
    <table class="default cron-task" cellspacing="0" cellpadding="0">
        <colgroup>
            <col width="20px">
            <col width="100px">
            <col width="150px">
            <col>
            <col width="20px">
        </colgroup>
        <thead>
            <tr>
                <th colspan="2"><?= _('Klassenname') ?></th>
                <th><?= _('Name') ?></th>
                <th><?= _('Beschreibung') ?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
    <? foreach ($tasks as $task): ?>
        <? if (!$schedule->isNew() && $task->task_id != $schedule->task_id) continue; ?>
        <tbody <? if (!$schedule->isNew() && $task->task_id === $schedule->task_id) echo 'class="selected"'; ?>>
            <tr>
                <td>
                <? if ($schedule->isNew()): ?>
                    <input required type="radio" name="task_id"
                           id="task-<?= $task->task_id ?>"
                           value="<?= $task->task_id ?>"
                           <? if ($task->task_id === $schedule->task_id) echo 'checked'; ?>>
                <? endif; ?>
                </td>
                <td>
                    <label for="task-<?= $task->task_id ?>">
                        <?= htmlReady($task->class) ?>
                    </label>
                </td>
                <td>
                    <label for="task-<?= $task->task_id ?>"><?= htmlReady($task->name) ?: '&nbsp;' ?></label>
                </td>
                <td colspan="2">
                    <label for="task-<?= $task->task_id ?>"><?= htmlReady($task->description) ?: '&nbsp;' ?></label>
                </td>
            </tr>
        <? if (count($task->parameters) > 0): ?>
            <tr>
                <td class="blank">&nbsp;</td>
                <td colspan="3">
                    <div class="parameters">
                        <?= $this->render_partial('admin/cronjobs/schedules/parameters', compact('task', 'schedule')) ?>
                    </div>
                </td>
                <td class="blank">&nbsp;</td>
            </tr>
        <? endif; ?>
        </tbody>
    <? endforeach; ?>
    </table>

    <h2 class="topic"><?= _('Zeitplan') ?></h2>
    <table class="default cron-schedule">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th>
                    <label>
                        <input type="radio" name="type" value="periodic"
                               <? if ($schedule->type === 'periodic') echo 'checked'; ?>>
                        <?= _('Wiederholt') ?>
                    </label>
                </th>
                <th>
                    <label>
                        <input type="radio" name="type" value="once"
                               <? if ($schedule->type === 'once') echo 'checked'; ?>>
                        <?= _('Einmalig') ?>
                    </label>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <table class="default">
                        <colgroup>
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>
                                    <label for="minute"><?= _('Minute') ?></label>
                                </th>
                                <th>
                                    <label for="hour"><?= _('Stunde') ?></label>
                                </th>
                                <th>
                                    <label for="day"><?= _('Tag') ?></label>
                                </th>
                                <th>
                                    <label for="month"><?= _('Monat') ?></label>
                                </th>
                                <th>
                                    <label for="day_of_week"><?= _('Wochentag') ?></label>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="cron-item">
                                    <select name="periodic[minute][type]" id="minute">
                                        <option value="" title="<?= _('beliebig') ?>">*</option>
                                        <option value="once" <? if ($schedule->minute !== null && $schedule->minute >= 0) echo 'selected'; ?>>
                                            min
                                        </option>
                                        <option value="periodic" <? if ($schedule->minute < 0) echo 'selected'; ?>>
                                            */min
                                        </option>
                                    </select>
                                    <div>
                                        <span>min=</span>
                                        <input type="number" name="periodic[minute][value]" value="<?= abs($schedule->minute) ?>">
                                    </div>
                                </td>
                                <td class="cron-item">
                                    <select name="periodic[hour][type]" id="hour">
                                        <option value="" title="<?= _('beliebig') ?>">*</option>
                                        <option value="once" <? if ($schedule->hour !== null && $schedule->hour >= 0) echo 'selected'; ?>>
                                            hour
                                        </option>
                                        <option value="periodic" <? if ($schedule->hour < 0) echo 'selected'; ?>>
                                            */hour
                                        </option>
                                    </select>
                                    <div>
                                        <span>hour=</span>
                                        <input type="number" name="periodic[hour][value]" value="<?= abs($schedule->hour) ?>">
                                    </div>
                                </td>
                                <td class="cron-item">
                                    <select name="periodic[day][type]" id="day">
                                        <option value="" title="<?= _('beliebig') ?>">*</option>
                                        <option value="once" <? if ($schedule->day !== null && $schedule->day >= 0) echo 'selected'; ?>>
                                            day
                                        </option>
                                        <option value="periodic" <? if ($schedule->day < 0) echo 'selected'; ?>>
                                            */day
                                        </option>
                                    </select>
                                    <div>
                                        <span>day=</span>
                                        <input type="number" name="periodic[day][value]" value="<?= abs($schedule->day) ?>">
                                    </div>
                                </td>
                                <td class="cron-item">
                                    <select name="periodic[month][type]" id="month">
                                        <option value="" title="<?= _('beliebig') ?>">*</option>
                                        <option value="once" <? if ($schedule->month !== null && $schedule->month >= 0) echo 'selected'; ?>>
                                            month
                                        </option>
                                        <option value="periodic" <? if ($schedule->month < 0) echo 'selected'; ?>>
                                            */month
                                        </option>
                                    </select>
                                    <div>
                                        <span>month=</span>
                                        <input type="number" name="periodic[month][value]" value="<?= abs($schedule->month) ?>">
                                    </div>
                                </td>
                                <td>
                                    <select name="periodic[day_of_week][value]" id="day_of_week">
                                        <option value=""><?= _('*') ?></option>
                                    <? foreach ($days_of_week as $index => $label): ?>
                                        <option value="<?= $index ?>" <? if ($schedule->day_of_week === $index) echo 'selected'; ?>>
                                            <?= $index ?> (<?= $label ?>)
                                        </option>
                                    <? endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td>
                    <label>
                        <?= _('Datum') ?>
                        <input type="text" name="once[date]" class="has-date-picker"
                               value="<? if ($schedule->type === 'once' && $schedule->next_execution) echo date('d.m.Y', $schedule->next_execution); ?>">
                    </label>

                    <label>
                        <?= _('Uhrzeit') ?>
                        <input type="text" name="once[time]" class="has-time-picker"
                               value="<? if ($schedule->type === 'once' && $schedule->next_execution) echo date('H:i', $schedule->next_execution) ?>">
                    </label>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="buttons" style="text-align: center;">
        <?= Button::createAccept(_('Speichern'), 'store') ?>
        <?= LinkButton::createCancel('Abbrechen', $controller->url_for('admin/cronjobs/schedules')) ?>
    </div>
</form>