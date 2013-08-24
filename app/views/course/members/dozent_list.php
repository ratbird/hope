
<table class="default collapsable ">
    <caption>
    	<span class="actions">
             <? if ($is_tutor) : ?>
                <?=$controller->getEmailLinkByStatus('dozent')?>
                <a href="<?= URLHelper::getLink('sms_send.php', array('filter' => 'send_sms_to_all', 'who' =>
                        'dozent', 'sms_source_page' => 'dispatch.php/course/members',
                        'course_id' => $course_id, 'subject' => $subject)) ?>">
                    <?= Assets::img('icons/16/blue/inbox.png',
                            tooltip2(sprintf(_('Nachricht an alle %s versenden'), $status_groups['dozent']))) ?>
                </a>
            <? endif ?>
 		</span>
 		<?= $this->status_groups['dozent'] ?>
    </caption>
    <colgroup>
        <col width="<?=($is_tutor) ? '40' : '20'?>">
        <col>
        <col width="80">
    </colgroup>
    <thead>
        <tr class="sortable">
            <th></th>
            <th <?= ($sort_by == 'nachname' && $sort_status == 'dozent') ? sprintf('class="sort%s"', $order) : '' ?>>
                <? ($sort_status != 'dozent') ? $order = 'desc' : $order = $order ?>
                <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=dozent&order=%s&toggle=%s',
                        $order, ($sort_by == 'nachname'))) ?>">
                    <?=_('Nachname, Vorname')?>
                </a>
            </th>
            <th style="text-align: right"><?= _('Aktion') ?></th>
        </tr>
    </thead>
    <tbody>
        <? $nr = 0?>
        <? foreach($dozenten as $dozent) : ?>
        <? $fullname = $dozent->user->getFullName('full_rev');?>
        <tr>
            <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
            <td>
                <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$dozent['username'])) ?>">
                    <?= Avatar::getAvatar($dozent['user_id'], $dozent['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $dozent['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                            array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                </a>
            </td>
            <td style="text-align: right">
                <? if($user_id != $dozent['user_id']) : ?>
                <a href="<?= URLHelper::getLink('sms_send.php',
                            array('filter' => 'send_sms_to_all',
                            'rec_uname' => $dozent['username'],
                            'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id),
                            'subject' => $subject))
                        ?>
                ">
                    <?= Assets::img('icons/16/blue/mail.png',
                            tooltip2(sprintf(_('Nachricht an %s senden'), htmlReady($fullname)))) ?>
                </a>
                <? endif ?>
            <? if (!$dozent_is_locked && $is_dozent && $user_id != $dozent['user_id'] && count($dozenten) > 1) : ?>
                <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/dozent/%s',$dozent['user_id'])) ?>">
                    <?= Assets::img('icons/16/blue/door-leave.png',
                        tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                </a>
            <? else : ?>
                <?= Assets::img('blank.gif', array('style' => 'padding-right: 10px'))?>
            <? endif ?>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>