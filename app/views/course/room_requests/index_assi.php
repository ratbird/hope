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
                    <?= Icon::create('info', 'clickable', ['title' => _('Weitere Informationen einblenden')])->asImg() ?>
                </a>
            <? endif ?>
                <a onClick="STUDIP.RoomRequestDialog.initialize('<?=URLHelper::getUrl('dispatch.php/course/room_requests/edit_dialog/-', array('new_room_request_type' => $key))?>');return false;" href="#">
                    <?= Icon::create('edit', 'clickable', ['title' => _('Diese Anfrage bearbeiten')])->asImg() ?>
                </a>
            <? if ($one['request']) : ?>
                <a onClick="jQuery('#assi_room_request_with_js').load('<?=URLHelper::getUrl('dispatch.php/course/room_requests/index_assi/-', array('delete_room_request_type' => $key))?>');return false;" href="#">
                    <?= Icon::create('trash', 'clickable', ['title' => _('Diese Anfrage entfernen')])->asImg() ?>
                </a>
            <? else : ?>
                <?= Assets::img('blank.gif', array('width' => '16'));?>
            <? endif ?>
            </div>
        </td>
    </tr>
    <? endforeach ?>
</table>
