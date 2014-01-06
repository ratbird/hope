<? use Studip\Button, Studip\LinkButton; ?>
<h1>
<?= $consumer->id
    ? sprintf(_('Registrierten Konsumenten "%s" bearbeiten'), $consumer->title)
    : _('Neuen Konsumenten registrieren') ?></h1>

    
<form class="<?= $consumer->id ? 'horizontal' : '' ?> settings"
      action="<?= $controller->url_for('admin/api/edit', $consumer->id) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    
    <fieldset>
        <legend><?= _('Grundeinstellungen') ?></legend>
        
        <div class="type-checkbox">
            <label for="active"><?= _('Aktiviert') ?></label>
            <input type="checkbox" class="switch" id="active" name="active" value="1"
                   <?= $consumer->active ? 'checked' : '' ?>>
        </div>

        <div class="type-text">
            <label for="title"><?= _('Titel')?></label>
            <input required type="text" id="title" name="title"
                   placeholder="<?= _('Beispiel-Applikation') ?>"
                   value="<?= htmlReady($consumer->title) ?>">
        </div>

        <div class="type-text">
            <label for="contact"><?= _('Kontaktperson') ?></label>
            <input required type="text" id="contact" name="contact"
                   placeholder="John Doe"
                   value="<?= htmlReady($consumer->contact) ?>">
        </div>

        <div class="type-text">
            <label for="email"><?= _('Kontaktadresse') ?></label>
            <input required type="text" id="email" name="email"
                   placeholder="support@appsite.tld"
                   value="<?= htmlReady($consumer->email) ?>">
        </div>

        <div class="type-text">
            <label for="callback"><?= _('Callback URL')?></label>
            <input required type="text" id="callback" name="callback"
                   placeholder="http://appsite.tld/auth"
                   value="<?= htmlReady($consumer->callback) ?>">
        </div>
        
    <? if ($consumer->id): ?>
        <div class="type-text">
            <label for="consumer_key"><?= _('Consumer Key')?></label>
            <input readonly type="text" id="consumer_key"
                   value="<?= htmlReady($consumer->auth_key) ?>">
        </div>

        <div class="type-text">
            <label for="consumer_secret"><?= _('Consumer Secret')?></label>
            <input readonly type="text" id="consumer_secret"
                   value="<?= htmlReady($consumer->auth_secret) ?>">
        </div>
    <? endif; ?>

        <? if ($consumer->id): ?>
            <div class="centered">
                <?= strftime(_('Erstellt am %d.%m.%Y %H:%M:%S'), $consumer->mkdate) ?><br>
            <? if ($consumer->mkdate != $consumer->chdate): ?>
                <?= strftime(_('Zuletzt geändert am %d.%m.%Y %H:%M:%S'), $consumer->chdate) ?>
            <? endif; ?>
            </div>
        <? endif; ?>
    </fieldset>
    
    <fieldset>
        <legend><?= _('Applikation-Details') ?></legend>

        <div class="type-checkbox">
            <label for="commercial"><?= _('Kommerziell') ?></label>
            <input type="checkbox" class="switch" id="commercial" name="commercial" value="1"
                   <?= $consumer->commercial ? 'checked' : '' ?>>
        </div>

        <div class="type-text">
            <label for="description"><?= _('Beschreibung')?></label>
            <textarea id="description" name="description"
                ><?= htmlReady($consumer->description) ?></textarea>
        </div>

        <div class="type-text">
            <label for="url"><?= _('URL')?></label>
            <input type="text" id="url" name="url"
                   placeholder="http://appsite.tld"
                   value="<?= htmlReady($consumer->url) ?>">
        </div>

        <div class="type-select">
            <label for="type"><?= _('Typ')?></label>
            <select name="type" id="type">
                <option value="">- <?= _('Keine Angabe') ?> -</option>
            <? foreach ($types as $type => $label): ?>
                <option value="<?= $type ?>" <?= $consumer->type == $type ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <? endforeach; ?>
            </select>
        </div>

        <div class="type-text">
            <label for="notes"><?= _('Notizen')?></label>
            <textarea id="notes" name="notes"
                ><?= htmlReady($consumer->notes) ?></textarea>
        </div>
    </fieldset>

    <div class="type-button">
        <?= Button::createAccept(_('speichern'), 'store') ?>
        <?= LinkButton::createCancel(_('abbrechen'), $controller->url_for('admin/api')) ?>
    </div>
</form>