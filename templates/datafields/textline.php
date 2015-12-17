<input type="text" name="<?= $name ?>[<?= $model->id ?>]"
       value="<?= htmlReady($value) ?>" id="<?= $name ?>_<?= $model->id ?>"
       <? if ($model->is_required) echo 'required'; ?>>
