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
                'text' => 'Hier k�nnen Sie globale Einstellungen zu Studentischen Arbeitsgruppen vornehmen.',
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
    <?= MessageBox::error(_('Keine Veranstaltungsart f�r Studiengruppen gefunden'),
        array(sprintf(_('Die Standardkonfiguration f�r Studiengruppen in der Datei <b>%s</b> fehlt oder ist unvollst�ndig.'),
                'config.inc.php'))) ?>
<? endif ?>
<? if (!Config::getInstance()->getValue('STUDYGROUPS_ENABLE')):?>
    <?= MessageBox::info( _("Die Studiengruppen sind derzeit <b>nicht</b> aktiviert.")
            . '<br>'. _("Zum Aktivieren f�llen Sie bitte das Formular aus und klicken Sie auf \"Speichern\".")); ?>
<? else: ?>
    <? if ($can_deactivate) : ?>
        <?= MessageBox::info( _("Die Studiengruppen sind aktiviert.")) ?>
        <form action="<?= $controller->url_for('course/studygroup/deactivate') ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <?= Button::create(_("Deaktivieren"), 'deaktivieren') ?>
        </form>
    <? else: ?>
        <?= MessageBox::info(_("Sie k�nnen die Studiengruppen nicht deaktivieren, solange noch welche in Stud.IP vorhanden sind!")) ?>
    <? endif; ?>
    <br>
<?php endif;?>
<form action="<?= $controller->url_for('course/studygroup/savemodules') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <!-- Title -->
<table class="default">
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
                <option value='invalid' selected><?= _("-- bitte ausw�hlen --")?></option>
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
<table class="default">
    <tr>
        <th colspan="2"> <b><?= _("Nutzungsbedingugen") ?></b> </th>
    </tr>
    <tr>
        <td colspan="2">
        <?= _("Geben Sie hier Nutzungsbedingungen f�r die Studiengruppen ein. ".
                "Diese m�ssen akzeptiert werden, bevor eine Studiengruppe angelegt werden kann.") ?>
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
