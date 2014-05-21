<li data-issue_id="<?= $topic->getId() ?>" class="topic_<?= $topic->getId() ?>">
    <a href="<?= URLHelper::getLink("dispatch.php/course/topics") ?>">
        <?= Assets::img("icons/16/blue/star", array('class' => "text-bottom")) ?>
        <?= htmlReady($topic['title']) ?>
    </a>
    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $topic['seminar_id'])) : ?>
        <a href="#" onClick="" class="remove_topic"><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
    <? endif ?>
</li>