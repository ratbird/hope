<?
# Lifter010: TODO
?>
<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <span id="modalquestion">
            <?= formatReady($question) ?>
        </span>
        <div style="margin-top: 1em;">
            <a href="<?= $approvalLink ?>">
                <?= makebutton('ja') ?>
            </a>
            <a href="<?= $disapprovalLink ?>" style="margin-left: 1em;">
                <?= makebutton('nein') ?>
            </a>
        </div>
    </div>
</div>