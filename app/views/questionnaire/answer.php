<form
    action="<?= URLHelper::getLink("dispatch.php/questionnaire/answer/".$questionnaire->getId()) ?>"
    method="post"
    enctype="multipart/form-data"
    class="questionnaire"
    <? if (Request::isAjax()) : ?>
        data-dialog
    <? endif ?>
    >
    <? if ($range_type && $range_id) : ?>
        <input type="hidden" name="range_type" value="<?= htmlReady($range_type) ?>">
        <input type="hidden" name="range_id" value="<?= htmlReady($range_id) ?>">
    <? endif ?>
    <div class="questionnaire_answer">
        <? foreach ($questionnaire->questions as $question) : ?>
            <? $template = $question->getDisplayTemplate() ?>
            <? if ($template) : ?>
                <article>
                    <?= $template->render() ?>
                </article>
            <? endif ?>
        <? endforeach ?>
    </div>

    <div class="terms">
        <? if ($questionnaire['anonymous']) : ?>
            <?= _("Die Teilnahme ist anonym.") ?>
        <? else : ?>
            <?= _("Die Teilnahme ist nicht anonym.") ?>
        <? endif ?>
        <? if ($questionnaire['editanswers']) : ?>
            <?= _("Sie können Ihre Antworten nachträglich ändern.") ?>
        <? endif ?>
        <? if ($questionnaire['stopdate']) : ?>
            <?= sprintf(_("Sie können den Fragebogen beantworten bis zum %s um %s Uhr."), date("d.m.Y", $questionnaire['stopdate']), date("G:i", $questionnaire['stopdate'])) ?>
        <? endif ?>
    </div>

    <div data-dialog-button style="text-align: center;">
        <?= \Studip\Button::create(_("Speichern"), 'questionnaire_answer', array('onClick' => "return STUDIP.Questionnaire.beforeAnswer.call(this);")) ?>

        <? if ($questionnaire->resultsVisible()) : ?>
            <?= \Studip\LinkButton::create(_("Ergebnisse anzeigen"), URLHelper::getURL("dispatch.php/questionnaire/evaluate/".$questionnaire->getId()), array('data-dialog' => "1")) ?>
        <? endif ?>
        <? if ($questionnaire->isEditable() && (!$questionnaire->isStarted() || !$questionnaire->countAnswers())) : ?>
            <?= \Studip\LinkButton::create(_("Bearbeiten"), URLHelper::getURL("dispatch.php/questionnaire/edit/".$questionnaire->getId()), array('data-dialog' => "1")) ?>
        <? endif ?>
        <?= \Studip\LinkButton::create(_("Kopieren"), URLHelper::getURL("dispatch.php/questionnaire/copy/".$questionnaire->getId()), array('data-dialog' => "1")) ?>
        <? if ($questionnaire->isEditable() && (!$questionnaire->isStarted())) : ?>
            <?= \Studip\LinkButton::create(_("Starten"), URLHelper::getURL("dispatch.php/questionnaire/start/".$questionnaire->getId())) ?>
        <? endif ?>
        <? if ($questionnaire->isEditable() && $questionnaire->isStarted()) : ?>
            <?= \Studip\LinkButton::create(_("Beenden"), URLHelper::getURL("dispatch.php/questionnaire/stop/".$questionnaire->getId())) ?>
        <? endif ?>
    </div>
</form>