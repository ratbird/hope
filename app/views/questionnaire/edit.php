<form action="<?= URLHelper::getLink("dispatch.php/questionnaire/edit/".(!$questionnaire->isNew() ? $questionnaire->getId() : "")) ?>"
      method="post" enctype="multipart/form-data"
      class="questionnaire_edit default"
      <?= Request::isAjax() ? "data-dialog" : "" ?>>
    <? if (Request::get("range_id")) : ?>
        <input type="hidden" name="range_id" value="<?= htmlReady(Request::get("range_id")) ?>">
        <input type="hidden" name="range_type" value="<?= htmlReady(Request::get("range_type", "static")) ?>">
    <? endif ?>
    <fieldset>
        <legend><?= _("Fragebogen") ?></legend>
        <label>
            <?= _("Titel des Fragebogens") ?>
            <input type="text" name="questionnaire[title]" value="<?= htmlReady($questionnaire['title']) ?>" class="size-l">
        </label>
    </fieldset>

    <? foreach ($questionnaire->questions as $index => $question) : ?>
        <?= $this->render_partial("questionnaire/_question.php", compact("question")) ?>
    <? endforeach ?>

    <div style="text-align: right;">
        <? foreach (get_declared_classes() as $class) :
            if (in_array('QuestionType', class_implements($class))) : ?>
                <a href="" onClick="STUDIP.Questionnaire.addQuestion('<?= htmlReady($class) ?>'); return false;">
                    <?= $class::getIcon(true, true)->asimg("20px", array('class' => "text-bottom")) ?>
                    <?= htmlReady($class::getName()) ?>
                    <?= _("hinzufügen") ?>
                </a>
            <? endif;
        endforeach ?>
    </div>

    <fieldset class="questionnaire_metadata">

        <label>
            <?= _("Startzeitpunkt (leer lassen für manuellen Start)") ?>
            <input type="text" name="questionnaire[startdate]" value="<?= $questionnaire['startdate'] ? date("d.m.Y", $questionnaire['startdate']) : ($questionnaire->isNew() ? _("sofort") : "") ?>" class="date">
        </label>

        <label>
            <?= _("Endzeitpunkt (leer lassen für manuelles Ende)") ?>
            <input type="text" name="questionnaire[stopdate]" value="<?= $questionnaire['stopdate'] ? date("d.m.Y", $questionnaire['stopdate']) : "" ?>" class="date">
        </label>

        <label>
            <input type="checkbox" name="questionnaire[anonymous]" value="1"<?= $questionnaire['anonymous'] ? " checked" : "" ?>>
            <?= _("Anonym teilnehmen") ?>
        </label>

        <label>
            <input type="checkbox" name="questionnaire[editanswers]" value="1"<?= $questionnaire['editanswers'] || $questionnaire->isNew() ? " checked" : "" ?>>
            <?= _("Teilnehmer dürfen ihre Antworten revidieren") ?>
        </label>

        <label>
            <?= _("Ergebnisse an Teilnehmer") ?>
            <select name="questionnaire[resultvisibility]">
                <option value="always"<?= $questionnaire['editanswers'] === "always" ? " selected" : "" ?>><?= _("Wenn sie geantwortet haben.") ?></option>
                <option value="afterending"<?= $questionnaire['editanswers'] === "afterending" ? " selected" : "" ?>><?= _("Nach Ende der Befragung.") ?></option>
                <option value="never"<?= $questionnaire['editanswers'] === "never" ? " selected" : "" ?>><?= _("Niemals.") ?></option>
            </select>
        </label>

    </fieldset>

    <script>
        jQuery(function () {
            jQuery("input[type=text].date").datepicker();
        });
    </script>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern"), 'questionnaire_store') ?>
    </div>
</form>