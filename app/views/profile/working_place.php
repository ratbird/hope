<br><b><?= _('Wo ich arbeite:') ?></b><br>

<ul>
    <? foreach ($institutes as $inst_result): ?>
        <li>
            <a href="<?= URLHelper::getLink('dispatch.php/institute/overview', array('auswahl' => $inst_result['Institut_id'])) ?>">
                <?= htmlReady($inst_result['Name']) ?>
            </a>
            <? if ($inst_result['raum'] != ''): ?>
                <br>
                <b><?= _('Raum:') ?></b>
                <?= htmlReady($inst_result['raum']) ?>
            <? endif; ?>

            <? if ($inst_result['sprechzeiten'] != ''): ?>
                <br>
                <b><?= _('Sprechzeit:') ?></b>
                <?= htmlReady($inst_result['sprechzeiten']) ?>
            <? endif; ?>

            <? if ($inst_result['Telefon'] != ''): ?>
                <br>
                <b><?= _('Telefon:') ?></b>
                <?= htmlReady($inst_result['Telefon']) ?>
            <? endif; ?>

            <? if ($inst_result['Fax'] != ''): ?>
                <br>
                <b><?= _('Fax:') ?></b>
                <?= htmlReady($inst_result['Fax']) ?>
            <? endif; ?>

            <? if (!empty($inst_result['datafield'])): ?>
                <table cellspacing="0" cellpadding="0" border="0">
                    <? foreach ($inst_result['datafield'] as $datafield): ?>
                        <tr>
                            <td style="padding-right: 5px"><?= htmlReady($datafield['name']) ?>:</td>
                            <td>
                                <?= $datafield['value'] ?>
                                <? if ($datafield['show_start']) echo '*'; ?>
                            </td>
                        </tr>
                    <? endforeach; ?>
                </table>
            <? endif; ?>

            <? foreach ($inst_result['role'] as $role): ?>
                <div>
                    <?= Assets::img('forumgrau2.png') ?>
                    <b><?=$role ?></b></div>
            <? endforeach; ?>
        </li>
    <? endforeach; ?>
</ul>
