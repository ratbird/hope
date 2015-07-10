<form class="studip_form" action="<?= $controller->url_for('admin/coursewizardsteps/save', $step->id) ?>" method="post">
    <label class="caption">
        <?= _('Name des Schrittes') ?>:
        <input type="text" name="name" size="50" maxlength="255" value="<?= htmlReady($step->name) ?>" required/>
    </label>
    <label class="caption" for="classname">
        <?= _('PHP-Klasse') ?>:
        <input type="text" name="classname" size="50" maxlength="255" value="<?=
            htmlReady($step->classname) ?>" required/>
    </label>
    <? if ($availableClasses && count($availableClasses)) : ?>
    <div>
        <ul class="clean">
            <? foreach ($availableClasses as $className) : ?>
            <li>
                <a href="#" onClick="jQuery('input[name=classname]').val('<?= htmlReady($className) ?>');">
                    <?= Assets::img("icons/16/black/arr_2up", array('class' => "text-bottom")) ?>
                    <?= htmlReady($className) ?>
                </a>
            </li>
            <? endforeach ?>
        </ul>
    </div>
    <? endif ?>
    <label class="caption">
        <?= _('Nummer des Schritts im Assistenten') ?>:
        <input type="number" name="number" size="4" maxlength="2" value="<?= $step->number ?>" required/>
    </label>
    <label class="caption">
        <input type="checkbox" name="enabled"<?= $step->enabled ? ' checked="checked"' : '' ?>/>
        <?= _('Schritt ist aktiv') ?>
    </label>
    <?= CSRFProtection::tokenTag() ?>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'submit') ?>
        <?= Studip\Button::createCancel(_('Abbrechen'), 'cancel', array('data-dialog' => 'close')) ?>
    </div>
</form>