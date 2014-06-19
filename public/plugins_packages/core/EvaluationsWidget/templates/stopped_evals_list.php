
<?if(is_array($stoppedEvalItemArray)) : ?>
    <? foreach ($stoppedEvalItemArray as $eval_item) :?>
<div id="eval_item_<?= $eval_item["id"] ?>" class="eval_item" role="article" data-url="<?= htmlReady(PluginEngine::getURL($plugin, array(), 'create_open_eval_content')) ?>">
    <table cellpadding="0" cellspacing="0"><tr>

            <td class="printhead2" nowrap="nowrap" width="1%" align="left" valign="center">

            </td><td  nowrap="nowrap" width="1%" class="printhead" valign="bottom"> <?=$eval_item["icon"].$eval_item["title"]?>  </td>
            <td align="right" nowrap="nowrap" class="printhead" width="99%" valign="bottom"><?= $eval_item["zusatz"] ?></td>
        </tr> </table>
</div>
<div id="eval_item_<?= $eval_item["id"] ?>_content" >
            <? require_once ("lib/evaluation/evaluation_show.lib.php") ;?>

    <table class ="inday" width="100%" border="0">
        <tr>
            <td style="font-size:0.8em;">
                        <? $eval = $eval_item["eval"];?>
                        <?=$eval->getText (); ?>

                        <? $stopdate = $eval->getRealStopdate();
                        $number   = EvaluationDB::getNumberOfVotes( $eval->getObjectID() );
                        $voted    = $votedNow || $votedEarlier; ?>
            </td></tr>
        <tr><td align="left" style="font-size:0.8em;">
                <div align="left" style="margin-left:3px; margin-right:3px;">
                    <hr noshade="noshade" size="1">
                            <?= EvalShow::getNumberOfVotesText( $eval, $voted );?>
                    <br>
                            <?=EvalShow::getAnonymousText( $eval, $voted );?>
                    <br>
                            <?= EvalShow::getStopdateText( $eval, $voted );?>
                    <br></div></td></tr>
        <tr><td align="center">
                        <?= EvalShow::createOverviewButton ($rangeID2, $evalID);?>
                        <?= EvalShow::createContinueButton ($eval);?>
                        <?= EvalShow::createDeleteButton ($eval);?>
                        <?= EvalShow::createExportButton ($eval);?>
                        <?= EvalShow::createReportButton ($eval);?>
            </td></tr></table>
</div>

    <? endforeach;?>
<? endif;?>
<?if(is_array($stoppedVoteItemArray)) : ?>
    <? foreach ($stoppedVoteItemArray as $vote_item) :?>

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
