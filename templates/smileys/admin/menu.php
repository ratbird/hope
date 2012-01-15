<table valign="top" width="80%">
    <tr>
        <td>

            <table align="center" cellpadding="2" cellspacing="0">
                <thead>
                    <tr>
                        <th colspan="2"><?= _('Auswahl') ?></th>
                    </tr>
                    <tr>
                        <th><?= _('1. Zeichen') ?></th>
                        <th><?= _('Anzahl') ?></th>
                    </tr>
                </thead>
                <tbody>
                <? foreach ($characters as $c): ?>
                    <tr class="<?= TextHelper::cycle('steelgraulight', 'steel1') ?>">
                        <td align="center" class="<?= $fc == $c['char'] ? 'smiley_redborder' : 'blank' ?>">
                            <a href="<?= URLHelper::getLink('?fc=' . $c['char']) ?>">
                                <?= $c['char'] ?>
                            </a>
                        </td>
                        <td align="right"><?= $c['count'] ?></td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>

        </td>
        <td valign="top">

            <?
                $groups = array(
                  'all'   => _('alle'),
                  'top20' => _('Top 20'),
                  'used'  => _('benutzte'),
                  'none'  => _('nicht benutzte'),
                  'short' => _('nur mit Kürzel')
                );
            ?>
            <table align="center" cellpadding="2" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th><?= _('Auswahl') ?></th>
                    </tr>
                </thead>
                <tbody>
                <? foreach ($groups as $key => $label): ?>
                    <tr class="<?= TextHelper::cycle('steelgraulight', 'steel1') ?>">
                        <td align="center" class="<?= $fc == $key ? 'smiley_redborder' : 'blank' ?>">
                            <a href="<?= URLHelper::getLink('?fc=' . $key) ?>">
                                <?= htmlReady($label) ?>
                            </a>
                        </td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>

            <br>

            <table align="center" cellpadding="2" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th><?= _('Aktionen') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="steelgraulight">
                        <td align="center">
                            <a href="<?= URLHelper::getLink('?cmd=updatetable') ?>">
                                <?= _('Tabelle aktualisieren') ?>
                            </a>
                        </td>
                    </tr>
                    <tr class="steel1">
                        <td align="center">
                            <a href="<?= URLHelper::getLink('?cmd=countsmiley') ?>">
                                <?= _('Smileys zählen') ?>
                            </a>
                        </td>
                    </tr>
                    <tr class="steelgraulight">
                        <td align="center">
                            <a href="<?= URLHelper::getLink('show_smiley.php', array('fc' => null)) ?>"
                               target="_smileys">
                                <?= _('Smiley-Übersicht öffnen') ?>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <br>

            <table align="center" cellpadding="2" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th colspan="2"><?= _('Smileys') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="steelgraulight">
                        <td align="left"><?= _('vorhanden:') ?></td>
                        <td align="right"><?= $info['count_all'] ?></td>
                    </tr>
                    <tr class="steel1">
                        <td align="left"><?= _('davon benutzt:') ?></td>
                        <td align="right"><?= $info['count_used'] ?></td>
                    </tr>
                    <tr class="steelgraulight">
                        <td align="left"><?= _('insgesamt benutzt:') ?></td>
                        <td align="right"><?= $info['sum'] ?></td>
                    </tr>
                    <tr class="steel1">
                        <td align="left" colspan="2">
                            <?= _('letzte Änderung:') ?>
                        </td>
                    </tr>
                    <tr class="steel1">
                        <td align="right" colspan="2">
                            <?= date('d.m.Y H:i:s', $info['last_change']) ?>
                        </td>
                    </tr>
                </tbody>
            </table>

        </td>
    </tr>
</table>
