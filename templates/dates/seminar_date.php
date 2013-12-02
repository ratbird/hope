<tr>
    <th width="1">
        <a href="<?= $link ?>">
            <? if ($termin_item['open']): ?>
                <?= Assets::img('icons/16/blue/arr_1down.png'); ?>
            <? else: ?>
                <?= Assets::img('icons/16/blue/arr_1right.png'); ?>
            <? endif; ?>
        </a>
    </th>
    <th width="1">
        <?= $icon ?>
    </th>
    <th>
        <a href="<?= $link ?>">
            <?= $titel ?>
        </a>
    </th>
    <th>

    </th>
</tr>

<tr id="termin_item_<?= $termin_item['termin_id'] ?>_content" <? if ((!$termin_item['open'])): ?> style="display:none;"<? endif; ?>>
    <td colspan="4" >
        <?= show_termin_item_content($termin_item, $new, $range_id, $show_admin) ?>
    </td>
</tr>