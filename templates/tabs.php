<?
# Lifter010: TODO
?>
<? SkipLinks::addIndex(_("Erste Reiternavigation"), 'tabs', 10); ?>
<ul id="tabs" role="navigation">
    <? foreach ($navigation as $nav) : ?>
        <? if ($nav->isVisible()) : ?>
            <li<?= $nav->isActive() ? ' class="current"' : '' ?>>
                <? if ($nav->isEnabled()) : ?>
                    <a href="<?= URLHelper::getLink($nav->getURL()) ?>">
                        <? if ($image = $nav->getImage()) : ?>
                        <? $color = ($nav->isActive())? 'black/' : 'white/' ?>
                            <img class="tab-icon" src="<?= str_replace("%COLOR%", ($nav->isActive()) ? 'black' : 'white', $image['src']) ?>" title="<?= $nav->getTitle() ? htmlReady($nav->getTitle()) : htmlReady($nav->getDescription()) ?>" />
                        <? endif ?>
                        <span><?= $nav->getTitle() ? htmlReady($nav->getTitle()) : '&nbsp;' ?></span>
                    </a>
                <? else: ?>
                    <span class="quiet">
                        <? if ($image = $nav->getImage()) : ?>
                            <img class="tab-icon" src="<?=$image['src']?>" title="<?= htmlReady($nav->getTitle()) ?>" />
                        <? endif ?>
                        <?= htmlReady($nav->getTitle()) ?>
                    </span>
                <? endif ?>
            </li>
        <? endif ?>
    <? endforeach ?>
</ul>
<ul id="tabs2" role="navigation">
    <? $subnavigation = $navigation->activeSubNavigation() ?>
    <? if (isset($subnavigation)) : ?>
        <? foreach ($subnavigation as $nav) : ?>
            <? if ($nav->isVisible()) : ?>
                <? SkipLinks::addIndex(_("Zweite Reiternavigation"), 'tabs2', 20); ?>
                <li<?= $nav->isActive() ? ' class="current"' : '' ?>>
                    <? if ($nav->isEnabled()) : ?>
                        <a href="<?= URLHelper::getLink($nav->getURL()) ?>">
                            <?= htmlReady($nav->getTitle()) ?>
                        </a>
                    <? else: ?>
                        <span class="quiet">
                            <?= htmlReady($nav->getTitle()) ?>
                        </span>
                    <? endif ?>
                </li>
            <? endif ?>
        <? endforeach ?>
    <? endif ?>
</ul>
<div class="clear"></div>
