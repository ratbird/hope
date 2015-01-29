<? if ($type === 'integer'): ?>
    <input class="allow-only-numbers" name="value" type="number"
           id="item-value" value="<?= htmlReady($value)?>">
<? elseif ($type === 'boolean'): ?>
    <input type="hidden" name="value" value="0">
    <input type="checkbox" name="value" value="1" id="item-value"
           class="studip-checkbox"
           <? if ($value) echo 'checked'; ?>>
    <label for="item-value"><?= _('aktiviert') ?></label>
<? elseif ($type === 'array') : ?>
    <?php $v = version_compare(PHP_VERSION, '5.4.0', '>=') ? studip_utf8decode(json_encode(studip_utf8encode($value),JSON_UNESCAPED_UNICODE)) : json_encode(studip_utf8encode($value)) ?>
    <textarea cols="80" rows="5" name="value" id="item-value"><?= htmlReady($v, true, true)?></textarea>
<? else: ?>
    <textarea cols="80" rows="3" name="value" id="item-value"><?= htmlReady($value)?></textarea>
<? endif; ?>
