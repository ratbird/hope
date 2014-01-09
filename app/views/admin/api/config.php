<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('admin/api/config') ?>" method="post">
    <fieldset>
        <legend><?= _('Einstellungen') ?></legend>

        <div class="type-checkbox">
            <label for="active"><?= _('REST-API aktiviert') ?></label>
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" <? if ($config['API_ENABLED']) echo 'checked'; ?>>
        </div>

        <div class="type-select">
            <label for="auth"><?= _('Standard-Authentifizierung beim Login') ?></label>
            <select name="auth" id="auth">
            <? foreach ($GLOBALS['STUDIP_AUTH_PLUGIN'] as $plugin): ?>
                <option <? if ($config['API_OAUTH_AUTH_PLUGIN'] === $plugin) echo 'selected'; ?>>
                    <?= $plugin ?>
                </option>
            <? endforeach; ?>
            </select>
        </div>

        <div class="type-button">
            <?= Button::createAccept(_('Speichern')) ?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/api')) ?>
        </div>
    </fieldset>
</form>
