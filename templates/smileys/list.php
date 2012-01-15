<?php
# Lifter007: TODO - long lines
?>
<table align="center" width="100%">
    <tr>
<? if (!$count): ?>
        <td align="center" colspan="3">
            <h4><?= _('Keine Smileys vorhanden.') ?></h4>
        </td>
<? else: ?>
    <? for ($i = 0, $max = ceil($count / 3); $i < 3; $i++): ?>
        <td valign="top" align="center">
            <table cellpadding="2" cellspacing="2" class="blank">
                <tr>
                    <td class="smiley_th"><?= _('Bild') ?></td>
                    <td class="smiley_th"><?= _('Schreibweise') ?></td>
                    <td class="smiley_th"><?= _('Kürzel') ?></td>
                <? if ($SMILEY_COUNTER): ?>
                    <td class="smiley_th">&Sigma;</td>
                <? endif; ?>
                </tr>

            <? foreach (array_slice($smileys, $i * $max, $max) as $item): ?>
                <? $id = $item['smiley_id']; ?>
                <tr align="center">
                    <td>
                    <? if ($user_id != 'nobody'): ?>
                        <a href="<?= URLHelper::getLink('?cmd=addfav&img=' . $id . '#anker' . $id) ?>"
                           name="anker<?= $item['smiley_id'] ?>">
                            <img src="<?= $GLOBALS['DYNAMIC_CONTENT_URL'] ?>/smile/<?= urlencode($item['smiley_name']) ?>.gif"
                                 <?= tooltip(sprintf(_('%s zu meinen Favoriten hinzufügen'), $item['smiley_name'])) ?>
                                 width="<?= $item['smiley_width'] ?>" height="<?= $item['smiley_height'] ?>">
                        </a>
                    <? else: ?>
                        <img src="<?= $GLOBALS['DYNAMIC_CONTENT_URL'] ?>/smile/<?= urlencode($item['smiley_name']) ?>.gif"
                             <?= tooltip($item['smiley_name']) ?>
                             width="<?= $item['smiley_width'] ?>" height="<?= $item['smiley_height'] ?>">
                    <? endif; ?>
                    </td>
                    <td><?= sprintf(':%s:', $item['smiley_name']) ?></td>
                    <td><?= htmlReady($item['short_name']) ?></td>
                <? if ($SMILEY_COUNTER): ?>
                    <td class="smiley_th">
                        <?= $row['smiley_counter'] + $row['short_counter'] ?>
                    </td>
                <? endif; ?>
                </tr>
            <? endforeach; ?>
            </table>

        </td>
    <? endfor; ?>
<? endif; ?>
    </tr>
</table>
