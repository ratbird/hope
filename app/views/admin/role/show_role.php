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
    <?= Button::create(_('Auswählen'), 'selectrole', array('title' => _('Rolle auswählen')))?>
</form>

<? if (!empty($role)): ?>

    <? if (count($users) > 0): ?>
        <? $index = 0 ?>
        <table class="default">
        <caption>
            <?= sprintf(_('Liste der Benutzer mit der Rolle "%s"'), htmlReady($role->getRolename())) ?>
        </caption>
        <thead>
            <tr>
                <th style="width: 3%;"></th>
                <th style="width: 27%;"><?= _('Name') ?></th>
                <th style="width: 3%;"><?= _('Status') ?></th>
                <th><?= _('Einrichtungszuordnung') ?></th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($users as $user): ?>
                <tr>
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
                    <td>
                    <? $institutes = join(', ', $user['institutes']); ?>
                        <?= htmlReady(substr($institutes,0,60)) ?>
                        <? if (strlen($institutes) > 60) :?>
                        ...<?= tooltipIcon(join("\n", $user['institutes']))?>
                        <? endif ?>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
        </table>
        <br>
    <? else: ?>
        <?= MessageBox::info(_('Es wurden keine Benutzer gefunden.')) ?>
    <? endif ?>


    <? if (count($plugins) > 0): ?>
        <? $index = 0 ?>
        <table class="default">
        <caption>
            <?= sprintf(_('Liste der Plugins mit der Rolle "%s"'), htmlReady($role->getRolename())) ?>
        </caption>
        <thead>
            <tr>
                <th style="width: 3%;"></th>
                <th style="width: 40%;"><?= _('Name') ?></th>
                <th><?= _('Typ') ?></th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($plugins as $plugin): ?>
                <tr>
                    <td style="text-align: right;">
                        <?= ++$index ?>.
                    </td>
                    <td>
                        <a href="<?= $controller->url_for('admin/role/assign_plugin_role', $plugin['id']) ?>">
                            <?= htmlReady($plugin['name']) ?>
                        </a>
                    </td>
                    <td>
                        <?= join(', ', $plugin['type']) ?>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
        </table>
    <? else: ?>
        <?= MessageBox::info(_('Es wurden keine Plugins gefunden.')) ?>
    <? endif ?>
<? endif ?>
