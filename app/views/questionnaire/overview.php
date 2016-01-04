<table class="default" id="questionnaire_overview">
    <thead>
        <tr>
            <th></th>
            <th><?= _("Fragebogen") ?></th>
            <th><?= _("Startet") ?></th>
            <th><?= _("Endet") ?></th>
            <th><?= _("Eingebunden") ?></th>
            <th><?= _("Teilnehmer") ?></th>
            <th><?= _("Aktionen") ?></th>
        </tr>
    </thead>
    <tbody>
        <? if (count($questionnaires)) : ?>
        <? foreach ($questionnaires as $questionnaire) : ?>
            <?= $this->render_partial("questionnaire/_overview_questionnaire.php", compact("questionnaire")) ?>
        <? endforeach ?>
        <? else : ?>
            <tr class="noquestionnaires">
                <td colspan="7" style="text-align: center">
                    <?= _("Sie haben noch keine Fragebögen erstellt.") ?>
                </td>
            </tr>
        <? endif ?>
    </tbody>
</table>

<?

$actions = new ActionsWidget();
$actions->addLink(_("Fragebogen erstellen"), URLHelper::getURL("dispatch.php/questionnaire/edit"), "icons/16/black/add", array('data-dialog' => "1"));

Sidebar::Get()->addWidget($actions);