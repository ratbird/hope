<?php
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
  * * * * * * * * * * * * */
$infobox['picture'] = 'infobox/groups.jpg';
$infobox['content'] = array(
    array(
        'kategorie' => _("Information"),
        'eintrag'   => array(
            array(
                'text' => 'Hier können Sie angeben, welche Module/Plugins in Studiengruppen verwendet werden dürfen.',
                "icon" => "icons/16/black/info.png"
            )
        )
    )
);

/* * * * * * * * * * * *
 * * * O U T P U T * * *
 * * * * * * * * * * * */

?>

<?= $this->render_partial("course/studygroup/_feedback") ?>
<? if (!$configured): ?>
    <?= MessageBox::error(_('Keine Veranstaltungsart für Studiengruppen gefunden'),
        array(sprintf(_('Die Standardkonfiguration für Studiengruppen in der Datei <b>%s</b> fehlt oder ist unvollständig.'),
                'config.inc.php'))) ?>
<? endif ?>
<? if (!Config::getInstance()->getValue('STUDYGROUPS_ENABLE')):?>
    <?= MessageBox::info( _("Die Studiengruppen sind derzeit <b>nicht</b> aktiviert.")
            . '<br>'. _("Zum Aktivieren füllen Sie bitte das Formular aus und klicken Sie auf \"Speichern\".")); ?>
<? else: ?>
    <? if ($can_deactivate) : ?>
        <?= MessageBox::info( _("Die Studiengruppen sind aktiviert.")) ?>
        <form action="<?= $controller->url_for('course/studygroup/deactivate') ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <?= Button::create(_("Deaktivieren"), 'deaktivieren') ?>
        </form>
    <? else: ?>
        <?= MessageBox::info(_("Sie können die Studiengruppen nicht deaktivieren, solange noch welche in Stud.IP vorhanden sind!")) ?>
    <? endif; ?>
    <br>
<?php endif;?>
<form action="<?= $controller->url_for('course/studygroup/savemodules') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <!-- Title -->
<table class="default zebra">
    <tr>
        <th colspan="2"> <b><?= _("Einrichtungszuordnung") ?></b> </th>
    </tr>
    <tr>
        <td>
            <?= _("Alle Studiengruppen werden folgender Einrichtung zugeordnet:") ?><br>
        </td>
        <td>
            <select name="institute">
            <? if (!Config::getInstance()->getValue('STUDYGROUPS_ENABLE')):?>
                <option value='invalid' selected><?= _("-- bitte auswählen --")?></option>
            <? endif ?>
            <? foreach ($institutes as $fak_id => $faculty) : ?>
                <option value="<?= $fak_id ?>" style="font-weight: bold"
                    <?= ($fak_id == $default_inst) ? 'selected="selected"' : ''?>>
                    <?= htmlReady(my_substr($faculty['name'], 0, 60)) ?>
                </option>
                <? foreach ($faculty['childs'] as $inst_id => $inst_name) : ?>
                <option value="<?= $inst_id ?>"
                    <?= ($inst_id == $default_inst) ? 'selected="selected"' : ''?>>
                    <?= htmlReady(my_substr($inst_name, 0, 60)) ?>
                </option>
                <? endforeach; ?>
            <? endforeach; ?>
            </select>
        </td>
    </tr>
</table>

<br>

<!-- Title -->
<table class="default zebra">
    <tr>
        <th colspan="2"> <b><?= _("Nutzungsbedingugen") ?></b> </th>
    </tr>
    <tr>
        <td colspan="2">
        <?= _("Geben Sie hier Nutzungsbedingungen für die Studiengruppen ein. ".
                "Diese müssen akzeptiert werden, bevor eine Studiengruppe angelegt werden kann.") ?>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="blank">
        <br>
        <textarea name="terms" style="width: 90%" rows="10" style='align:middle;'><?= htmlReady($terms) ?></textarea>
        <br>
        </td>
    </tr>
</table>
<p style="text-align: center">
    <br>
    <?= Button::createAccept(_("Speichern"), 'speichern') ?>
</p>
</form>
