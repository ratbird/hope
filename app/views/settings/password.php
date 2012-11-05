<? use Studip\Button; ?>

<form id="edit_password" method="post" action="<?= $controller->url_for('settings/password/store') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="zebra-hover settings">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="2"><?= _('Passwort ändern') ?></th>
            </tr>
        </thead>
        <tbody class="maxed">
            <tr>
                <td>
                    <label for="password"><?= _('Aktuelles Passwort') ?></label>
                </td>
                <td>
                    <input required type="password" id="password" name="password">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="new_password"><?= _('Neues Passwort') ?></label>
                </td>
                <td>
                    <input required type="password" pattern=".{4,}"
                           id="new_password" name="new_password"
                           data-message="<?= _('Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.') ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="new_password_confirm"><?= _('Passwort bestätigen') ?></label>
                </td>
                <td>
                    <input required type="password" pattern=".{4,}"
                           id="new_password_confirm" name="new_password_confirm"
                           data-must-equal="#new_password">
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">
                    <?= Button::create(_('Übernehmen'), 'store', array('title' => _('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
