<?
# Lifter010: TODO
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */

$infobox = array(
    'picture' => 'infobox/administration.png',
    'content' => array(
        array(
            'kategorie' => _('Information'),
            'eintrag'   => array(
                array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => $this->infotext
                )
            )
        )
    )
);

$items = array();

foreach (Navigation::getItem('/course/admin/main') as $nav) {
    if ($nav->isVisible(true)) {
        $image = $nav->getImage();
        $text = '<a href="' . URLHelper::getLink($nav->getURL()) . '">' . htmlReady($nav->getTitle()). '</a>';
        $items[] = array('icon' => $image['src'], 'text' => $text);
    }
}

if (count($items)) {
    array_unshift($infobox['content'], array('kategorie' => _('Aktionen'), 'eintrag' => $items));
}
?>

<h1 class="smashbox_kategorie">
    <?= _('Verwaltungsfunktionen') ?>
</h1>

<div class="smashbox_stripe">
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
