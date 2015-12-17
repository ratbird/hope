<input type="radio" name="<?= $name ?>[<?= $model->id ?>][combo]"
       value="select" id="combo_<?= $model->id ?>_select"
       <? if (in_array($value, $values)) echo 'checked'; ?>>
<select name="<?= $name ?>[<?= $model->id ?>][select]" onfocus="$('#combo_<?= $model->id ?>_select').prop('checked', true);">
<? foreach ($values as $v): ?>
    <option value="<?= htmlReady($v) ?>" <? if ($v === $value) echo 'selected'; ?>>
        <?= htmlReady($v) ?>
    </option>
<? endforeach; ?>
</select>

<input type="radio" name="<?= $name ?>[<?= $model->id ?>][combo]"
       value="text" id="combo_<?= $model->id ?>_text"
       <? if (!in_array($value, $values)) echo 'checked'; ?>>
<input name="<?= $name ?>[<?= $model->id ?>][text]"
       value="<? if (!in_array($value, $values)) echo htmlReady($value); ?>"
       onfocus="$('#combo_<?= $model->id ?>_text').prop('checked', true);">
