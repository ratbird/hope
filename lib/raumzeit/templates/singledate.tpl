<?
# Lifter010: TODO
?>
<? if (!$tpl['deleted']) { ?>
<TR>
    <TD width="2%" align="right" valign="center" class="<?=$tpl['class']?>" nowrap="nowrap" height="25">
        <A name="<?=$tpl['sd_id']?>" />
        <? if (!$_LOCKED) { ?>
        <A href="<?= URLHelper::getLink('?cmd='. ($sd_open[$tpl['sd_id']] ? 'close' : 'open') . '&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
            <?= Assets::img('icons/16/blue/arr_1right.png', array('class' => 'text-top')) ?>
        </A>
        <INPUT type="checkbox" name="singledate[]" value="<?=$tpl['sd_id']?>" <?=$tpl['checked']?>>
        <? } ?>
    </TD>
    <TD width="43%" nowrap class="<?=$tpl['class']?>">
        <? if (!$_LOCKED) { ?>
        <A class="tree" href="<?= URLHelper::getLink('?cmd='. ($sd_open[$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
        <? } ?>
            <FONT color="#000000">
                <?=$tpl['date']?>
            </FONT>
        <? if (!$_LOCKED) { ?>
        </A>
        <? } ?>
    </TD>
    <TD width="30%" nowrap class="<?=$tpl['class']?>">
        <FONT color="#000000">
            <?=$tpl['room']?>
        </FONT>
        <? if ($tpl['ausruf']) { ?>
            <A href="javascript:;" onClick="alert('<?=jsReady($tpl['ausruf'], 'inline-single')?>')">
                <?= Assets::img($tpl['symbol'], tooltip($tpl['ausruf']))?>
            </A>
        <? } ?>
    </TD>
    <TD width="20%" nowrap class="<?=$tpl['class']?>" align="right">
        <? if (!$_LOCKED) { ?>
        <A href="<?= URLHelper::getLink('?cmd='. ($sd_open[$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/edit.png" <?=tooltip(_("Termin bearbeiten"))?>>
        </A>
        <A href="<?= URLHelper::getLink('?cmd=delete_singledate&sd_id='. $tpl['sd_id'] .'&cycle_id='. $tpl['cycle_id'])?>">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/trash.png" <?=tooltip(_("Termin löschen"))?>>
        </A>
        <? } ?>
    </TD>
</TR>
<? } else { ?>
<TR>
    <TD width="2%" align="right" valign="center" class="<?=$tpl['class']?>" nowrap="nowrap">
        <? if (!$_LOCKED) { ?>
            <? if ($GLOBALS['perm']->have_perm('dozent')) : ?>
                <A href="<?= URLHelper::getLink('?cmd='. ($sd_open[$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
                    <?= Assets::img('icons/16/blue/arr_1right.png') ?>
                </A>
            <? else : ?>
                <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/arr_1right.png">
            <? endif; ?>
            <INPUT type="checkbox" name="singledate[]" value="<?=$tpl['sd_id']?>" <?=$tpl['checked']?>>
        <? } ?>
    </TD>
    <TD width="43%" nowrap class="<?=$tpl['class']?>">
        <FONT size="-1" color="#666666">
            <?=$tpl['date']?>
        </FONT>
    </TD>
    <TD width="30%" nowrap class="<?=$tpl['class']?>">
        <? if ($tpl['comment']) : ?>
            <i><?=_("Kommentar")?>: <?=$tpl['comment']?></i>
        <? else : ?>
        <font color="#666666">
            <?=$tpl['room']?>
        </font>
        <? endif; ?>
    </TD>
    <TD width="20%" nowrap class="<?=$tpl['class']?>" align="right">
        <? if (!$_LOCKED) { ?>
        <a href="<?= URLHelper::getLink('?cmd=undelete_singledate&sd_id='. $tpl['sd_id'] .'&cycle_id='. ($tpl['cycle_id'] ? $tpl['cycle_id'] : ''))?>">
            <?= Assets::img('icons/16/grey/decline/trash.png', array('class' => 'text-top', 'title' => _("Termin wiederherstellen")))?>
        </A>
        <? } ?>
    </TD>
</TR>
<? }
unset($tpl);
