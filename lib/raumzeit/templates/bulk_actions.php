<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>

<a name="Stapelaktionen"></a>

<div style="clear: both">
    <b><?= _('F�r die ausgew�hlten Termine folgende Aktionen durchf�hren:') ?></b>
    <br>
    <br>
</div>
<div style="display: table-row;">
    <div style="display: table-cell; width: 33%;">
        <b>
            <?=_("Durchf�hrende Dozenten")?> ...
        </b>
        <br>

        <select name="related_persons_action" style="width: 100%" aria-label="<?= _("W�hlen Sie aus, ob Dozenten den ausgew�hlten regelm��igen Terminen hinzugef�gt, von diesen entfernt oder f�r diese Termine definiert werden sollen.") ?>">
            <option value="">-- <?= _('Aktion ausw�hlen') ?> --</option>
            <option value="add" title="<?= _("Die ausgew�hlten Dozenten werden den ausgew�hlten Terminen hinzugef�gt. Die zuvor schon durchf�hrenden Dozenten bleiben aber weiterhin zus�tzlich eingetragen.") ?>">... <?= _("hinzuf�gen") ?></option>
            <option value="delete" title="<?= _("Die ausgew�hlten Dozenten leiten nicht die ausgew�hlten Termine. Andere Dozenten bleiben bestehen.") ?>">... <?= _("entfernen") ?></option>
        </select>
        <br>

        <select name="related_persons[]" multiple style="vertical-align: top; width: 100%;" aria-label="<?= _("W�hlen Sie die Dozenten aus, die regelm��igen Terminen hinzugef�gt oder von diesen entfernt werden sollen.") ?>">
            <? foreach ($sem->getMembers('dozent') as $dozent) : ?>
            <option value="<?= htmlReady($dozent['user_id']) ?>"><?= htmlReady($dozent['fullname']) ?></option>
            <? endforeach ?>
        </select>
        <!--<?= Button::create(_('�bernehmen'), 'related_persons_action_do') ?>-->
        <br>
        <br>
    </div>
    
    <? $gruppen = Course::find($sem->getId())->statusgruppen ?>
    <? if (count($gruppen) > 0) : ?>
    <div style="display: table-cell; width: 33%;">
        <b>
            <?=_("Betrifft die Gruppen")?> ...
        </b>
        <br>

        <select name="related_groups_action" style="width: 100%" aria-label="<?= _("W�hlen Sie aus, ob Dozenten den ausgew�hlten regelm��igen Terminen hinzugef�gt, von diesen entfernt oder f�r diese Termine definiert werden sollen.") ?>">
            <option value="">-- <?= _('Aktion ausw�hlen') ?> --</option>
            <option value="add" title="<?= _("Die ausgew�hlten Dozenten werden den ausgew�hlten Terminen hinzugef�gt. Die zuvor schon durchf�hrenden Dozenten bleiben aber weiterhin zus�tzlich eingetragen.") ?>">... <?= _("hinzuf�gen") ?></option>
            <option value="delete" title="<?= _("Die ausgew�hlten Dozenten leiten nicht die ausgew�hlten Termine. Andere Dozenten bleiben bestehen.") ?>">... <?= _("entfernen") ?></option>
        </select>
        <br>

        <select name="related_groups[]" multiple style="vertical-align: top; width: 100%;" aria-label="<?= _("W�hlen Sie die Gruppen aus, f�r die die Termine gelten. Ist keine Gruppe ausgew�hlt, gilt der Termin f�r alle Nutzer und Gruppen der Veranstaltung.") ?>">
            <? foreach ($gruppen as $gruppe) : ?>
            <option value="<?= htmlReady($gruppe->getId()) ?>"><?= htmlReady($gruppe['name']) ?></option>
            <? endforeach ?>
        </select>
        <!--<?= Button::create(_('�bernehmen'), 'related_groups_action_do') ?>-->
        <br>
        <br>
    </div>
    <? endif ?>

    <div style="display: table-cell; width: 33%;">
        <b><?= _('Raumangaben:') ?></b><br>
        <? if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) : ?>
        <? $resList->reset() ?>
        <label>
            <input type="radio" name="action" value="room" checked="checked">
            <?= _("Raum:"); ?>
        </label>

        <select name="room" onFocus="jQuery('input[type=radio][name=action][value=room]').attr('checked', 'checked')">
            <option value="">-- <?= _('Raum ausw�hlen') ?> --</value>
            <? while ($res = $resList->next()) : ?>
                <option value="<?= $res['resource_id'] ?>">
                    <?= my_substr(htmlReady($res["name"]), 0, 30) ?> <?= $seats[$res['resource_id']] ? '('. $seats[$res['resource_id']] .' '. _('Sitzpl�tze') .')' : '' ?>
                </option>
            <? endwhile; ?>
        </select>

        <?= Assets::img('icons/16/grey/room-clear.png', array(
            'class'     => 'bookable_rooms_action',
            'title'     => _("Nur buchbare R�ume anzeigen"),
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
            <?=_('keine �nderungen an den Raumangaben vornehmen') ?>
        </label>
        <br>
    </div>
</div>
<br style="clear: both;"><br>

    
<div style="text-align: center">
    <div class="button-group">
        <?= Button::createAccept(_('�bernehmen')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'),
                URLHelper::getURL('?')) ?>
    </div>
</div>

<input type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
<input type="hidden" name="cmd" value="bulkAction">
