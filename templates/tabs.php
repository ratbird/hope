<?
# Lifter010: TODO
?>
<? SkipLinks::addIndex(_("Erste Reiternavigation"), 'tabs', 10); ?>
<ul id="tabs" role="navigation">
    <? foreach ($navigation as $nav) : ?>
        <? if ($nav->isVisible()) : ?>
            <li<?= $nav->isActive() ? ' class="current"' : '' ?>>
                <? if ($nav->isEnabled()) : ?>
                    <?
                    $badge_attr = '';
                    if ($nav->hasBadgeNumber()) {
                      $badge_attr = ' class="badge" data-badge-number="' . intval($nav->getBadgeNumber())  . '"';
                    }
                    ?>

                    <a href="<?= URLHelper::getLink($nav->getURL()) ?>"<?= $badge_attr ?>>
                        <? if ($image = $nav->getImage()) : ?>
                            <img class="tab-icon" src="<?=$image['src']?>" title="<?= $nav->getTitle() ? htmlReady($nav->getTitle()) : htmlReady($nav->getDescription()) ?>" />
                        <? endif ?>
                        <span title="<?= $nav->getDescription() ? htmlReady($nav->getDescription()) :  htmlReady($nav->getTitle())?>" ><?= $nav->getTitle() ? htmlReady($nav->getTitle()) : '&nbsp;' ?></span>
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
