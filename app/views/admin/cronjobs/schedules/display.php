<dl class="cronjob">
    <dt><?= _('Titel') ?></dt>
    <dd><?= htmlReady($schedule->title) ?></dd>
    
<? if ($schedule->description): ?>
    <dt><?= _('Beschreibung') ?></dt>
    <dd><?= htmlReady($schedule->description) ?></dd>
<? endif; ?>
        
    <dt><?= _('Aktiv') ?></dt>
    <dd><?= $schedule->active ? _('Ja') : _('Nein') ?></dd>

    <dt><?= _('Priorit�t') ?></dt>
    <dd><?= CronjobSchedule::describePriority($schedule->priority) ?></dd>

<? if (count($schedule->parameters) > 0): ?>
    <dt><?= _('Parameter') ?></dt>
    <dd>
        <ul>
        <? foreach ($schedule->parameters as $parameter): ?>
            <li><?= htmlReady($parameter) ?></li>
        <? endforeach; ?>
        </ul>
    </dd>
<? endif; ?>

    <dt><?= _('Aufgabe') ?></dt>
    <dd><?= htmlReady($schedule->task->name) ?></dd>
    
    <dt><?= _('Typ') ?></dt>
<? if ($schedule->type === 'once'): ?>
    <dd>
        <?= sprintf(_('Einmalig am %s um %s'), date('d.m.Y', $schedule->next_execution), date('H:i', $schedule->next_execution)) ?>
    </dd>
    
    <dt><?= _('Ausgef�hrt') ?>?</dt>
    <dd>
    <? if ($schedule->execution_count > 0): ?>
        <?= _('Ja') ?>, <?= sprintf(_('am %s um %s'), date('d.m.Y', $schedule->last_execution), date('H:i:s', $schedule->last_execution)) ?>
    <? else: ?>
        <?= _('Nein') ?>
    <? endif; ?>
    </dd>
<? else: ?>
    <dd>
        <?= _('Regelm�ssig') ?>
        <?= $this->render_partial('admin/cronjobs/schedules/periodic-schedule', $schedule->toArray()) ?>
    </dd>

    <dt><?= _('Ausf�hrungen') ?></dt>
    <dd><?= number_format($schedule->execution_count, 0, ',', '.') ?></dd>

    <? if ($schedule->active): ?>
        <dt><?= _('N�chste Ausf�hrung') ?></dt>
        <dd><?= date('d.m.Y H:i:s', $schedule->next_execution) ?></dd>
    <? endif; ?>

    <? if ($schedule->execution_count > 0): ?>
        <dt><?= _('Letzte Ausf�hrung') ?></dt>
        <dd><?= $schedule->last_execution ? date('d.m.Y H:i:s', $schedule->last_execution) : _('nie') ?></dd>

        <dt><?= _('Letztes Ergebnis') ?></dt>
        <dd><code><?= htmlReady($schedule->last_result) ?></code></dd>
    <? endif; ?>

<? endif; ?>
</dl>

<a rel="option" href="<?= $controller->url_for('admin/cronjobs/logs/schedule', $schedule->schedule_id) ?>">
    <?= _('Log anzeigen') ?>
</a>
<a rel="option" href="<?= $controller->url_for('admin/cronjobs/schedules/edit', $schedule->schedule_id) ?>">
    <?= _('Cronjob bearbeiten') ?>
</a>