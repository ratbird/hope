<? if ($_SESSION['sms_msg']) {
    parse_msg($_SESSION['sms_msg']);
    unset($_SESSION['sms_msg']);
} ?>

<div class="online-list <? if (!$showOnlyBuddies) echo 'online-list-double'; ?>">
    <table class="default">
        <caption>
            <?= _('Buddies') ?>
        </caption>
        <colgroup>
            <col width="<?= reset(Avatar::getDimension(Avatar::SMALL)) ?>px">
            <col>
            <col>
            <col width="1%">
        </colgroup>
        <? if (count($users['buddies']) > 0): ?>
        <thead>
            <tr>
                <th colspan="2"><?= _('Name') ?></th>
                <th><?= _('Letztes Lebenszeichen') ?></th>
                <th class="actions"><?= _('Aktionen') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $last_group = false;
           foreach ($users['buddies'] as $buddy):
        ?>
            <? if ($showGroups && $last_group !== $buddy['group']): ?>
            <tr>
                <th colspan="4">
                    <a href="<?= URLHelper::getLink('contact.php?view=gruppen',
                                                    array('filter' => $buddy['group_id'])) ?>"
                       class="link-intern" style="color: #000;">
                        <?= htmlReady($buddy['group']) ?>
                    </a>
                </th>
            </tr>
            <? $last_group = $buddy['group'];
                endif;
            ?>
            <?= $this->render_partial('online/user-row', array('user' => $buddy)) ?>
        <? endforeach; ?>
        </tbody>
        <? else: ?>
            <? if ($buddy_count === 0): ?>
                <tbody>
                    <tr>
                        <td colspan="4">
                            <?= _('Sie haben keine Buddies ausgew�hlt.') ?>
                        </td>
                    </tr>
                </tbody>
            <? elseif (count($users['buddies']) === 0): ?>
                <tbody>
                    <tr>
                        <td colspan="4">
                            <?= _('Es sind keine Ihrer Buddies online.') ?>
                        </td>
                    </tr>
                </tbody>
            <? endif; ?>
        <? endif; ?>
        <tfoot>
            <tr>
                <td colspan="4">
                <? printf(_('Zum Adressbuch (%u Eintr�ge) klicken Sie %shier%s.'),
                          GetSizeofBook(),
                          '<a href="' . URLHelper::getLink('contact.php') . '">', '</a>') ?>
                </td>
            </tr>
        </tfoot>
    </table>

<? if (!$showOnlyBuddies): ?>
    <table class="default">
        <caption>
            <?= _('Andere NutzerInnen') ?>
            <? if ($users['others'] > 0): ?>
                <small>
                    (<?= sprintf(_('+ %u unsichtbare NutzerInnen'), $users['others']) ?>)
                </small>
            <? endif; ?>
        </caption>
        <colgroup>
            <col width="<?= reset(Avatar::getDimension(Avatar::SMALL)) ?>px">
            <col>
            <col>
            <col width="1%">
        </colgroup>
    <? if (count($users['users']) > 0): ?>
        <thead>
            <tr>
                <th colspan="2"><?= _('Name') ?></th>
                <th><?= _('Letztes Lebenszeichen') ?></th>
                <th class="actions"><?= _('Aktionen') ?></th>
            </tr>
        </thead>
        <tbody>
        <? foreach (array_slice($users['users'], ($page - 1) * $limit, $limit) as $user): ?>
            <?= $this->render_partial('online/user-row', compact('user')) ?>
        <? endforeach; ?>
        </tbody>
    <? elseif ($users['others'] > 0): ?>
        <tbody>
            <tr>
                <td colspan="4">
                    <?= _('Keine sichtbaren Nutzer online.') ?>
                </td>
            </tr>
        </tbody>
    <? else: ?>
        <tbody>
            <tr>
                <td colspan="4">
                    <?= _('Kein anderer Nutzer ist online.') ?>
                </td>
            </tr>
        </tbody>
    <? endif; ?>
        <tfoot>
            <tr>
                <td colspan="4">
                    <? if (count($users['users']) > $limit): ?>
                        <span class="actions">
                            <?= $GLOBALS['template_factory']->render(
                                    'shared/pagechooser',
                                    array('perPage' => $limit,
                                          'num_postings' => count($users['users']),
                                          'page' => $page,
                                          'pagelink' => 'dispatch.php/online?page=%s')
                            ) ?>
                        </span>
                    <? else: ?>
                        &nbsp;
                    <? endif; ?>
                </td>
            </tr>
        </tfoot>
    </table>
<? endif; ?>
</div>
