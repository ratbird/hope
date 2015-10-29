<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

if (isset($flash['question']) && isset($flash['candidate'])) {
    $dialog = $GLOBALS['template_factory']->open('shared/question');
    echo $this->render_partial($dialog, array(
        "question"        => $flash['question'],
        "approvalLink"    => $controller->url_for('course/studygroup/edit_members/'
                                                  . $sem_id . '/remove_approved/todo/' . get_ticket()
                                                  . '?user=' . $flash['candidate']),
        "disapprovalLink" => $controller->url_for('course/studygroup/members/' . $sem_id . '/' . $page)
    ));
}
?>

<?= $this->render_partial("course/studygroup/_feedback") ?>


<table class="default sortable-table">
    <colgroup>
        <col width="40">
        <col>
        <col width="30">
    </colgroup>
    <caption>
        <?= _('GruppengründerIn') ?>
    </caption>
    <thead>
    <tr>
        <th data-sort="false"></th>
        <th data-sort="text"><?= _('Name') ?></th>
        <th data-sort="false"><?= _('Aktionen') ?></th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($moderators as $m) : ?>
        <tr>
            <td>
                <a style="position: relative" href="<?= $controller->url_for('profile', array('username' => $m['username']))?>">
                    <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::SMALL,
                        array('style' => 'margin-right: 5px', 'title' => htmlReady($m['fullname']))) ?>
                    <?= ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
                        ? Assets::img('red_star.png', array('style' => 'position: absolute; margin: 0px 0px 0px -15px'))
                        : '' ?>
                </a>
            </td>
            <td>
                <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                    <?= htmlReady($m['fullname']) ?>
                </a>
            </td>
            <td class="actions">
                <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent')
                       || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)
                ) : ?>

                    <?= $this->render_partial('course/studygroup/_members_options.php', compact('m')) ?>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>


<table class="default sortable-table">
    <colgroup>
        <col width="40">
        <col>
        <col width="30">
    </colgroup>
    <caption>
        <?= _('ModeratorIn') ?>
    </caption>
    <thead>
    <tr>
        <th data-sort="false"></th>
        <th data-sort="text"><?= _('Name') ?></th>
        <th data-sort="false"><?= _('Aktionen') ?></th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($tutors as $m) : ?>
        <tr>
            <td>
                <a style="position: relative" href="<?= $controller->url_for('profile', array('username' => $m['username']))?>">
                    <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::SMALL,
                        array('style' => 'margin-right: 5px', 'title' => htmlReady($m['fullname']))) ?>
                    <?= ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
                        ? Assets::img('red_star.png', array('style' => 'position: absolute; margin: 0px 0px 0px -15px'))
                        : '' ?>
                </a>
            </td>
            <td>
                <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                    <?= htmlReady($m['fullname']) ?>
                </a>
            </td>
            <td class="actions">
                <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent')
                       || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)
                ) : ?>

                    <?= $this->render_partial('course/studygroup/_members_options.php', compact('m')) ?>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>



<table class="default sortable-table">
    <colgroup>
        <col width="40">
        <col>
        <col width="30">
    </colgroup>
    <caption>
        <?= _('Mitglieder') ?>
    </caption>
    <thead>
    <tr>
        <th></th>
        <th data-sort="text"><?= _('Name') ?></th>
        <th data-sort="false"><?= _('Aktion') ?></th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($cmembers as $m) : ?>
        <tr>
            <td>
                <a style="position: relative" href="<?= $controller->url_for('profile', array('username' => $m['username']))?>">
                    <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::SMALL,
                        array('style' => 'margin-right: 5px', 'title' => htmlReady($m['fullname']))) ?>
                    <?= ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
                        ? Assets::img('red_star.png', array('style' => 'position: absolute; margin: 0px 0px 0px -15px'))
                        : '' ?>
                </a>
            </td>
            <td>
                <a href="<?= $controller->url_for('profile', array('username' => $m['username'])) ?>">
                    <?= htmlReady($m['fullname']) ?>
                </a>
            </td>
            <td class="actions">
                <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent')
                       || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)
                ) : ?>

                    <?= $this->render_partial('course/studygroup/_members_options.php', compact('m')) ?>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>



<? if ($rechte) : ?>
    <? if (count($accepted) > 0) : ?>
        <table class="default sortable-table">
            <caption><?= _('Offene Mitgliedsanträge') ?></caption>
            <colgroup>
                <col width="40">
                <col>
                <col width="80">
            </colgroup>
            <tr>
                <th data-sort="false" ></th>
                <th data-sort="text" >
                    <?= _('Name') ?>
                </th>
                <th data-sort="false" >
                    <?= _('Aktionen') ?>
                </th>
            </tr>

            <? foreach($accepted as $p) : ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                            <?= Avatar::getAvatar($p['user_id'])->getImageTag(Avatar::SMALL) ?>
                        </a>
                    </td>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                            <?= htmlReady($p['fullname']) ?>
                        </a>
                    </td>
                    <td class="actions">
                        <a href="<?=$controller->url_for('course/studygroup/edit_members/' . $sem_id . '/accept?user='.$p['username'])?>">
                            <?= Assets::img('icons/blue/accept', tooltip2(_('Eintragen')))?>
                        </a>

                        <a href="<?=$controller->url_for('course/studygroup/edit_members/' . $sem_id . '/deny?user='.$p['username'])?>">
                            <?= Assets::img('icons/blue/trash', tooltip2(_('Ablehnen')))?>
                        </a>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    <? endif; ?>

    <? if (count($invitedMembers) > 0) : ?>
        <table class="default sortable-table">
            <caption><?= _('Verschickte Einladungen') ?></caption>
            <colgroup>
                <col width="40">
                <col>
                <col width="80">
            </colgroup>
            <tr>
                <th data-sort="false"></th>
                <th data-sort="text">
                    <?= _('Name') ?>
                </th>
                <th data-sort="false" >
                    <?= _('Aktionen') ?>
                </th>
            </tr>

            <? foreach($invitedMembers as $p) : ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                            <?= Avatar::getAvatar($p['user_id'])->getImageTag(Avatar::SMALL) ?>
                        </a>
                    </td>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                            <?= htmlReady($p['fullname']) ?>
                        </a>
                    </td>
                    <td class="actions">
                        <a href="<?=$controller->url_for('course/studygroup/edit_members/' . $sem_id . '/cancelInvitation?user='.$p['username'])?>">
                            <?= Assets::img('icons/blue/trash', tooltip2(_('Verschickte Einladungen löschen')))?>
                        </a>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    <? endif; ?>
<? endif; ?>
