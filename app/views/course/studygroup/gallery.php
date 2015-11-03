<h1><?= _('Mitglieder') ?></h1>

<ul class="studygroup-gallery">
    <? foreach ($cmembers as $user_id => $m) : ?>
        <? ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
            ? $options = array('style' => 'border: 3px solid rgb(255, 100, 100);'
                . 'border: 1px solid rgba(255, 0, 0, 0.5)')
            : $options = array() ?>
        <? $this->m = $m ?>
        <li>

            <section>
                <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                    <?= Avatar::getAvatar($user_id)->getImageTag(Avatar::MEDIUM, $options) ?>
                    <div>
                         <?= htmlReady($m['fullname']) ?>
                        <? if (isset($moderators[$user_id])) : ?>
                            <em><?= _("GruppengründerIn") ?></em>
                        <? elseif (isset($tutors[$user_id])) : ?>
                            <em><?= _("ModeratorIn") ?></em>
                        <? endif ?>
                    </div>
                </a>
        </li>
    <? endforeach ?>
</ul>

<? if ($anzahl > 20) : ?>
    <div style="text-align:right; padding-top: 2px; padding-bottom: 2px; margin-top:1.5em">
        <?= $GLOBALS['template_factory']->render('shared/pagechooser',
            array("perPage" => 20, "num_postings" => $anzahl, "page" => $page, "pagelink" => 'dispatch.php/course/studygroup/members/' . $sem_id . '/%s')) ?>
    </div>
<? endif; ?>
