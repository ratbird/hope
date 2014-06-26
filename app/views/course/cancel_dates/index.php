<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<?= $form->getFormStart($controller->url_for('course/cancel_dates')) ?>
<h3>
<?=_("Folgende Veranstaltungstermine ausfallen lassen")?>:
</h3>
<div style="padding: 5px; margin: 5px;font-weight: bold;">
<? echo join(', ', array_map(function ($d) {return $d->toString();}, $dates)); ?>
</div>
<div style="font-weight:bold;padding-top:5px;">
<?= $form->getFormFieldCaption('comment')?>:
</div>
    <div>
        <div>
        <?= _("Wenn Sie die nicht stattfindenden Termine mit einem Kommentar versehen, werden die Ausfalltermine im Ablaufplan weiterhin dargestellt und auch im Terminkalender eingeblendet.")?>
        </div>
        <div>
        <?= $form->getFormField('comment') ?>
        </div>
    </div>
<div style="font-weight:bold;padding-top:5px;">
    <?= $form->getFormFieldCaption('snd_message')?>:
    <?= $form->getFormField('snd_message') ?>
</div>

<div style="text-align:center" data-dialog-button>
     <?= $form->getFormButton('save_close')?>
</div>
<? if ($issue_id) : ?>
    <input type="hidden" name="issue_id" value="<?= $issue_id ?>">
<? else : ?>
    <input type="hidden" name="termin_id" value="<?= $dates[0]->getTerminId() ?>">
<? endif ?>
<?= $form->getFormEnd() ?>