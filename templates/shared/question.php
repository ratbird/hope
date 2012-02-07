<?
# Lifter010: TODO
    use Studip\LinkButton;
?>
<div class="modaloverlay"></div>
<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <span id="modalquestion">
            <?= formatReady($question) ?>
        </span>
        <div style="margin-top: 1em;">
            <?= LinkButton::createAccept(_('JA!'), $approvalLink) ?>
            <?= LinkButton::createCancel(_('NEIN!'), $disapprovalLink) ?>
        </div>
    </div>
</div>