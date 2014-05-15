<h2><?= sprintf(_('Cronjob-Aufgabe "%s" ausführen'), htmlReady($task->name)) ?></h2>
<? if (isset($result)): ?>
<pre><code><?= htmlReady($result ?: _('- Keine Ausgabe -')) ?></code></pre>
<? else: ?>
<p><?= htmlReady($task->description) ?></p>
<form action="<?= $controller->url_for('admin/cronjobs/tasks/execute/' . $task->id) ?>" method="post" data-lightbox>
<? if (count($task->parameters)): ?>
    <?= $this->render_partial('admin/cronjobs/schedules/parameters') ?>
<? endif; ?>
    <div data-lightbox-button>
        <?= Studip\Button::createAccept(_('Ausführen'), 'submit') ?>
    </div>
</form>
<? endif; ?>