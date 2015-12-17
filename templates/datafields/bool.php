<input type="hidden" name="<?= $name ?>[<?= $model->id ?>]" value="0">
<input type="checkbox" name="<?= $name ?>[<?= $model->id ?>]"
       value="1" id="<?= $name ?>_<?= $model->id ?>"
       <? if ($value) echo 'checked'; ?>
       <? if ($model->is_required) echo 'required'; ?>>