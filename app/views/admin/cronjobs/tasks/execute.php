<h2><?= sprintf(_('Cronjob-Aufgabe "%s" ausf�hren'), htmlReady($task->name)) ?></h2>
<? if (isset($result)): ?>
<pre><code><?= htmlReady($result ?: _('- Keine Ausgabe -')) ?></code></pre>
<? else: ?>
<p><?= htmlReady($task->description) ?></p>
<form action="<?= $controller->url_for('admin/cronjobs/tasks/execute/' . $task->id) ?>" method="post" rel="lightbox">
<? if (count($task->parameters)): ?>
    <?= $this->render_partial('admin/cronjobs/schedules/parameters') ?>
<? endif; ?>
    <div>
        <?= Studip\Button::createAccept(_('Ausf�hren'), 'submit', array('rel' => 'option')) ?>
    </div>
</form>
<? endif; ?>