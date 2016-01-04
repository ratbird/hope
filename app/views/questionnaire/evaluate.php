<div class="questionnaire_results questionnaire_<?= $questionnaire->getId() ?>" data-questionnaire_id="<?= $questionnaire->getId() ?>">

    <? if ($questionnaire->resultsVisible()) : ?>
        <? foreach ($questionnaire->questions as $question) : ?>
            <article class="question_<?= $question->getId() ?>">
                <? $template = $question->getResultTemplate() ?>
                <?= $template ? $template->render() : _("Ergebnisse konnten nicht ausgewertet werden.") ?>
            </article>
        <? endforeach ?>
    <? else : ?>
        <div style="margin-top: 13px;">
            <? if ($questionnaire['resultvisibility'] === "afterending") : ?>
                <?= MessageBox::info(_("Die Ergebnisse des Fragebogens werden ver�ffentlich, wenn die Befragung abgeschlossen ist.")) ?>
            <? else : ?>
                <?= MessageBox::info(_("Die Ergebnisse der Befragung werden nicht �ber Stud.IP ausgewertet.")) ?>
           <? endif ?>
        </div>
    <? endif ?>

    <div class="terms">
        <? if ($questionnaire['anonymous']) : ?>
            <?= _("Die Teilnahme ist anonym.") ?>
        <? else : ?>
            <?= _("Die Teilnahme ist nicht anonym.") ?>
        <? endif ?>
        <? if ($questionnaire['stopdate']) : ?>
            <?= sprintf(_("Sie k�nnen den Fragebogen beantworten bis zum %s um %s Uhr."), date("d.m.Y", $questionnaire['stopdate']), date("G:i", $questionnaire['stopdate'])) ?>
        <? endif ?>
    </div>

    <script>
        STUDIP.Questionnaire.lastUpdate = Math.floor(Date.now() / 1000);
    </script>
    <div data-dialog-button style="max-height: none; opacity: 1; text-align: center;">
        <? if ($questionnaire->isAnswerable() && $questionnaire['editanswers']) : ?>
            <?= \Studip\LinkButton::create($questionnaire->isAnswered() ? _("Antwort �ndern") : _("Beantworten"), URLHelper::getURL("dispatch.php/questionnaire/answer/".$questionnaire->getId(), array('range_type' => $range_type, 'range_id' => $range_id)), array('data-dialog' => "1")) ?>
        <? endif ?>
        <? if ($questionnaire->isEditable()) : ?>
            <?= \Studip\LinkButton::create(_("Ergebnisse herunterladen"), URLHelper::getURL("dispatch.php/questionnaire/export/".$questionnaire->getId())) ?>
        <? endif ?>
        <? if ($questionnaire->isEditable() && (!$questionnaire->isStarted() || !$questionnaire->countAnswers())) : ?>
            <?= \Studip\LinkButton::create(_("Bearbeiten"), URLHelper::getURL("dispatch.php/questionnaire/edit/".$questionnaire->getId(), array('range_type' => $range_type, 'range_id' => $range_id)), array('data-dialog' => "1")) ?>
        <? endif ?>
        <? if ($GLOBALS['perm']->have_perm('autor')) : ?>
            <?= \Studip\LinkButton::create(_("Kopieren"), URLHelper::getURL("dispatch.php/questionnaire/copy/".$questionnaire->getId()), array('data-dialog' => "1")) ?>
        <? endif ?>
        <? if ($questionnaire->isEditable() && !$questionnaire->isStarted()) : ?>
            <?= \Studip\LinkButton::create(_("Starten"), URLHelper::getURL("dispatch.php/questionnaire/start/".$questionnaire->getId())) ?>
        <? endif ?>
        <? if ($questionnaire->isEditable() && $questionnaire->isStarted()) : ?>
            <?= \Studip\LinkButton::create(_("Beenden"), URLHelper::getURL("dispatch.php/questionnaire/stop/".$questionnaire->getId())) ?>
        <? endif ?>

    </div>

</div>
