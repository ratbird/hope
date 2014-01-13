<form action="?" method="post" name="institute_choose">
<?= CSRFProtection::tokenTag() ?>
    <div style="font-weight:bold">
    <?=_("Einrichtung:")?>
    </div>
    <select name="choose_institut_id" style="vertical-align:middle;">
    <? while (list($institut_id,$institute) = each($myInstitutes)) : ?>
    <option <?=($current_institut_id == $institut_id ? 'selected' : '')?> <?=($institute["is_fak"] ? 'style="font-weight:bold;"' : "") ?> value="<?= $institut_id?>">
    <?= htmlReady(my_substr($institute["name"] . ' (' . $institute["num_sets"] . ')',0,100));?>
    </option>
    <? if ($institute["is_fak"] == 'all') : ?>
        <? $num_inst = $institute["num_inst"]; for ($i = 0; $i < $num_inst; ++$i) : ?>
            <? list($institut_id,$institute) = each($myInstitutes);?>
            <option <?=($current_institut_id == $institut_id ? 'selected' : '')?> value="<?= $institut_id?>">
            &nbsp;&nbsp;<?= htmlReady(my_substr($institute["name"] . ' (' . $institute["num_sets"] . ')',0,100));?>
            </option>
        <? endfor ?>
    <? endif ?>
    <? endwhile ?>
    </select>
    <div style="font-weight:bold">
    <?=_("Präfix des Namens:")?>
    </div>
    <div>
    <input type="text" name="set_name_prefix" value="<?=htmlReady($set_name_prefix)?>" size="40">
    </div>
    <div style="font-weight:bold">
    <b><?=_("Enthaltene Regeln:")?> </b>
    </div>
    <div>
    <? foreach ($ruleTypes as $type => $detail) : ?>
        <label>
        <input type="checkbox" name="choose_rule_type[<?= $type?>]" <?=(isset($current_rule_types[$type]) ? 'checked' : '')?> value="1">
        <?= htmlReady($detail['name']);?>
        </label>
    <? endforeach; ?>
    </div>
    <div>
    <?= Studip\Button::create(_('Auswählen'), 'choose_institut', array('title' => _("Einrichtung auswählen"))) ?>
    </div>
</form>
