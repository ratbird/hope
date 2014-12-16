<form method="post" action="<?= $controller->url_for('admin/semester/edit/' . $semester->id) ?>" data-dialog="size=auto">
    <?= CSRFProtection::tokenTag() ?>
<table class="default has-form">
    <caption class="hide-in-dialog">
        <?= PageLayout::getTitle() ?>
    </caption>
    <colgroup>
        <col width="150px">
        <col>
    </colgroup>
    <tbody>
        <tr>
            <td>
                <label for="name"><?= _('Name des Semesters') ?></label>
            </td>
            <td colspan="4">
                <input required type="text" name="name" id="name"
                       value="<?= htmlReady($semester->name) ?>"
                       <? if (isset($errors['name'])) echo 'class="invalid"'; ?>>
            </td>
        </tr>
        <tr>
            <td>
                <label for="token"><?= _('Kürzel') ?></label>
            </td>
            <td colspan="4">
                <input type="text" name="token" id="token"
                       value="<?= htmlReady($semester->semester_token) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="description"><?= _('Beschreibung') ?></label>
            </td>
            <td colspan="4">
                <textarea name="description" id="description"><?= htmlReady($semester->description) ?></textarea>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <?= _('Semesterzeitraum') ?>
            </th>
        </tr>
        <tr>
            <td>
                <label for="beginn"><?= _('Beginn') ?></label>
            <? if ($semester->absolute_seminars_count > 0): ?>
                <?= tooltipIcon(_('Das Startdatum kann nur bei Semestern geändert werden, in denen keine Veranstaltungen liegen!'), true) ?>
            <? endif; ?>
            </td>
            <td>
            <? if ($semester->absolute_seminars_count > 0): ?>
                <input type="text" name="beginn" value="<?= date('d.m.Y', $semester->beginn) ?>" readonly>
            <? else: ?>
                <input required type="text" id="beginn" name="beginn"
                       class="has-date-picker <? if (isset($errors['beginn'])) echo 'invalid'; ?>"
                       value="<? if ($semester->beginn) echo date('d.m.Y', $semester->beginn) ?>">
            <? endif; ?>
            </td>
        </tr>
        <tr>
            <td>
                <label for="ende"><?= _('Ende') ?></label>
            </td>
            <td>
                <input required type="text" id="ende" name="ende"
                       class="has-date-picker <? if (isset($errors['ende'])) echo 'invalid'; ?>"
                       value="<? if ($semester->ende) echo date('d.m.Y', $semester->ende); ?>">
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <?= _('Vorlesungszeitraum') ?>
            </th>
        </tr>
        <tr>
            <td>
                <label for="vorles_beginn"><?= _('Beginn') ?></label>
            </td>
            <td>
                <input required type="text" id="vorles_beginn" name="vorles_beginn"
                       class="has-date-picker <? if (isset($errors['vorles_beginn'])) echo 'invalid'; ?>"
                       value="<? if ($semester->vorles_beginn) echo date('d.m.Y', $semester->vorles_beginn); ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="vorles_ende"><?= _('Ende') ?></label>
            </td>
            <td>
                <input required type="text" id="vorles_ende" name="vorles_ende"
                       class="has-date-picker <? if (isset($errors['vorles_ende'])) echo 'invalid'; ?>"
                       value="<? if ($semester->vorles_ende) echo date('d.m.Y', $semester->vorles_ende); ?>">
            </td>
        </tr>
    </tbody>
    <tfoot data-dialog-button>
        <tr>
            <td colspan="2" style="text-align:center">
                <?= Studip\Button::createAccept(_('Speichern')) ?>
                <?= Studip\LinkButton::createCancel(_('Abbrechen'),
                                                    $controller->url_for('admin/semester'))?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
