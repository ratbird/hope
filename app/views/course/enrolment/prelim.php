<div>
    <?=_("Um endg�ltig in die Veranstaltung aufgenommen zu werden, m�ssen Sie noch weitere Voraussetzungen erf�llen.")?>
</div>
<div>
    <?=_("Lesen Sie bitte folgenden Hinweistext:")?>
</div>
<br>
<div>
    <?=formatReady($admission_prelim_txt)?>
</div>
<? if ($admission_prelim_comment) : ?>
    <br>
    <label for="admission_comment">
        <?=_("Bemerkungen zu Teilnahmevoraussetzungen:")?>
    </label>
    <br>
    <textarea name="admission_comment" id="admission_comment" cols="50" rows="5"></textarea>
<? endif ?>
