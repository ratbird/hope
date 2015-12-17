<div style="white-space: nowrap;">
    <input type="text" name="<?= $name ?>[<?= $model->id ?>][]"
           value="<?= $values[0] ?>" title="<?= _('Stunden') ?>"
           maxlength="2" size="1"
           <? if ($model->is_required) echo 'required'; ?>>
    :
    <input type="text" name="<?= $name ?>[<?= $model->id ?>][]"
           value="<?= $values[1] ?>" title="<?= _('Minuten') ?>"
           maxlength="2" size="1"
           <? if ($model->is_required) echo 'required'; ?>>
</div>
