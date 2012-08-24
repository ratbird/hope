<table class="default">
    <tr>
        <th><?= _('Art der Anfrage') ?></th>
        <th style="text-align:center;"><?= _('Bearbeiten') ?></th>
    </tr>
    <? foreach ($options as $key => $one): ?>
    <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
        <td><?=htmlReady($one['name'])?></td>
        <td>
            <div style="width:100px;text-align:right;white-space: nowrap;word-spacing:1em;">
            <? if ($one['request']) : ?>
                <a class="load-in-new-row" href="<?= $controller->link_for('index_assi/-', array('request_id' => $key)) ?>">
                    <?= Assets::img('icons/16/blue/info.png', array('title' => _('Weitere Informationen einblenden'))) ?>
                </a>
            <? endif ?>
                <a onClick="STUDIP.RoomRequestDialog.initialize('<?=URLHelper::getUrl('dispatch.php/course/room_requests/edit_dialog/-', array('new_room_request_type' => $key))?>');return false;" href="#">
                    <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Diese Anfrage bearbeiten'))) ?>
                </a>
            <? if ($one['request']) : ?>
                <a onClick="jQuery('#assi_room_request_with_js').load('<?=UrlHelper::getUrl('dispatch.php/course/room_requests/index_assi/-', array('delete_room_request_type' => $key))?>');return false;" href="#">
                    <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Diese Anfrage entfernen'))) ?>
                </a>
            <? else : ?>
                <?= Assets::img('blank.gif', array('width' => '16'));?>
            <? endif ?>
            </div>
        </td>
    </tr>
    <? endforeach ?>
</table>
