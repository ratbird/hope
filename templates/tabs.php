<? SkipLinks::addIndex(_("Erste Reiternavigation"), 'tabs', 10); ?>
<ul id="tabs">
    <? foreach ($navigation as $nav) : ?>
        <? if ($nav->isVisible()) : ?>
            <li<?= $nav->isActive() ? ' class="current"' : '' ?>>
                <? if ($nav->isEnabled()) : ?>
                    <a href="<?= URLHelper::getLink($nav->getURL()) ?>">
                        <? if ($image = $nav->getImage()) : ?>
                            <img class="tab-icon" src="<?=$image['src']?>" title="<?= htmlReady($nav->getTitle()) ?>" />
                        <? endif ?>
                        <span><?= htmlReady($nav->getTitle()) ?></span>
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
<ul id="tabs2">
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
