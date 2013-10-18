<? if ($msg) parse_msg($msg); ?>
<? if(!empty($flash['delete'])) : ?>
    <?= createQuestion2(sprintf(_('Wollen Sie die/den "%s" wirklich austragen?'), $status_groups[$flash['status']]),
        array('users' => $flash['delete']),
        array(),
        $controller->url_for(sprintf('course/members/cancel_subscription/collection/%s', $flash['status']))); ?>
<? endif ?>

<? if (count($dozenten) > 0) : ?>
    <?= $this->render_partial('course/members/dozent_list') ?>
<? endif ?>

<? if (count($tutoren) > 0) : ?>
    <br />
    <?= $this->render_partial('course/members/tutor_list') ?>
<? endif ?>

<? if ($is_tutor && $semAdmissionEnabled) : ?>
    <p style="float: right">
        <? //TODO?>
        <strong><?= _('Teilnahmebeschr�nkte Veranstaltung') ?></strong> -
        <?= _('Teilnehmerkontingent') ?> <?= $course->admission_turnout ?>,
        <?= _('davon belegt') ?>: <?= $count['members_contingent'] ?>,
        <?= _('zus�tzlich belegt') ?>: <?= $count['members'] - $count['members_contingent'] ?>
    </p>
    <div class="clear"></div>
<? endif ?>

<? if(count($autoren) >0) : ?>
    <br />
    <?= $this->render_partial('course/members/autor_list') ?>
<? endif ?>

<? if (count($users) > 0) : ?>
    <br />
    <?= $this->render_partial('course/members/user_list') ?>
<? endif ?>

<? if ($is_tutor && count($accepted) > 0) : ?>
    <?= $this->render_partial('course/members/accepted_list') ?>
<? endif ?>

<? if ($is_tutor && count($awaiting) > 0) : ?>
    <?= $this->render_partial('course/members/awaiting_list') ?>
<? endif ?>
