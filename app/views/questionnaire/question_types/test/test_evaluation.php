<?
$data = $vote['questiondata']->getArrayCopy();
?>
<h3>
    <?= Assets::img("icons/20/black/test", array('class' => "text-bottom")) ?>
    <?= formatReady($vote['questiondata']['question']) ?>
</h3>
<? if (count($vote->answers) > 0) : ?>
    <div style="max-height: none; opacity: 1;" id="questionnaire_<?= $vote->getId() ?>_chart" class="ct-chart"></div>
    <script>
    <?= Request::isAjax() ? 'jQuery(document).one("dialog-open", function () {' : 'jQuery(function () {' ?>
        <?

        $results = array();
        $results_users = array();
        $users = array();
        foreach ($data['options'] as $option) {
            $results[] = 0;
            $results_users[] = array();
        }
        foreach ($answers as $answer) {
            if ($data['multiplechoice']) {
                foreach ($answer['answerdata']['answers'] as $a) {
                    $results[(int) $a - 1]++;
                    $results_users[(int) $a - 1][] = $answer['user_id'];
                    $users[] = $answer['user_id'];
                }
            } else {
                $results[(int) $answer['answerdata']['answers'] - 1]++;
                $results_users[(int) $answer['answerdata']['answers'] - 1][] = $answer['user_id'];
                $users[] = $answer['user_id'];
            }
        }
        $users = array_unique($users);
        ?>
        var data = {
            labels: <?= json_encode(studip_utf8encode($data['options'])) ?>,
            series: [<?= json_encode(studip_utf8encode($results)) ?>]
        };
        <? if ($vote['questiondata']['multiplechoice']) : ?>
            new Chartist.Bar('#questionnaire_<?= $vote->getId() ?>_chart', data, { onlyInteger: true, axisY: { onlyInteger: true } });
        <? else : ?>
            data.series = data.series[0];
            new Chartist.Pie('#questionnaire_<?= $vote->getId() ?>_chart', data, { labelPosition: 'outside' });
        <? endif ?>
    });
    </script>
<? endif ?>
<? if (is_array($users) && in_array($GLOBALS['user']->id, $users)) : ?>
    <div style="max-height: none; opacity: 1; font-size: 1.4em; text-align: center;">
        <? if ($vote->correctAnswered()) : ?>
            <?= Assets::img("icons/25/green/accept", array('class' => "text-bottom")) ?>
            <?= _("Richtig beantwortet!") ?>
        <? else : ?>
            <?= Assets::img("icons/25/red/decline", array('class' => "text-bottom")) ?>
            <?= _("Falsch beantwortet!") ?>
        <? endif ?>
    </div>
<? endif ?>

<table class="default nohover">
    <tbody>
    <? foreach ($vote['questiondata']['options'] as $key => $option) : ?>
        <tr class="<?= $data['correctanswer'] ? "correct" : "incorrect" ?>">
            <td style="text-align: right; background-size: <?= $countAnswers ? round((int) $results[$key] / $countAnswers * 100) : 0 ?>% 100%; background-position: right center; background-image: url('<?= Assets::image_path("vote_lightgrey.png") ?>'); background-repeat: no-repeat;" width="50%">
                <strong><?= formatReady($option) ?></strong>
                <? if (in_array($key + 1, $data['correctanswer'])) : ?>
                    <?= Assets::img("icons/16/green/checkbox-checked", array('class' => "text-bottom", 'title' =>  _("Diese Antwort ist richtig"))) ?>
                <? else : ?>
                    <?= Assets::img("icons/16/grey/checkbox-unchecked", array('class' => "text-bottom", 'title' => _("Eine falsche Antwort"))) ?>
                <? endif ?>
            </td>
            <td style="white-space: nowrap;">
                <? $countAnswers = $vote->questionnaire->countAnswers() ?>
                (<?= $countAnswers ? round((int) $results[$key] / $countAnswers * 100) : 0 ?>%
                | <?= (int) $results[$key] ?>/<?= $countAnswers ?>)
            </td>
            <td width="50%">
                <? if (!$vote->questionnaire['anonymous'] && $results[$key]) : ?>
                    <? foreach ((array) $results_users[$key] as $index => $user_id) : ?>
                        <? if ($user_id && $user_id !== "nobody") : ?>
                            <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($user_id))) ?>">
                                <?= Avatar::getAvatar($user_id, get_username($user_id))->getImageTag(Avatar::SMALL, array('title' => htmlReady(get_fullname($user_id)))) ?>
                                <? if (count($results_users[$key]) < 4) : ?>
                                    <?= htmlReady(get_fullname($user_id)) ?>
                                <? endif ?>
                            </a>
                        <? endif ?>
                    <? endforeach ?>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>

