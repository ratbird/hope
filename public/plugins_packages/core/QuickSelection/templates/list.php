<div id="quickSelectionWrap" style="padding: 1ex;">
    <div id="quickSelectionDiagWrap">
    </div>
    <? foreach ($navigation as $nav) : ?>
        <? if ($nav->isVisible()) : ?>
            <div class="mainmenu">
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
    <? endforeach ?>
</div>
