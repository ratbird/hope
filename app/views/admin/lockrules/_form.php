<?
# Lifter010: 
use Studip\Button, Studip\LinkButton;

?>
<form action="<?= $action ?>" method="post">
    <?= CSRFProtection::tokenTag(); ?>
    <table class="default nohover">
        <colgroup>
            <col width="30%">
            <col>
        </colgroup>
        <caption>
            <? if ($lock_rule->name) : ?>
                <?= sprintf(_('Sperrebene "%s" �ndern'), htmlready($lock_rule["name"])) ?>
            <? else : ?>
                <?= _('Neue Sperrebene eingeben f�r den Bereich:') ?> <?= $rule_type_names[$lock_rule_type]; ?>
            <? endif ?>
        </caption>
        <tr>
            <td>
                <?= _("Name") ?>
            </td>
            <td>
                <input type="text" style="width:90%" required name="lockdata_name"
                       value="<?= htmlReady($lock_rule['name']) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <?= _('Beschreibung') ?>
                <div
                    style="font-size:80%"><?= _('(dieser Text wird auf allen Seiten mit gesperrtem Inhalt angezeigt)') ?></div>
            </td>
            <td>
                <textarea name="lockdata_description" rows="5"
                          style="width:90%"><?= htmlReady($lock_rule["description"]) ?></textarea>
        </tr>
        <tr>
            <td>
                <?= _('Nutzerstatus') ?>
                <div
                    style="font-size:80%"><?= _('(die Einstellungen dieser Sperrebene gelten f�r Nutzer bis zu dieser Berechtigung)') ?></div>
            </td>
            <td>
                <select name="lockdata_permission">
                    <? foreach ($lock_rule_permissions as $p) : ?>
                        <option <?= ($lock_rule['permission'] == $p ? 'selected' : '') ?>><?= $p ?></option>
                    <? endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

    <? foreach ($lock_config['groups'] as $group => $group_title) : ?>
        <? $attributes = array_filter(array_map(create_function('$a', 'return $a["group"]=="' . $group . '" ? $a["name"] : null;'), $lock_config['attributes'])); ?>
        <? if (count($attributes)) : ?>
            <table class="default">
                <caption><?= htmlready($group_title) ?></caption>
                <colgroup>
                    <col width="70%">
                    <col width="15%">
                    <col width="15%">
                </colgroup>
                <thead>
                <tr>
                    <th></th>
                    <th><?= _('gesperrt') ?></th>
                    <th><?= _('nicht gesperrt') ?></th>
                </tr>
                </thead>
                <tbody>
                <? foreach ($attributes as $attr => $attr_name) : ?>
                    <tr>
                        <td>
                            <?= htmlready($attr_name) ?>
                        </td>
                        <td>
                            <input type="radio"
                                   name="lockdata_attributes[<?= $attr ?>]" <?= ($lock_rule['attributes'][$attr] ? 'checked' : '') ?>
                                   value="1"/>
                        </td>
                        <td>
                            <input type="radio"
                                   name="lockdata_attributes[<?= $attr ?>]" <?= (!$lock_rule['attributes'][$attr] ? 'checked' : '') ?>
                                   value="0"/>
                        </td>
                    </tr>
                <? endforeach ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3" style="text-align:center">
                        <?= Button::create(_('�bernehmen'), 'ok', array('title' => _('Einstellungen �bernehmen'))) ?>
                    </td>
                </tr>
                </tfoot>
            </table>
        <? endif ?>

    <? endforeach ?>
</form>