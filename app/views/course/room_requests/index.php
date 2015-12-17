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
                        <?= Icon::create('info', 'clickable', ['title' => _('Weitere Informationen einblenden')])->asImg(16) ?>
                    </a>
                    <? $params = array('request_id' => $rr->getId()) ?>
                    <? if (Request::isXhr()) : ?>
                        <? $params['asDialog'] = true; ?>
                    <? endif ?>
                    <a data-dialog="size=big"
                       href="<?= $controller->link_for('edit/' . $course_id, $params) ?>">
                        <?= Icon::create('edit', 'clickable', ['title' => _('Diese Anfrage bearbeiten')])->asImg(16) ?>
                    </a>
                    <? if (getGlobalPerms($GLOBALS['user']->id) == 'admin' || ($GLOBALS['perm']->have_perm('admin') && count(getMyRoomRequests(null, null, true, $rr->getId())))) : ?>
                        <a href="<?= URLHelper::getLink('resources.php', array('view'           => 'edit_request',
                                                                               'single_request' => $rr->getId()
                        )) ?>">
                            <?= Icon::create('admin', 'clickable', ['title' => _('Diese Anfrage selbst auflösen')])->asImg(16) ?>
                        </a>
                    <? endif ?>
                    <a href="<?= $controller->link_for('delete/' . $course_id, array('request_id' => $rr->getId())) ?>">
                        <?= Icon::create('trash', 'clickable', ['title' => _('Diese Anfrage zurückziehen')])->asImg(16) ?>
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
