<? use Studip\Button; ?>

<h3 style="text-align: center;"><?= _('Ich studiere folgende Fächer und Abschlüsse:') ?></h3>

<? if ($allow_change['sg']): ?>
<form action="<?= $controller->url_for('settings/studies/store_sg') ?>" method="post">
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>
<? endif; ?>
<table class="default" id="select_fach_abschluss">
    <colgroup>
        <col>
        <col>
        <col width="100px">
        <col width="100px">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Fach') ?></th>
            <th><?= _('Abschluss') ?></th>
            <th id="fachsemester_label"><?= _('Fachsemester') ?></th>
            <th style="text-align:center;" id="austragen_label">
            <? if ($allow_change['sg']): ?>
                <?= _('austragen') ?>
            <? else: ?>
                &nbsp;
            <? endif; ?>
            </th>
        </tr>
    </thead>
    <tbody>
    <? if (count($about->user_fach_abschluss) === 0 && $allow_change['sg']): ?>
        <tr>
            <td colspan="4" style="background: inherit;">
                <strong><?= _('Sie haben sich noch keinem Studiengang zugeordnet.') ?></strong><br>
                <br>
                <?= _('Tragen Sie bitte hier die Angaben aus Ihrem Studierendenausweis ein!') ?>
            </td>
        <tr>
    <? endif; ?>
    <? foreach ($about->user_fach_abschluss as $details): ?>
        <tr>
            <td><?= htmlReady($details['fname']) ?></td>
            <td><?= htmlReady($details['aname']) ?></td>
        <? if ($allow_change['sg']): ?>
            <td>
                <select name="change_fachsem[<?= $details['studiengang_id'] ?>][<?= $details['abschluss_id'] ?>]" aria-labelledby="fachsemester_label">
                <? for ($i = 0; $i <= 50; $i += 1): ?>
                    <option <? if ($i == $details['semester']) echo 'selected'; ?>><?= $i ?></option>
                <? endfor; ?>
                </select>
            </td>
            <td style="text-align:center">
                <input type="checkbox" aria-labelledby="austragen_label" name="fach_abschluss_delete[<?= $details['studiengang_id'] ?>]" value="<?= $details['abschluss_id'] ?>">
            </td>
        <? else: ?>
            <td><?= htmlReady($details['semester']) ?></td>
            <td>
                <?= Assets::img('icons/16/grey/accept.png', array('class' => 'text-top')) ?>
            </td>
        <? endif; ?>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">
            <? if ($allow_change['sg']): ?>
                <p>
                    <?= _('Wählen Sie die Fächer, Abschlüsse und Fachsemester in der folgenden Liste aus:') ?>
                </p>

                <p>
                    <a name="studiengaenge"></a>
                    <?= $about->select_studiengang() ?>

                    <a name="abschluss"></a>
                    <?= $about->select_abschluss() ?>

                    <a name="semester"></a>
                    <select name="fachsem" aria-label="<?= _("Bitte Fachsemester wählen") ?>">
                    <? for ($i = 0; $i <= 50; $i += 1): ?>
                        <option><?= $i ?></option>
                    <? endfor; ?>
                    </select>
                </p>

                <p>
                    <?= _('Wenn Sie einen Studiengang wieder austragen möchten, '
                         .'markieren Sie die entsprechenden Felder in der oberen Tabelle.') ?>
                    <?= _('Mit einem Klick auf <b>Übernehmen</b> werden die gewählten Änderungen durchgeführt.') ?><br>
                    <br>
                    <?= Button::create(_('Übernehmen'), 'store_sg', array('title' => _('Änderungen übernehmen'))) ?>
                </p>
            <? else: ?>
                <?= _('Die Informationen zu Ihrem Studiengang werden vom System verwaltet, '
                     .'und können daher von Ihnen nicht geändert werden.') ?>
            <? endif; ?>
            </td>
        </tr>
    </tfoot>
</table>
<? if ($allow_change['sg']): ?>
</form>
<? endif; ?>
