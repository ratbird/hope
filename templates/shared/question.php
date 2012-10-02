<?
# Lifter010: TODO
    use Studip\LinkButton;
?>
<div class="modaloverlay">
    <div class="messagebox">
        <span id="modalquestion">
            <?= formatReady($question) ?>
        </span>
        <div>
            <?= LinkButton::createAccept(_('JA!'), $approvalLink) ?>
            <?= LinkButton::createCancel(_('NEIN!'), $disapprovalLink) ?>
        </div>
    </div>    
</div>
