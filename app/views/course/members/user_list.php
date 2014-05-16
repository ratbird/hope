<? use \Studip\Button; ?>
<a name="users"></a>

<form action="<?= $controller->url_for('course/members/edit_user/') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default collapsable">
        <colgroup>
            <? if($is_tutor) :?>
            <col width="20">
            <? endif ?>
            <col width="20">
            <col>
            <? if($is_tutor) :?>
            <col width="15%">
            <col width="40%">
            <? endif ?>
            <col width="80">
        </colgroup>
        <caption>
            <?= $status_groups['user'] ?>
            <? if($is_tutor) :?>
            <span class="actions">
                <?= $controller->getEmailLinkByStatus('user', $users) ?>
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array(
                                'filter' => 'send_sms_to_all',
                                'who' => 'user',
                                'course_id' => $course_id,
                                'default_subject' => $subject)
                ) ?>">
                       <?= Assets::img('icons/16/blue/inbox.png', tooltip2(sprintf(_('Nachricht an alle %s versenden'), $status_groups['user']))) ?>
                </a>
            </span>
            <? endif ?>
        </caption>
        <thead>
            <tr class="sortable">
                <? if($is_tutor) :?>
                <th><input aria-label="<?= sprintf(_('Alle %s auswählen'), $status_groups['user']) ?>"
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=user]"></th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == 'user') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <? ($sort_status != 'user') ? $order = 'desc' : $order = $order ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=user&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#users">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <? if($is_tutor) : ?>
                <th <?= ($sort_by == 'mkdate' && $sort_status == 'user') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=mkdate&sort_status=user&order=%s&toggle=%s',
                       $order, ($sort_by == 'mkdate'))) ?>#user">
                        <?= _('Anmeldedatum') ?>
                    </a>
                </th>
                <th><?=_('Studiengang')?></th>
                <? endif ?>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($users as $leser) : ?>
        <? $fullname = $leser['fullname'];?>
            <tr>
                <? if($is_tutor) :?>
                <td>
                    <input aria-label="<?= sprintf(_('%s auswählen'), $status_groups['user']) ?>"
                           type="checkbox" name="user[<?= $leser['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$leser['username'])) ?>">
                    <?= Avatar::getAvatar($leser['user_id'],$leser['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px','title' => htmlReady($fullname))); ?>
                    <?= $leser['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <? if($is_tutor) : ?>
                    <td>
                        <? if(!empty($leser['mkdate'])) : ?>
                            <?= strftime('%x %X', $leser['mkdate'])?>
                        <? endif ?>
                    </td>
                    <td>
                        <?= $this->render_partial("course/members/_studycourse.php", array('study_courses' => UserModel::getUserStudycourse($leser['user_id']))) ?>
                    </td>
                <? endif ?>
                <td style="text-align: right">
                    <? if($user_id != $leser['user_id']) : ?>
                    <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                                array('filter' => 'send_sms_to_all',
                                'rec_uname' => $leser['username'],
                                'default_subject' => $subject))
                            ?>
                    ">
                        <?= Assets::img('icons/16/blue/mail.png',
                                tooltip2(sprintf(_('Nachricht an %s senden'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>

                    <? if ($is_tutor) : ?>
                    <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/user/%s',
                                $leser['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/door-leave.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if ($is_tutor) : ?>
        <tfoot>
            <tr>
                <td colspan="6">
                    <select name="action_user" id="user_action" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion auswählen') ?></option>
                        <option value="upgrade"><?= sprintf(_('Zu %s hochstufen'),
                                htmlReady($status_groups['autor'])) ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <option value="message"><?=_('Nachricht senden')?></option>
                        <!--<option value="copy_to_course"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= Button::create(_('Ausführen'), 'submit_user') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>
