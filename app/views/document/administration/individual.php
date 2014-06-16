<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('document/administration/individual') ?>" method="post" class="studip_form">
    <fieldset class="document-admin-search">
        <legend><?= _('Suche') ?></legend>

        <label>
            <?= _('Vorname') ?>
            <input type="text" id="userVorname" name="userVorname">
        </label>

        <label>
            <?= _('Nachname') ?>
            <input type="text" id="userNachname" name="userNachname">
        </label>

        <label>
            <?= _('Benutzername') ?>
            <input type="text" id="userName" name="userName">
        </label>

        <label>
            <?= _('E-Mail') ?>
            <input type="text" id="userMail" name="userMail">
        </label>

        <label>
            <?= _('Nutzergruppe') ?>
            <select id="userGroup" name="userGroup">
            <? foreach(words('alle user autor tutor dozent admin root') as $one): ?>
                <option><?= $one ?></option>
            <? endforeach ?>
            </select>
        </label>

        <div class="submit_wrapper">
            <?= Button::createAccept(_('Suche'), 'search') ?>
        </div>
    </fieldset>    
</form>

<table class="default">
    <thead>
        <tr>
            <th><?= _('Vorname') ?></th>
            <th><?= _('Nachname') ?></th>
            <th><?= _('Nutzername') ?></th>
            <th><?= _('E-Mail') ?></th>
            <th><?= _('Max. Upload') ?></th>
            <th><?= _('Nutzerquota') ?></th>
            <th><?= _('Untersagte Typen') ?></th>
            <th><?= _('Upload verboten') ?></th>
            <th><?= _('Bereich gesperrt') ?></th>
            <th><?= _('Aktion') ?></th>

        </tr>
    </thead>
    <tbody>
    <? foreach ($this->viewData['users'] as $u): ?>
        <tr>
            <td><?= htmlReady($u['vorname']) ?></td>
            <td><?= htmlReady($u['nachname']) ?></td>
            <td><?= htmlReady($u['username']) ?></td>
            <td><?= htmlReady($u['email']) ?></td>
            <td><?= $u['upload'] ?> <?= $u['upload_unit'] ?></td>
            <td><?= $u['quota'] ?> <?= $u['quota_unit'] ?></td>
            <td>
            <? foreach ($u['types'] as $typ): ?>
                <?= $typ['type'] ?>
            <? endforeach;?>
            </td>
            <td>
                <input type="checkbox" name="box" disabled <? if ($u['forbidden']) echo 'checked'; ?>>
            </td>
            <td>
                <input type="checkbox" name="box" disabled <? if ($u['area_close']) echo 'checked'; ?>>
            </td>
            <td>
                 <a data-dialog href="<?= $controller->url_for('document/administration/edit/0/' . $u['user_id']) ?>">
                    <?= Assets::img('icons/16/blue/edit', tooltip2(_('Einstellung bearbeiten'))) ?>
                </a>
            <?if ($u['deleteIcon'] == 1) :?>
                <a href="<?= $controller->url_for('document/administration/delete/' . $u['config_id']) ?>">
                    <?= Assets::img('icons/16/blue/trash.png', tooltip2(_('Einstellungen löschen'))) ?>
                </a>
            <?  endif;?>
            </td>
        </tr>
    <? endforeach;?>
    </tbody>
</table>
