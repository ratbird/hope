<table class="blank"  align="center" valign="top" width="100%" border="0" cellpadding="1" cellspacing="0">
    <tr valign="top">
        <td class="table_row_odd" align="left" width="40%">
            <font size="-1"><b>
            <?=$title?>
            </b></font>
        </td>
        <td class="table_row_odd" align="left" width="40%">
        <? if ($all_open) : ?>
            <a href="<?=URLHelper::getURL('?close_all=1&view='.$view.'&cms_select='.$cms_select.'&search_key='.$search_key)?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/close_all.png" alt="<?=_("Alle Module schließen")?>" title="<?=_("Alle Module schließen")?>" border="0">
            </a>
        <? else : ?>
            <a href="<?=URLHelper::getLink('?open_all=1&view='.$view.'&cms_select='.$cms_select.'&search_key='.$search_key)?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/open_all.png" alt="<?=_("Alle Module öffnen")?>" title="<?=_("Alle Module öffnen")?>"  border="0">
            </a>
        <? endif?>
        </td>
    </tr>
</table>
