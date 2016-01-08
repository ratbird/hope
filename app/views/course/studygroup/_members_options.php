<? if ($GLOBALS['perm']->have_studip_perm('dozent', $sem_id) && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id, $m['user_id'])) : ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/downgrade?user='.$m['username'])?>">
        <?= Icon::create('arr_2down', 'clickable', ['title' => _('Runterstufen')])->asImg(20)?>
    </a>
<? endif ?>

<? if($GLOBALS['perm']->have_studip_perm('dozent', $sem_id) && !array_key_exists($m['user_id'], $moderators)) : ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/promote/tutor?user='.$m['username'])?>">
        <?= Icon::create('arr_2up', 'clickable', ['title' => _('Hochstufen')])->asImg(20)?>
    </a>
<? endif ?>

<? if ($m['user_id'] !== $GLOBALS['user']->id && !array_key_exists($m['user_id'], $moderators)): ?>
    <a href="<?=$controller->url_for('course/studygroup/edit_members/'.$sem_id.'/remove?user='.$m['username'])?>">
        <?= Icon::create('trash', 'clickable', ['title' => _('Rauswerfen')])->asImg(20)?>
    </a>
<? endif; ?>