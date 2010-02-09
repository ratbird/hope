<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <?= formatReady($question) ?>
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
