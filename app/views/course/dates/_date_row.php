<tr id="date_<?= $date->getId() ?>" class="<?= $date instanceof CourseExDate ? "ausfall" : "" ?><?= $is_next_date ? 'nextdate' : ""?>"<?= $is_next_date ? ' title="'._("Der nächste Termin").'"' : ""?> data-termin_id="<?= htmlReady($date->id) ?>">
    <td data-timestamp="<?=htmlReady($date['date']);?>" class="date_name">
        <? $icon = 'date+' . ($date['chdate'] > $last_visitdate ? 'new/' : '');?>
        <? if (is_a($date, "CourseExDate")) : ?>
            <?= Icon::create($icon, 'info')->asImg(['class' => "text-bottom"]) ?>
            <?= htmlReady($date->getFullname()) ?>
            <?= tooltipIcon($date->content)?>
        <? else : ?>
            <a href="<?= URLHelper::getLink('dispatch.php/course/dates/details/' . $date->getId()) ?>" data-dialog>
                <?= Icon::create($icon, 'clickable')->asImg(['class' => "text-bottom"]) ?>
                <?= htmlReady($date->getFullname()) ?>
            </a>
        <? endif ?>
        <? if (count($date->dozenten) && count($date->dozenten) != $lecturer_count) : ?>
            (<? foreach ($date->dozenten as $key => $dozent) {
                if ($key > 0) {
                    echo ", ";
                }
                echo htmlReady($dozent->getFullName());
            } ?>)
        <? endif ?>
    </td>
    <td><?= htmlReady($date->getTypeName()) ?></td>
    <? if (!$date instanceof CourseExDate) : ?>
        <td>
            <div style="display: flex; flex-direction: row;">
                <ul class="themen_list clean" style="">
                <? foreach ($date->topics as $topic) : ?>
                    <?= $this->render_partial('course/dates/_topic_li', compact('topic', 'date')) ?>
                <? endforeach ?>
                </ul>
                <? if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) : ?>
                    <a href="<?= URLHelper::getLink("dispatch.php/course/dates/new_topic", array('termin_id' => $date->getId())) ?>" style="align-self: flex-end;" title="<?= _("Thema hinzufügen") ?>" data-dialog>
                        <?= Icon::create('add', 'clickable')->asImg(12) ?>
                    </a>
                <? endif ?>
            </div>
        </td>
        <td>
        <? if ($date->getRoom()) : ?>
            <?= $date->getRoom()->getFormattedLink() ?>
        <? else : ?>
            <?= htmlReady($date->raum) ?>
        <? endif ?>
        </td>
    <? else : ?>
        <td colspan="2"></td>
    <? endif ?>
</tr>