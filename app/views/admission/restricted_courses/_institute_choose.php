<form action="?" method="post" name="institute_choose">
<?= CSRFProtection::tokenTag() ?>
    <h4>
    <?=_("Bitte wählen Sie eine Einrichtung aus:")?>
    </h4>
    <select name="choose_institut_id" style="vertical-align:middle;">
    <? while (list($institut_id,$institute) = each($my_inst)) : ?>
    <option <?=($current_institut_id == $institut_id ? 'selected' : '')?> <?=($institute["is_fak"] ? 'style="font-weight:bold;"' : "") ?> value="<?= $institut_id?>">
    <?= htmlReady($institute["name"] . ' (' . $institute["num_sem"] . ')');?>
    </option>
    <? if ($institute["is_fak"] == 'all') : ?>
        <? $num_inst = $institute["num_inst"]; for ($i = 0; $i < $num_inst; ++$i) : ?>
            <? list($institut_id,$institute) = each($my_inst);?>
            <option <?=($current_institut_id == $institut_id ? 'selected' : '')?> value="<?= $institut_id?>">
            &nbsp;&nbsp;<?= htmlReady($institute["name"] . ' (' . $institute["num_sem"] . ')');?>
            </option>
        <? endfor ?>
    <? endif ?>
    <? endwhile ?>
    </select>
    &nbsp;
    <?=SemesterData::GetSemesterSelector(array('name'=>'select_semester_id', 'style'=>'vertical-align:middle;'), $current_semester_id, 'semester_id', false)?>
    <?= Studip\Button::create(_('Auswählen'), 'choose_institut', array('title' => _("Einrichtung auswählen"))) ?>
    <div>
    <b><?=_("Präfix des Veranstaltungsnamens / Nummer:")?></b>
    <input type="text" name="sem_name_prefix" value="<?=htmlReady($sem_name_prefix)?>" style="vertical-align:middle;" size="20">
    </div>
</form>
