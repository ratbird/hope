<table class="index_box">

    <tr>
        <td class="blank" valign="top" style="padding-left:25px; width:80%;" id="index_navigation">
            <div id="quickSelectionDiagWrap">

            </div>
            <div id="quickSelectionWrap">


                <? foreach ($add_removesNames as $nav) : ?>
                <? if (!empty($nav)) : ?>
                    <? if ($nav->isVisible()) : ?>
                <div class="mainmenu" id="quickSelectionDiag">
                            <? if (is_internal_url($url = $nav->getURL())) : ?>
                    <a href="<?= URLHelper::getLink($url) ?>">
                                <? else : ?>
                        <a href="<?= htmlReady($url) ?>" target="_blank">
                                    <? endif ?>
                                    <?= htmlReady($nav->getTitle()) ?></a>
                                <? $pos = 0 ?>
                                <? foreach ($nav as $subnav) : ?>
                                    <? if ($subnav->isVisible()) : ?>
                        <font size="-1">
                                            <?= $pos++ ? ' / ' : '<br>' ?>
                                            <? if (is_internal_url($url = $subnav->getURL())) : ?>
                            <a href="<?= URLHelper::getLink($url) ?>">
                                                <? else : ?>
                                <a href="<?= htmlReady($url) ?>" target="_blank">
                                                    <? endif ?>
                                                    <?= htmlReady($subnav->getTitle()) ?></a>
                        </font>

                            <? endif ?>
                        <? endforeach ?>

            </div>
                <? endif ?>
                <? endif?>
            <? endforeach ?>

                </div>
        </td>

    </tr>
</table>
