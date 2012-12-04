<b><?=_("Wo ich arbeite:")?></b><br />

<? foreach($institutes as $inst_result) :?>
    <a href="<?=URLHelper::getLink('institut_main.php', array('auswahl' => $inst_result['Institut_id']))?>">
        <?= htmlReady($inst_result["Name"])?>
    </a>
    <? if($inst_result['raum'] != "") :?>
        <b><br><?=_("Raum:")?></b> <?=htmlReady($inst_result["raum"])?>
    <? endif?>

    <? if($inst_result['sprechzeiten'] != "") :?>
        <b><br><?=_("Sprechzeit:")?></b> <?=htmlReady($inst_result["sprechzeiten"])?>
    <? endif?>

    <? if($inst_result['Telefon'] != "") :?>
        <b><br><?=_("Telefon:")?></b> <?=htmlReady($inst_result["Telefon"])?>
    <? endif?>

    <? if($inst_result['Fax'] != "") :?>
        <b><br><?=_("Fax:")?></b> <?=htmlReady($inst_result["Fax"])?>
    <? endif?>

    <? if(!empty($inst_result['datafield'])) :?>
        <table cellspacing="0" cellpadding="0" border="0">
        <? foreach($inst_result['datafield'] as $datafield) :?>
            <tr>
                <td></td>
                <td><?= htmlReady($datafield['name'])?>:</td>
                <td><?= $datafield['value']?> <?=($datafield['show_start'] == true)? '*' : ''?></td>
            </tr>
        <? endforeach?>
        </table>
    <? endif?>

    <? if(isset($inst_result['role']) && !empty($inst_result['role'])) :?>
        <table cellpadding="0" cellspacing="0" border="0">
        <?=$inst_result['role']?>
        </table>
    <? else :?>
        <br />
    <?endif?>
<?endforeach?>
