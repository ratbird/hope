<form class="studip_form" action="<?= $controller->url_for('admin/coursewizardsteps/delete', $step->id) ?>" method="post">
    <?= sprintf(_('Soll der Eintrag "%s" wirklich gelöscht werden?'), htmlReady($step->name)) ?>
    <?= CSRFProtection::tokenTag() ?>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Ja'), 'delete') ?>
        <?= Studip\Button::createCancel(_('Nein'), 'cancel', array('data-dialog' => 'close')) ?>
    </div>
</form>