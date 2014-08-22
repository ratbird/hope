<table class="default">
    <caption>
        <?= sprintf(_('Veranstaltungen mit regelm��igen Zeiten am %s, %s Uhr'), htmlReady($day), htmlReady($start) .' - '. htmlReady($end)) ?>
    </caption>
    <colgroup>
        <col width="15%">
        <col width="85%">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Nummer') ?></th>
            <th><?= _('Name') ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($seminars as $seminar) : ?>
            <tr class="<?= TextHelper::cycle('table_row_odd', 'table_row_even')?>">
                <td><?= htmlReady($seminar->getNumber()) ?></td>
                <td>
                    <a href="<?= URLHelper::getLink('dispatch.php/course/details/', array('sem_id' => $seminar->getId())) ?>">
                        <?= Assets::img('icons/16/blue/link-intern.png') ?>
                        <?= htmlReady($seminar->getName()) ?>
                    </a>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>
<br>
