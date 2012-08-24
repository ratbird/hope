<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= $this->render_partial('admin/role/status_message') ?>

<h3>
    <?= _('Rollenverwaltung für Benutzer') ?>
</h3>

<form action="<?= $controller->url_for('admin/role/assign_role') ?>" style="margin-bottom: 1em;" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <? if (empty($users)): ?>
        <?= _('Name der Person:') ?>
        <input type="text" name="username" value="<?= htmlReady($username) ?>" style="width: 300px;">
        <?= Button::create(_('Suchen'), 'search', array('title' => _('Benutzer suchen')))?>
    <? else: ?>
        <?= _('Benutzer:') ?>
        <select name="usersel" style="min-width: 300px;">
        <? foreach ($users as $user): ?>
            <option value="<?= $user->getUserid() ?>" <?= isset($currentuser) && $currentuser->isSameUser($user) ? "selected" : "" ?>>
                <?= htmlReady(sprintf('%s %s (%s)', $user->getGivenname(), $user->getSurname(), $user->getUsername())) ?>
            </option>
        <? endforeach ?>
        </select>
        <?= Button::create(_('Auswählen'), 'select', array('title' => _('Benutzer auswählen')))?>
        <?= LinkButton::create(_('Zurücksetzen'), $controller->url_for('admin/role/assign_role'), array('title' => _('Suche zurücksetzen')))?>
    <? endif ?>
</form>

<? if (isset($currentuser)): ?>
    <form action="<?= $controller->url_for('admin/role/save_role', $currentuser->getUserid()) ?>" method="POST">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
        <table class="default">
            <tr>
                <th style="text-align: center;">
                    <?= sprintf(_('Rollen für %s'), htmlReady($currentuser->getGivenname() . ' ' . $currentuser->getSurname())) ?>
                </th>
                <th></th>
                <th><?= _('Verfügbare Rollen') ?></th>
            </tr>
            <tr class="table_row_even">
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
                    <input type="image" name="assign_role" src="<?= Assets::image_path('icons/16/yellow/arr_2left.png') ?>" title="<?= _('Markierte Rollen dem Benutzer zuweisen') ?>">
                    <br>
                    <br>
                    <input type="image" name="remove_role" src="<?= Assets::image_path('icons/16/yellow/arr_2right.png') ?>" title="<?= _('Markierte Rollen entfernen') ?>">
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
                'icon' => 'icons/16/black/roles.png',
                'text' => '<a href="'.$controller->url_for('admin/role').'">'._('Rollen verwalten').'</a>'
            ), array(
                'icon' => 'icons/16/black/person.png',
                'text' => '<a href="'.$controller->url_for('admin/role/assign_role').'">'._('Benutzerzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'icons/16/black/plugin.png',
                'text' => '<a href="'.$controller->url_for('admin/role/assign_plugin_role').'">'._('Pluginzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'icons/16/black/log.png',
                'text' => '<a href="'.$controller->url_for('admin/role/show_role').'">'._('Rollenzuweisungen anzeigen').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/info.png",
                'text' => _('Hier können Sie nach Benutzern suchen und ihnen verschiedene Rollen zuweisen.')
            )
        )
    )
);

$infobox = array('picture' => 'infobox/modules.jpg', 'content' => $infobox_content);
?>
