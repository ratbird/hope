<div role="article">
    <? foreach ($terminItemArray as $termin_id => $termin_item) :?>

    <div id="termin_item_<?= $termin_id ?>" class="news_item" role="article">
        <table cellpadding="0" cellspacing="0"><tr>

                <td bgcolor="<?=$termin_item["timecolor"]?>" class="printhead2" nowrap="nowrap" width="1%" align="left" valign="center">

                    <a href="#" onclick="TERMIN_WIDGET.openclose('<?= $termin_id ?>'); return false;" >

                        <img alt="" src="<?=Assets::image_path('forumgrau2.png')?> " tooltip="<?=(_("Objekt aufklappen"))?>" >

                    </a>

                </td><td  nowrap="nowrap" width="1%" class="printhead" valign="bottom"> <?=$termin_item["icon"].$termin_item["titel"]?>  </td>
                <td align="right" nowrap="nowrap" class="printhead" width="99%" valign="bottom"><?= $termin_item["zusatz"] ?></td>
            </tr> </table>
    </div>
    <div id="termin_item_<?= $termin_id ?>_content" style="display:none;" >
            <?= TerminWidget::show_termin_item_content($termin_item, $new, $range_id, $show_admin) ?>
    </div>

    <? endforeach;?>

</div>
