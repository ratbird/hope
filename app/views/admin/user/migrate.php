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
                    <?= _('Neuer zusammengef�hrter Benutzer:') ?>
                </td>
                <td>
                    <?= QuickSearch::get('new_id', new StandardSearch('user_id'))->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="convert_ident">
                        <?= _('Identit�tsrelevante Daten migrieren:') ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="convert_ident" id="convert_ident" checked>
                    <i>
                        <?= _('(Es werden zus�tzlich folgende Daten migriert: '
                             .'Veranstaltungen, Studieng�nge, pers�nliche '
                             .'Profildaten inkl. Nutzerbild, Institute, '
                             .'generische Datenfelder und Buddies.)') ?>
                    </i>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="delete_old">
                        <?= _('Den alten Benutzer l�schen:') ?>
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
                    "text" => _("Folgende Daten werden migriert:<br> Forumsbeitr�ge, Dateien, Kalender, Archiv, Evaluationen, Kategorien, Literatur, Nachrichten, Ank�ndigungen, Abstimmungen, Termine, Umfragen, Wiki, Statusgruppen und Adressbuch."),
                    "icon" => "icons/16/black/info.png"
                    )
            )
        )
    )
);
