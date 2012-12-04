<table class="index_box">
    <tr>
        <td class="table_header_bold" style="font-weight: bold;">
            <?= _('Veranstaltungen') ?>
        </td>
    </tr>

    <tr>
        <td class="index_box_cell">
            <? foreach($seminare AS $semester => $seminar) :?>
            <? if(!empty($seminar)):?><b><?=$semester?></b>
                <br><br>
                
                <?foreach($seminar AS $id => $inhalt) :?>
                    <b><a href="<?=URLHelper::getLink("details.php?", array('sem_id' =>$id))?>"><?=htmlReady($inhalt)?></a></b><br>
                <?endforeach?>
            <?endif?>
            <?endforeach?>
        </td>
    </tr>
</table>