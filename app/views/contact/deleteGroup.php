<form class="studip_form" method="post" action="<?= $controller->url_for('contact/deleteGroup/'.$group->id) ?>">
    <? CSRFProtection::tokenTag() ?>
    <p>
        <?= sprintf(_('Gruppe %s wirklich l�schen?'), htmlReady($group->name)) ?>
    </p>
    <div data-dialog-button>
        <?= Studip\Button::create(_('L�schen'), 'delete') ?>
    </div>
</form>