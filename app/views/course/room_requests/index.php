<?php
echo $flash['message'];
?>

<? if (count($room_requests)) : ?>
    <table class="default">
        <caption>
            <?= _("Vorhandene Raumanfragen") ?>
        </caption>
        <colgroup>
            <col width="50%">
            <col width="15%">
            <col width="25">
            <col>
        </colgroup>
        <tr>
            <th width="50%"><?= _('Art der Anfrage') ?></th>
            <th width="15%"><?= _('Anfragender') ?></th>
            <th width="25%"><?= _('Bearbeitungsstatus') ?></th>
            <th></th>
        </tr>
        <? foreach ($room_requests as $rr): ?>
            <tr>
                <td>
                    <?= htmlReady($rr->getTypeExplained(), 1, 1) ?>
                </td>
                <td>
                    <?= htmlReady($rr['user_id'] ? get_fullname($rr['user_id']) : '') ?>
                </td>
                <td>
                    <?= htmlReady($rr->getStatusExplained()) ?>
                </td>
                <td class="actions">
                    <a class="load-in-new-row"
                       href="<?= $controller->link_for('info/' . $rr->getId()) ?>">
                        <?= Assets::img('icons/16/blue/info.png', array('title' => _('Weitere Informationen einblenden'))) ?>
                    </a>
                    <? $params = array('request_id' => $rr->getId()) ?>
                    <? if (Request::isXhr()) : ?>
                        <? $params['asDialog'] = true; ?>
                    <? endif ?>
                    <a data-dialog="size=big"
                       href="<?= $controller->link_for('edit/' . $course_id, $params) ?>">
                        <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Diese Anfrage bearbeiten'))) ?>
                    </a>
                    <? if (getGlobalPerms($GLOBALS['user']->id) == 'admin' || ($GLOBALS['perm']->have_perm('admin') && count(getMyRoomRequests(null, null, true, $rr->getId())))) : ?>
                        <a href="<?= URLHelper::getLink('resources.php', array('view'           => 'edit_request',
                                                                               'single_request' => $rr->getId()
                        )) ?>">
                            <?= Assets::img('icons/16/blue/admin.png', array('title' => _('Diese Anfrage selbst auflösen'))) ?>
                        </a>
                    <? endif ?>
                    <a href="<?= $controller->link_for('delete/' . $course_id, array('request_id' => $rr->getId())) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Diese Anfrage zurückziehen'))) ?>
                    </a>
                </td>
            </tr>
        <? endforeach ?>
        <? if ($request_id == $rr->getId()) : ?>
            <tr>
                <td colspan="4">
                    <?= $this->render_partial('course/room_requests/_request.php', array('request' => $rr)); ?>
                </td>
            </tr>
        <? endif ?>
    </table>
<? else : ?>
    <?= MessageBox::info(_('Zu dieser Veranstaltung sind noch keine Raumanfragen vorhanden.')) ?>
<? endif ?>

<? if (Request::isXhr()) : ?>
    <div data-dialog-button>
        <?= \Studip\LinkButton::createEdit(_('Neue Raumanfrage erstellen'), $controller->url_for('course/room_requests/new/' . $course_id, $url_params), array('data-dialog' => 'size=big')) ?>
    </div>
<? endif ?>
<?
