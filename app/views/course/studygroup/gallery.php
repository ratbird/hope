<h1><?= _('Mitglieder') ?></h1>

<ul class="studygroup-gallery">
    <? foreach ($cmembers as $user_id => $m) : ?>
        <? ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
            ? $options = array('style' => 'border: 3px solid rgb(255, 100, 100);'
                . 'border: 1px solid rgba(255, 0, 0, 0.5)')
            : $options = array() ?>
        <? $this->m = $m ?>
        <li>
            <div>
                <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                    <?= Avatar::getAvatar($user_id)->getImageTag(Avatar::MEDIUM, $options) ?>
                </a>
            </div>

            <div>
                <a href="<?= $controller->url_for('messages/write', array('rec_uname' => $m['username'])) ?>"
                   data-dialog="size=50%">
                    <?= Icon::create('mail', 'clickable', ['title' => _('Nachricht schreiben')])->asImg(20) ?>
                </a>
                <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent') || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
                    <?= $this->render_partial('course/studygroup/_members_options.php', compact('m')) ?>
                <? endif ?>
            </div>

            <div style="font-size: 0.8em;">
                <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                    <?= htmlReady($m['fullname']) ?>
                    <? if (isset($moderators[$user_id])) : ?>
                        <p><em><?= _("GruppengründerIn") ?></em></p>
                    <? elseif (isset($tutors[$user_id])) : ?>
                        <p><em><?= _("ModeratorIn") ?></em></p>
                    <? endif ?>
                </a>
            </div>
        </li>
    <? endforeach ?>
</ul>
