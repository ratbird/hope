<? use Studip\LinkButton; ?>

<p style="font-weight: bold;">
    <?= sprintf(_('Bereich: <i>%s</i> Datensätze der Tabelle %s'), $check, $plugin->getCheckDetailTable($id)) ?>
</p>
<p style="text-align: center;">
    <?= LinkButton::create(_('Löschen'), $controller->url_for('check/' . $check . '/delete/' . $id)) ?>
    <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('check/' . $check)) ?>
</p>

<table class="default zebra-hover" style="font-size:smaller">
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