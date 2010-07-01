<?php
if ($input['type'] === "text") : ?>
    <input type="text" name="<?= $input['name'] ?>" value="<?= $input['value'] ?>" style="width: 80%">
<? endif;

if ($input['type'] === "textarea") : ?>
    <textarea name="<?= $input['name'] ?>" style="width: 80%; height: 100px;" class=""><?= 
        $input['value'] 
    ?></textarea>
<? endif;

if ($input['type'] === "select") : ?>
    <select name="<?= $input['name'] ?>" style="width: 80%">
    <? if ($input['choices']) : foreach ($input['choices'] as $choice_value => $choice_name) : ?>
        <option value="<?= $choice_value ?>"<? 
            if ($choice_value == $input['value']) print " selected" 
            ?>><?= $choice_name ?></option>
    <? endforeach; endif; ?>
    </select>
<? endif;

if ($input['type'] === "multiselect") : ?>
    <select name="<?= $input['name'] ?>" style="width: 80%" multiple size="8">
    <? if ($input['choices']) : foreach ($input['choices'] as $choice_value => $choice_name) : ?>
        <option value="<?= $choice_value ?>"<?= 
            in_array($choice_value, is_array($input['value']) ? $input['value'] : array($input['value'])) 
            ? " selected" 
            : "" ?>><?= $choice_name ?></option>
    <? endforeach; endif; ?>
    </select>
<? endif;

if ($input['type'] === "datafield") {
    print $input['value']; 
}
