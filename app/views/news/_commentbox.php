<div class="comment">
    <div class="head">
        <div class="date"><?= reltime($comment[3]) ?></div>
        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $comment[2]) ?>">
            #<?= $index + 1 ?> <?= htmlReady($comment[1]) ?>
        </a>
    </div>
    <?= formatReady($comment[0]) ?>
</div>