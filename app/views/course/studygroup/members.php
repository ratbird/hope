<?
if (isset($flash['question']) && isset($flash['candidate'])) {
    $dialog = $GLOBALS['template_factory']->open('shared/question');
    echo $this->render_partial($dialog, array(
        "question"        => $flash['question'],
        "approvalLink"    => $controller->url_for('course/studygroup/edit_members/'
                                                  . $sem_id . '/remove_approved/todo/' . get_ticket()
                                                  . '?user=' . $flash['candidate']),
        "disapprovalLink" => $controller->url_for('course/studygroup/members/' . $sem_id . '/' . $page),
    ));
}
$view = count($moderators) + count($tutors) + count($autors) >= 50 ? "list" : "gallery";
?>

<?= $this->render_partial("course/studygroup/_feedback", compact('anzahl', 'page', 'sem_id')) ?>

<? $partial = $view == 'list' ? 'course/studygroup/_members_list.php' : 'course/studygroup/gallery.php' ?>

<? if (!empty($moderators)) : ?>
    <?= $this->render_partial($partial, array(
        'title' => $sem_class['title_dozent_plural'] ?: _("Gruppenadministrator/-innen"),
        'sem_id' => $sem_id,
        'members' => $moderators,
        'moderator_list' => true
    )) ?>
<? endif ?>

<? if (!empty($tutors)) : ?>
    <?= $this->render_partial($partial, array(
        'title' => $sem_class['title_tutor_plural'] ?: _("Moderator/-innen"),
        'sem_id' => $sem_id,
        'members' => $tutors
    )) ?>
<? endif ?>

<? if (!empty($autors)) : ?>
    <?= $this->render_partial($partial, array(
        'title' => $sem_class['title_autor_plural'] ?: _("Mitglieder"),
        'sem_id' => $sem_id,
        'members' => $autors
    )) ?>
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
            <thead>
                <tr>
                    <th data-sort="false"></th>
                    <th data-sort="text">
                        <?= _('Name') ?>
                    </th>
                    <th data-sort="false">
                        <?= _('Aktionen') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($accepted as $p) : ?>
                    <tr>
                        <td>
                            <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p->username) ?>">
                                <?= Avatar::getAvatar($p['user_id'])->getImageTag(Avatar::SMALL) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p->username) ?>">
                                <?= htmlReady($p->user->getFullname('no_title_rev')) ?>
                            </a>
                        </td>
                        <td class="actions">
                            <a href="<?= $controller->url_for('course/studygroup/edit_members/' . $sem_id . '/accept?user=' . $p->username) ?>">
                                <?= Icon::create('accept', 'clickable', ['title' => _('Eintragen')])->asImg() ?>
                            </a>

                            <a data-confirm="<?= _('Wollen Sie die Mitgliedschaft wirklich ablehnen?') ?>"
                               href="<?= $controller->url_for('course/studygroup/edit_members/' . $sem_id . '/deny?user=' . $p->username) ?>">
                                <?= Icon::create('decline', 'clickable', ['title' => _('Wollen Sie die Mitgliedschaft wirklich ablehnen?')])->asImg() ?>
                            </a>
                        </td>
                    </tr>
                <? endforeach ?>
            </tbody>
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
            <thead>
                <tr>
                    <th data-sort="false"></th>
                    <th data-sort="text">
                        <?= _('Name') ?>
                    </th>
                    <th data-sort="false">
                        <?= _('Aktionen') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
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
                                <?= Icon::create('decline', 'clickable', ['title' => _('Verschickte Einladungen löschen')])->asImg() ?>
                            </a>
                        </td>
                    </tr>
                <? endforeach ?>
            </tbody>
        </table>
    <? endif; ?>
<? endif; ?>
