<?= $GLOBALS['vote_message'][$vote->id] ?>
<p>
    <?= htmlReady($vote->question) ?>
</p>
<form action="<?= ContentBoxHelper::href($vote->id) ?>" method="post">
    <section class="answers">
        <? foreach (Request::submitted('sort') ? $vote->answers->orderBy("count desc") : $vote->answers as $answer): ?>
        <div class="answer">
            <? if ($controller->showResult($vote)): ?>
            <div class="bar">
                <div class="percent">
                    <?= htmlReady($answer->percent) ?>%
                </div>
                <div style="display: inline-block; 
                     border:1px solid black; 
                     width: <?= 100 * ($answer->width) ?>px; 
                     height: 8px; 
                     background-color: rgb(<?= 255 - round(215 * ($answer->width)) ?>, <?= 255 - round(182 * ($answer->width)) ?>, <?= 255 - round(131 * ($answer->width)) ?>); ">
                </div>
            </div>
            <div class="text">
                <?= htmlReady($answer->answer) ?>
            </div>
            <div class="infotext">
                (<?= $answer->count ?> <?= $answer->count == 1 ? _("Stimme") : _("Stimmen") ?>)
                <? if (Request::submitted('revealNames') && $vote->namesvisibility): ?>
                ( <?= join(', ', $answer->getUsernames()) ?> )
                <? endif; ?>      
            </div>
            <? else: ?>
            <label>
                <? if ($vote->multiplechoice): ?>
                <input type="checkbox" name="vote_answers[]" value="<?= $answer->position ?>">
                <? else: ?>
                <input type="radio" name="vote_answers[]" value="<?= $answer->position ?>">
                <? endif ?>
                <?= htmlReady($answer->answer) ?>
            </label>
            <? endif; ?>
        </div>
        <? endforeach; ?>
    </section>

    <footer>
        <? if ($vote->multiplechoice): ?>
        <?= _('Sie konnten mehrere Antworten auswählen.') ?>
        <? endif; ?>
        <?= $vote->countInfo ?> 
        <?= $vote->anonymousInfo ?> 
        <?= $vote->endInfo ?>
        <div class="buttons">
            <?= $this->render_partial('vote/_buttons.php', array('vote' => $vote)); ?>
        </div>
    </footer>
</form>