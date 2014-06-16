<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('document/administration/deactivateDocumentArea/' . $config_id) ?>"
      method="post" class="studip_form">
<? if(isset($header)): ?>
    <h3><?= htmlReady($header) ?></h3>
<? endif;?>
    <fieldset>
        <legend><?=_('Begründung') ?></legend>
        <label for="reason_text">
             <textarea name="reason_text" id="reason_text" cols="35" rows="4"><?= htmlReady($reason_text) ?></textarea>
        </label>
    </fieldset>

    <div data-dialog-button>
        <?= Button::createAccept(_('Sperren'), 'store') ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('document/administration/filter')) ?>
    </div>
</form>
