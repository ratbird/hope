<? use Studip\Button, Studip\LinkButton;?>

<form action="<?= $controller->url_for('course/members/set_autor_csv')?>" method="post" name="user">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <caption>
        <?=sprintf(_('%s hinzufügen'), htmlReady(get_title_for_status('autor', 1)))?>
    </caption>
    <thead>
    <tr>
        <th colspan="2">
            <?=_('Teilnehmerliste übernehmen')?>
        </th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?=_('Eingabeformat')?>:

                <?= tooltipIcon(sprintf(_('In das Textfeld <strong>Teilnehmerliste übernehmen</strong> können Sie eine Liste mit Namen von %s eingeben,
                    die in die Veranstaltung aufgenommen werden sollen. Wählen Sie in der Auswahlbox das gewünschte Format, in dem Sie die Namen eingeben möchten.<br />
                    <strong>Eingabeformat</strong><br/>
                    <strong>Nachname, Vorname &crarr;</strong><br />Geben Sie dazu in jede Zeile den Nachnamen und (optional) den Vornamen getrennt durch ein Komma oder ein Tabulatorzeichen ein.<br />
                    <strong>Nutzername &crarr;</strong><br />Geben Sie dazu in jede Zeile den Stud.IP Nutzernamen ein.'),$status_groups['autor']),false, true);?>
            </td>
            <td colspan="2">
                <select name="csv_import_format">
                    <option value="realname"><?=_("Nachname, Vorname")?> &crarr;</option>
                    <option value="username"><?=_("Nutzername")?> &crarr;</option>
                    <? if(!empty($accessible_df)) : ?>
                        <? foreach ($accessible_df as $df) : ?>
                            <option value="<?=$df->getId()?>" <?=(Request::get('csv_import_format') ==  $df->getId()? 'selected="selected"': '')?>><?= htmlReady($df->getName())?> &crarr;</option>
                        <? endforeach?>
                    <? endif ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <td style="width: 30%"><?= sprintf(_('<strong>%s</strong> in die Veranstaltung eintragen'), htmlReady(get_title_for_status('autor', 1)))?></td>
            <td style="width: 50%">
                <textarea name="csv_import" rows="6" cols="50"></textarea>
            </td>
        </tr>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="2">
            <?= Button::createAccept(_('Eintragen'), 'add_member_list',
                array('title' => sprintf(_("als %s eintragen"), htmlReady(get_title_for_status('autor', 1))))) ?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('course/members/index')) ?>
        </td>
    </tr>
    </tfoot>
</table>
</form>

