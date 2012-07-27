<?
$inputs = array(
    'lock_%s_day'   => array('format' => 'd', 'placeholder' => _('tt')),
    'lock_%s_month' => array('format' => 'm', 'placeholder' => _('mm')),
    'lock_%s_year'  => array('format' => 'Y', 'placeholder' => _('jjjj'), 'length' => 4),
    'lock_%s_hour'  => array('format' => 'H', 'placeholder' => _('ss'), 'divider' => true),
    'lock_%s_min'   => array('format' => 'i', 'placeholder' => _('mm')),
);
?>

<? if (count($locks) > 0): ?>
    <table class="default zebra-hover" style="width: 50%;">
        <colgroup>
            <col width="2*">
            <col width="2*">
            <col width="1*">
        </colgroup>
        <thead>
            <tr>
                <th><?= _('Beginn:') ?></th>
                <th><?= _('Ende:') ?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($locks as $lock): ?>
            <tr>
            <? if ($_SESSION['resources_data']['lock_edits'][$lock['lock_id']]): ?>
                <!-- edit lock start time -->
                <td>
                <?  foreach ($inputs as $key => $data): ?>
                    <? if ($data['divider']): ?><br><? endif; ?>
                    <input type="text" style="font-size:8pt;"
                           size="<?= $data['length'] ?: 2 ?>" maxlength="<?= $data['length'] ?: 2 ?>"
                           name="<?= sprintf($key, 'begin') ?>[]"
                           value="<?= $lock['lock_begin'] ? date($data['format'], $lock['lock_begin']) : '' ?>"
                           placeholder="<?= htmlReady($data['placeholder']) ?>">
                <? endforeach; ?>
                </td>

                <!-- edit lock end time -->
                <td>
                <? foreach ($inputs as $key => $data): ?>
                    <? if ($data['divider']): ?><br><? endif; ?>
                    <input type="text" style="font-size:8pt;"
                           size="<?= $data['length'] ?: 2 ?>" maxlength="<?= $data['length'] ?: 2 ?>"
                           name="<?= sprintf($key, 'end') ?>[]"
                           value="<?= $lock['lock_end'] ? date($data['format'], $lock['lock_end']) : '' ?>"
                           placeholder="<?= htmlReady($data['placeholder']) ?>">
                <? endforeach; ?>
                </td>

                <td style="text-align: right; vertical-align: bottom;">
                    <input type="hidden" name="lock_id[]" value="<?= $lock['lock_id'] ?>">
    
                    <input type="image" name="lock_sent" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" border="0" <?= tooltip(_('Diesen Eintrag speichern')) ?> class="text-top">
                    <a href="<?= URLHelper::getLink('?kill_lock=' . $lock['lock_id']) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _('Diesen Eintrag löschen'))) ?>
                    </a>
                </td>
            <? else: ?>
                <td><?= date('d.m.Y H:i', $lock['lock_begin']) ?></td>
                <td><?= date('d.m.Y H:i', $lock['lock_end']) ?></td>
                <td style="text-align: right; vertical-align: bottom;">
                    <a href="<?= URLHelper::getLink('?edit_lock=' . $lock['lock_id']) ?>">
                        <?= Assets::img('icons/16/blue/edit.png', array('class' => 'text-top') + tooltip2(_('Diesen Eintrag bearbeiten'))) ?>
                    </a>
                    <a href="<?= URLHelper::getLink('?kill_lock=' . $lock['lock_id']) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-top') + tooltip2(_('Diesen Eintrag löschen'))) ?>
                    </a>
                </td>
            <? endif; ?>
            </tr>
        <? endforeach; ?>
        </tbody>
    </table>
<? endif; ?>
