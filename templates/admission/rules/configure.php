<label for="message" class="caption">
    <?= _('Nachricht bei fehlgeschlagener Anmeldung') ?>:
</label>
<textarea name="message" rows="4" cols="50"><?= $rule->getMessage() ?></textarea>
<br/>
<label for="start_date" class="caption">
    <?= _('G�ltigkeitszeitraum der Regel') ?>:
</label>
<div class="form_group">
    <?= _('von') ?>
    <input type="text" size="8" maxlength="10" name="start_date" 
        id="start_date" value="<?= $rule->getStartTime() ? 
        date('d.m.Y', $rule->getStartTime()) : '' ?>" data-max-date=""/>
    <?= _('bis') ?>
    <input type="text" size="8" maxlength="10" name="end_date" 
        id="end_date" value="<?= $rule->getEndTime() ? 
        date('d.m.Y', $rule->getEndTime()) : '' ?>" data-min-date=""/>
    <script>
        $('#start_date').datepicker();
        $('#end_date').datepicker();
    </script>
</div>
