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
            <INPUT type="image" <?=makebutton('ok', 'src')?> name="checkboxAction" align="absmiddle">
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
            <INPUT type="image" <?=makebutton('buchen', 'src')?> name="bookRoom" align="absmiddle"><BR/>
            <? endif; ?>
            <?=_("freie Ortsangabe")?>:
            <INPUT type="text" name="freeRoomText" size="50" maxlength="255">
            <?=$GLOBALS['RESOURCES_ENABLE']? _("(f&uuml;hrt <em>nicht</em> zu einer Raumbuchung)") : ''?>
            <INPUT type="image" <?=makebutton('uebernehmen', 'src')?> name="freeText" align="absmiddle"><BR/>
            </FONT>
        </TD>
    </TR>
</TABLE>
<INPUT type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
<? } ?>
