<?php
# Lifter010: TODO
$is_locked = $input['locked'] ? 'disabled readonly' : '';
if ($input['type'] === "text") : ?>
    <input <?=$is_locked ?> type="text" name="<?= $input['name'] ?>" value="<?= htmlReady($input['value']) ?>" style="width: 80%">
<? endif;

if ($input['type'] === "number") : ?>
    <input <?=$is_locked ?> type="number" name="<?= $input['name'] ?>" value="<?= htmlReady($input['value']) ?>" style="width: 80%" min="<?= $input['min'] ?>">
<? endif; 

if ($input['type'] === "textarea") : ?>
    <textarea <?=$is_locked ?> name="<?= $input['name'] ?>" style="width: 80%; height: 100px;" class=""><?=
        htmlReady($input['value'])
    ?></textarea>
<? endif;

if ($input['type'] === "select") : ?>
    <? if (!$input['choices'][$input['value']]) : ?>
        <?= _("Keine �nderung m�glich") ?>
    <? else : ?>
    <select <?=$is_locked ?> name="<?= $input['name'] ?>" style="width: 80%">
    <? if ($input['choices']) : foreach ($input['choices'] as $choice_value => $choice_name) : ?>
        <option value="<?= htmlReady($choice_value) ?>"<?
            if ($choice_value == $input['value']) print " selected"
            ?>><?= htmlReady($choice_name) ?></option>
    <? endforeach; endif; ?>
    </select>
    <? endif ?>
<? endif;

if ($input['type'] === "multiselect") : ?>
    <select <?=$is_locked ?> name="<?= $input['name'] ?>" style="width: 80%" multiple size="8">
    <? if ($input['choices']) : foreach ($input['choices'] as $choice_value => $choice_name) : ?>
        <option value="<?= htmlReady($choice_value) ?>"<?=
            in_array($choice_value, is_array($input['value']) ? $input['value'] : array($input['value']))
            ? " selected"
            : "" ?>><?= $choice_name ?></option>
    <? endforeach; endif; ?>
    </select>
<? endif;

if ($input['type'] === "datafield"):?>
        <div style="float:left;padding-right:0.5em;">
            <?=$input['locked'] ? $input['display_value'] : $input['html_value'];?>
        </div>
        <?if($input['description']):?>
            <?=tooltipIcon(_($input['description']))?>
        <?endif?>
<?endif?>
