<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($flash['success'])): ?>
    <?= MessageBox::info($flash['success']) ?>
<? endif; ?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? endif; ?>

<? if (empty($via_ajax)): ?>
<h2><?= _("Bearbeiten von Konfigurationsparameter") ?></h2>
<? endif; ?>

<form action="<?= $controller->url_for('admin/configuration/edit_configuration/'.$edit['config_id']) ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Name") ?>:</td>
            <td><?= htmlReady($edit['field'])?></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Inhalt") ?>:</td>
            <td>
            <? if ($edit['type'] == 'integer'): ?>
                <input class="allow-only-numbers" name="value" type="text" value="<?= htmlReady($edit['value'])?>" />
            <? elseif ($edit['type'] == 'boolean'): ?>
                <select name="value">
                    <option value = "1" <?= $edit['value'] ? 'selected="selected"' : '' ?> style="background: url(<?= Assets::image_path('icons/16/green/accept.png') ?>) right center no-repeat">
                        TRUE
                    </option>
                    <option value = "0" <?= $edit['value'] ? '' : 'selected="selected"' ?> style="background: url(<?= Assets::image_path('icons/16/red/decline.png') ?>) right center no-repeat">
                        FALSE
                    </option>
                </select>
            <? else : ?>
                <textarea cols="80" rows="3" name="value"><?= htmlReady($edit['value'])?></textarea>
            <? endif; ?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Kommentar") ?>:</td>
            <td><textarea cols="80" rows="2" name="comment"><?= htmlReady($edit['comment']) ?></textarea></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Standard") ?>:</td>
            <td>
            <? if ($edit['is_default'] == 1): ?>
                TRUE
            <? elseif ($edit['is_default'] == 0): ?>
                FALSE
            <? elseif ($edit['is_default'] == NULL): ?>
                <em>- <?= _('kein Eintrag vorhanden') ?> -</em>
            <? endif; ?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Typ") ?></td>
            <td><?= $edit['type'] ?></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Bereich") ?>:</td>
            <td><?= $edit['range'] ?></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><label for="section"><?= _("Kategorie") ?>:</label></td>
            <td>
                <select name= "section" onchange="$(this).next('input').val( $(this).val() );">
                <? foreach (array_keys($allconfigs) as $section): ?>
                  <option value = "<?= $section?>"
                    <?= ($edit['section'] == $section) ? 'selected="selected"' : '' ?>>
                    <?=$section?>
                  </option>
                <? endforeach; ?>
                </select>
                <input type="text" name="section_new" id="section">
                (<em><?= _('Bitte die neue Kategorie eingeben')?></em>)
           </td>
        </tr>
        <tr class="steel2">
            <td>&nbsp;</td>
            <td>
                <?= Button::createAccept(_('übernehmen'),'uebernehmen', array('title' => _('Änderungen übernehmen')))?>
                <?= LinkButton::createCancel(_('abbrechen'), $controller->url_for('admin/configuration/configuration'), array('title' => _('Zurück zur Übersicht')))?>
            </td>
        </tr>
    </table>
</form>