<?
# Lifter010: TODO
    use Studip\LinkButton;
?>
<div class="modaloverlay">
    <div class="messagebox">
        <div class="content">
            <?= formatReady($question) ?>
        </div>
        <div class="buttons">
            <?= LinkButton::createAccept(_('JA!'), $approvalLink) ?>
            <?= LinkButton::createCancel(_('NEIN!'), $disapprovalLink) ?>
        </div>
    </div>    
</div>
