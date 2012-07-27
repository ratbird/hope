<? use Studip\Button, Studip\LinkButton; ?>

<?
$types = array(
    'bool'   => _('Zustand'),
    'num'    => _('einzeiliges Textfeld'),
    'text'   => _('mehrzeiliges Textfeld'),
    'select' => _('Auswahlfeld'),
)
?>

<form method="post" action="<?= URLHelper::getLink() ?>">
    <?= CSRFProtection::tokenTag() ?>

<table class="default zebra" style="margin: 0 1%; width: 98%;">
    <colgroup>
        <col width="4%">
        <col width="16%">
        <col width="80%">
    </colgroup>
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th colspan="2"><?= _('Neue Eigenschaft anlegen') ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>&nbsp;</td>
            <td>
                <label for="add_property"><?= _('Name:') ?></label>
            </td>
            <td>
                <input type="text" id="add_property" name="add_property"
                       size="50" maxlength="255"
                       placeholder="&lt;<?= _('bitte geben Sie hier den Namen ein') ?>&gt;">
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <label for="add_property_type"><?= _('Art: ') ?></label>
            </td>
            <td>
                <select id="add_property_type" name="add_property_type">
                <? foreach ($types as $key => $label): ?>
                    <option value="<?= $key ?>"><?= htmlReady($label) ?></option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="steel2">
            <td>&nbsp;</td>
            <td colspan="2">
                <?= Button::create(_('Anlegen'), '_add_property') ?>
            </td>
        </tr>
    </tfoot>
</table>

</form>

<br>

<form method="post" action="<?= URLHelper::getLink() ?>">
    <?= CSRFProtection::tokenTag() ?>

<div style="text-align: center; margin-top: 1em;">
    <?= Button::createAccept(_('Übernehmen'), '_send_property_type') ?>
</div>

<table class="default zebra" style="margin: 0 1%; width: 98%;">
    <colgroup>
        <col width="4%">
        <col width="25%">
        <col width="35%">
        <col width="30%">
        <col width="6%">
    </colgroup>
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th><?= _('Eigenschaft') ?></th>
            <th colspan="2"><?= _('Art der Eigenschaft') ?></th>
            <th style="text-align: center;"><?= _('X') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($properties as $property): ?>
        <tr>
            <td>&nbsp;</td>
            <td valign="top">
                <input type="text" size="20" maxlength="255"
                       name="change_property_name[<?= $property['property_id'] ?>]"
                       value="<?= htmlReady($property['name']) ?>">
                <br>
                <?= sprintf(_('wird von <b>%s</b> Typen verwendet'), $property['depTyp']) ?><br>
            <? if ($property['system']): ?>
                <?= _('(systemobjekt)') ?>
            <? endif; ?>
            </td>
            <td valign="top">
                <label>
                    <?= _('Art:') ?><br>
                    <select name="send_property_type[<?= $property['property_id'] ?>]">
                    <? foreach ($types as $key => $label): ?>
                        <option value="<?= $key ?>" <? if ($property['type'] == $key) echo 'selected'; ?>>
                            <?= htmlReady($label) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </label>
                <br>
            <? if ($property['type'] == 'bool'): ?>
                <label>
                    <?= _('Bezeichnung:') ?><br>
                    <input type="text" size="30" maxlength="255"
                           name="send_property_bool_desc[<?= $property['property_id'] ?>]"
                           value="<?= htmlReady($property['options']) ?>">
                </label>
            <? elseif ($property['type'] == 'select'): ?>
                <label>
                    <?= _('Optionen:') ?><br>
                    <input type="text" size="30" maxlength="255"
                           name="send_property_select_opt[<?= $property['property_id'] ?>]"
                           value="<?= htmlReady($property['options']) ?>">
                </label>
            <? endif; ?>
            </td>
            <td style="vertical-align: top;">
                <label>
                    <?= _('Vorschau:') ?> <br>
                <? if ($property['type'] == 'bool'): ?>
                    <input type="checkbox" checked>
                    <?= htmlReady($property['options']) ?>
                <? elseif ($property['type'] == 'num'): ?>
                    <input type="text" size="30" maxlength="255">
                <? elseif ($property['type'] == 'text'): ?>
                    <textarea cols="30" rows="2"></textarea>
                <? elseif ($property['type'] == 'select'): ?>
                    <select>
                    <? foreach (explode(';', $property['options']) as $option): ?>
                        <option><?= htmlReady($option) ?></option>
                    <? endforeach; ?>
                    </select>
                <? endif; ?>
                </label>
            </td>
            <td valign="bottom" align="center">
                <?= _('diese Eigenschaft') ?><br>
            <? if (($property['depTyp']==0) && !$type['system']): ?>
                <?= LinkButton::create(_('Löschen'), URLHelper::getURL('?delete_property=' . $property['property_id'])) ?>
            <? else: ?>
                <?= Button::create(_('Löschen'), array('disabled' => 'disabled')) ?>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="steel2">
            <td colspan="5" style="text-align: center;">
                <?= Button::createAccept(_('Übernehmen'), '_send_property_type') ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>

<br><br>
