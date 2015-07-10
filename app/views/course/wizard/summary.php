<form class="studip_form" action="<?= $controller->url_for('course/wizard/process', $stepnumber, $temp_id) ?>" method="post">
    <h1><?= _('Anlegen der Veranstaltung') ?></h1>
    <?php if ($dialog) : ?>
        <?= MessageBox::info(_('Sie haben alle benötigten Daten angegeben und '.
            'können nun die Veranstaltung anlegen.')) ?>
    <?php else : ?>
        <?= MessageBox::info(_('Sie haben alle benötigten Daten angegeben und '.
            'können nun die Veranstaltung anlegen. Der nächste Schritt führt Sie '.
            'gleich in den Verwaltungsbereich der neu angelegten Veranstaltung, wo '.
            'Sie weitere Daten hinzufügen können.')) ?>
    <?php endif ?>
    <div style="clear: both; padding-top: 25px;">
        <input type="hidden" name="step" value="<?= $stepnumber ?>"/>
        <?php if ($dialog) : ?>
        <input type="hidden" name="dialog" value="1"/>
        <?php endif ?>
        <?= Studip\Button::create(_('Zurück'), 'back',
            $dialog ? array('data-dialog' => 'size=50%', 'data-dialog-button' => true) : array()) ?>
        <?= Studip\Button::createAccept(_('Veranstaltung anlegen'), 'create',
            $dialog ? array('data-dialog-button' => true) : array()) ?>
    </div>
</form>