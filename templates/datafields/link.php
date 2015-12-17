<input type="url" name="<?= $name ?>[<?= $model->id ?>]"
       value="<?= htmlReady($value) ?>" id="<?= $name ?>_<?= $model->id ?>"
       size="30" placeholder="http://"
       <? if ($model->is_required) echo 'required'; ?>>
