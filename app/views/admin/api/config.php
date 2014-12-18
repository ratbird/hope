<? use Studip\Button, Studip\LinkButton; ?>

<form class="studip_form" action="<?= $controller->url_for('admin/api/config') ?>" method="post">
    <fieldset>
        <legend><?= _('Einstellungen') ?></legend>
        
        <div class="studip_input_wrapper">
            <input type="hidden" name="active" value="0">
            <label>
                
                <input type="checkbox" name="active" value="1" <? if ($config['API_ENABLED']) echo 'checked'; ?>>
            <?= _('REST-API aktiviert') ?></label>
        </div>
        
        <label class="caption" for="auth"><?= _('Standard-Authentifizierung beim Login') ?>
            <select name="auth" id="auth">
            <? foreach ($GLOBALS['STUDIP_AUTH_PLUGIN'] as $plugin): ?>
                <option <? if ($config['API_OAUTH_AUTH_PLUGIN'] === $plugin) echo 'selected'; ?>>
                    <?= $plugin ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>
    </fieldset>
    <div class="submit_wrapper">
        <?= Button::createAccept(_('Speichern')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/api')) ?>
    </div>
</form>
