<?
# Lifter010: TODO
?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::success($flash['success'], $flash['success_detail']) ?>
<? elseif (isset($flash['delete'])): ?>
    <?= createQuestion(_('Wollen Sie die Zuordnung der Veranstaltung zum automatischen Eintragen wirklich löschen?'),  array('delete' => 1), array('back' => 1), $controller->url_for('admin/autoinsert/delete') .'/'. $flash['delete']) ?>
<? endif; ?>

<h2>
    <?= _('Automatisches Eintragen von Erstnutzern in Veranstaltungen') ?>
</h2>
<h3>
    <?= _('Suche nach Veranstaltungen')?>
</h3>

<form action="<?= $controller->url_for('admin/autoinsert') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<?=$this->render_partial("admin/autoinsert/_search.php", array('semester_data' => $semester_data))?>
</form>

<? if (count($seminar_search) > 0): ?>
<form action="<?= $controller->url_for('admin/autoinsert/new') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr>
        <th colspan="2"><?= _('Suchergegbnisse') ?></th>
    </tr>
    <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
        <td>
            <label for="sem_id"><?= _('Veranstaltung:') ?></label>
        </td>
        <td>
           <select name="sem_id" id="sem_id">
           <? foreach ($seminar_search as $seminar): ?>
                <option value="<?= $seminar[0] ?>">
                    <?= $seminar[1] ?>
                </option>
           <? endforeach; ?>
            </select>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
        <td>
            <?= _('Automatisches Eintragen mit Nutzerstatus:') ?>
        </td>
        <td>
            <input type="checkbox" name="rechte[]" value="dozent" id="dozent">
            <label for="dozent"><?= _('Dozent') ?></label>
            <input type="checkbox" name="rechte[]" value="tutor" id="tutor">
            <label for="tutor"><?= _('Tutor') ?></label>
            <input type="checkbox" name="rechte[]" value="autor" id="autor">
            <label for="autor"><?= _('Autor') ?></label>
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <?= makeButton('anlegen','input',_('anlegen'),'anlegen') ?>
        </td>
    </tr>
</table>
</form>
<? endif;?>

<h3>Vorhandene Zuordnungen</h3>
<table width="100%" class="default">
    <tr class="steel">
        <td><b><?= _('Veranstaltungen') ?></b></td>
        <td align="center"><b><?= _('Dozent') ?></b></td>
        <td align="center"><b><?= _('Tutor') ?></b></td>
        <td align="center"><b><?= _('Autor') ?></b></td>
        <td align="right"><b><?= _('Aktionen') ?></b></td>
    </tr>
   <? foreach ($auto_sems as $auto_sem): ?>
    <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
        <td>
            <a href="<?= URLHelper::getLink('seminar_main.php?auswahl='.$auto_sem['seminar_id']) ?>">
                <?= htmlReady($auto_sem['Name'])?>
            </a>
        </td>
        <?=$this->render_partial("admin/autoinsert/_status.php", array('status' => 'dozent', 'auto_sem' => $auto_sem))?>
        <?=$this->render_partial("admin/autoinsert/_status.php", array('status' => 'tutor', 'auto_sem' => $auto_sem))?>
        <?=$this->render_partial("admin/autoinsert/_status.php", array('status' => 'autor', 'auto_sem' => $auto_sem))?>
        <td align="right">
            <a href="<?=$controller->url_for('admin/autoinsert/delete')?>/<?= $auto_sem['seminar_id'] ?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Veranstaltung entfernen'), 'class' => 'text-top')) ?>
            </a>
        </td>
    </tr>
    <? $i ++?>
    <? endforeach; ?>
</table>

<?
$aktionen[] = array(
    "text" => '<a href="'.$controller->url_for('admin/autoinsert').'">'._('Übersicht').'</a>',
    "icon" => "icons/16/black/edit.png"
);
$aktionen[] = array(
    "text" => '<a href="'.$controller->url_for('admin/autoinsert/manual').'">'._('Benutzergruppen manuell eintragen').'</a>',
    "icon" => "icons/16/black/visibility-visible.png"
);

$infobox = array(
    'picture' => 'infobox/modules.jpg',
    'content' => array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag'   => $aktionen
        ),
        array(
            'kategorie' => _("Hinweise"),
            'eintrag'   => array(
                array(
                    "text" => _("Wählen Sie Veranstaltungen aus, in die neue Benutzer oder bereits vorhandene Benutzer anhand ihrer Statusgruppe automatisch eingetragen werden sollen."),
                    "icon" => "icons/16/black/info.png"
                ),
                array(
                    "text" => _("Es können nur Veranstaltungen ohne gesetzte Zugangsberechtigungen ausgewählt werden."),
                    "icon" => "icons/16/black/info.png"
                )
            )
        )
    )
);