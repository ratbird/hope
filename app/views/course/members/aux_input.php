<form class="studip_form" method="post">
    <? foreach ($datafields as $field): ?>
        <? if ($field->getTypedDatafield()->isVisible() && $field->getTypedDatafield()->isEditable()): ?>
            <? $editable = true; ?>
            <label><?= $field->name ?>
                <?= $field->getTypedDatafield()->getHTML('aux'); ?>
            </label>
        <? endif; ?>
    <? endforeach; ?>
    <? if ($editable): ?>
        <?= \Studip\Button::create(_('Speichern'), 'save') ?>
    <? else: ?>
        <?= _('Keine einstellbaren Zusatzdaten vorhanden') ?>
    <? endif; ?>
</form>