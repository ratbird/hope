<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if ($deputies): ?>
    <form method="post" action="<?= $controller->url_for('settings/deputies/store') ?>">
        <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
        <?= CSRFProtection::tokenTag() ?>

        <table class="default no-hover">
            <caption>
                <?= _('Standardvertretungen') ?>
            </caption>
            <colgroup>
                <col>
                <? if ($edit_about_enabled): ?>
                    <col width="200px">
                <? endif; ?>
                <col width="100px">
            </colgroup>
            <thead>
            <tr>
                <th><?= _('Nutzer'); ?></th>
                <? if ($edit_about_enabled): ?>
                    <th><?= _('darf mein Profil bearbeiten'); ?></th>
                <? endif; ?>
                <th><?= _('l�schen'); ?></th>
            </tr>
            </thead>
            <tbody>
            <? foreach ($deputies as $deputy): ?>
                <tr>
                    <td>
                        <?= Avatar::getAvatar($deputy['user_id'])->getImageTag(Avatar::SMALL) ?>
                        <?= htmlReady($deputy['fullname'] . ' (' . $deputy['username'] . ', ' . _('Status') . ': ' . $deputy['perms'] . ')') ?>
                    </td>
                    <? if ($edit_about_enabled): ?>
                        <td align="center">
                            <label>
                                <input type="radio" name="edit_about[<?= $deputy['user_id'] ?>]" value="1"
                                    <? if ($deputy['edit_about']) echo 'checked'; ?>>
                                <?= _('ja') ?>
                            </label>

                            <label>
                                <input type="radio" name="edit_about[<?= $deputy['user_id'] ?>]" value="0"
                                    <? if (!$deputy['edit_about']) echo 'checked'; ?>>
                                <?= _('nein') ?>
                            </label>
                        </td>
                    <? endif; ?>
                    <td align="center">
                        <input type="checkbox" name="delete[]" value="<?= $deputy['user_id'] ?>">
                    </td>
                </tr>
            <? endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="<?= 2 + (int)$edit_about_enabled ?>">
                    <?= Button::create(_('�bernehmen'), 'store', array('title' => _('�nderungen speichern'))) ?>
                </td>
            </tr>
            </tfoot>
        </table>
    </form>
<? else: ?>
    <?= MessageBox::info(_('Sie haben noch niemanden als Ihre Standardvertretung eingetragen. Benutzen Sie obige Personensuche, um dies zu tun.')); ?>
<? endif; ?>
