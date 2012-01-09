<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <?= formatReady($question) ?>
        <div style="margin-top: 0.5em;">
            <form action="<?=$action ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
                <div style="margin-top: 0.5em;">
                    <?= Button::createAccept(_('JA!'), 'kill', array('title' => _('Sperrebene löschen')))?>
                    <span style="margin-left: 1em;">
                        <?= Button::createCancel(_('NEIN!'), 'cancel', array('title' => _('abbrechen')))?>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>