<h1><?= _('Zusatzangaben') ?></h1>

<? if (!empty($aux['rows'])) : ?>
    <form method="post">
        <table class="default">
            <caption><?= _('Zusatzangaben bearbeiten') ?></caption>
            <thead>
            <tr>
                <? foreach ($aux['head'] as $head): ?>
                    <th><?= $head ?></th>
                <? endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <? foreach ($aux['rows'] as $entry): ?>
                <tr>
                    <? foreach ($aux['head'] as $key => $value): ?>
                        <td><?= htmlReady($entry[$key]) ?></td>
                    <? endforeach; ?>
                </tr>
            <? endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="0">
                    <?= \Studip\Button::create(_('Speichern'), 'save') ?>
                    <?= \Studip\Button::create(_('Exportieren'), 'export') ?>
                </td>
            </tr>
            </tfoot>

        </table>
    </form>
<? else : ?>
    <?= MessageBox::info(_('Keine Zusatzangaben oder TeilnehmerInnen vorhanden.')) ?>
<? endif ?>