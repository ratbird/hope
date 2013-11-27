<table class="default">
    <caption>
        <?= _('Veranstaltungen') ?>
    </caption>
    <tbody>
        <? foreach ($seminare as $semester => $seminar) : ?>
            <tr>
                <th>
                    <?= htmlReady($semester) ?>
                </th>
            </tr>
            <? foreach ($seminar as $id => $inhalt) : ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('details.php', array('sem_id' => $id)) ?>"><?= htmlReady($inhalt) ?></a>               
                    </td>
                </tr>
            <? endforeach ?>
        <? endforeach ?>
    </tbody>
</table>