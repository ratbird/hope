<? if ($_SESSION['sms_msg']) {
    parse_msg($_SESSION['sms_msg']);
    unset($_SESSION['sms_msg']);
} ?>

<div class="online-list <? if (!$showOnlyBuddies) echo 'online-list-double'; ?>">
    <table class="default zebra">
        <colgroup>
            <col width="<?= reset(Avatar::getDimension(Avatar::SMALL)) ?>px">
            <col>
            <col width="100px">
            <col width="<?= 50 + 25 * (int)class_exists('Blubber') ?>px">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="4">
                    <?= _('Buddies') ?>
                </th>
            </tr>
        </thead>
    <? if (count($users['buddies']) > 0): ?>
        <tbody>
            <tr>
                <th colspan="2"><?= _('Name') ?></th>
                <th colspan="2"><?= _('Letztes Lebenszeichen') ?></th>
            </tr>
        <? $last_group = false;
           foreach ($users['buddies'] as $buddy):
        ?>
          <? if ($showGroups && $last_group !== $buddy['group']): ?>
            <tr>
                <th class="blue_gradient" colspan="4">
                    <a href="<?= URLHelper::getLink('contact.php?view=gruppe',
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
    <? endif; ?>
        <tfoot>
        <? if ($buddy_count === 0): ?>
            <tr>
                <td colspan="4">
                    <?= _('Sie haben keine Buddies ausgewählt.') ?>
                </td>
            </tr>
        <? elseif (count($users['buddies']) === 0): ?>
            <tr>
                <td colspan="4">
                    <?= _('Es sind keine Ihrer Buddies online.') ?>
                </td>
            </tr>
        <? endif; ?>
            <tr>
                <td colspan="4">
                <? printf(_('Zum Adressbuch (%u Einträge) klicken Sie %shier%s.'),
                          GetSizeofBook(),
                          '<a href="' . URLHelper::getLink('contact.php') . '">', '</a>') ?>
                </td>
            </tr>
        </tfoot>
    </table>

<? if (!$showOnlyBuddies): ?>
    <table class="default zebra">
        <colgroup>
            <col width="<?= reset(Avatar::getDimension(Avatar::SMALL)) ?>px">
            <col>
            <col width="100px">
            <col width="<?= 50 + 25 * (int)class_exists('Blubber') ?>px">
        </colgroup>
        <thead>
            <tr>
                <th colspan="4" class="table_header_bold">
                    <?= _('Andere NutzerInnen') ?>
                <? if ($users['others'] > 0): ?>
                    <small>
                        (<?= sprintf(_('+ %u unsichtbare NutzerInnen'), $users['others']) ?>)
                    </small>
                <? endif; ?>
                </th>
            </tr>
        </thead>
    <? if (count($users['users']) > 0): ?>
        <tbody>
            <tr>
                <th colspan="2"><?= _('Name') ?></th>
                <th colspan="2"><?= _('Letztes Lebenszeichen') ?></th>
            </tr>
        <? foreach (array_slice($users['users'], ($page - 1) * $limit, $limit) as $user): ?>
            <?= $this->render_partial('online/user-row', compact('user')) ?>
        <? endforeach; ?>
        </tbody>
    <? elseif ($users['others'] > 0): ?>
        <tfoot>
            <tr>
                <td colspan="4">
                    <?= _('Keine sichtbaren Nutzer online.') ?>
                </td>
            </tr>
        </tfoot>
    <? else: ?>
        <tfoot>
            <tr>
                <td colspan="4">
                    <?= _('Kein anderer Nutzer ist online.') ?>
                </td>
            </tr>
        </tfoot>
    <? endif; ?>
    <? if (count($users['users']) > $limit): ?>
        <tfoot>
            <tr>
                <td class="content_seperator" colspan="4" style="text-align: right;">
                    <?= $GLOBALS['template_factory']->render(
                            'shared/pagechooser',
                            array('perPage' => $limit,
                                  'num_postings' => count($users['users']),
                                  'page' => $page,
                                  'pagelink' => 'dispatch.php/online?page=%s')
                    ) ?>
                </td>
            </tr>
        </tfoot>
    <? endif; ?>
    </table>
<? endif; ?>
</div>
