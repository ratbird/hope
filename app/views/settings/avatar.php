<? use Studip\Button; ?>

<table class="settings zebra" id="edit_avatar">
    <colgroup>
        <col width="50%">
        <col width="50%">
    </colgroup>
    <thead>
        <tr>
            <th colspan="2">
                <?= _('Auf dieser Seite können Sie ein Profilbild hochladen.') ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;">
                <?= _("Aktuell angezeigtes Bild:") ?><br><br>
                <?= Avatar::getAvatar($user->user_id)->getImageTag(Avatar::NORMAL) ?><br><br>
            </td>
            <td>
                <form enctype="multipart/form-data" action="<?= $controller->url_for('settings/avatar/upload') ?>" method="post">
                    <?= CSRFProtection::tokenTag() ?>
                    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
                
                    <?= _('Hochladen eines Bildes:') ?><br><br>
                    <?= _('1. Wählen Sie mit <b>Durchsuchen</b> eine Bilddatei von Ihrer Festplatte aus.') ?><br><br>
                    <input name="imgfile" type="file" style="width: 80%"><br><br>
                    <?= _('2. Klicken Sie auf <b>absenden</b>, um das Bild hochzuladen.') ?><br><br>
                    <?= Button::createAccept(_('Absenden'), 'upload') ?><br><br>
                    <b><?= _('ACHTUNG!') ?></b><br>
                    <?= sprintf (_('Die Bilddatei darf max. %d KB groß sein, es sind nur Dateien mit den Endungen %s, %s oder %s erlaubt!'), 
                                 Avatar::MAX_FILE_SIZE / 1024,
                                 '<b>.jpg</b>', '<b>.png</b>', '<b>.gif</b>') ?>
                </form>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
        <? if ($customized): ?>
            <td>
                <form id="delete_picture" method="post" action="<?= $controller->url_for('settings/avatar/reset') ?>">
                    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
                    <?= CSRFProtection::tokenTag() ?>
                    <?= Button::createCancel(_('Aktuelles Bild löschen'), 'reset') ?>
                </form>
            </td>
            <td>&nbsp;</td>
        <? else: ?>
            <td colspan="2">&nbsp;</td>
        <? endif; ?>
        </td>
    </tfoot>
</table>
