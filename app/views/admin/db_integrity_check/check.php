<? use Studip\LinkButton; ?>

<? if ($delete): ?>
<?= createQuestion(sprintf(_('Sie beabsichtigen %s Datens�tze der Tabelle %s zu l�schen.'),
                           $data[$delete]['count'],
                           $data[$delete]['count'])
                   . "\n"
                   . 'Dieser Schritt kann nicht r�ckg�ngig gemacht werden! '
                   . 'Sind Sie sicher?',
                   array('confirmed' => 'yes'), array('confirmed' => 'no')) ?>
<? endif; ?>

<p style="text-align: center;">
    <b><?= sprintf(_('Bereich: <i>%s</i> der Datenbank wird gepr�ft!'), $check) ?></b><br>
    <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('index')) ?>
</p>
<table class="default">
    <thead>
        <tr>
            <th width="20%"><?= _('Tabelle') ?></th>
            <th width="60%"><?= _('Ergebnis') ?></th>
            <th width="20%"><?= _('Aktion') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($data as $index => $item): ?>
        <tr>
            <td><?= htmlReady($item['table']) ?></td>
            <td><?= sprintf(_('%u Datens�tze gefunden'), $item['count']) ?></td>
            <td>
            <? if ($item['count'] > 0): ?>
                <?= LinkButton::create(_('Anzeigen'), $controller->url_for('show/' . $check . '/' . $index)) ?>
                <?= LinkButton::create(_('L�schen'), $controller->url_for('check/' . $check . '/delete/' . $index)) ?>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
<br><br>
