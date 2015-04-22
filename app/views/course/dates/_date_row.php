<tr id="date_<?= $date->getId() ?>" class="<?= is_a($date, "CourseExDate") ? "ausfall" : "" ?><?= $is_next_date ? 'nextdate' : ""?>"<?= $is_next_date ? ' title="'._("Der nächste Termin").'"' : ""?>>
    <td data-timestamp="<?=htmlReady($date['date']);?>">
        <? if (is_a($date, "CourseExDate")) : ?>
            <?= Assets::img("icons/16/black/date", array('class' => "text-bottom")) ?>
            <?= htmlReady($date->getFullname()) ?>
            <?= tooltipIcon($date->content)?>
        <? else : ?>
        <a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-dialog>
            <?= Assets::img("icons/16/blue/date", array('class' => "text-bottom")) ?>
            <?= htmlReady($date->getFullname()) ?>
        </a>
        <? endif ?>
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