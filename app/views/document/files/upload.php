<form enctype="multipart/form-data" method="post" class="studip_form"
      action="<?= $controller->url_for('document/files/upload/' . $folder_id) ?>">

    <input type="hidden" name="studip-ticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <fieldset>
            <label>
                <?= _('Datei(en) auswählen') ?>
                <input name="file[]" type="file" required multiple>
            </label>
        </fieldset>

        <fieldset>
            <label>
                <?= _('Titel') ?>
                <input type="text" name="title" placeholder="<?= _('Titel') ?>">
            </label>
        </fieldset>

        <fieldset>
            <label>
                <?= _('Beschreibung') ?>
                <textarea name="description" placeholder="<?= _('Optionale Beschreibung') ?>"></textarea>
            </label>
        </fieldset>
<?/*
        <fieldset>
            <label>
                <input type="radio" name="restricted" value="0">
                <?= _('Ja, dieses Dokument ist frei von Rechten Dritter.') ?>
            </label>
            <label>
                <input type="radio" name="restricted" value="1">
                <?= sprintf(_('Nein, dieses Dokumnt ist %snicht%s frei von Rechten Dritter.'), '<em>', '</em>') ?>
            </label>
        </fieldset>
    </fieldset>
*/?>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Hochladen'), 'upload') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'),
                $controller->url_for('document/files/index/' . $env_dir)) ?>
    </div>
</form>
