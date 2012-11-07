<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<?= (isset($flash['error']))?MessageBox::error($flash['error'], $flash['error_detail']):'' ?>
<? if (empty($via_ajax)): ?>
<h2><?=_("Bearbeiten von Konfigurationsparameter für den Nutzer: ")?><?=htmlReady($search_user['fullname'])?></h2>
<? endif; ?>
<form action="<?= $controller->url_for('admin/configuration/user_configuration/update') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td><?=_("Name:")?>(<em>field</em>) </td>
            <td>
                <input type="hidden" name = "field" value = "<?= htmlReady($search_user['field'])?>">
                <input type="hidden" name = "user_id" value = "<?= htmlReady($user_id)?>">
                <?= htmlReady($search_user['field']) ?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td><?=_("Inhalt:")?>(<em>value</em>) </td>
            <td>
            <? if ($search_user['type'] == 'integer'): ?>
                <input class="allow-only-numbers" name="value" type="text" value="<?= htmlReady($search_user['value'])?>" />
            <? elseif ($edit['type'] == 'boolean'): ?>
                <select name="value">
                    <option value = "1" <?= $search_user['value'] ? 'selected="selected"' : '' ?> style="background: url(<?= Assets::image_path('icons/16/green/accept.png') ?>) right center no-repeat">
                        TRUE
                    </option>
                    <option value = "0" <?= $search_user['value'] ? '' : 'selected="selected"' ?> style="background: url(<?= Assets::image_path('icons/16/red/decline.png') ?>) right center no-repeat">
                        FALSE
                    </option>
                </select>
            <? elseif ($search_user['type'] == 'array') : ?>
                <textarea cols="80" rows="5" name="value"><?= htmlReady(json_encode(studip_utf8encode($search_user['value'])),true,true)?></textarea>
            <? else : ?>
                <textarea cols="80" rows="3" name="value"><?= htmlReady($search_user['value'])?></textarea>
            <? endif; ?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td><?=_("Beschreibung:")?>(<em>description</em>) </td>
            <td><?= htmlReady($search_user['description'])?></td>
        </tr>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td>&nbsp;</td>
            <td>
                <?= Button::createAccept(_('Übernehmen'),'uebernehmen', array('title' => _('Änderungen übernehmen')))?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/configuration/configuration'),array('title' => _('Zurück zur Übersicht')))?>
            </td>
        </tr>
    </table>
</form>