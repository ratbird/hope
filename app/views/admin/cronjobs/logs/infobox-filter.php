<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('admin/cronjobs/logs/filter') ?>" method="post"
      class="cronjob-filters" onchange="this.submit()">
    <div>
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
    </div>

    <div>
        <label for="schedule_id"><?= _('Cronjob') ?></label>
        <select name="filter[schedule_id]" id="schedule_id">
            <option value=""><?= _('Alle Logeinträge anzeigen') ?></option>
        <? foreach ($schedules as $schedule): ?>
            <option value="<?= $schedule->schedule_id ?>" <? if ($filter['schedule_id'] === $schedule->schedule_id) echo 'selected'; ?>>
                <?= htmlReady($schedule->title) ?>
            </option>
        <? endforeach; ?>
        </select>
    </div>

    <div>
        <label for="task_id"><?= _('Aufgabe') ?></label>
        <select name="filter[task_id]" id="task_id">
            <option value=""><?= _('Alle Aufgaben anzeigen') ?></option>
        <? foreach ($tasks as $task): ?>
            <option value="<?= $task->task_id ?>" <? if ($filter['task_id'] === $task->task_id) echo 'selected'; ?>>
                <?= htmlReady($task->name) ?>
            </option>
        <? endforeach; ?>
        </select>
    </div>

    <noscript>
        <?= Button::create(_('Filtern')) ?>
    </noscript>
</form>

<? if (!empty($filter)): ?>
    <?= LinkButton::createCancel(_('Filter zurücksetzen'), $controller->url_for('admin/cronjobs/logs/filter')) ?>
<? endif; ?>
