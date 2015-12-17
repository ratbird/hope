<? if(array_key_exists($m['user_id'], $tutors) && ($m['user_id'] == $GLOBALS['auth']->auth['uid'])) :?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/autor?user='.$m['username'])?>">
        <?= Icon::create('arr_2down', 'clickable', ['title' => _('Runterstufen')])->asImg()?>
    </a>
<? elseif (array_key_exists($m['user_id'], $tutors) && $GLOBALS['perm']->have_studip_perm('dozent', $sem_id)) : ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/autor?user='.$m['username'])?>">
        <?= Icon::create('arr_2down', 'clickable', ['title' => _('Runterstufen')])->asImg()?>
    </a>
<? elseif($GLOBALS['perm']->have_studip_perm('dozent', $sem_id) && !array_key_exists($m['user_id'], $moderators)) : ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/tutor?user='.$m['username'])?>">
        <?= Icon::create('arr_2up', 'clickable', ['title' => _('Hochstufen')])->asImg()?>
    </a>
<? endif ?>

<? if ($m['user_id'] !== $GLOBALS['user']->id && !array_key_exists($m['user_id'], $moderators)): ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/remove?user='.$m['username'])?>">
        <?= Icon::create('trash', 'clickable', ['title' => _('Rauswerfen')])->asImg()?>
    </a>
<? endif; ?>