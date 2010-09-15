<?
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = 'infobox/administration.jpg';
if ($GLOBALS['perm']->have_perm('dozent')) {
    $infobox['content'] = array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag'   => array(
                array(
                    'text' => _("Diese Veranstaltung <a href=".URLHelper::getLink('copy_assi.php?list=TRUE&new_session=TRUE')."\>kopieren</a> und damit eine neue Veranstaltung mit gleichen Einstellungen erstellen."),
                    'icon' => 'icons/16/black/add/seminar.png'
                ),
            )
        )
    );
}

if (($GLOBALS['perm']->have_studip_perm('dozent', $GLOBALS['SessSemName'][1])
    && get_config('ALLOW_DOZENT_ARCHIV'))
    || $GLOBALS['perm']->have_perm('admin')) {

    $infobox['content'][0]['eintrag'][] = array(
        'text' => _("Diese Veranstaltung <a href=".URLHelper::getLink('archiv_assi.php?list=TRUE&new_session=TRUE')."\>archivieren</a> und damit beenden."),
        'icon' => 'icons/16/black/remove/seminar.png'
    );

    if ($visible) {
        $text = sprintf(_('Diese Veranstaltung %sunsichtbar%s schalten'),
            '<a href="'.  $controller->url_for('course/management/visible/0') .'">',
            '</a>');
    } else {
        $text = sprintf(_('Diese Veranstaltung %ssichtbar%s schalten'),
            '<a href="'.  $controller->url_for('course/management/visible/1') .'">',
            '</a>');
    }
    $infobox['content'][0]['eintrag'][] = array(
        'text' => $text,
        'icon' => 'icons/16/black/visibility-invisible.png'
    );
}

$infobox['content'][] = array(
    'kategorie' => _("Information"),
    'eintrag'   => array(
        array(
            'text' => _("Sie können hier Ihre Veranstaltung in mehreren Kategorien anpassen. Informationen wie Grunddaten oder Termine und Einstellungen  Zugangsbeschränkungen und Funktionen können Sie hier administrieren."),
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
