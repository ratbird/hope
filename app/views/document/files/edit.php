<form method="post" action="<?= $controller->url_for('document/files/edit/' . $entry->id) ?>" class="studip_form">
    <input type="hidden" name="studip-ticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <fieldset class="required">
            <label>
                <?= _('Name:') ?>
                <input type="text" name="filename" placeholder="<?= _('Dateiname') ?>" value="<?= htmlReady($entry->file->filename) ?>" required>
            </label>
        </fieldset>

        <fieldset class="required">
            <label>
                <?= _('Titel:') ?>
                <input type="text" name="name" placeholder="<?= _('Titel der Datei') ?>" value="<?= htmlReady($entry->name) ?>" required>
            </label>
        </fieldset>

        <fieldset>
            <label>
                <?= _('Beschreibung:') ?>
                <textarea name="description" placeholder="<?= _('Optionale Beschreibung für die Datei') ?>"><?= htmlReady($entry->description) ?></textarea>
            </label>
        </fieldset>
<?/*
        <fieldset>
            <label>
                <input type="radio" name="restricted" value="0" <? if (!$entry->file->restricted) echo 'checked'; ?>>
                <?= _('Ja, dieses Dokument ist frei von Rechten Dritter.') ?>
            </label>
            <label>
                <input type="radio" name="restricted" value="1" <? if ($entry->file->restricted) echo 'checked'; ?>>
                <?= _('Nein, dieses Dokument ist <u>nicht</u> frei von Rechten Dritter.') ?>
            </label>
        </fieldset>
*/?>
    </fieldset>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('document/files/index/' . $entry->directory->id)) ?>
    </div>
</form>
