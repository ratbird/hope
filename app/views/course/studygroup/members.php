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

<? if (!empty($moderators)) : ?>
    <?= $this->render_partial('course/studygroup/_members_list.php',
        array('title' =>  _('GruppengründerIn'), 'sem_id' => $sem_id, 'members' => $moderators, 'moderator_list' => true))?>
<? endif ?>

<? if (!empty($tutors)) : ?>
    <?= $this->render_partial('course/studygroup/_members_list.php',
        array('title' =>  _('ModeratorIn'), 'sem_id' => $sem_id, 'members' => $tutors))?>
<? endif ?>

<? if (!empty($cmembers)) : ?>
    <?= $this->render_partial('course/studygroup/_members_list.php',
        array('title' =>  _('Mitglieder'), 'sem_id' => $sem_id, 'members' => $cmembers))?>
<? endif ?>



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
                <th data-sort="false"></th>
                <th data-sort="text">
                    <?= _('Name') ?>
                </th>
                <th data-sort="false">
                    <?= _('Aktionen') ?>
                </th>
            </tr>

            <? foreach ($accepted as $p) : ?>
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
                        <a href="<?= $controller->url_for('course/studygroup/edit_members/' . $sem_id . '/accept?user=' . $p['username']) ?>">
                            <?= Assets::img('icons/blue/accept', tooltip2(_('Eintragen'))) ?>
                        </a>

                        <a data-confirm="<?=_('Wollen Sie die Mitgliedschaft wirklich ablehnen?')?>" href="<?= $controller->url_for('course/studygroup/edit_members/' . $sem_id . '/deny?user=' . $p['username']) ?>">
                            <?= Assets::img('icons/blue/decline', tooltip2(_('Wollen Sie die Mitgliedschaft wirklich ablehnen?'))) ?>
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
                <th data-sort="false">
                    <?= _('Aktionen') ?>
                </th>
            </tr>

            <? foreach ($invitedMembers as $p) : ?>
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
                        <a href="<?= $controller->url_for('course/studygroup/edit_members/' . $sem_id . '/cancelInvitation?user=' . $p['username']) ?>">
                            <?= Assets::img('icons/blue/decline', tooltip2(_('Verschickte Einladungen löschen'))) ?>
                        </a>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    <? endif; ?>
<? endif; ?>
