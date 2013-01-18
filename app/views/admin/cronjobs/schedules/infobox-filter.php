<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('admin/cronjobs/schedules/filter') ?>" method="post"
      class="cronjob-filters" onchange="this.submit()">
    <div>
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
    </div>

    <div>
        <label for="task_id"><?= _('Aufgabe') ?></label>
        <select name="filter[task_id]" id="task_id">
            <option value=""><?= _('Alle Cronjobs anzeigen') ?></option>
        <? foreach ($tasks as $task): ?>
            <option value="<?= $task->task_id ?>" <? if ($filter['task_id'] === $task->task_id) echo 'selected'; ?>>
                <?= htmlReady($task->name) ?>
            </option>
        <? endforeach; ?>
        </select>
    </div>

    <div>
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
    </div>
    
    <noscript>
        <?= Button::create(_('Filtern')) ?>
    </noscript>
</form>

<? if (!empty($filter)): ?>
    <?= LinkButton::createCancel(_('Filter zurücksetzen'), $controller->url_for('admin/cronjobs/schedules/filter')) ?>
<? endif; ?>
