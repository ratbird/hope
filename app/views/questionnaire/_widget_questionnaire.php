<article class="<?= ContentBoxHelper::classes($questionnaire->id, $is_new) ?> widget_questionnaire_<?= $questionnaire->getId() ?>"  data-questionnaire_id="<?= htmlReady($questionnaire->getId()) ?>">
    <header>
        <h1>
            <a href="<?= ContentBoxHelper::switchhref($questionnaire->id, array('contentbox_type' => 'vote')) ?>">
                <?= htmlReady($questionnaire->title) ?>
            </a>
        </h1>
        <nav>
            <a href="<?= $questionnaire->user_id ? URLHelper::getLink('dispatch.php/profile', array('username' => get_username($questionnaire->user_id))) : '' ?>">
                <?= $questionnaire->user_id ? htmlReady(get_fullname($questionnaire->user_id)) : '' ?>
            </a>
            <span>
                <?= strftime("%d.%m.%Y", $questionnaire->mkdate) ?>
            </span>
            <span>
                <?= $questionnaire->countAnswers() ?>
            </span>
        </nav>
    </header>
    <section>
        <? if ($questionnaire->isAnswered()) : ?>
            <?= $this->render_partial('questionnaire/evaluate.php', array('questionnaire' => $questionnaire, 'range_type' => $range_type, 'range_id' => $range_id)); ?>
        <? else : ?>
            <?= $this->render_partial('questionnaire/answer.php', array('questionnaire' => $questionnaire, 'range_type' => $range_type, 'range_id' => $range_id)); ?>
        <? endif ?>
    </section>
</article>