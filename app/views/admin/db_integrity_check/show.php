<? use Studip\LinkButton; ?>

<p style="font-weight: bold;">
    <?= sprintf(_('Bereich: <i>%s</i> Datens�tze der Tabelle %s'), $check, $plugin->getCheckDetailTable($id)) ?>
</p>
<p style="text-align: center;">
    <?= LinkButton::create(_('L�schen'), $controller->url_for('check/' . $check . '/delete/' . $id)) ?>
    <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('check/' . $check)) ?>
</p>

<table class="default" style="font-size:smaller">
    <thead>
        <tr>
        <? foreach ($header as $column): ?>
            <th><?= $column ?></th>
        <? endforeach; ?>
    </thead>
    <tbody>
    <? foreach ($rows as $row): ?>
        <tr>
        <? foreach ($header as $column): ?>
            <td><?= htmlReady(substr($row[$column], 0, 50)) ?></td>
        <? endforeach; ?>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>