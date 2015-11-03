<form class="studip_form" action="<?= $controller->url_for('course/wizard/process', $stepnumber, $temp_id) ?>" method="post">
    <h1><?= _('Anlegen der Veranstaltung') ?></h1>
    <?php if ($dialog) : ?>
        <?= MessageBox::info(_('Sie haben alle ben�tigten Daten angegeben und '.
            'k�nnen nun die Veranstaltung anlegen.')) ?>
    <?php else : ?>
        <?= MessageBox::info(_('Sie haben alle ben�tigten Daten angegeben und '.
            'k�nnen nun die Veranstaltung anlegen. Der n�chste Schritt f�hrt Sie '.
            'gleich in den Verwaltungsbereich der neu angelegten Veranstaltung, wo '.
            'Sie weitere Daten hinzuf�gen k�nnen.')) ?>
    <?php endif ?>
    <div style="clear: both; padding-top: 25px;">
        <? if ($source_course) : ?>
            <label>
            <input type="checkbox" checked name="copy_basic_data" value="1">
            <?=sprintf(_("Alle Grunddaten der Ursprungsveranstaltung (%s) kopieren"),
                '<a data-dialog href="' . URLHelper::getLink('dispatch.php/course/details', array('sem_id' => $source_course->id)) . '">' . htmlReady($source_course->getFullname()) . '</a>')?>
            </label>
        <? endif ?>
        <input type="hidden" name="step" value="<?= $stepnumber ?>"/>
        <?php if ($dialog) : ?>
        <input type="hidden" name="dialog" value="1"/>
        <?php endif ?>
        <?= Studip\Button::create(_('Zur�ck'), 'back',
            $dialog ? array('data-dialog' => 'size=50%', 'data-dialog-button' => true) : array()) ?>
        <?= Studip\Button::createAccept(_('Veranstaltung anlegen'), 'create',
            $dialog ? array('data-dialog-button' => true) : array()) ?>
    </div>
</form>