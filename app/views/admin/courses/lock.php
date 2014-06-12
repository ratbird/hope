<select name="lock_sem[<?=$semid?>]" style="max-width: 200px">
    <? for ($i = 0; $i < count($all_lock_rules); $i++) : ?>
        <option value="<?= $all_lock_rules[$i]["lock_id"] ?>"
        <?= ($all_lock_rules[$i]["lock_id"]==$values['lock_rule']) ?  'selected' :''?>>
            <?= htmlReady($all_lock_rules[$i]["name"]) ?>
        </option>
    <? endfor ?>
</select>