<?= $GLOBALS['vote_message'][$vote->id] ?>
<? $show_result = $controller->showResult($vote) ?>
<? $maxvotes = $vote->maxvotes ?>
<section>
    <div>
        <?= formatReady($vote->question) ?>
    </div>
    <form action="<?= ContentBoxHelper::href($vote->id) ?>" method="post">
        <section class="answers">
            <? foreach (Request::get('sort') ? $vote->answers->orderBy("count desc", SORT_NUMERIC) : $vote->answers->orderBy("position", SORT_NUMERIC) as $answer): ?>
                <div class="answer">
                    <? if ($show_result): ?>
                        <div class="bar">
                            <div class="percent">
                                <?= htmlReady($vote->count ? (round($answer->count * 100 / $vote->count)) : 0) ?>%
                            </div>
                            <? $width = $maxvotes ? $answer->count / $maxvotes : 0; ?>
                            <div style="display: inline-block;
                                border:1px solid black;
                                width: <?= 100 * ($width) ?>px;
                                height: 8px;
                                background-color: rgb(<?= 255 - round(215 * ($width)) ?>, <?= 255 - round(182 * ($width)) ?>, <?= 255 - round(131 * ($width)) ?>); ">
                            </div>
                        </div>
                        <div class="text">
                            <?= formatReady($answer->answer) ?>
                        </div>
                        <div class="infotext">
                            (<?= $answer->count ?> <?= $answer->count == 1 ? _("Stimme") : _("Stimmen") ?>)
                            <? if (Request::get('revealNames') === $vote->id && !$vote->anonymous && ($admin || $vote->namesvisibility)): ?>
                                ( <?= join(', ', $answer->getUsernames()) ?> )
                            <? endif; ?>
                        </div>
                    <? else: ?>
                        <label>
                            <? if ($vote->multiplechoice): ?>
                                <input type="checkbox" name="vote_answers[]" value="<?= $answer->position ?>" <?= !$vote->isRunning() ? 'disabled="disabled"' : ''?>>
                            <? else: ?>
                                <input type="radio" name="vote_answers[]" value="<?= $answer->position ?>" <?= !$vote->isRunning() ? 'disabled="disabled"' : ''?>>
                            <? endif ?>
                            <?= formatReady($answer->answer) ?>
                        </label>
                    <? endif; ?>
                </div>
            <? endforeach; ?>
        </section>

        <footer>
            <? if ($vote->multiplechoice): ?>
                <?= _('Sie konnten mehrere Antworten ausw�hlen.') ?>
            <? endif; ?>
            <?= $vote->countInfo ?>
            <?= $vote->anonymousInfo ?>
            <?= $vote->endInfo ?>
            <div class="buttons">
                <?= $this->render_partial('vote/_buttons.php', array('vote' => $vote)); ?>
            </div>
        </footer>
    </form>
</section>
