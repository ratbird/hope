<?= $this->render_partial('role_admin/status_message') ?>

<h3>
    <?= _('Rollenverwaltung für Benutzer') ?>
</h3>

<form action="<?= $controller->url_for('role_admin/assign_role') ?>" style="margin-bottom: 1em;" method="POST">
    <? if (empty($users)): ?>
        <?= _('Name der Person:') ?>
        <input type="text" name="username" value="<?= htmlReady($username) ?>" style="width: 300px;">
        <?= makeButton('suchen', 'input', _('Benutzer suchen'), 'search') ?>
    <? else: ?>
        <?= _('Benutzer:') ?>
        <select name="usersel" style="min-width: 300px;">
        <? foreach ($users as $user): ?>
            <option value="<?= $user->getUserid() ?>" <?= isset($currentuser) && $currentuser->isSameUser($user) ? "selected" : "" ?>>
                <?= htmlReady(sprintf('%s %s (%s)', $user->getGivenname(), $user->getSurname(), $user->getUsername())) ?>
            </option>
        <? endforeach ?>
        </select>
        <?= makeButton('auswaehlen', 'input', _('Benutzer auswählen'), 'select') ?>
        <a href="<?= $controller->url_for('role_admin/assign_role') ?>">
            <?= makeButton('zuruecksetzen', 'img', _('Suche zurücksetzen')) ?>
        </a>
    <? endif ?>
</form>

<? if (isset($currentuser)): ?>
    <form action="<?= $controller->url_for('role_admin/save_role', $currentuser->getUserid()) ?>" method="POST">
        <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
        <table class="default">
            <tr>
                <th style="text-align: center;">
                    <?= sprintf(_('Rollen für %s'), htmlReady($currentuser->getGivenname() . ' ' . $currentuser->getSurname())) ?>
                </th>
                <th></th>
                <th><?= _('Verfügbare Rollen') ?></th>
            </tr>
            <tr class="steel1">
                <td style="text-align: right;">
                    <select multiple name="assignedroles[]" size="10" style="width: 300px;">
                        <? foreach ($assignedroles as $assignedrole): ?>
                            <option value="<?= $assignedrole->getRoleid() ?>">
                                <?= htmlReady($assignedrole->getRolename()) ?>
                                <? if ($assignedrole->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
                <td style="text-align: center;">
                    <input type="image" name="assign_role" src="<?= Assets::image_path('move_left.gif') ?>" title="<?= _('Markierte Rollen dem Benutzer zuweisen') ?>">
                    <br>
                    <br>
                    <input type="image" name="remove_role" src="<?= Assets::image_path('move_right.gif') ?>" title="<?= _('Markierte Rollen entfernen') ?>">
                </td>
                <td>
                    <select size="10" name="rolesel[]" multiple style="width: 300px;">
                        <? foreach ($roles as $role): ?>
                            <option value="<?= $role->getRoleid() ?>">
                                <?= htmlReady($role->getRolename()) ?>
                                <? if ($role->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>

    <h3>
        <?= _('Implizit zugewiesene Systemrollen') ?>
    </h3>

    <? foreach ($all_userroles as $role): ?>
        <? if (!in_array($role, $assignedroles)): ?>
            <?= htmlReady($role->getRolename()) ?><br>
        <? endif ?>
    <? endforeach ?>
<? endif ?>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin').'">'._('Rollen verwalten').'</a>'
            ), array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin/assign_role').'">'._('Benutzerzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin/assign_plugin_role').'">'._('Pluginzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin/show_role').'">'._('Rollenzuweisungen anzeigen').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                'icon' => 'ausruf_small.gif',
                'text' => _('Hier können Sie nach Benutzern suchen und ihnen verschiedene Rollen zuweisen.')
            )
        )
    )
);

$infobox = array('picture' => 'modules.jpg', 'content' => $infobox_content);
?>
