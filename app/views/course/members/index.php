<? if ($msg) parse_msg($msg); ?>

<? if (!$is_tutor) : ?>
    <? if ($my_visibilty['iam_visible']) : ?>
        <br />
        <b><?= _('Sie erscheinen nicht auf der Teilnehmerliste.'); ?></b><br>
        <a href="<?= $controller->url_for(sprintf('course/members/change_visibility?cmd=make_visible&mode=%s', 
                $my_visibilty['visible_mode'])) ?>">
            <?= Assets::img('icons/16/blue/visibility-visible.png', tooltip2('Sichtbarkeit ändern')) ?>
            <?= _('Klicken Sie hier, um sichtbar zu werden.') ?>
        </a>
        <br />
    <? else : ?>
        <br />
        <b><?= _('Sie erscheinen für andere TeilnehmerInnen sichtbar auf der Teilnehmerliste..'); ?></b><br>
        <a href="<?= $controller->url_for(sprintf('course/members/change_visibility?cmd=make_invisible&mode=%s',
                $my_visibilty['visible_mode'])) ?>">
            <?= Assets::img('icons/16/blue/visibility-invisible.png', tooltip2('Sichtbarkeit ändern')) ?>
            <?= _('Klicken Sie hier, um unsichtbar zu werden.') ?>
        </a>
        <br />
    <? endif ?>
    <br />
<? endif ?>


<? if (count($dozenten) > 0) : ?>
    <?= $this->render_partial('course/members/dozent_list') ?>
    <br />
<? endif ?>

<? if (count($tutoren) > 0) : ?>
    <?= $this->render_partial('course/members/tutor_list') ?>
<? endif ?>

<br />

<? if ($rechte == 'autor' && $semAdmissionEnabled) : ?>
    <p style="float: right">
        <? //TODO?>
        <strong><?= _('Teilnahmebeschränkte Veranstaltung') ?></strong> -
        <?= _('Teilnehmerkontingent') ?> <?= $course['admission_turnout'] ?>, 
        <?= _('davon belegt') ?>: <?= $count['members_contingent'] ?>, 
        <?= _('zusätzlich belegt') ?>: <?= $count['members'] - $count['members_contingent'] ?>
    </p>
    <div class="clear"></div>
<? endif ?>


<?= $this->render_partial('course/members/autor_list') ?>

<? if ($rechte && count($users) > 0) : ?>
    <br />
    <?= $this->render_partial('course/members/user_list') ?>
<? endif ?>

<? if ($rechte && count($accepted) > 0) : ?>
    <?= $this->render_partial('course/members/accepted_list') ?>
<? endif ?>

<? if ($rechte && count($awaiting) > 0) : ?>
    <?= $this->render_partial('course/members/awaiting_list') ?>
<? endif ?>
