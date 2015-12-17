<li data-issue_id="<?= $topic->getId() ?>" class="topic_<?= $date->getId() ?>_<?= $topic->getId() ?>">
    <a href="<?= URLHelper::getLink('dispatch.php/course/topics#' . $topic->getId(), array('open' => $topic->getId())) ?>" class="title">
        <?= Icon::create('topic', 'clickable')->asImg(['class' => 'text-bottom']) ?>
        <?= htmlReady($topic['title']) ?>
    </a>
    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) : ?>
    <a href="#" onClick="STUDIP.Dates.removeTopicFromIcon.call(this); return false;">
        <?= Icon::create('trash', 'clickable')->asImg(['class' => "text-bottom"]) ?>
    </a>
    <? endif ?>
</li>