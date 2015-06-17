<tr id="date_<?= $date->getId() ?>" class="<?= $date instanceof CourseExDate ? "ausfall" : "" ?><?= $is_next_date ? 'nextdate' : ""?>"<?= $is_next_date ? ' title="'._("Der nächste Termin").'"' : ""?> data-termin_id="<?= htmlReady($date->id) ?>">
    <td data-timestamp="<?=htmlReady($date['date']);?>" class="date_name">
    <? if (is_a($date, "CourseExDate")) : ?>
            <?= Assets::img("icons/16/black/date", array('class' => "text-bottom")) ?>
            <?= htmlReady($date->getFullname()) ?>
            <?= tooltipIcon($date->content)?>
    <? else : ?>
        <a href="<?= URLHelper::getLink('dispatch.php/course/dates/details/' . $date->getId()) ?>" data-dialog>
            <?= Assets::img('icons/16/blue/date', array('class' => 'text-bottom')) ?>
            <?= htmlReady($date->getFullname()) ?>
        </a>
    <? endif ?>
    </td>
    <td><?= htmlReady($date->getTypeName()) ?></td>
    <td style="display: flex; flex-direction: row;">
        <ul class="themen_list clean" style="">
        <? foreach ($date->topics as $topic) : ?>
            <?= $this->render_partial('course/dates/_topic_li', compact('topic', 'date')) ?>
        <? endforeach ?>
        </ul>
        <? if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) : ?>
            <a href="<?= URLHelper::getLink("dispatch.php/course/dates/new_topic", array('termin_id' => $date->getId())) ?>" style="align-self: flex-end;" title="<?= _("Thema hinzufügen") ?>" data-dialog>
                <?= Assets::img("icons/12/grey/add") ?>
            </a>
        <? endif ?>
    </td>
    <td>
    <? if ($date->getRoom()) : ?>
        <?= $date->getRoom()->getFormattedLink() ?>
    <? else : ?>
        <?= htmlReady($date->raum) ?>
    <? endif ?>
    </td>
</tr>