<?
# Lifter010: TODO
?>
<? SkipLinks::addIndex(_("Personen, deren Standardvertretung ich bin"), 'my_deputy_bosses') ?>
<table class="default" id="my_deputy_bosses">
    <caption>
        <?= _("Personen, deren Standardvertretung ich bin") ?>
    </caption>
    <colgroup>
        <col width="30px">
        <col>
    </colgroup>
    <thead>
    <tr>
        <th></th>
        <th><?= _("Name") ?></th>
        <th><?= _('Aktion') ?></th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($my_bosses as $boss) : ?>
        <tr>
            <td>
                <?= Avatar::getAvatar($boss['user_id'])->getImageTag(Avatar::SMALL, array('title' => htmlReady($boss['fullname']))) ?>
            </td>
            <td>
                <?= htmlReady($boss['fullname'])?>
            </td>
            <td>
                <? if ($boss['edit_about'] && $deputies_edit_about_enabled) : ?>
                    <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $boss['username'])) ?>">
                        <?= Assets::img('icons/blue/person.svg', tooltip2(_('Personenangaben bearbeiten'))) ?>
                    </a>
                <? endif ?>
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                    array('filter' => 'send_sms_to_all',
                          'rec_uname' => $boss['username']))?>">
                    <?= Assets::img('icons/blue/mail.svg', tooltip2(sprintf(_('Nachricht an %s senden'),
                        htmlReady($boss['fullname'])))) ?>
                </a>
                <a href="<?= URLHelper::getLink('dispatch.php/my_courses/delete_boss/'.
                        $boss['user_id'])?>" data-confirm="<?=sprintf(
                        _('Wollen Sie sich wirklich als Standardvertretung von %s austragen?'),
                        $boss['fullname']) ?>">
                    <?= Assets::img('icons/blue/trash.svg',
                        tooltip2(sprintf(_('Mich als Standardvertretung von %s austragen'),
                        htmlReady($boss['fullname'])))) ?>
                </a>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>
<br/>
