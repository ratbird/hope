<?php
# Lifter010: TEST
?>
<h1><?= _('Teilnehmeransicht konfigurieren') ?></h1>

<form action="<?= URLHelper::getURL() ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

<? foreach ($GLOBALS['SEM_CLASS'] as $key => $val): ?>
    <table class="default">
        <colgroup>
            <col width="50%">
            <col width="25%">
            <col width="25%">
        </colgroup>
        <thead>
            <tr>
                <th><?= htmlReady($val['name']) ?></th>
                <th><?= _('Status') ?></th>
                <th><?= _('Anzeige') ?></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($GLOBALS['TEILNEHMER_VIEW'] as $data): ?>
            <tr class="<?= TextHelper::cycle('hover_even', 'hover_odd') ?>">
                <td><?= htmlReady($data['name']) ?></td>
                <td>
                <? if ($active[$key][ $data['field'] ]): ?>
                    <?= Assets::img('icons/16/blue/checkbox-checked', array('class' => 'text-top')) ?>
                    <span style="color: green;"><?= _('Anzeigen erlaubt') ?></span>
                <? else: ?>
                    <?= Assets::img('icons/16/blue/checkbox-unchecked', array('class' => 'text-top')) ?>
                    <span style="color: red;"><?= _('Anzeigen nicht erlaubt') ?></span>
                <? endif; ?>
                </td>
                <td>
                    <input type="hidden" name="fields[<?= $key ?>][<?= htmlReady($data['field']) ?>]" value="0">
                    <label>
                        <input type="checkbox" name="fields[<?= $key ?>][<?= htmlReady($data['field']) ?>]" value="1"
                               <? if ($active[$key][ $data['field'] ]) echo 'checked'; ?>>
                        <?= _('erlauben') ?>
                    </label>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">&nbsp;</td>
                <td><?= Studip\Button::create(_('Zuweisen'), 'assign') ?></td>
            </tr>
        </tfoot>
    </table>

    <br>
<? endforeach; ?>
</form>
