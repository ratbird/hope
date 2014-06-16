<form class="studip_form">
    <h3><?=(_('Ihr Dateibereich ist gesperrt worden'))?></h3>
    <fieldset>
        <legend><?=(_('Grund:'))?></legend>
        <?= htmlReady($message)?>
        <br><br>
        <?= _('Bei Fragen kontaktieren Sie den Support: ')?>
        <?= $support?>
    </fieldset>
</form>
