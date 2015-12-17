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
                    <?= Icon::create('checkbox-checked', 'clickable', ['title' => _('Aufgabe deaktivieren')])->asImg() ?>
                </a>
            <? else: ?>
                <a href="<?= $controller->url_for('admin/cronjobs/tasks/activate', $tasks[$i]->task_id, $page) ?>" data-behaviour="ajax-toggle">
                    <?= Icon::create('checkbox-unchecked', 'clickable', ['title' => _('Aufgabe aktivieren')])->asImg() ?>
                </a>
            <? endif; ?>
            </td>
            <td style="text-align: right">
            <? if ($tasks[$i]->valid): ?>
                <a data-dialog href="<?= $controller->url_for('admin/cronjobs/tasks/execute', $tasks[$i]->task_id) ?>">
                    <?= Icon::create('play', 'clickable', ['title' => _('Aufgabe ausf�hren')])->asImg() ?>
                </a>
            <? endif; ?>
                <a href="<?= $controller->url_for('admin/cronjobs/logs/task', $tasks[$i]->task_id) ?>">
                    <?= Icon::create('log', 'clickable', ['title' => _('Log anzeigen')])->asImg() ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/tasks/delete', $tasks[$i]->task_id, $page) ?>">
                    <?= Icon::create('trash', 'clickable', ['title' => _('Aufgabe l�schen')])->asImg() ?>
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
                    <option value="">- <?= _('Aktion ausw�hlen') ?></option>
                    <option value="activate"><?= _('Aktivieren') ?></option>
                    <option value="deactivate"><?= _('Deaktivieren') ?></option>
                    <option value="delete"><?= _('L�schen') ?></option>
                </select>
                <?= Button::createAccept(_('Ausf�hren'), 'bulk') ?>
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
