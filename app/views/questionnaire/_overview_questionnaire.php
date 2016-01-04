<tr id="questionnaire_<?= $questionnaire->getId() ?>">
    <td>
        <? if ($questionnaire->isStarted()) : ?>
            <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/stop/".$questionnaire->getId()) ?>" title="<?= _("Fragebogen beenden") ?>">
                <?= Assets::img("icons/blue/20/stop", array('class' => "text-bottom")) ?>
            </a>
        <? else : ?>
            <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/start/".$questionnaire->getId()) ?>" title="<?= _("Fragebogen starten") ?>">
                <?= Assets::img("icons/blue/20/play", array('class' => "text-bottom")) ?>
            </a>
        <? endif ?>
    </td>
    <td>
        <?= htmlReady($questionnaire['title']) ?>
        <span>
            <?
            $icons = array();
            foreach ($questionnaire->questions as $question) {
                $class = $question['questiontype'];
                $icons[$class] = $class::getIcon();
            }
            foreach ($icons as $class => $icon) {
                echo Assets::img($icon, array('class' => "text-bottom", 'title' => $class::getName()));
            }
            ?>
        </span>
    </td>
    <td>
        <?= $questionnaire['startdate'] ? date("d.m.Y H:i", $questionnaire['startdate']) : _("händisch") ?>
    </td>
    <td>
        <?= $questionnaire['stopdate'] ? date("d.m.Y H:i", $questionnaire['stopdate']) : _("händisch") ?>
    </td>
    <td class="context">
        <? if (count($questionnaire->assignments)) : ?>
            <ul class="clean">
                <? foreach ($questionnaire->assignments as $assignment) : ?>
                    <li>
                        <? if ($assignment['range_id'] === "start") : ?>
                            <?= _("Stud.IP Startseite")?>
                        <? endif ?>
                        <? if ($assignment['range_id'] === "public") : ?>
                            <?= _("Öffentlich per Link")?>
                        <? endif ?>
                        <? if ($assignment['range_type'] === "user") : ?>
                            <?= _("Profilseite")?>
                        <? endif ?>
                        <? if ($assignment['range_type'] === "course") : ?>
                            <?= htmlReady(Course::find($assignment['range_id'])->name) ?>
                        <? endif ?>
                        <? if ($assignment['range_type'] === "institute") : ?>
                            <?= htmlReady(Institute::find($assignment['range_id'])->name) ?>
                        <? endif ?>
                    </li>
                <? endforeach ?>
            </ul>
        <? else : ?>
            <?= _("Nirgendwo") ?>
        <? endif ?>
    </td>
    <td>
        <? $countedAnswers = $questionnaire->countAnswers() ?>
        <?= htmlReady($countedAnswers) ?></td>
    <td style="white-space: nowrap;">
        <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/evaluate/".$questionnaire->getId()) ?>" data-dialog title="<?= _("Auswertung") ?>">
            <?= Assets::img("icons/blue/20/stat", array('class' => "text-bottom")) ?>
        </a>
        <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/export/".$questionnaire->getId()) ?>" title="<?= _("Export als CSV") ?>">
            <?= Assets::img("icons/blue/20/file-excel", array('class' => "text-bottom")) ?>
        </a>
        <? if ($questionnaire->isStarted() && $countedAnswers) : ?>
            <?= Assets::img("icons/grey/20/edit", array('class' => "text-bottom", 'title' => _("Der Fragebogen wurde gestartet und kann nicht mehr bearbeitet werden."))) ?>
        <? else : ?>
            <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/edit/".$questionnaire->getId()) ?>" data-dialog title="<?= _("Fragebogen bearbeiten") ?>">
                <?= Assets::img("icons/blue/20/edit", array('class' => "text-bottom")) ?>
            </a>
        <? endif ?>
        <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/context/".$questionnaire->getId()) ?>" data-dialog title="<?= _("Zuweisungen bearbeiten") ?>">
            <?= Assets::img("icons/blue/20/group2", array('class' => "text-bottom")) ?>
        </a>
        <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/delete/".$questionnaire->getId()) ?>" onClick="return window.confirm('<?= _("Wirklich löschen?") ?>');" title="<?= _("Fragebogen löschen.") ?>">
            <?= Assets::img("icons/blue/20/trash", array('class' => "text-bottom")) ?>
        </a>
    </td>
</tr>