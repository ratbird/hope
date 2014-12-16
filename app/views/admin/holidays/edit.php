<form method="post" action="<?= $controller->url_for('admin/holidays/edit/' . $holiday->id) ?>" data-dialog="size=auto">
    <?= CSRFProtection::tokenTag() ?>

<table class="default has-form">
    <caption class="hide-in-dialog">
        <?= PageLayout::getTitle() ?>
    </caption>
    <colgroup>
        <col width="200px">
        <col>
    </colgroup>
    <tbody>
        <tr>
            <td>
                <label for="name"><?= _('Name der Ferien') ?>:</label>
            </td>
            <td>
                <input required type="text" name="name" id="name"
                       style="width: 100%; min-width: 200px;"
                       value="<?= htmlReady($holiday->name) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="description"><?= _('Beschreibung') ?>:</label>
            </td>
            <td>
                <textarea name="description" id="description" style="width: 100%;"><?= htmlReady($holiday->description) ?></textarea>
            </td>
        </tr>
        <tr>
            <th colspan="2"><?= _('Ferienzeitraum') ?></th>
        </tr>
        <tr>
            <td>
                <label for="beginn"><?= _('Beginn') ?>:</label>
            </td>
            <td>
                <input required type="text" id="beginn" name="beginn" class="has-date-picker"
                       value="<? if ($holiday->beginn) echo date('d.m.Y', $holiday->beginn) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="ende"><?= _('Ende') ?>:</label>
            </td>
            <td>
                <input required type="text" id="ende" name="ende" class="has-date-picker"
                       value="<? if ($holiday->ende) echo date('d.m.Y', $holiday->ende) ?>">
            </td>
        </tr>
    </tbody>
    <tfoot data-dialog-button>
        <tr>
            <td colspan="2" style="text-align: center">
                <?= Studip\Button::createAccept(_('Speichern')) ?>
                <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/holidays')) ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
