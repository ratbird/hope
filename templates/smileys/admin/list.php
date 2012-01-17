<?
    use Studip\Button, Studip\LinkButton;

    $src = sprintf('%s/smile/%%s.gif', $GLOBALS['DYNAMIC_CONTENT_URL']);
?>
<form action="<?= URLHelper::getLink('?cmd=update') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

    <table align="center" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th><?= _('Nr.') ?></th>
                <th><?= _('Smiley') ?></th>
                <th><?= _('Smileyname') ?></th>
                <th>&Sigma;</th>
                <th><?= _('Kürzel') ?></th>
                <th>&Sigma;</th>
                <th><?= _('Löschen') ?></th>
            </tr>
        </thead>
    <? if (empty($smileys)): ?>
        <tbody>
            <tr>
                <td align="center" class="blank" colspan="7">
                    <?= _('Keine Smileys vorhanden.') ?>
                </td>
            </tr>
        </tbody>
    <? else: ?>
        <tbody>
        <? $count = 1; ?>
        <? foreach ($smileys as $smiley): ?>
            <tr align="center" class="<?= TextHelper::cycle('steelgraulight', 'steel1') ?>">
                <td align="right"><?= $count++ ?></td>
                <td>
                    <img src="<?= sprintf($src, urlencode($smiley['smiley_name'])) ?>"
                         <?= tooltip($smiley['smiley_name']) ?>
                         width="<?= $smiley['smiley_width'] ?>"
                         height="<?= $smiley['smiley_height'] ?>">
                </td>
                <td>
                    <input name="rename_<?= urlencode($smiley['smiley_name']) ?>"
                           value="<?= $smiley['smiley_name'] ?>" size="20">
                </td>
                <td><?= $smiley['smiley_counter'] ?></td>
                <td><?= htmlReady($smiley['short_name']) ?></td>
                <td><?= $smiley['short_name'] ? $smiley['short_counter'] : '-' ?></td>
                <td>
                    <a href="<?= URLHelper::getLink('?cmd=delete&img=' . $smiley['smiley_id']) ?>"
                       title="<?= sprintf(_('Smiley %s löschen'), $smiley['smiley_name']) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-top')) ?>
                    </a>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" align="center">
                    <?= Button::createAccept('absenden') ?>
                </td>
            </tr>
        </tfoot>
    <? endif; ?>
    </table>
</form>
