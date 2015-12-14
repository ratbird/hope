<label for="message" class="caption">
    <?= _('Nachricht bei fehlgeschlagener Anmeldung') ?>:
    <?= (strpos($rule->getMessage(),'%s') ? tooltipicon(_("Die Zeichen %s sind ein Platzhalter für änderbare Bedingungen")) : '')?>
</label>
<textarea name="message" rows="4" cols="50"><?= htmlReady($rule->getMessage()) ?></textarea>
<br/>
<label for="start_date" class="caption">
    <?= _('Gültigkeitszeitraum der Regel') ?>:
</label>
<div class="form_group">
    <?= _('von') ?>
    <input type="text" size="16" maxlength="16" name="start_date"
        id="start_date" value="<?= $rule->getStartTime() ? 
        date('d.m.Y H:i', $rule->getStartTime()) : '' ?>" data-max-date=""
        placeholder="tt.mm.jjjj --:--"/>
    <?= _('bis') ?>
    <input type="text" size="16" maxlength="16" name="end_date"
        id="end_date" value="<?= $rule->getEndTime() ? 
        date('d.m.Y H:i', $rule->getEndTime()) : '' ?>" data-min-date=""
        placeholder="tt.mm.jjjj --:--"/>
    <script>
        $('#start_date').datetimepicker();
        $('#end_date').datetimepicker();
    </script>
</div>
