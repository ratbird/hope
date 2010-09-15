<?
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = 'infobox/administration.jpg';
$infobox['content'][] = array(
    'kategorie' => _("Information"),
    'eintrag'   => array(
        array(
            'text' => _("Als Mitarbeiter Ihrer Einrichtung können Sie für diese Inhalte in mehreren Kategorien bereitstellen. Inhalte in Ihrer Einrichtung können von allen Stud.IP-Nutzern abgerufen werden."),
            "icon" => "icons/16/black/info.png"
         )
     )
);

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
