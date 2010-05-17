<h3>
    <?= _('Plugin-Details') ?>
</h3>

<table>
    <tr>
        <td>Name:</td>
        <td><?= htmlspecialchars($plugin['name']) ?></td>
    </tr>
    <tr>
        <td>Klasse:</td>
        <td><?= $plugin['class'] ?></td>
    </tr>
    <tr>
        <td>Typ:</td>
        <td><?= join(', ', $plugin['type']) ?></td>
    </tr>
    <tr>
        <td>Origin:</td>
        <td><?= htmlspecialchars($manifest['origin']) ?></td>
    </tr>
    <tr>
        <td>Version:</td>
        <td><?= htmlspecialchars($manifest['version']) ?></td>
    </tr>
    <tr>
        <td>Beschreibung:</td>
        <td><?= htmlspecialchars($manifest['description']) ?></td>
    </tr>
</table>

<p>
    <a href="<?= $controller->url_for('plugin_admin') ?>">
        <?= makeButton('zurueck', 'img', _('zurück zur Plugin-Verwaltung')) ?>
    </a>
</p>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('plugin_admin').'">'._('Verwaltung von Plugins').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                'icon' => 'ausruf_small.gif',
                'text' => _('Hier finden Sie weitere Informationen zum ausgewählten Plugin.')
            )
        )
    )
);

$infobox = array('picture' => 'infoboxes/modules.jpg', 'content' => $infobox_content);
?>
