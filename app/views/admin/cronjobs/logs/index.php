<? use Studip\Button; ?>

<form action="<?= $controller->url_for('admin/cronjobs/logs/bulk', $page) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

<table class="default zebra-hover cronjobs">
    <colgroup>
        <col width="20px">
        <col width="150px">
        <col width="150px">
        <col>
        <col width="50px">
        <col width="50px">
    </colgroup>
    <thead>
        <tr>
            <th>
                <input type="checkbox" name="all" value="1"
                       data-proxyfor=":checkbox[name='ids[]']">
            </th>
            <th><?= _('Ausgeführt') ?></th>
            <th><?= _('Geplant') ?></th>
            <th><?= _('Cronjob') ?></th>
            <th><?= _('Ok?') ?></th>
            <th><?= _('Optionen') ?></th>
        </tr>
    </thead>
    <tbody>
<? for ($i = 0; $i < $max_per_page; $i += 1): ?>
    <? if (!isset($logs[$i])): ?>
        <tr class="empty">
            <td colspan="6">&nbsp;</td>
        </tr>
    <? else: ?>
        <tr id="log-<?= $logs[$i]->log_id ?>">
            <td style="text-align: center">
                <input type="checkbox" name="ids[]" value="<?= $logs[$i]->log_id ?>">
            </td>
            <td><?= date('d.m.Y H:i:s', $logs[$i]->executed) ?></td>
            <td><?= date('d.m.Y H:i:s', $logs[$i]->scheduled) ?></td>
            <td><?= htmlReady($logs[$i]->schedule->title ?: $logs[$i]->schedule->task->name) ?></td>
            <td>
            <? if ($logs[$i]->duration == -1): ?>
                <?= Assets::img('icons/16/grey/question', tooltip2(_('Läuft noch'))) ?>
            <? elseif ($logs[$i]->exception === null): ?>
                <?= Assets::img('icons/16/green/accept', tooltip2(_('Ja'))) ?>
            <? else: ?>
                <?= Assets::img('icons/16/red/decline', tooltip2(_('Nein'))) ?>
            <? endif; ?>
            </td>
            <td style="text-align: right">
                <a rel="lightbox" href="<?= $controller->url_for('admin/cronjobs/logs/display', $logs[$i]->log_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/admin', tooltip2(_('Logeintrag anzeigen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/cronjobs/logs/delete', $logs[$i]->log_id, $page) ?>">
                    <?= Assets::img('icons/16/blue/trash', tooltip2(_('Logeintrag löschen'))) ?>
                </a>
            </td>
        </tr>
    <? endif; ?>
<? endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="printhead">
                <select name="action">
                    <option value="">- <?= _('Aktion auswählen') ?></option>
                    <option value="delete"><?= _('Löschen') ?></option>
                </select>
                <?= Button::createAccept(_('Ausführen')) ?>
            </td>
            <td colspan="3" class="printhead" style="text-align: right; vertical-align: middle;">
            <?
                $pagination = $GLOBALS['template_factory']->open('shared/pagechooser');
                $pagination->set_attributes(array(
                    'perPage'      => $max_per_page,
                    'num_postings' => $total,
                    'page'         => $page,
                    'pagelink'     => $controller->url_for('admin/cronjobs/logs/index/%u')
                ));
                echo $pagination->render();
            ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
