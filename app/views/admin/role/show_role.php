<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= $this->render_partial('admin/role/status_message') ?>

<h3>
    <?= _('Rollenzuweisungen anzeigen') ?>
</h3>

<form action="<?= $controller->url_for('admin/role/show_role') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <select name="role" style="width: 300px">
        <? foreach ($roles as $getrole): ?>
            <option value="<?= $getrole->getRoleid() ?>" <?= $getrole->getRoleid() == $roleid ? 'selected' : '' ?>>
                <?= htmlReady($getrole->getRolename()) ?>
                <? if ($getrole->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
            </option>
        <? endforeach ?>
    </select>
    <?= Button::create(_('auswählen'), 'selectrole', array('title' => _('Rolle auswählen')))?>
</form>

<? if (!empty($role)): ?>
    <h3>
        <?= sprintf(_('Liste der Benutzer mit der Rolle "%s"'), htmlReady($role->getRolename())) ?>
    </h3>

    <? if (count($users) > 0): ?>
        <? $index = 0 ?>
        <table class="default">
            <tr>
                <th style="width: 3%;"></th>
                <th style="width: 40%;"><?= _('Name') ?></th>
                <th><?= _('Status') ?></th>
            </tr>

            <? foreach ($users as $user): ?>
                <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
                    <td style="text-align: right;">
                        <?= ++$index ?>.
                    </td>
                    <td>
                        <a href="<?= $controller->url_for('admin/role/assign_role', $user['user_id']) ?>">
                            <?= htmlReady(sprintf('%s %s (%s)', $user['Vorname'], $user['Nachname'], $user['username'])) ?>
                        </a>
                    </td>
                    <td>
                        <?= $user['perms'] ?>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    <? else: ?>
        <?= Messagebox::info(_('Es wurden keine Benutzer gefunden.')) ?>
    <? endif ?>

    <h3>
        <?= sprintf(_('Liste der Plugins mit der Rolle "%s"'), htmlReady($role->getRolename())) ?>
    </h3>

    <? if (count($plugins) > 0): ?>
        <? $index = 0 ?>
        <table class="default">
            <tr>
                <th style="width: 3%;"></th>
                <th style="width: 40%;"><?= _('Name') ?></th>
                <th><?= _('Typ') ?></th>
            </tr>

            <? foreach ($plugins as $plugin): ?>
                <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
                    <td style="text-align: right;">
                        <?= ++$index ?>.
                    </td>
                    <td>
                        <a href="<?= $controller->url_for('admin/role/assign_plugin_role', $plugin['id']) ?>">
                            <?= htmlspecialchars($plugin['name']) ?>
                        </a>
                    </td>
                    <td>
                        <?= join(', ', $plugin['type']) ?>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    <? else: ?>
        <?= Messagebox::info(_('Es wurden keine Plugins gefunden.')) ?>
    <? endif ?>
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
                'text' => _('Hier werden alle Benutzer und Plugins angezeigt, die der ausgewählten Rolle zugewiesen sind.')
            ), array(
                "icon" => "icons/16/black/info.png",
                'text' => _('Klicken Sie auf den Namen eines Nutzers oder Plugins, um die Rollenzuweisungen dieses Nutzers oder Plugins zu ändern.')
            )
        )
    )
);

$infobox = array('picture' => 'infobox/modules.jpg', 'content' => $infobox_content);
?>
