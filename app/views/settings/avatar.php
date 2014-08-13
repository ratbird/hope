<form enctype="multipart/form-data" action="<?= $controller->url_for('settings/avatar/upload') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="default nohover" id="edit_avatar">
        <colgroup>
            <col width="40%">
            <col width="60%">
        </colgroup>
        <tbody>
            <tr>
                <td style="text-align: center;">
                    <?= Avatar::getAvatar($user->user_id)->getImageTag(Avatar::NORMAL) ?><br><br>
                </td>
                <td>
                    <?= MessageBox::info(_('ACHTUNG!'), 
                                         array(sprintf(_('Die Bilddatei darf max. %d KB groß sein, es sind nur Dateien mit den Endungen %s, %s oder %s erlaubt!'), 
                                                       Avatar::MAX_FILE_SIZE / 1024,
                                                       '<b>.jpg</b>', '<b>.png</b>', '<b>.gif</b>'))) ?>

                    <br>

                    <?= _('1. Wählen Sie mit <b>Durchsuchen</b> eine Bilddatei von Ihrer Festplatte aus.') ?><br><br>
                    <input name="imgfile" type="file" style="width: 80%" accept="image/gif,image/png,image/jpeg"><br><br>

                    <?= _('2. Klicken Sie auf <b>absenden</b>, um das Bild hochzuladen.') ?><br><br>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align: center;">
                <? if ($customized): ?>
                    <?= Studip\Button::createCancel(_('Aktuelles Bild löschen'), 'reset') ?>
                <? endif; ?>
                </td>
                <td style="text-align: center;">
                    <?= Studip\Button::createAccept(_('Absenden'), 'upload') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
