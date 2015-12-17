<textarea name="<?= $name ?>[<?= $model->id ?>]"
          id="<?= $name ?>_<?= $model->id ?>"
          rows="6" cols="58"
          <? if ($model->is_required) echo 'required'; ?>
><?= htmlReady($value) ?></textarea>