<div style="white-space: nowrap;">
    <input type="text" name="<?= $name ?>[<?= $model->id ?>][]"
           maxlength="2" size="1"
           value="<? if ($value) echo date('d', $timestamp); ?>"
           title="<?= _('Tag') ?>"
           <? if ($model->is_required) echo 'required'; ?>>.

    <select name="<?= $name ?>[<?= $model->id ?>][]" title="<?= _('Monat') ?>"
            <? if ($model->is_required) echo 'required'; ?>>
        <option value=""></option>
    <? for ($i = 0; $i < 12; $i += 1): ?>
        <option value="<?= $i + 1 ?>"
                <? if ($value && date('n', $timestamp) == $i + 1) echo 'selected'; ?>>
            <?= studip_utf8decode(strftime('%B', strtotime('Januar 1st +' . $i . ' months'))) ?>
        </option>
    <? endfor;?>
    </select>

    <input type="text" name="<?= $name ?>[<?= $model->id ?>][]"
           maxlength="4" size="3"
           value="<? if ($value) echo date('Y', $timestamp); ?>"
           title="<?= _('Jahr') ?>"
           <? if ($model->is_required) echo 'required'; ?>>
</div>
