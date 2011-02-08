<?
# Lifter010: TODO
?>
<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <?= formatReady($data['question']) ?>
        <div style="margin-top: 0.5em;">
            <form action="<?= $data['action'] ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
                <? if($data['users']) :?>
                <div style="margin-top: 0.5em; text-align: left;">
                    <ul>
                    <?foreach($data['users'] as $user) :?>
                        <li>
                            <?= $user['Vorname'] ?> <?= $user['Nachname'] ?> (<?= $user['username'] ?>)
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
                    <?= makeButton('ja', 'input', _('Benutzer löschen'), 'delete') ?>
                    <span style="margin-left: 1em;">
                        <?= makeButton('nein', 'input', _('abbrechen'), 'back') ?>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>