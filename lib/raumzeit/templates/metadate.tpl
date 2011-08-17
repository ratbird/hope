<?
# Lifter010: TODO
?>
<?
if (!$sd_open[$tpl['md_id']] || $_LOCKED) { ?>
<TR>
    <TD class="steel1" colspan="9">
        <A name="<?=$tpl['md_id']?>" />
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD width="2%" align="right" valign="center" class="<?=$tpl['class']?>">
                    <A href="<?= URLHelper::getLink('?cmd=open&open_close_id=' . $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
                    <?= Assets::img('icons/16/blue/arr_1right.png', array('class' => 'text-top')) ?>
                    </A>
                </TD>
                <TD width="23%" nowrap="nowrap" class="<?=$tpl['class']?>">
                    <? if (!$_LOCKED || !$sd_open[$tpl['md_id']]) { ?>
                    <A class="tree" href="<?= URLHelper::getLink('?cmd=open&open_close_id='. $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
                    <? } else { ?>
                    <A class="tree" href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
                    <? } ?>
                        <FONT size="-1" <?=tooltip($tpl['date_tooltip'], false)?>>
                            <?=htmlready($tpl['date'])?>
                        </FONT>
                    </A>
                </TD>
                <? if ($GLOBALS['RESOURCES_ENABLE']) { ?>
                <TD width="35%" nowrap="nowrap" class="<?=$tpl['class']?>">
                    <FONT size="-1">
                        <B><?=_("Raum:")?></B>
                        <?=$tpl['room']?>
                    </FONT>
                    <? if ($tpl['ausruf']) { ?>
                    <a href="javascript:alert('<?=$tpl['ausruf']?>')">
                        <?= Assets::img('icons/16/red/exclaim-circle.png', tooltip(_("Wichtige Informationen über Raumbuchungen anzeigen"))) ?>
                    </a>
                    <? } ?>
                    <? if ($tpl['room_request_ausruf']) { ?>
                        &nbsp;
                        <A href="javascript:;" onClick="alert('<?=jsReady($tpl['room_request_ausruf'], 'inline-single')?>');">
                            <?= Assets::img($tpl['symbol'], tooltip($tpl['room_request_ausruf']))?>
                        </A>
                    <? } ?>
                </TD>
                <TD width="20%" nowrap="nowrap" class="<?=$tpl['class']?>">
                <? if( $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) : ?>
                    <FONT size="-1">
                        <B><?=_("Einzel-Raumanfragen:")?></B>
                        <?=$tpl['anfragen']?>
                    </FONT>
                <? endif; ?>
                </TD>
                <? } else { ?>
                <TD width="55%" class="<?=$tpl['class']?>">&nbsp;</TD>
                <? } ?>
                <TD width="20%" nowrap="nowrap" class="<?=$tpl['class']?>" align="right">
                    <? if (!$_LOCKED) { ?>
                        <? if ($show_sorter) : ?>
                            <a href="<?=URLHelper::getLink('?cmd=moveCycle&direction=up&cycle_id='. $tpl['md_id']) ?>">
                            <?= Assets::img('icons/16/yellow/arr_2up.png', array('align' => 'absmiddle'))?>
                            </a>
                            <a href="<?=URLHelper::getLink('?cmd=moveCycle&direction=down&cycle_id='. $tpl['md_id']) ?>">
                            <?= Assets::img('icons/16/yellow/arr_2down.png', array('align' => 'absmiddle'))?>
                            </a>
                        <? endif;?>
                    <A href="<?= URLHelper::getLink('?cmd=deleteCycle&cycle_id='. $tpl['md_id']) ?>">
                        <?=Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _('Regelmäßige Zeit inklusive aller zugehörigen Termine löschen!'))) ?>
                    </A>
                    <? } ?>
                </TD>
            </TR>
        </TABLE>
    </TD>
<?
} else { ?>
<TR>
    <TD class="steel1" colspan="9">
        <A name="<?=$tpl['md_id']?>" />
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD width="2%" align="left" valign="top" class="<?=$tpl['class']?>" nowrap="nowrap">
                    <A href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
                        <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/arr_1down.png" border="0" valign="absmiddle">
                    </A>
                </TD>
                <TD width="93%" nowrap="nowrap" class="<?=$tpl['class']?>">
                    <FORM action="<?= URLHelper::getLink() ?>" method="post" name="EditCycle" style="display: inline">
                        <?= CSRFProtection::tokenTag() ?>
                        <FONT size="-1"><B>
                            <SELECT name="day">
                            <? foreach(array_merge(range(1,6), array(0)) as $d) : ?>
                                <OPTION value="<?=$d?>"<?=($tpl['mdDayNumber']==$d) ? 'selected="selected"' : ''?>><?=getWeekday($d, false)?></OPTION>
                            <? endforeach; ?>
                            </SELECT>,
                            <INPUT type="text" name="start_stunde" maxlength="2" size="2" value="<?=leadingZero($tpl['mdStartHour'])?>"> :
                            <INPUT type="text" name="start_minute" maxlength="2" size="2" value="<?=leadingZero($tpl['mdStartMinute'])?>">
                            <?=_("bis")?>
                            <INPUT type="text" name="end_stunde" maxlength="2" size="2" value="<?=leadingZero($tpl['mdEndHour'])?>"> :
                            <INPUT type="text" name="end_minute" maxlength="2" size="2" value="<?=leadingZero($tpl['mdEndMinute'])?>"> Uhr
                            <?=Termin_Eingabe_javascript(2,0,0,$tpl['mdStartHour'],$tpl['mdStartMinute'],$tpl['mdEndHour'],$tpl['mdEndMinute']);?>
                            &nbsp;&nbsp;<?=_("Beschreibung:")?> <INPUT type="text" name="description" value="<?=$tpl['mdDescription']?>">
                            &nbsp;&nbsp;<INPUT type="image" name="editCycle" align="absmiddle" <?=makebutton('uebernehmen', 'src')?>>
                            <INPUT type="hidden" name="cycle_id" value="<?=$tpl['md_id']?>">
                        </B></FONT>
                         <br>
                        <?=_("Turnus")?>:
                        <select name="turnus">
                        <option value="0"<?=$tpl['cycle'] == 0 ? 'selected' : ''?>><?=_("wöchentlich");?></option>
                        <option value="1"<?=$tpl['cycle'] == 1 ? 'selected' : ''?>><?=_("zweiwöchentlich")?></option>
                        <option value="2"<?=$tpl['cycle'] == 2 ? 'selected' : ''?>><?=_("dreiwöchentlich")?></option>
                        </select>
                        &nbsp;&nbsp;
                        <?=_("beginnt in der")?>:
                        <select name="startWeek">
                        <?
                            foreach ($start_weeks as $value => $data) :
                                echo '<option value="'.$value.'"';
                                if ($tpl['week_offset'] == $value) echo ' selected="selected"';
                                echo '>'.$data['text'].'</option>', "\n";
                            endforeach;
                        ?>
                        </select>
                        &nbsp;&nbsp;
                        <?=_("SWS Dozent:")?>
                        &nbsp;
                        <INPUT type="text" name="sws" maxlength="3" size="1" value="<?=$tpl['sws']?>">
                        <? if($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) : ?>
                        <div style="padding-top:2px">
                        <?=_("Raumanfrage")?>
                        <A href="<?= URLHelper::getLink('dispatch.php/course/room_requests/edit/' .$tpl['seminar_id'], $tpl['room_request'] ? array('request_id' => $tpl['room_request']->request_id) : array('new_room_request_type' => 'cycle_' . $tpl['md_id'])) ?>">
                            <?=($tpl['room_request']) ? makebutton('bearbeiten', 'img') : makebutton('erstellen', 'img')?>
                        </A>
                        <? if ($tpl['room_request']) { ?>
                        <?=_("oder")?>
                        <A href="<?= URLHelper::getLink('?cmd=removeMetadateRequest&metadate_id='. $tpl['md_id'] ) ?>">
                            <?=($tpl['room_request']) ? makebutton('zurueckziehen', 'img') : ''?>
                        </A>
                        </div>
                        <? } ?>
                        <? endif;?>
                    </FORM></TD>
                <TD width="5%" nowrap="nowrap" class="<?=$tpl['class']?>" align="right">
                    <? if ($show_sorter) : ?>
                        <a href="<?=URLHelper::getLink('?cmd=moveCycle&direction=up&cycle_id='. $tpl['md_id']) ?>">
                        <?= Assets::img('icons/16/yellow/arr_2up.png', array('align' => 'absmiddle'))?>
                        </a>
                        <a href="<?=URLHelper::getLink('?cmd=moveCycle&direction=down&cycle_id='. $tpl['md_id']) ?>">
                        <?= Assets::img('icons/16/yellow/arr_2down.png', array('align' => 'absmiddle'))?>
                        </a>
                    <? endif; ?>
                    <A href="<?= URLHelper::getLink('?cmd=deleteCycle&cycle_id='. $tpl['md_id']) ?>">
                        <?=Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _('Regelmäßige Zeit inklusive aller zugehörigen Termine löschen!'))) ?>
                    </A>

                </TD>
            </TR>

        </TABLE>
    </TD>
<?
}
unset($tpl);
