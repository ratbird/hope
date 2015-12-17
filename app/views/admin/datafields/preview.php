<form action="<?= $controller->url_for('admin/datafields/config/' . $struct->getID()) ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>

    <input type="hidden" name="typeparam" value="<?= htmlReady($struct->getTypeParam()) ?>">

    <fieldset>
        <legend><?= _('Vorschau') ?></legend>

        <label>
            <?= $preview->getName() ?>
            
            <?= $preview->getHTML('dummy') ?>
        </label>
    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'store') ?>
        <?= Studip\Button::create(_('Bearbeiten'), 'edit', ['data-dialog' => 'size=auto']) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/datafields/index/' . $struct->getObjectType() . '#' . $struct->getObjectType())) ?>
    </footer>
</form>
