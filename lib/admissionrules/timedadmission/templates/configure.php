<h3><?= $rule->getName() ?></h3>
<label for="message" class="caption">
    <?= _('Nachricht bei fehlgeschlagener Anmeldung') ?>:
</label>
<textarea name="message" rows="4" cols="50"><?= $rule->getMessage() ?></textarea>
<br/>
<label for="startdate" class="caption">
    <?= _('Start des Anmeldezeitraums') ?>:
</label>
<div class="form_group">
    <input type="text" size="8" maxlength="10" name="startdate" 
        id="startdate" value="<?= $rule->getStartTime() ? 
        date('d.m.Y', $rule->getStartTime()) : '' ?>" data-max-date=""/>
    &nbsp;&nbsp;
    <input type="text" name="starttime" id="starttime" size="4"
        value="<?= $rule->getStartTime() ? date('H:i', $rule->getStartTime()) : '' ?>"/>
</div>
<br/>
<label for="enddate" class="caption">
    <?= _('Ende des Anmeldezeitraums') ?>:
</label>
<div class="form_group">
    <input type="text" size="8" maxlength="10" name="enddate" 
        id="enddate" value="<?= $rule->getEndTime() ? 
        date('d.m.Y', $rule->getEndTime()) : '' ?>" data-min-date=""/>
    &nbsp;&nbsp;
    <input type="text" name="endtime" id="endtime" size="4"
        value="<?= $rule->getEndTime() ? date('H:i', $rule->getEndTime()) : '' ?>"/>
</div>
<script>
    $('#startdate').datepicker();
    $('#starttime').timepicker();
    $('#enddate').datepicker();
    $('#endtime').timepicker();
</script>