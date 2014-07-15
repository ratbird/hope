<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<h3>
    <?= _('Plugin-Details') ?>
</h3>

<table>
    <tr>
        <td>Name:</td>
        <td><?= htmlReady($plugin['name']) ?></td>
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
        <td><?= htmlReady($manifest['origin']) ?></td>
    </tr>
    <tr>
        <td>Version:</td>
        <td><?= htmlReady($manifest['version']) ?></td>
    </tr>
    <tr>
        <td>Beschreibung:</td>
        <td><?= htmlReady($manifest['description']) ?></td>
    </tr>
</table>

<p>
    <?= LinkButton::create('<< '.  _("Zurück"), $controller->url_for('admin/plugin'), array('title' => _('zurück zur Plugin-Verwaltung')))?>
</p>

<?
$sidebar = Sidebar::Get();
$sidebar->setTitle(_('Plugins'));
$sidebar->setImage(Assets::image_path('sidebar/plugin-sidebar.png'));
