<? foreach ($type_param as $pkey => $pval): ?>
<label>
    <input type="radio" name="<?= $name ?>[<?= $model->id ?>]"
           value="<?= $is_assoc ? (string) $pkey : $pval ?>"
           <? if ($value === ($is_assoc ? (string)$pkey : $pval)) echo 'checked'; ?>
           <? if ($model->is_required) echo 'required'; ?>>
    <?= htmlReady($pval) ?>
</label>
<? endforeach; ?>