<?
# Lifter010: TODO
foreach (Navigation::getItem("/")->getSubNavigation() as $path => $nav) {
    if ($nav->isActive()) {
        $path1 = $path;
    }
}
?>
<? SkipLinks::addIndex(_("Erste Reiternavigation"), 'tabs', 10); ?>
<ul id="tabs" role="navigation">
    <? foreach ($navigation as $path => $nav) : ?>
        <? if ($nav->isVisible()) : ?>
            <li id="nav_<?= $path1 ?>_<?= $path ?>"<?= $nav->isActive() ? ' class="current"' : '' ?>>
                <? $nav->isActive() && $path2 = $path ?>
                <? if ($nav->isEnabled()) : ?>
                    <?
                    $badge_attr = '';
                    if ($nav->hasBadgeNumber()) {
                      $badge_attr = ' class="badge" data-badge-number="' . intval($nav->getBadgeNumber())  . '"';
                    }
                    ?>

                    <a href="<?= URLHelper::getLink($nav->getURL()) ?>"<?= $badge_attr ?>>
                        <? if ($image = $nav->getImage()) : ?>
                            <?= Assets::img($image['src'], array('class' => "tab-icon", 'alt' => htmlReady($nav->getTitle()), 'title' => $nav->getTitle() ? htmlReady($nav->getTitle()) : htmlReady($nav->getDescription()))) ?>
                        <? endif ?>
                        <span title="<?= $nav->getDescription() ? htmlReady($nav->getDescription()) :  htmlReady($nav->getTitle())?>" ><?= $nav->getTitle() ? htmlReady($nav->getTitle()) : '&nbsp;' ?></span>
                    </a>
                <? else: ?>
                    <span class="quiet">
                        <? if ($image = $nav->getImage()) : ?>
                            <?= Assets::img($image['src'], array('class' => "tab-icon", 'alt' => htmlReady($nav->getTitle()), 'title' => htmlReady($nav->getTitle()))) ?>
                        <? endif ?>
                        <?= htmlReady($nav->getTitle()) ?>
                    </span>
                <? endif ?>
            </li>
        <? endif ?>
    <? endforeach ?>
</ul>
