<? use Studip\Button; ?>

<?
    $genders = array(
        _('unbekannt'),
        _('m�nnlich'),
        _('weiblich'),
    );
?>

<? if ($user->auth_plugin !== 'standard'): ?>
    <?= MessageBox::info(sprintf(_('Ihre Authentifizierung (%s) benutzt nicht die Stud.IP Datenbank, '
                                  .'daher k�nnen Sie einige Felder nicht ver�ndern!'),
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
            <col width="20%">
            <col width="40%">
            <col width="40%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3"><?= _('Benutzerkonto bearbeiten') ?></th>
            </tr>
        </thead>
        <tbody class="maxed">
            <tr>
                <td>
                    <label for="new_username" <? if (!$restricted) echo 'class="required"'; ?>>
                        <?= _('Nutzername:') ?>
                        <? if ($restricted) : ?>
                            <?= tooltipIcon('Dieses Feld d�rfen Sie nicht �ndern, Adminzugriff ist hier nicht erlaubt!') ?>
                        <? endif ?>
                    </label>
                </td>
                <td>
                    <input required type="text" name="new_username" id="new_username"
                           pattern="<?= htmlReady(trim($validator->username_regular_expression, '/i^$()')) ?>"
                           data-message="<?= _('Der Benutzername ist unzul�ssig. Er muss mindestens 4 Zeichen lang sein und darf keine Sonderzeichen oder Leerzeichen enthalten.') ?>"
                           value="<?= $user['username'] ?>"
                           autocorrect="off" autocapitalize="off"
                           <? if ($restricted || !$controller->shallChange('auth_user_md5.username')) echo 'disabled'; ?>>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <label <? if (!$restricted) echo 'class="required"'; ?>>
                        <?= _('Name:') ?>
                        <? if ($restricted) : ?>
                            <?= tooltipIcon('Dieses Feld d�rfen Sie nicht �ndern, Adminzugriff ist hier nicht erlaubt!') ?>
                        <? endif ?>
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('Vorname:') ?>
                        <br>
                        <input required type="text" name="vorname"
                               pattern="<?= htmlReady(trim($validator->name_regular_expression, '/i^$()')) ?>"
                               value="<?= htmlReady($user['Vorname']) ?>"
                               <? if ($restricted || !$controller->shallChange('auth_user_md5.Vorname', 'name')) echo 'disabled'; ?>>
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('Nachname:') ?><br>
                        <input required type="text" name="nachname"
                               pattern="<?= htmlReady(trim($validator->name_regular_expression, '/i^$()')) ?>"
                               data-message="<?= _('Bitte geben Sie Ihren tats�chlichen Nachnamen an.') ?>"
                               value="<?= htmlReady($user['Nachname']) ?>"
                               <? if ($restricted || !$controller->shallChange('auth_user_md5.Nachname', 'name')) echo 'disabled'; ?>>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label <? if (!$restricted) echo 'class="required"'; ?>>
                        <?= _('E-Mail:') ?>
                        <? if ($restricted) : ?>
                            <?= tooltipIcon('Dieses Feld d�rfen Sie nicht �ndern, Adminzugriff ist hier nicht erlaubt!') ?>
                        <? endif ?>                        
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('E-Mail:') ?><br>
                        <input required type="email" name="email1" id="email1"
                               value="<?= htmlReady($user['Email']) ?>"
                               <? if ($restricted || !$controller->shallChange('auth_user_md5.Email')) echo 'disabled'; ?>>
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('E-Mail Wiederholung:') ?><br>
                        <input required type="email" name="email2" id="email2"
                               value="<?= htmlReady($user['Email']) ?>"
                               data-must-equal="#email1"
                               <? if ($restricted || !$controller->shallChange('auth_user_md5.Email')) echo 'disabled'; ?>>
                    </label>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="title_front"><?= _('Titel:') ?></label>
                </td>
                <td>
                    <select id="title_front_chooser" name="title_front_chooser"
                            aria-label="<?= _('Titel ausw�hlen') ?>"
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
                            aria-label="<?= _('Titel nachgestellt ausw�hlen') ?>"
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
                <td>
                    <label><?= _('Geschlecht') ?></label>
                </td>
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
        <? if (!$restricted && $controller->shallChange('auth_user_md5.Email')): ?>
            <tr class="divider email-change-confirm">
                <td colspan="3" class="printhead">
                    <p id="email-change-confirm">
                        <?= _('Falls Sie Ihre E-Mail-Adresse �ndern, muss diese �nderung durch die Eingabe '
                             .'Ihres Passworts best�tigt werden:') ?>
                    </p>
                    <input type="password" name="password" aria-labelledby="email-change-confirm">
                </td>
            </tr>
        <? endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <?= Button::create(_('�bernehmen'), 'store', array('title' => _('�nderungen �bernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
