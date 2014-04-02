<?
# Lifter010: TODO - Quicksearches still lack a label

use Studip\Button, Studip\LinkButton;

?>
<h2><?= _('Benutzermigration') ?></h2>

<form action="<?= $controller->url_for('admin/user/migrate') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <colgroup>
            <col width="250px">
            <col>
        </colgroup>
        <tbody>
            <tr>
                <td>
                    <?= _('Alter Benutzer:') ?>
                </td>
                <td>
                    <?= QuickSearch::get('old_id', new StandardSearch('user_id'))->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?= _('Neuer zusammengeführter Benutzer:') ?>
                </td>
                <td>
                    <?= QuickSearch::get('new_id', new StandardSearch('user_id'))->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="convert_ident">
                        <?= _('Identitätsrelevante Daten migrieren:') ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="convert_ident" id="convert_ident" checked>
                    <i>
                        <?= _('(Es werden zusätzlich folgende Daten migriert: '
                             .'Veranstaltungen, Studiengänge, persönliche '
                             .'Profildaten inkl. Nutzerbild, Institute, '
                             .'generische Datenfelder und Buddies.)') ?>
                    </i>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="delete_old">
                        <?= _('Den alten Benutzer löschen:') ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="delete_old" id="delete_old" value="1">
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <?= Button::create(_('Umwandeln'),
                                       'umwandeln',
                                       array('title' => _('Den ersten Benutzer in den zweiten Benutzer migrieren'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<? //infobox

include '_infobox.php';

$infobox = array(
    'picture' => 'sidebar/person-sidebar.png',
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
                    )
            )
        )
    )
);
