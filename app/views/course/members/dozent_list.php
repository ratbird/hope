
<table class="default collapsable ">
    <caption>
    <? if ($is_tutor) : ?>
        <span class="actions">
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array(
                        'filter' => 'send_sms_to_all',
                        'emailrequest' => 1,
                        'who' => 'dozent',
                        'course_id' => $course_id,
                        'default_subject' => $subject)) ?>" data-dialog>
                    <?= Icon::create('inbox', 'clickable', ['title' => sprintf(_('Nachricht mit Mailweiterleitung an alle %s versenden'),$status_groups['dozent'])])->asImg(16) ?>
                </a>
        </span>
    <? endif ?>
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
        <? $fullname = $dozent['fullname'];?>
        <tr>
            <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
            <td>
                <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$dozent['username'])) ?>">
                    <?= Avatar::getAvatar($dozent['user_id'], $dozent['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $dozent['mkdate'] >= $last_visitdate ? Assets::img('red_star',
                            array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                </a>
                <? if ($is_tutor && $dozent['comment'] != '') : ?>
                    <?= tooltipIcon(sprintf('<strong>%s</strong><br>%s', _('Bemerkung'), htmlReady($dozent['comment'])), false, true) ?>
                <? endif ?>
            </td>
            <td style="text-align: right">
                <? if ($is_tutor) : ?>
                    <a data-dialog title='<?= _('Bemerkung hinzufügen') ?>' href="<?=$controller->url_for('course/members/add_comment', $dozent['user_id']) ?>">
                        <?= Icon::create('comment', 'clickable')->asImg() ?>
                    </a>
                <? endif ?>
                <? if($user_id != $dozent['user_id']) : ?>
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                        array('filter' => 'send_sms_to_all',
                            'emailrequest' => 1,
                            'rec_uname' => $dozent['username'],
                            'default_subject' => $subject))
                        ?>
                " data-dialog>
                    <?= Icon::create('mail', 'clickable', ['title' => sprintf(_('Nachricht mit Mailweiterleitung an %s senden'),htmlReady($fullname))])->asImg(16) ?>
                </a>
                <? endif ?>
            <? if (!$dozent_is_locked && $is_dozent && $user_id != $dozent['user_id'] && count($dozenten) > 1) : ?>
                <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/dozent/%s',$dozent['user_id'])) ?>">
                    <?= Icon::create('door-leave', 'clickable', ['title' => sprintf(_('%s austragen'),htmlReady($fullname))])->asImg(16) ?>
                </a>
            <? else : ?>
                <?= Assets::img('blank.gif', array('style' => 'padding-right: 10px'))?>
            <? endif ?>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>
