<? use Studip\Button; ?>

<form action="<?= $controller->url_for('admin/cronjobs/tasks/bulk', $page) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

<table class="default cronjobs">
    <colgroup>
        <col width="20px">
        <col width="200px">
        <col>
        <col width="100px">
        <col width="40px">
        <col width="70px">
    </colgroup>
    <thead>
        <tr>
            <th>
                <input type="checkbox" name="all" value="1"
                       data-proxyfor=":checkbox[name='ids[]']"
                       data-activates=".cronjobs select[name=action]">
            </th>
            <th><?= _('Aufgabe') ?></th>
            <th><?= _('Beschreibung') ?></th>
            <th><?= _('Herkunft') ?></th>
            <th><?= _('Aktiv') ?></th>
            <th><?= _('Optionen') ?></th>
        </tr>
    </thead>
    <tbody>
<? for ($i = 0; $i < $max_per_page; $i += 1): ?>
    <? if (!isset($tasks[$i])): ?>
        <tr class="empty">
            <td colspan="6">&nbsp;</td>
        </tr>
    <? else: ?>
        <tr id="job-<?= $tasks[$i]->task_id ?>">
            <td style="text-align: center">
                <input type="checkbox" name="ids[]" value="<?= $tasks[$i]->task_id ?>">
            </td>
            <td><?= htmlReady($tasks[$i]->name) ?></td>
            <td><?= htmlReady($tasks[$i]->description) ?></td>
            <td><?= $tasks[$i]->isCore() ? _('Kern') : _('Plugin') ?></td>
            <td style="text-align: center;">
            <? if ($tasks[$i]->active): ?>
                <a href="<?= $controller->url_for('admin/cronjobs/tasks/deactivate', $tasks[$i]->task_id, $page) ?>" data-behaviour="ajax-toggle">
                    <?= Assets::img('icons/16/blue/checkbox-checked', tooltip2(_('Aufgabe deaktivieren'))) ?>
                </a>
            <? else: ?>
                <a href="<?= $controller->url_for('admin/cronjobs/tasks/activate', $tasks[$i]->task_id, $page) ?>" data-behaviour="ajax-toggle">
                    <?= Assets::img('icons/16/blue/checkbox-unchecked', tooltip2(_('Aufgabe aktivieren'))) ?>
                </a>
            <? endif; ?>
            </td>
            <td style="text-align: right">
                <a data-lightbox href="<?= $controller->url_for('admin/cronjobs/tasks/execute/', $tasks[$i]->task_id) ?>">
                    <?= Assets::img('icons/16/blue/play', tooltip2(_('Aufgabe ausführen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/logs/task', $tasks[$i]->task_id) ?>">
                    <?= Assets::img('icons/16/blue/log', tooltip2(_('Log anzeigen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/tasks/delete', $tasks[$i]->task_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/trash', tooltip2(_('Aufgabe löschen'))) ?>
                </a>
            </td>
        </tr>
    <? endif; ?>
<? endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="printhead">
                <select name="action" data-activates=".cronjobs button[name=bulk]">
                    <option value="">- <?= _('Aktion auswählen') ?></option>
                    <option value="activate"><?= _('Aktivieren') ?></option>
                    <option value="deactivate"><?= _('Deaktivieren') ?></option>
                    <option value="delete"><?= _('Löschen') ?></option>
                </select>
                <?= Button::createAccept(_('Ausführen'), 'bulk') ?>
            </td>
            <td colspan="3" class="printhead" style="text-align: right; vertical-align: middle;">
            <?
                $pagination = $GLOBALS['template_factory']->open('shared/pagechooser');
                $pagination->set_attributes(array(
                    'perPage'      => $max_per_page,
                    'num_postings' => $total,
                    'page'         => $page,
                    'pagelink'     => $controller->url_for('admin/cronjobs/tasks/index/%u')
                ));
                echo $pagination->render();
            ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
