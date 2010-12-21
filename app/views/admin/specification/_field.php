<?php
    $fields = Request::getArray('fields');
    $order = Request::getArray('order');
?>
<tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
    <td><?= htmlReady($name) ?></td>
    <td>
        <input type="text" max="3" size="3" name="order[<?= $id ?>]" value="<?= 0 + (($order and isset($order[$id])) ? $order[$id] : @$rule['order'][$id]) ?>">
    </td>
    <td>
        <input type="hidden" name="fields[<?= $id ?>]" value="0" />
        <input type="checkbox"
               name="fields[<?= $id ?>]"
               value="1"
               <?= (($fields and isset($fields[$id])) ? $fields[$id] : @$rule['attributes'][$id]) ? 'checked="checked"' : '' ?> />
    </td>
</tr>
