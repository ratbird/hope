<select name="<?= $name ?>[<?= $model->id ?>]" id="<?= $name ?>_<?= $model->id ?>"
        <? if ($multiple) echo 'multiple'; ?>
        <? if ($model->is_required) echo 'required'; ?>>
<? foreach ($type_param as $pkey => $pval): ?>
    <option value="<?= $is_assoc ? (string)$pkey : $pval ?>"
            <? if ($value === ($is_assoc ? (string)$pkey : $pval)) echo 'selected'; ?>>
        <?= htmlReady($pval) ?>
    </option>
<? endforeach; ?>
</select>
