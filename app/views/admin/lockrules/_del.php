<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<div class="modaloverlay">
    <div class="messagebox">
        <div class="content">
            <?= formatReady($question) ?>
        </div>
        <div class="buttons">
            <form action="<?=$action ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
                <?= Button::createAccept(_('JA!'), 'kill', array('title' => _('Sperrebene löschen')))?>
                <span style="margin-left: 1em;">
                    <?= Button::createCancel(_('NEIN!'), 'cancel', array('title' => _('abbrechen')))?>
                </span>
            </form>
        </div>
    </div>
</div>