<?
# Lifter010: TODO
?>
<? if (!$tpl['deleted']) { ?>
<TR>
    <? if ($tpl['space']) { ?>
    <TD width="4%" class="table_row_even">
        &nbsp;
    </TD>
    <? } ?>
    <TD width="90%" nowrap class="<?=$tpl['class']?>"<?=!$tpl['space'] ? ' colspan="3"' : ' colspan="2"'?>>
        <?= Assets::input('icons/16/yellow/arr_2right.png',
                          tooltip2(_('Ausgew�hlte Themen diesem Termin zuordnen')) +
                          array('name' => $tpl['sd_id'] . ($tpl['cycle_id'] ? '_'.$tpl['cycle_id'] : ''))) ?>
        <FONT size="-1" color="#000000">
            <?=$tpl['date']?>,
            <?=$tpl['room']?>
        </FONT>
    </TD>
</TR>
<? if ($tpl['art']) { ?>
<TR>
    <? if ($tpl['space']) { ?>
    <TD width="4%" class="table_row_even">
        &nbsp;
    </TD>
    <? } ?>
    <TD width="90%" nowrap class="table_row_odd"<?=!$tpl['space'] ? ' colspan="3"' : ' colspan="2"'?>>
        <FONT size="-1">
            <I>&nbsp;<?=_("Terminart:")?>&nbsp;<?=$tpl['art'];?></I>
        </FONT>
    </TD>
</TR>
<? } ?>
<? } else { ?>
<TR>
    <? if ($tpl['space']) { ?>
    <TD width="2%" class="table_row_even">
        &nbsp;
    </TD>
    <? } ?>
    <TD width="90%" nowrap class="<?=$tpl['class']?>"<?=!$tpl['space'] ? ' colspan="3"' : ' colspan="2"'?>>
        <FONT size="-1" color="#666666">
            <?=$tpl['date']?>,
            <?=$tpl['room']?>
        </FONT>
    </TD>
</TR>
<? }
    unset($tpl)
?>
