<?
$infobox_content[] = array(
    'kategorie' => _('Informationen / Bedienungshinweise'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/black/info.png',
            'text' => _('Sie befinden sich hier in der Administrationsansicht des Forums. '
                    . 'Mit den blauen Pfeilen k�nnen Sie einen oder mehrere Eintr�ge ausw�hlen, welche dann verschoben werden k�nnen. ')
        ),
        array(
            'icon' => 'icons/16/black/info.png',
            'text' => _('Sie sollten nicht mehr als 20 Eintr�ge gleichzeitig ausw�hlen, da das verschieben sonst sehr lange dauern kann.')
        )
    )
);

$infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
?>
<div id="forum">
    <ul style="margin: 0; padding-left: 20px;">
    <? foreach ($list as $category_id => $entries) : ?>
        <li data-id="<?= $category_id ?>">
            <a class="tooltip2"></a>
            <b><?= htmlReady($categories[$category_id]) ?></b>
            <a href="javascript:STUDIP.Forum.paste('<?= $category_id ?>');" data-role="paste" style="display: none">
                <?= Assets::img('icons/16/yellow/arr_2left.png') ?>
            </a>    
            <br>

            <?= $this->render_partial('index/_admin_entries', compact('entries')) ?>
        </li>
    <? endforeach ?>
    </ul>
</div>