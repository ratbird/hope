<?
# Lifter010: TODO
?>
        <td align="center">
            <? if (in_array($status, $auto_sem['status'])) : ?>
            <a href="<?= $controller->url_for('admin/autoinsert/edit/'.$auto_sem['seminar_id']) ?>/<?= $status ?>/1">
                <?= Assets::img('icons/16/blue/checkbox-checked.png') ?>
            </a>
            <? else : ?>
            <a href="<?= $controller->url_for('admin/autoinsert/edit/'.$auto_sem['seminar_id']) ?>/<?= $status ?>">
                <?= Assets::img('icons/16/blue/checkbox-unchecked.png') ?>
            </a>
            <? endif ?>
        </td>