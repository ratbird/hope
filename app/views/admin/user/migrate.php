<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

?>
<h2><?= _('Benutzermigration') ?></h2>

<form action="<?= $controller->url_for('admin/user/migrate') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr class="steel1">
        <td>
            <?= _('Alter Benutzer:') ?>
        </td>
        <td>
        <?= QuickSearch::get('old_id', new StandardSearch('user_id'))->render() ?>
        </td>
    </tr>
    <tr class="steelgraulight">
        <td nowrap>
            <?= _('Neuer zusammengeführter Benutzer:') ?>
        </td>
        <td>
        <?= QuickSearch::get('new_id', new StandardSearch('user_id'))->render() ?>
        </td>
    </tr>
    <tr class="steel1">
        <td nowrap>
            <?= _('Identitätsrelevante Daten migrieren:') ?>
        </td>
        <td>
            <input type="checkbox" name="convert_ident" checked="checked">
            <i>
                <?= _('(Es werden zusätzlich folgende Daten migriert: Name, Veranstaltungen, Studiengänge, Gästebuch, persönliche Profildaten, Institute, generische Datenfelder und Buddys.)') ?>
            </i>
        </td>
    </tr>
    <tr class="steelgraulight">
        <td nowrap>
            <?= _('Den alten Benutzer löschen:') ?>
        </td>
        <td>
            <input type="checkbox" name="delete_old">
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <?= Button::create(_('Umwandeln'), array('title' => _('Den ersten Benutzer in den zweiten Benutzer migrieren'))) ?>
        </td>
    </tr>
</table>
</form>

<? //infobox

include '_infobox.php';

$infobox = array(
    'picture' => 'infobox/board1.jpg',
    'content' => array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag' => $aktionen
        ),
        array(
            'kategorie' => _("Information"),
            'eintrag'   => array(
                array(
                    "text" => _("Folgende Daten werden migriert:<br> Forumsbeiträge, Dateien, Kalender, Archiv, Evaluationen, Kategorien, Literatur, Nachrichten, Ankündigungen, Abstimmungen, Termine, Umfragen, Wiki, Statusgruppen und Adressbuch."),
                    "icon" => "icons/16/black/info.png"
                    ),
                array(
                    "text" => _("Das Benutzerbild wird nicht kopiert."),
                    "icon" => "icons/16/black/info.png"
                )
            )
        )
    )
);
