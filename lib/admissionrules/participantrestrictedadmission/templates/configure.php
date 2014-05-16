<h3><?= $rule->getName() ?></h3>
<label for="start" class="caption">
    <?= _('Zeitpunkt der automatischen Platzverteilung') ?>:
</label>
<div class="form_group">
    <input type="date" name="distributiondate" id="distributiondate" size="8"
        value="<?= $rule->getDistributionTime() ? date('d.m.Y', $rule->getDistributionTime()) : '' ?>"/>
    &nbsp;&nbsp;
    <input type="time" name="distributiontime" id="distributiontime" size="4"
        value="<?= $rule->getDistributionTime() ? date('H:i', $rule->getDistributionTime()) : '23:59' ?>"/>
</div>
<? if ($rule->isFCFSallowed()) : ?>
    <label for="enable_FCFS">
    <input type="checkbox" id="enable_FCFS"  name="enable_FCFS" value="1" <?= (!is_null($rule->getDistributionTime()) && !$rule->getDistributionTime() ? "checked" : ""); ?>>
    <?=_("<u>Keine</u> automatische Platzverteilung (Windhund-Verfahren)")?>
    </label>
<? endif ?>
<script>
    $('#distributiondate').datepicker();
    $('#distributiontime').timepicker();
</script>