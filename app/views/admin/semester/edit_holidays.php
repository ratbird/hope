<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<form method="post" action="<?= $controller->url_for('admin/semester/edit_holidays') ?><?= ($holiday['holiday_id'])? '/'.$holiday['holiday_id'] : '' ?>">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr>
    <? if(!$is_new) : ?>
         <th colspan="5"><?= _("Ferien bearbeiten") ?></th>
    <? else : ?>
         <th colspan="5"><?= _("Ferien neu anlegen") ?></th>
    <? endif ?>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Name der Ferien:") ?>
        </td>
        <td colspan="4">
            <input type="text" size="60" value="<?= ($holiday['name']) ? htmlReady($holiday['name']) : '' ?>" name="name" style="width: 350px;" required>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Beschreibung:") ?>
        </td>
        <td colspan="4">
            <textarea name="description" rows="4" cols="50" style="width: 350px;"><?= ($holiday['description']) ? htmlReady($holiday['description']) : '' ?></textarea>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Ferienzeitraum:") ?>
        </td>
        <td>
            <?= _("Beginn:") ?>
        </td>
        <td>
            <input id="beginn" type="text" name="beginn" value="<?= ($holiday['beginn']) ? date('d.m.Y', $holiday['beginn']) : '' ?>" required>
        </td>
        <td>
            <?= _("Ende:") ?>
        </td>
        <td>
            <input id="ende" type="text" name="ende" value="<?= ($holiday['ende']) ? date('d.m.Y', $holiday['ende']) : '' ?>" required>
        </td>
    </tr>
    <tr>
        <td colspan="5" align="center">
        <? if (!$is_new) : ?>
            <?= Button::createAccept(_('Speichern'), "speichern", array('title' => _('Die Änderungen speichern'))) ?>
        <? else : ?>
            <?= Button::create(_('Anlegen'), "anlegen", array('title' => _('Neue Ferien anlegen'))) ?>
        <? endif ?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/semester')) ?>
        </td>
    </tr>
</table>
</form>

<script>
    jQuery('#beginn').datepicker();
    jQuery('#ende').datepicker();
</script>