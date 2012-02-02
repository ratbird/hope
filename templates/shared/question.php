<?
# Lifter010: TODO
    use Studip\LinkButton;
?>
<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <span id="modalquestion">
            <?= formatReady($question) ?>
        </span>
        <div style="margin-top: 1em;">
            <?= LinkButton::createAccept(_('JA!'), $approvalLink) ?>
            <?= LinkButton::createCancel(_('NEIN!'), $approvalLink) ?>
        </div>
    </div>
</div>