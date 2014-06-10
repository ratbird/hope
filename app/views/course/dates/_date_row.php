<?
$is_next_date = (!$dates[$key - 1] || ($dates[$key - 1]['end_time'] < time())) && ($date['end_time'] >= time());
?>
<tr id="date_<?= $date->getId() ?>"<?= $is_next_date ? ' class="nextdate" title="'._("Der nächste Termin").'"' : ""?>>
    <td data-timestamp="<?=htmlReady($date['date']);?>">
        <a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-dialog>
            <?= Assets::img("icons/16/blue/date", array('class' => "text-bottom")) ?>
            <?= htmlReady($date->getFullname()) ?>
        </a>
    </td>
    <td><?= htmlReady($date->getTypeName()) ?></td>
    <td>
        <ul class="themen_list clean">
            <? foreach ($date->topics as $topic) : ?>
                <?= $this->render_partial("course/dates/_topic_li", compact("topic")) ?>
            <? endforeach ?>
        </ul>
    </td>
    <td>
        <? if ($date->getRoom()) : ?>
        <?= $date->getRoom()->getFormattedLink() ?>
        <? else : ?>
        <?= htmlReady($date->raum) ?>
        <? endif ?>
    </td>
</tr>