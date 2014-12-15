<form class="studip_form" method="post" action="<?= $controller->url_for('contact/editGroup/'.$this->group->id) ?>">
    <? CSRFProtection::tokenTag() ?>
    <label>
        <?= _('Gruppenname') ?>
        <input type="text" name="name" placeholder="<?= _('Gruppenname') ?>" value='<?= htmlReady($this->group->name) ?>'>
    </label>
    <div data-dialog-button>
        <?= Studip\Button::create($this->group->isNew() ? _('Anlegen') : _('Speichern'), 'store') ?>
    </div>
</form>