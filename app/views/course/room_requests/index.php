<?php
echo $flash['message'];
?>
<h3>
    <?=_("Vorhandene Raumanfragen")?>
</h3>
<? if (count($room_requests)) : ?>
    <table class="default">
        <tr>
            <th width="50%"><?= _('Art der Anfrage') ?></th>
            <th width="15%"><?= _('Anfragender') ?></th>
            <th width="25%"><?= _('Bearbeitungsstatus')?></th>
            <th style="text-align:center"><?= _('Aktionen') ?></th>
        </tr>
    <? foreach ($room_requests as $rr): ?>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
        <td>
        <?=htmlReady($rr->getTypeExplained(),1,1)?>
        </td>
        <td>
        <?=htmlReady($rr['user_id'] ? get_fullname($rr['user_id']) : '')?>
        </td>
        <td>
        <?=htmlReady($rr->getStatusExplained())?>
        </td>
        <td>
        <div style="width:100px;text-align:right;white-space: nowrap">
            <a class="load-in-new-row" href="<?= $controller->link_for('index/'.$course_id, array('request_id' => $rr->getId())) ?>">
                <?= Assets::img('icons/16/blue/info.png', array('title' => _('Weitere Informationen einblenden'))) ?>
            </a>
            <a href="<?= $controller->link_for('edit/'.$course_id, array('request_id' => $rr->getId())) ?>">
                <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Diese Anfrage bearbeiten'))) ?>
            </a>
            <? if (getGlobalPerms($GLOBALS['user']->id) == 'admin' || ($GLOBALS['perm']->have_perm('admin') && count(getMyRoomRequests(null, null, true, $rr->getId())))) : ?>
                <a href="<?= URLHelper::getLink('resources.php', array('view' => 'edit_request', 'single_request' => $rr->getId())) ?>">
                    <?= Assets::img('icons/16/blue/admin.png', array('title' => _('Diese Anfrage selbst aufl�sen'))) ?>
                </a>
            <? endif ?>
            <a href="<?= $controller->link_for('delete/'.$course_id, array('request_id' => $rr->getId())) ?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Diese Anfrage zur�ckziehen'))) ?>
            </a>
        </div>
        </td>
    </tr>
    <? endforeach ?>
    <? if ($request_id == $rr->getId()) : ?>
        <tr>
            <td colspan="4">
                <?= $this->render_partial('course/room_requests/_request.php', array('request' => $rr));?>
            </td>
        </tr>
    <? endif ?>
    </table>
<? else : ?>
    <?= MessageBox::info(_("Zu dieser Veranstaltung sind noch keine Raumanfragen vorhanden.")) ?>
<? endif ?>
<?
$infobox_content = array(
    array(
        'kategorie' => _('Raumanfragen und gew�nschte Raumeigenschaften'),
        'eintrag'   => array(
    array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier sehen Sie eine �bersicht der Raumanfragen zu dieser Veranstaltung.")
    ),
    array(
            'icon' => 'icons/16/black/add.png',
            'text' => '<a href="'.$controller->link_for('new/'.$course_id).'">'._('Neue Raumanfrage erstellen').'</a>'
        ))
    ),
);
if ($adminList) {
    $infobox_content[] = array(
        "kategorie" => _("Veranstaltungsliste:"),
        "eintrag"   =>
            array(
                array(
                      "icon" => "icons/16/black/link-intern.png",
                      "text" => $adminList->render()
                )
            )
    );
}
$infobox = array('picture' => 'infobox/board2.jpg', 'content' => $infobox_content);