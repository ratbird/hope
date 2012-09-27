<?
# Lifter010: TODO
use Studip\Button,
    Studip\LinkButton;
?>
<tr>
    <td class="table_row_even" colspan="9">
        <A name="<?=$tpl['md_id']?>"></A>
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD width="2%" align="right" valign="center" class="<?=$tpl['class']?>" style="height: 27px">
                    <? if (Request::option('cycle_id') == $tpl['md_id']) : ?>
                    <a href="<?= URLHelper::getLink('?#' . $tpl['md_id']) ?>">
                        <?= Assets::img('icons/16/blue/arr_1down.png', array('class' => 'text-top')) ?>
                    </a>
                    <? else : ?>
                    <a href="<?= URLHelper::getLink('?cycle_id=' . $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
                        <?= Assets::img('icons/16/blue/arr_1right.png', array('class' => 'text-top')) ?>
                    </a>
                    <? endif ?>
                    </A>
                </TD>
                <TD width="23%" nowrap="nowrap" class="<?=$tpl['class']?>" <?=tooltip($tpl['date_tooltip'], false)?>>
                    <? if (Request::option('cycle_id') == $tpl['md_id']) : ?>
                    <a class="tree" href="<?= URLHelper::getLink('?#'. $tpl['md_id']) ?>">
                    <? else : ?>
                    <a class="tree" href="<?= URLHelper::getLink('?cycle_id='. $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
                    <? endif ?>
                        <?=htmlready($tpl['date'])?>
                    </a>
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

                    <a href="<?= URLHelper::getLink('?editCycleId='. $tpl['md_id']) ?>" style="margin-right: 10px">
                        <?=Assets::img('icons/16/blue/edit.png', array('class' => 'text-top', 'title' => _('Regelmäßigen Termin bearbeiten.'))) ?>
                    </a>

                    <A href="<?= URLHelper::getLink('?cmd=deleteCycle&cycle_id='. $tpl['md_id']) ?>">
                        <?=Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _('Regelmäßige Zeit inklusive aller zugehörigen Termine löschen!'))) ?>
                    </A>
                    <? } ?>
                </TD>
            </TR>
        </TABLE>
    </TD>
</tr>    
<? if (Request::option('editCycleId') == $tpl['md_id']) : ?>
<tr>
    <td class="table_row_even" colspan="9" style="padding-left: 10px">
        <A name="<?=$tpl['md_id']?>"></A>
        
                    <FORM action="<?= URLHelper::getLink() ?>" method="post" name="EditCycle" style="display: inline">
                        <?= CSRFProtection::tokenTag() ?>
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
                        
                        <span style="padding-left: 15px">
                            <?=_("SWS:")?>
                            <input type="text" name="sws" maxlength="3" size="1" value="<?=$tpl['sws']?>">
                        </span>
                        <br><br>

                        <?=_("Turnus")?>:
                        <select name="turnus">
                        <option value="0"<?=$tpl['cycle'] == 0 ? 'selected' : ''?>><?=_("wöchentlich");?></option>
                        <option value="1"<?=$tpl['cycle'] == 1 ? 'selected' : ''?>><?=_("zweiwöchentlich")?></option>
                        <option value="2"<?=$tpl['cycle'] == 2 ? 'selected' : ''?>><?=_("dreiwöchentlich")?></option>
                        </select>
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
                        <br><br>

                        <?=_("Beschreibung:")?> <input type="text" name="description" value="<?=$tpl['mdDescription']?>" style="width: 450px">
                        <input type="hidden" name="cycle_id" value="<?=$tpl['md_id']?>">
                        <br><br>
                        
                        <div style="text-align: center">
                            <div class="button-group">
                                <?= Button::createAccept(_('Übernehmen'), 'editCycle') ?>
                                <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getUrl()) ?>
                            </div>
                            
                            <div class="button-group">
                                <? if($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) : ?>
                                    <?= LinkButton::create($tpl['room_request'] ? _('Raumanfrage bearbeiten') : _('Raumanfrage erstellen'),
                                        URLHelper::getURL('dispatch.php/course/room_requests/edit/' .$tpl['seminar_id'], $tpl['room_request'] ? array('request_id' => $tpl['room_request']->request_id) : array('new_room_request_type' => 'cycle_' . $tpl['md_id'])),
                                        array('onClick' => "STUDIP.RoomRequestDialog.initialize(this.href.replace('edit','edit_dialog'));return false;")) ?>

                                    <? if ($tpl['room_request']) : ?>
                                        <?=_("oder")?>
                                        <?= LinkButton::create(_('Raumfrage zurückziehen'), URLHelper::getURL('?cmd=removeMetadateRequest&metadate_id='. $tpl['md_id'])) ?>
                                    <? endif ?>
                                <? endif ?>
                            </div>
                        </div>
                    </FORM>
    </td>
</tr>
<? endif ?>
<? unset($tpl); ?>