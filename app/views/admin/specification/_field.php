<?php
# Lifter010: TODO
    $fields = Request::getArray('fields');
    $order = Request::getArray('order');
?>
<tr>
    <td><?= htmlReady($name) ?> <? if ($required) : ?><span style="color: red; font-size: 1.6em">*</span><? endif ?></td>
    <td>
        <input type="text" size="3" name="order[<?= $id ?>]" value="<?= (int) (($order && isset($order[$id])) ? $order[$id] : @$rule['order'][$id]) ?>">
    </td>
    <td>
        <input type="hidden" name="fields[<?= $id ?>]" value="0" />
        <input type="checkbox"
               name="fields[<?= $id ?>]"
               value="1"
               <?= (($fields && isset($fields[$id])) ? $fields[$id] : @$rule['attributes'][$id]) ? 'checked="checked"' : '' ?> />
    </td>
</tr>
