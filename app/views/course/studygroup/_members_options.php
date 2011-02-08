<?
# Lifter010: TODO
?>
<? if (array_key_exists($m['user_id'], $moderators) && $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$m['username'].'/promote/tutor') ?>" alt="NutzerIn runterstufen">
        <?= makebutton('runterstufen') ?>
    </a>
<? elseif (array_key_exists($m['user_id'], $tutors)) : ?>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$m['username'].'/promote/autor') ?>" alt="NutzerIn runterstufen">
        <?= makebutton('runterstufen') ?>
    </a>
<? else : ?>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$m['username'].'/promote/tutor') ?>" alt="NutzerIn befördern">
        <?= makebutton('hochstufen') ?>
    </a>
    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$m['username'].'/remove') ?>" alt="NutzerIn runterstufen">
        <?= makebutton('rauswerfen') ?>
    </a>
<? endif ?>
