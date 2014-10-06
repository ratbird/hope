<form action="<?= $controller->url_for('profilemodules/update', compact('username')) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default" id="profile_modules">
        <caption><?= _('Inhaltselemente') ?></caption>
        <thead>
            <tr>
                <th>
                    <input type="checkbox" name="modules[]" value="all"
                           data-proxyfor="#profile_modules tbody td :checkbox[name^='module']">
                </th>
                <th><?= _('Name') ?></th>
                <th><?= _('Beschreibung') ?></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($this->controller->modules as $module): ?>
            <tr>
                <td>
                    <input type="checkbox" name="modules[]" value="<?= $module['id'] ?>"
                           id="module_<?= $module['id'] ?>"
                           <? if ($module['activated']) echo 'checked'; ?>>
                </td>
                <td>
                    <label for="module_<?= $module['id'] ?>">
                        <strong><?= htmlReady($module['name']) ?><strong>
                    </label>
                </td>
                <td>
                <? if (isset($module['description'])) : ?>
                    <?= formatReady($module['description']) ?>
                <? else: ?>
                    <?= _('Für dieses Element ist keine Beschreibung vorhanden.') ?>
                <? endif ?>
        
                <? if (isset($module['homepage'])) : ?>
                    <p>
                        <strong><?= _('Weitere Informationen:') ?></strong>
                        <a href="<?= htmlReady($module['homepage']) ?>">
                            <?= htmlReady($module['homepage']) ?>
                        </a>
                    </p>
                <? endif ?>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <?= Studip\Button::createAccept(_('Übernehmen'), 'submit') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
