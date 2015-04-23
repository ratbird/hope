<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<?= $form->getFormStart($controller->url_for('course/block_appointments/index/' . $course_id)) ?>
<h3>
<?=_("Die Veranstaltung findet in folgendem Zeitraum statt")?>:
</h3>
<div style="border: 1px solid; padding: 5px; margin: 5px;">
    <div>
    <?= $form->getFormFieldCaption('start_day', array('style' => 'float:left;width:120px;'))?>
    <?= $form->getFormField('start_day')?>
    </div>
    <div>
    <?= $form->getFormFieldCaption('end_day', array('style' => 'float:left;width:120px;'))?>
    <?= $form->getFormField('end_day')?>
    </div>
</div>
<h3>
<?=_("Die Veranstaltung findet zu folgenden Zeiten statt")?>:
</h3>
<div style="border: 1px solid; padding: 5px; margin: 5px;">
    <div>
        <?= $form->getFormFieldCaption('start_time', array('style' => 'float:left;width:120px;'))?>
        <?= $form->getFormField('start_time')?>
    </div>
    <div>
        <?= $form->getFormFieldCaption('end_time', array('style' => 'float:left;width:120px;'))?>
        <?= $form->getFormField('end_time')?>
    </div>
</div>
<h3>
<?=_("Weitere Daten")?>:
</h3>
<div style="border: 1px solid; padding: 5px; margin: 5px;">
    <div>
        <?= $form->getFormFieldCaption('termin_typ', array('style' => 'float:left;width:120px;'))?>
        <?= $form->getFormField('termin_typ')?>
    </div>
    <div>
        <?= $form->getFormFieldCaption('room_text', array('style' => 'float:left;width:120px;'))?>
        <?= $form->getFormField('room_text')?>
    </div>
</div>
<h3>
<?=_("Mehrere Termine parallel anlegen")?>:
</h3>
<div style="border: 1px solid; padding: 5px; margin: 5px;">
    <?= $form->getFormFieldCaption('date_count', array('style' => 'float:left;width:120px;'))?>
    <?= $form->getFormField('date_count')?>
</div>
<h3>
<?=_("Die Veranstaltung findet an folgenden Tagen statt")?>:
</h3>
<?= $form->getFormField('days') ?>
<div style="text-align:center">
     <?= $form->getFormButton('save_close')?>
     <?= $form->getFormButton('preview')?>
     <?= $form->getFormButton('close')?>
</div>
<?= $form->getFormEnd() ?>