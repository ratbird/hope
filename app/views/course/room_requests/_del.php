<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <?= formatReady($question) ?>
        <div style="margin-top: 0.5em;">
            <form action="<?=$action ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
                <div style="margin-top: 0.5em;">
                    <?= makeButton('ja', 'input', _('Raumanfrage löschen'), 'kill') ?>
                    <span style="margin-left: 1em;">
                        <?= makeButton('nein', 'input', _('abbrechen'), 'cancel') ?>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>