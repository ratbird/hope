<div style="text-align: left;">
    <a href="javascript:" onClick="STUDIP.Raumzeit.toggleCheckboxes('<?= $tpl['cycle_id'] ?: 'irregular' ?>')" style="margin-right: 15px">
        <?= _('Alle auswählen/abwählen') ?>
    </a>
    <select name="checkboxAction">
        <option style="font-weight: bold;"><?= _('ausgewählte Termine...') ?></option>
        <? if ($tpl['cycle_id']) : ?>
            <option value="cancel"><?= _('ausfallen lassen') ?></option>
            <option value="takeplace"><?= _('stattfinden lassen') ?></option>
        <? else : ?>
            <option value="delete"><?= _('löschen') ?></option>
        <? endif ?>
        <option value="edit"><?= _('bearbeiten') ?></option>
    </select>
    <?= Studip\Button::create(_('Ausführen')) ?>
</div>
