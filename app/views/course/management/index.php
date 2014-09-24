<?php
# Lifter010: TODO
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/admin-sidebar.png');

$links = new ActionsWidget();
foreach (Navigation::getItem('/course/admin/main') as $nav) {
    if ($nav->isVisible(true)) {
        $image = $nav->getImage();
        $links->addLink($nav->getTitle(), URLHelper::getLink($nav->getURL()), $image['src']);
    }
}
$sidebar->addWidget($links);
?>

<h1>
    <?= _('Verwaltungsfunktionen') ?>
</h1>

<div>
    <div style="margin-left: 1.5em;">

        <? foreach (Navigation::getItem('/course/admin') as $name => $nav) : ?>
            <? if ($nav->isVisible() && $name != 'main') : ?>
                <a class="click_me" href="<?= URLHelper::getLink($nav->getURL()) ?>">
                    <div>
                        <span class="click_head">
                            <?= htmlReady($nav->getTitle()) ?>
                        </span>
                        <p>
                            <?= htmlReady($nav->getDescription()) ?>
                        </p>
                    </div>
                </a>
            <? endif ?>
        <? endforeach ?>

    </div>
    <br style="clear: left;">
</div>
