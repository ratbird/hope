<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<? if (!$_LOCKED) { ?>
<TABLE cellpadding="1" cellspacing="0" border="0" width="<?=$tpl['width']?>">
    <TR>
        <TD class="steel1" colspan="2">
            &nbsp;&nbsp;&nbsp;
            <SELECT name="checkboxAction">
                <OPTION value="noSelection">-- <?=_("Aktion ausw&auml;hlen")?> --</OPTION>
                <OPTION value="chooseAll"><?=_("alle ausw&auml;hlen")?></OPTION>
                <OPTION value="chooseNone"><?=_("Auswahl aufheben")?></OPTION>
                <OPTION value="invert"><?=_("Auswahl umkehren")?></OPTION>
                <OPTION value="deleteChoosen"><?=_("ausgew&auml;hlte l&ouml;schen")?></OPTION>
                <OPTION value="unDeleteChoosen"><?=_("ausgew&auml;hlte wiederherstellen")?></OPTION>
                <OPTION value="deleteAll"><?=_("alle l&ouml;schen")?></OPTION>
                <OPTION value="chooseEvery2nd"><?=_("jeden 2. ausw&auml;hlen")?></OPTION>
            </SELECT>
            <?= Button::createAccept(_('Ok'), 'checkboxAction') ?>
        </TD>
    </TR>
    <TR>
        <TD colspan="2" class="steel1">
            &nbsp;
        </TD>
    </TR>
    <TR>
        <TD align="left" class="steelgraulight">&nbsp;</TD>
        <TD align="left" class="steelgraulight">
            <FONT size="-1">
                <B><?=_("Ausgewählten Terminen Dozenten hinzufügen oder entfernen.")?>&nbsp;</B>
            </FONT><BR/>
    </TR>
    <TR>
        <TD align="left" class="steelgraulight">&nbsp;</TD>
        <TD colspan="8" class="steelgraulight" align="left">
            <select name="related_persons_action" aria-label="<?= _("Wählen Sie aus, ob Dozenten den ausgewählten regelmäßigen Terminen hinzugefügt, von diesen entfernt oder für diese Termine definiert werden sollen.") ?>">
                <option value=""><?= _("-- Aktion auswählen --") ?></option>
                <option value="add" title="<?= _("Die ausgewählten Dozenten werden den ausgewählten Terminen hinzugefügt. Die zuvor schon durchführenden Dozenten bleiben aber weiterhin zusätzlich eingetragen.") ?>"><?= _("durchführende Dozenten hinzufügen") ?></option>
                <option value="delete" title="<?= _("Die ausgewählten Dozenten leiten nicht die ausgewählten Termine. Andere Dozenten bleiben bestehen.") ?>"><?= _("durchführende Dozenten entfernen") ?></option>
            </select>
            <select name="related_persons[]" multiple style="vertical-align: top;" aria-label="<?= _("Wählen Sie die Dozenten aus, die regelmäßigen Terminen hinzugefügt oder von diesen entfernt werden sollen.") ?>">
                <? foreach ($sem->getMembers('dozent') as $dozent) : ?>
                <option value="<?= htmlReady($dozent['user_id']) ?>"><?= htmlReady($dozent['Vorname']." ".$dozent['Nachname']) ?></option>
                <? endforeach ?>
            </select>
            <?= Button::create(_('Übernehmen'), 'related_persons_action_do') ?>
            <br>
        </TD>
    </TR>
    <TR>
        <TD colspan="2" class="steel1">
            &nbsp;
        </TD>
    </TR>
    <TR>
        <TD align="left" class="steelgraulight">&nbsp;</TD>
        <TD align="left" class="steelgraulight">
            <FONT size="-1">
                <B><?=_("ausgew&auml;hlte Termine")?>&nbsp;</B>
            </FONT><BR/>
    </TR>
    <TR>
        <TD align="left" class="steelgraulight">&nbsp;</TD>
        <TD colspan="8" class="steelgraulight" align="left">
            <FONT size="-1">
            <?
                if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) :
                    $resList->reset();
                    echo _("Raum:");
            ?>
            <?= Assets::img('icons/16/blue/room_clear.png', array('class' => 'bookable_rooms_action', 'title' => _("Nur buchbare Räume anzeigen"))) ?>
            <SELECT name="room">
                <OPTION value="nochange" selected><?=_("keine &Auml;nderung")?></option>
                <OPTION value="retreat"><?=_("Raumbuchung aufheben")?></option>
                <OPTION value="nothing"><?=_("keine Buchung, nur Textangabe")?></option>
                <?
                    while ($res = $resList->next()) {
                        echo '<OPTION value="'.$res['resource_id'].'">'.my_substr(htmlReady($res["name"]), 0, 30).'</OPTION>';
                    }
                ?>
            </SELECT>
            <?= Button::create(_('Buchen'), 'bookRoom') ?>
            <? endif; ?>
            <?=_("freie Ortsangabe")?>:
            <INPUT type="text" name="freeRoomText" size="50" maxlength="255">
            <?=$GLOBALS['RESOURCES_ENABLE']? _("(f&uuml;hrt <em>nicht</em> zu einer Raumbuchung)") : ''?>
            <?= Button::create(_('Übernehmen'), 'freeText') ?>
            </FONT>
        </TD>
    </TR>
</TABLE>
<INPUT type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
<? } ?>
