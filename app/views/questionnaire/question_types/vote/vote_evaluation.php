<?
$data = $vote['questiondata']->getArrayCopy();
$results = array();
$results_users = array();
foreach ($data['options'] as $option) {
    $results[] = 0;
    $results_users[] = array();
}
if (count($data['options']) > 0) {
    foreach ($answers as $answer) {
        if ($data['multiplechoice']) {
            foreach ($answer['answerdata']['answers'] as $a) {
                $results[(int) $a - 1]++;
                $results_users[(int) $a - 1][] = $answer['user_id'];
            }
        } else {
            $results[(int) $answer['answerdata']['answers'] - 1]++;
            $results_users[(int) $answer['answerdata']['answers'] - 1][] = $answer['user_id'];
        }
    }
}

$ordered_results = $results;
arsort($ordered_results);
$ordered_options = array();
$ordered_users = array();
foreach ($ordered_results as $index => $value) {
    if ($value > 0) {
        $ordered_options[] = $data['options'][$index];
    } else {
        unset($ordered_results[$index]);
    }
}
rsort($ordered_results);
?>
<h3>
    <?= Icon::create("vote", "clickable")->asimg("20px", array('class' => "text-bottom")) ?>
    <?= formatReady($vote['questiondata']['question']) ?>
</h3>

<? if (count($vote->answers) > 0 && count($data['options']) > 0) : ?>
    <div style="max-height: none; opacity: 1;" id="questionnaire_<?= $vote->getId() ?>_chart" class="ct-chart"></div>
    <script>
    <?= Request::isAjax() ? 'jQuery(document).add(".questionnaire_results").one("dialog-open", function () {' : 'jQuery(function () {' ?>
        var data = {
            labels: <?= json_encode(studip_utf8encode($ordered_options)) ?>,
            series: [<?= json_encode(studip_utf8encode($ordered_results)) ?>]
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

<table class="default nohover">
    <tbody>
    <? $countAnswers = $vote->questionnaire->countAnswers() ?>
    <? foreach ($vote['questiondata']['options'] as $key => $option) : ?>
        <tr>
            <? $percentage = $countAnswers ? round((int) $results[$key] / $countAnswers * 100) : 0 ?>
            <td style="text-align: right; background-size: <?= $percentage ?>% 100%; background-position: right center; background-image: url('<?= Assets::image_path("vote_lightgrey.png") ?>'); background-repeat: no-repeat;" width="50%">
                <strong><?= formatReady($option) ?></strong>
            </td>
            <td style="white-space: nowrap;">
                (<?= $percentage ?>%
                | <?= (int) $results[$key] ?>/<?= $countAnswers ?>)
            </td>
            <td width="50%">
                <? if (!$vote->questionnaire['anonymous'] && $results[$key]) : ?>
                <? foreach ($results_users[$key] as $index => $user_id) : ?>
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
