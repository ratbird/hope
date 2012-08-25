<? use Studip\LinkButton; ?>
<p style="font-weight: bold;">
    <?= _('Folgende Bereiche der Datenbank können geprüft werden:') ?>
</p>
<table class="default zebra">
    <colgroup>
        <col width="10%">
        <col width="70%">
        <col width="10%">
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Bereich') ?></th>
            <th><?= _('Beschreibung') ?></th>
            <th><?= _('Anzahl') ?></th>
            <th><?= _('Aktion') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($checks as $check => $plugin): ?>
        <tr>
            <td><?= $check ?></td>
            <td style="font-size:smaller">
                <?= _('Überprüft Tabelle:') ?>
                <b><?= $plugin->getCheckMasterTable() ?></b>
                <?= _('gegen') ?>
                <i><?= implode(', ', $plugin->getCheckDetailList()) ?></i>
            </td>
            <td>
                <?= $plugin->getCheckCount() ?>
            </td>
            <td style="text-align: center;">
                <?= LinkButton::create(_('Jetzt testen'), $controller->url_for('check', $check)) ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
<br><br>
