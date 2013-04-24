<table class="default collapsable zebra-hover">
    <colgroup>
        <col width="<?=($rechte) ? '6%' : '3%'?>">
        <col width="<?=($rechte) ? '79%' : '82%'?>">
        <col width="15%">
    </colgroup>
    <thead>
        <tr>
            <th colspan="2" class="table_header_bold" >
                <?= $this->status_groups['dozent'] ?>
                <?= tooltipIcon(sprintf(_('%s haben Administrationrechte'), $status_groups['dozent'])) ?>
            </th>
            <th class="table_header_bold" style="text-align:right">
            <? if ($rechte) : ?>
                <?=$controller->getEmailLinkByStatus('dozent')?>
                <a href="<?= URLHelper::getLink('sms_send.php', array('filter' => 'send_sms_to_all', 'who' =>
                        'dozent', 'sms_source_page' => 'dispatch.php/course/members',
                        'course_id' => $course_id, 'subject' => $subject)) ?>">

                    <?= Assets::img('icons/16/blue/inbox.png',
                            tooltip2(sprintf(_('Nachricht an alle %s verschicken'), $status_groups['dozent']))) ?>
                </a>
                <? if ($is_dozent && !$dozent_is_locked) : ?>
                    <a href="<?=$controller->url_for('course/members/add_dozent/')?>">
                        <?= Assets::img('icons/16/blue/add/community.png',
                                tooltip2(sprintf(_('Neuen %s hinzufügen'), $status_groups['dozent'])))?>
                    </a>
                <? endif ?>
            <? endif ?>
            </th>
        </tr>
        <tr class="sortable">
            <th colspan="2" <?= ($sort_by == 'nachname' && $sort_status == 'dozent') ? sprintf('class="sort%s"', $order) : '' ?>>
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
        <? $fullname = $dozent->user->getFullName();?>
        <tr>
            <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
            <td>
                <a href="<?= $controller->url_for(sprintf('profile?username=%s',$dozent['username'])) ?>">
                    <?= Avatar::getAvatar($dozent['user_id'], $dozent['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $dozent['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                            array('style' => 'position: relative; top: -5px; left: -15px; margin: 0px; right: 0px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                </a>
            </td>
            <td style="text-align: right">
                <a href="<?= URLHelper::getLink('sms_send.php',
                            array('filter' => 'send_sms_to_all',
                            'rec_uname' => $dozent['username'],
                            'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id),
                            'subject' => $subject))
                        ?>
                ">
                    <?= Assets::img('icons/16/blue/mail.png',
                            tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($fullname)))) ?>
                </a>

            <? if ($rechte && $is_dozent && $user_id != $dozent['user_id'] && count($dozenten) > 1) : ?>
                <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'),
                        htmlReady($fullname)) ?>');"
                    href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/dozent/%s/%s',
                            $page, $dozent['user_id'])) ?>">
                    <?= Assets::img('icons/16/blue/remove/person.png',
                            tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                </a>
            <? endif ?>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>