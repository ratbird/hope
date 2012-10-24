<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<div class="modaloverlay">
    <div class="messagebox">
        <div class="content">
            <?= formatReady($data['question']) ?>
        </div>
        <div>
            <form action="<?= $data['action'] ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
                <? if($data['users']) :?>
                <div style="margin-top: 0.5em; text-align: left;">
                    <ul>
                    <?foreach($data['users'] as $user) :?>
                        <li>
                            <?= htmlReady($user['Vorname']) ?> <?= htmlReady($user['Nachname']) ?> (<?= htmlReady($user['username']) ?>)
                            <input type="hidden" name="user_ids[]" value="<?= $user['user_id'] ?>">
                        </li>
                    <?endforeach?>
                    </ul>
                </div>
                <? endif ?>
                <div style="margin-top: 0.5em; text-align: left;">
                    <input id="documents" name="documents" value="1" checked type="checkbox">
                    <label style="padding-left:0.5em" for="documents"><?= _("Dokumente löschen?") ?></label>
                </div>
                <div style="margin-top: 0.5em; text-align: left;">
                    <input id="mail" name="mail" value="1" checked type="checkbox">
                    <label style="padding-left:0.5em" for="mail"><?= _("Emailbenachrichtigung verschicken?") ?></label>
                </div>
                <div style="margin-top: 0.5em;">
                    <?= Button::createAccept(_('JA!'), 'delete', array('title' => _('Benutzer löschen')))?>
                    <span style="margin-left: 1em;">
                        <?= Button::createCancel(_('NEIN!'), 'back')?>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>