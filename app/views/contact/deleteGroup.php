<form class="studip_form" method="post" action="<?= $controller->url_for('contact/deleteGroup/'.$group->id) ?>">
    <? CSRFProtection::tokenTag() ?>
    <p>
        <?= sprintf(_('Gruppe %s wirklich löschen?'), htmlReady($group->name)) ?>
    </p>
    <div data-dialog-button>
        <?= Studip\Button::create(_('Löschen'), 'delete') ?>
    </div>
</form>