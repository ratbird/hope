<?
# Lifter010: TODO
?>
<? if (array_key_exists($m['user_id'], $moderators) && $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/tutor?user='.$m['username']) ?>">
        <?= makebutton('runterstufen') ?>
    </a>
<? elseif (array_key_exists($m['user_id'], $tutors)) : ?>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/autor?user='.$m['username']) ?>">
        <?= makebutton('runterstufen') ?>
    </a>
<? else : ?>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/tutor?user='.$m['username']) ?>">
        <?= makebutton('hochstufen') ?>
    </a>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/remove?user='.$m['username']) ?>">
        <?= makebutton('rauswerfen') ?>
    </a>
<? endif ?>
