<table class="index_box">
    <tr>
        <td class="table_header_bold" style="font-weight: bold;">
            <?= _('Veranstaltungen') ?>
        </td>
    </tr>

    <tr>
        <td class="index_box_cell" style="font-weight: bold;">
    <? foreach ($seminare as $semester => $seminar) :?>
            <?= htmlReady($semester) ?><br>
            <br>

        <? foreach ($seminar as $id => $inhalt) :?>
            <a href="<?= URLHelper::getLink('details.php', array('sem_id' => $id))?>">
                <?= htmlReady($inhalt) ?>
            </a><br>
        <?endforeach?>
    <?endforeach?>
        </td>
    </tr>
</table>