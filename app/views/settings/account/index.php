<? use Studip\Button; ?>

<?
    $genders = array(
        _('unbekannt'),
        _('männlich'),
        _('weiblich'),
    );
?>

<? if ($user->auth_plugin !== 'standard'): ?>
    <?= MessageBox::info(sprintf(_('Ihre Authentifizierung (%s) benutzt nicht die Stud.IP Datenbank, '
                                  .'daher können Sie einige Felder nicht verändern!'),
                                 $user->auth_plugin)) ?>
<? endif; ?>

<? if ($locked_info): ?>
    <?= MessageBox::info(formatLinks($locked_info)) ?>
<? endif; ?>

<form id="edit_userdata" method="post" name="pers"
      action="<?= $controller->url_for('settings/account/store') ?>"
      <? if (!$restricted) echo 'data-validate="true"'; ?>>
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="zebra-hover settings">
        <colgroup>
            <col width="33%">
            <col width="33%">
            <col width="33%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3"><?= _('Benutzerkonto bearbeiten') ?></th>
            </tr>
        </thead>
        <tbody class="labeled maxed">
        <? if (!$restricted): ?>
            <tr>
                <td>
                    <label class="required" for="new_username"><?= _('Username:') ?></label>
                </td>
                <td>
                    <input required type="text" name="new_username" id="new_username"
                           pattern="<?= htmlReady(trim($validator->username_regular_expression, '/i^$()')) ?>"
                           data-message="<?= _('Der Benutzername ist unzulässig. Er muss mindestens 4 Zeichen lang sein und darf keine Sonderzeichen oder Leerzeichen enthalten.') ?>"
                           value="<?= $user['username'] ?>"
                           autocorrect="off" autocapitalize="off"
                           <? if (!$controller->shallChange('auth_user_md5.username')) echo 'disabled'; ?>>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <label class="required"><?= _('Name:') ?></label>
                </td>
                <td>
                    <label>
                        <?= _('Vorname:') ?><br>
                        <input required type="text" name="vorname"
                               pattern="<?= htmlReady(trim($validator->name_regular_expression, '/i^$()')) ?>"
                               value="<?= htmlReady($user['Vorname']) ?>"
                               <? if (!$controller->shallChange('auth_user_md5.Vorname', 'name')) echo 'disabled'; ?>>
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('Nachname:') ?><br>
                        <input required type="text" name="nachname"
                               pattern="<?= htmlReady(trim($validator->name_regular_expression, '/i^$()')) ?>"
                               data-message="<?= _('Bitte geben Sie Ihren tatsächlichen Nachnamen an.') ?>"
                               value="<?= htmlReady($user['Nachname']) ?>"
                               <? if (!$controller->shallChange('auth_user_md5.Nachname', 'name')) echo 'disabled'; ?>>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label class="required"><?= _('E-Mail:') ?></label>
                </td>
                <td>
                    <label>
                        <?= _('E-Mail:') ?><br>
                        <input required type="email" name="email1" id="email1"
                               value="<?= htmlReady($user['Email']) ?>"
                               <? if (!$controller->shallChange('auth_user_md5.Email')) echo 'disabled'; ?>>
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('E-Mail Wiederholung:') ?><br>
                        <input required type="email" name="email2" id="email2"
                               value="<?= htmlReady($user['Email']) ?>"
                               data-must-equal="#email1"
                               <? if (!$controller->shallChange('auth_user_md5.Email')) echo 'disabled'; ?>>
                    </label>
                </td>
            </tr>
        <? else: ?>
            <tr>
                <td><?= _('Username:') ?></td>
                <td><?= $user['username'] ?></td>
                <td rowspan="3" class="blank">
                    <?= MessageBox::error(_('Adminzugriff hier nicht möglich!')) ?>
                </td>
            </tr>
            <tr>
                <td><?= _('Name:') ?></td>
                <td>
                    <?= htmlReady($user['Vorname']) ?>
                    <?= htmlReady($user['Nachname']) ?>
                </td>
            </tr>
            <tr>
                <td><?= _('E-Mail:') ?></td>
                <td>
                    <?= formatReady($user['Email']) ?>
                </td>
            </tr>
        <? endif; ?>
            <tr>
                <td>
                    <label for="title_front"><?= _('Titel:') ?></label>
                </td>
                <td>
                    <select id="title_front_chooser" name="title_front_chooser"
                            aria-label="<?= _('Titel auswählen') ?>"
                            data-target="#title_front"
                            <? if (!$controller->shallChange('auth_user_md5.title_front', 'title')) echo 'disabled'; ?>>
                    <? foreach ($GLOBALS['TITLE_FRONT_TEMPLATE'] as $title): ?>
                        <option <? if ($user['title_front'] == $title) echo 'selected'; ?>>
                            <?= htmlReady($title) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="title_front" id="title_front"
                           data-target="#title_front_chooser"
                           value="<?= htmlReady($user['title_front']) ?>"
                           <? if (!$controller->shallChange('auth_user_md5.title_front', 'title')) echo 'disabled'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="title_rear"><?= _('Titel nachgest.:') ?></label>
                </td>
                <td>
                    <select name="title_rear_chooser" id="title_rear_chooser"
                            data-target="#title_rear"
                            <? if (!$controller->shallChange('auth_user_md5.title_rear', 'title')) echo 'disabled'; ?>>
                    <? foreach ($GLOBALS['TITLE_REAR_TEMPLATE'] as $title): ?>
                        <option <? if ($user['title_rear'] == $title) echo 'selected'; ?>>
                            <?= htmlReady($title) ?>
                        </option>
                    <? endforeach ; ?>
                    </select>
                </td>
                <td>
                    <input type="text" style="width: 98%;" name="title_rear" id="title_rear"
                           data-target="#title_rear_chooser"
                           value="<?= htmlReady($user['title_rear']) ?>"
                           <? if (!$controller->shallChange('auth_user_md5.title_rear', 'title')) echo 'disabled'; ?>>
                </td>
            </tr>
            <tr>
                <td><?= _('Geschlecht') ?></td>
                <td colspan="2">
                <? foreach ($genders as $index => $gender): ?>
                    <label>
                        <input type="radio" name="geschlecht" value="<?= $index ?>"
                               <? if ($user['geschlecht'] == $index) echo 'checked'; ?>
                               <? if (!$controller->shallChange('auth_user_md5.geschlecht', 'gender')) echo 'disabled'; ?>>
                        <?= htmlReady($gender) ?>
                    </label>
                <? endforeach; ?>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <?= Button::create(_('Übernehmen'), 'store', array('title' => _('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
