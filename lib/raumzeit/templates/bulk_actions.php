<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>

<a name="Stapelaktionen"></a>

<div style="clear: both">
    <b><?= _('Für die ausgewählten Termine folgende Aktionen durchführen:') ?></b>
    <br>
    <br>
</div>

<div style="float: left; width: 49%;">
    <b>
        <?=_("Durchführende Dozenten")?> ...
    </b>
    <br>

    <select name="related_persons_action" style="width: 400px" aria-label="<?= _("Wählen Sie aus, ob Dozenten den ausgewählten regelmäßigen Terminen hinzugefügt, von diesen entfernt oder für diese Termine definiert werden sollen.") ?>">
        <option value="">-- <?= _('Aktion auswählen') ?> --</option>
        <option value="add" title="<?= _("Die ausgewählten Dozenten werden den ausgewählten Terminen hinzugefügt. Die zuvor schon durchführenden Dozenten bleiben aber weiterhin zusätzlich eingetragen.") ?>">... <?= _("hinzufügen") ?></option>
        <option value="delete" title="<?= _("Die ausgewählten Dozenten leiten nicht die ausgewählten Termine. Andere Dozenten bleiben bestehen.") ?>">... <?= _("entfernen") ?></option>
    </select>
    <br>

    <select name="related_persons[]" multiple style="vertical-align: top; width: 400px;" aria-label="<?= _("Wählen Sie die Dozenten aus, die regelmäßigen Terminen hinzugefügt oder von diesen entfernt werden sollen.") ?>">
        <? foreach ($sem->getMembers('dozent') as $dozent) : ?>
        <option value="<?= htmlReady($dozent['user_id']) ?>"><?= htmlReady($dozent['fullname']) ?></option>
        <? endforeach ?>
    </select>
    <!--<?= Button::create(_('Übernehmen'), 'related_persons_action_do') ?>-->
    <br>
    <br>
</div>

<div style="float: right; width: 49%">
    <b><?= _('Raumangaben:') ?></b><br>
    <? if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) : ?>
    <? $resList->reset() ?>
    <label>
        <input type="radio" name="action" value="room" checked="checked">
        <?= _("Raum:"); ?>
    </label>

    <select name="room" onFocus="jQuery('input[type=radio][name=action][value=room]').attr('checked', 'checked')">
        <option value="">-- <?= _('Raum auswählen') ?> --</value>
        <? while ($res = $resList->next()) : ?>
            <option value="<?= $res['resource_id'] ?>">
                <?= my_substr(htmlReady($res["name"]), 0, 30) ?> <?= $seats[$res['resource_id']] ? '('. $seats[$res['resource_id']] .' '. _('Sitzplätze') .')' : '' ?>
            </option>
        <? endwhile; ?>
    </select>

    <?= Assets::img('icons/16/grey/room_clear.png', array(
        'class'     => 'bookable_rooms_action',
        'title'     => _("Nur buchbare Räume anzeigen"),
        'data-name' => 'bulk_action'
    )) ?>

    <br>
    <br>

    <label>
        <input type="radio" name="action" value="freetext">
        <?= _('freie Ortsangabe (keine Raumbuchung):') ?><br>
    </label>

    <? else : ?>

    <br>
    <label>
        <input type="radio" name="action" value="freetext">
        <?= _('freie Ortsangabe:') ?><br>
    </label>
    <? endif ?>

    <input type="text" name="freeRoomText" maxlength="255" value="<?= $tpl['freeRoomText'] ?>" style="margin-left: 25px; width: 90%;"
        onFocus="jQuery('input[type=radio][name=action][value=freetext]').attr('checked', 'checked')">
    <br>
    <br>

    <? if ($GLOBALS['RESOURCES_ENABLE']) : ?>
        <label>
            <input type="radio" name="action" value="noroom">
            <?=_('kein Raum') ?>
        </label>
        <br>
    <? endif ?>

    <br>
    <label>
        <input type="radio" name="action" value="nochange" checked="checked">
        <?=_('keine Änderungen an den Raumangaben vornehmen') ?>
    </label>
    <br>
</div>
<br style="clear: both;"><br>

    
<div style="text-align: center">
    <div class="button-group">
        <?= Button::createAccept(_('Übernehmen')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'),
                URLHelper::getURL('?')) ?>
    </div>
</div>

<input type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
<input type="hidden" name="cmd" value="bulkAction">
