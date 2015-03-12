<form class="studip_form" method="post">
    <fieldset>
        <legend><?= htmlReady($aux->name) ?></legend>
        <p><?= formatReady($aux->description) ?></p>
    <? foreach ($datafields as $field): ?>
        <? if ($field->getTypedDatafield()->isVisible() && $field->getTypedDatafield()->isEditable()): ?>
            <? $editable = true; ?>
            <label><?= $field->name ?></label>
                <?= $field->getTypedDatafield()->getHTML('aux'); ?>

        <? endif; ?>
    <? endforeach; ?>
    <? if ($editable): ?>
        <?= \Studip\Button::create(_('Speichern'), 'save') ?>
    <? else: ?>
        <?= _('Keine einstellbaren Zusatzdaten vorhanden') ?>
    <? endif; ?>
    </fieldset>
</form>