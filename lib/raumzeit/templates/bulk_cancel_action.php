<?
use Studip\Button, Studip\LinkButton;
?>

<a name="Stapelaktionen"></a>

<div style="clear: both">
    <b><?= _('Die ausgewählten Termine ausfallen lassen:') ?></b>
    
</div>

<? include('lib/raumzeit/templates/cancel_action.php'); ?>
<div style="text-align: center">
    <div class="button-group">
        <?= Button::createAccept(_('Übernehmen')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'),
                URLHelper::getURL('?')) ?>
    </div>
</div>
<input type="hidden" name="checkboxAction" value="cancel">
<input type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
<input type="hidden" name="cmd" value="bulkAction">
