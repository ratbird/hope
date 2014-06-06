<li data-issue_id="<?= $topic->getId() ?>" class="topic_<?= $topic->getId() ?>">
    <a href="<?= URLHelper::getLink("dispatch.php/course/topics", array('open' => $topic->getId())) ?>">
        <?= Assets::img("icons/16/blue/topic", array('class' => "text-bottom")) ?>
        <?= htmlReady($topic['title']) ?>
    </a>
    <div class="topic_decription"><?= $topic['description'] ? formatReady($topic['description']) : _("Keine Beschreibung vorhanden") ?></div>
    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $topic['seminar_id'])) : ?>
        <a href="#" onClick="" class="remove_topic"><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
    <? endif ?>
</li>