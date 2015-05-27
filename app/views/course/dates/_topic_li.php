<li data-issue_id="<?= $topic->getId() ?>" class="topic_<?= $topic->getId() ?>">
    <a href="<?= URLHelper::getLink('dispatch.php/course/topics#' . $topic->getId(), array('open' => $topic->getId())) ?>">
        <?= Assets::img('icons/16/blue/topic', array('class' => 'text-bottom')) ?>
        <?= htmlReady($topic['title']) ?>
    </a>
</li>