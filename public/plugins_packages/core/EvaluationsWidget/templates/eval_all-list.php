<div role="article">
    <?if(is_array($evalItemArray)) : ?>
        <? foreach ($evalItemArray as $eval_item) :?>
    <div id="eval_item_<?= $eval_item["id"] ?>" class="evaluation_item" role="article" data-url="<?= htmlReady(PluginEngine::getURL($plugin, array(), 'create_open_eval_content')) ?>">
        <table cellpadding="0" cellspacing="0"><tr>

                <td bgcolor="<?=$eval_item["timecolor"]?>" class="printhead2" nowrap="nowrap" width="1%" align="left" valign="center">
                    <a href="#" onclick="EVALUATIONSWIDGET.openclose('<?=$eval_item["id"]?>','eval'); return false;" >
                        <img alt="" src="<?=Assets::image_path('forumgrau2.png')?> " tooltip="<?=(_("Objekt aufklappen"))?>" >
                    </a>
                </td><td  nowrap="nowrap" width="1%" class="printhead" valign="bottom"> <?=$eval_item["icon"].$eval_item["title"]?>  </td>
                <td align="right" nowrap="nowrap" class="printhead" width="99%" valign="bottom"><?= $eval_item["zusatz"] ?></td>
            </tr> </table>
    </div>
    <div id="eval_item_<?= $eval_item["id"] ?>_content" style="display:none;" >

    </div>

        <? endforeach;?>
    <? endif;?>
    <?if(is_array($voteItemArray)) : ?>
        <? foreach ($voteItemArray as $vote_item) :?>

    <div id="vote_item_<?= $vote_item["id"] ?>" class="evaluation_item" role="article"  data-url="<?= htmlReady(PluginEngine::getURL($plugin, array(),'create_open_vote_content')) ?>">
        <table cellpadding="0" cellspacing="0"><tr>

                <td bgcolor="<?=$vote_item["timecolor"]?>" class="printhead2" nowrap="nowrap" width="1%" align="left" valign="center">

                    <a href="#" onclick="EVALUATIONSWIDGET.openclose('<?=$vote_item["id"]?>','vote'); return false;" >

                        <img alt="" src="<?=Assets::image_path('forumgrau2.png')?> " tooltip="<?=(_("Objekt aufklappen"))?>" >

                    </a>

                </td><td  nowrap="nowrap" width="1%" class="printhead" valign="bottom"> <?=$vote_item["icon"].$vote_item["title"]?>  </td>
                <td align="right" nowrap="nowrap" class="printhead" width="99%" valign="bottom"><?= $vote_item["voteInfo"] ?></td>
            </tr> </table>
    </div>
    <div id="vote_item_<?= $vote_item["id"] ?>_content" style="display:none;" >

    </div>

        <? endforeach;?>
    <? endif;?>
    <? if($stopped) : ?>
    <div  class="evaluation_item" role="article"  >
        <table cellpadding="0" cellspacing="0"><tr>

                <td bgcolor="<?=$vote_item["timecolor"]?>" class="printhead2" nowrap="nowrap" width="1%" align="left" valign="bottom">

                    <a href="#" onclick="EVALUATIONSWIDGET.openclosestopped(); return false;" >

                        <img alt="" src="<?=Assets::image_path('forumgrau2.png')?> " tooltip="<?=(_("Objekt aufklappen"))?>" >

                    </a>


                </td><td  nowrap="nowrap" width="1%" class="printhead" valign="bottom"><img alt="" src="<?=Assets::image_path(VOTE_ICON_STOPPED)?>" > <?= _("Abgelaufene Umfragen")?>  </td>
                <td align="right" nowrap="nowrap" class="printhead" width="99%" valign="bottom"> </td>
            </tr> </table>

    </div>
    <div id="stopped_contend" style="display:none;" data-url="<?= htmlReady(PluginEngine::getURL($plugin, array(),'open_stopped_content')) ?>">

    </div>
    <? endif;?>
