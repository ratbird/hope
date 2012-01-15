<?php
# Lifter007: TODO - long lines
?>
<table class="default blank">
    <tr class="topic">
        <td><b><?= _('meine Smiley-Favoriten') ?></b></td>
    </tr>
    <tr>
        <td>
            <blockquote>
                <?= _('Klicken Sie auf ein Smiley um es zu Ihren Favoriten hinzuzufügen. '
                     .'Wenn Sie auf einen Favoriten klicken, wird er wieder entfernt.') ?>
                <br>
                <?= _('Sie können maximal 20 Smileys aussuchen.') ?>
            </blockquote>

            <table align="center">
                <tr>
                    <td align="left">
                    <? foreach ($favorites as $row): ?>
                        <table bgcolor="#94a6bc">
                            <tr align="center">
                                <td class="smiley_th"><?= _('Favoriten') ?></td>
                            <? foreach ($row['index'] as $index): ?>
                                <td class="smiley_th"><?= $index ?></td>
                            <? endforeach; ?>
                            </tr>
                            <tr align="center">
                                <td class="smiley_th"><?= _('Smiley') ?></td>
                            <? foreach ($row['data'] as $name => $smiley): ?>
                                <td class="blank">
                                    <a href="<?= URLHelper::getLink('?cmd=delfav&img=' . $smiley['id']) ?>">
                                        <img src="<?= $GLOBALS['DYNAMIC_CONTENT_URL'] ?>/smile/<?= $name ?>.gif"
                                            <?= tooltip(sprintf(_('%s  entfernen'), $name)) ?>
                                            width="<?= $smiley['width'] ?>" height="<?= $smiley['height'] ?>"
                                            border="0">
                                    </a>
                                </td>
                            <? endforeach; ?>
                            </tr>
                            <tr align="center">
                                <td class="smiley_th"><?= _('Schreibweise') ?></td>
                            <? foreach ($row['name'] as $name): ?>
                                <td class="blank">&nbsp;:<?= htmlReady($name) ?>:&nbsp;</td>
                            <? endforeach; ?>
                            </tr>
                        </table>
                    <? endforeach; ?>
                    </td>
                </tr>
            </table>

            <br>
        </td>
    </tr>
</table>
