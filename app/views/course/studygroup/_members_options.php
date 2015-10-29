<? if(array_key_exists($m['user_id'], $tutors) && ($m['user_id'] == $GLOBALS['auth']->auth['uid'])) :?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/autor?user='.$m['username'])?>">
        <?= Assets::img('icons/blue/arr_2down', tooltip2(_('Runterstufen')))?>
    </a>
<? elseif (array_key_exists($m['user_id'], $tutors) && $GLOBALS['perm']->have_studip_perm('dozent', $sem_id)) : ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/autor?user='.$m['username'])?>">
        <?= Assets::img('icons/blue/arr_2down', tooltip2(_('Runterstufen')))?>
    </a>
<? elseif($GLOBALS['perm']->have_studip_perm('dozent', $sem_id) && !array_key_exists($m['user_id'], $moderators)) : ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/tutor?user='.$m['username'])?>">
        <?= Assets::img('icons/blue/arr_2up', tooltip2(_('Hochstufen')))?>
    </a>
<? endif ?>

<? if ($m['user_id'] !== $GLOBALS['user']->id && !array_key_exists($m['user_id'], $moderators)): ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/remove?user='.$m['username'])?>">
        <?= Assets::img('icons/blue/trash', tooltip2(_('Rauswerfen')))?>
    </a>
<? endif; ?>