<table class="studygroupmemberlist default sortable-table">
    <colgroup>
        <col width="40">
        <col>
        <? if (!$moderator_list) : ?>
            <col>
        <? endif ?>
    </colgroup>
    <caption>
        <?= $title ?>
    </caption>
    <thead>
        <tr>
            <th data-sort="false"></th>
            <th data-sort="text"><?= _('Name') ?></th>
            <? if (!$moderator_list) : ?>
                <th data-sort="false" class="actions"><?= _('Aktionen') ?></th>
            <? endif ?>
        </tr>
    </thead>
    <tbody>
        <? foreach ($members as $m) : ?>
            <? $fullname = $m instanceof CourseMember ? $m->user->getFullname('no_title_rev') : $m['fullname']?>
            <tr>
                <td>
                    <a style="position: relative"
                       href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                        <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))) ?>
                        <?= ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
                            ? Assets::img('red_star', array('style' => 'position: absolute; margin: 0px 0px 0px -15px'))
                            : '' ?>
                    </a>
                </td>
                <td>
                    <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                        <?= $fullname ?>
                    </a>
                </td>
                <? if (!$moderator_list) : ?>
                    <td class="actions">
                        <a href="<?= $controller->url_for('messages/write', array('rec_uname' => $m['username'])) ?>"
                           data-dialog="size=50%">
                            <?= Icon::create('mail', 'clickable', ['title' => _('Nachricht schreiben')])->asImg(20) ?>
                        </a>
                        <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent') || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
                            <?= $this->render_partial('course/studygroup/_members_options.php', compact('m')) ?>
                        <? endif ?>
                    </td>
                <? endif ?>
            </tr>
        <? endforeach ?>
    </tbody>
</table>