<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<?
define('SELECTED', ' checked');
if (Config::get()->RESOURCES_ENABLE && Config::get()->RESOURCES_ENABLE_BOOKINGSTATUS_COLORING) {
    $class = 'content_title_red';
} else {
    $class = 'printhead';
}
?>
<TR>
    <TD class="table_row_even" colspan="9">
        <a name="newCycle"></a>
        <FORM action="<?= URLHelper::getLink() ?>" method="post" name="Formular">
        <?= CSRFProtection::tokenTag() ?>
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD width="2%" align="right" valign="top" class="<?=$class?>">
                    <?= Assets::img('icons/16/blue/arr_1down.png') ?>
                </TD>
                <TD width="98%" nowrap class="<?=$class?>" colspan="8">
                    <FONT size="-1">
                        <SELECT name="day">
                            <? foreach(range(1,6) + array(6 => 0) as $d) : ?> <? /* the + operator creates a union of two arrays, it's not an addition! */ ?>
                                <OPTION value="<?=$d?>"<?=(isset($tpl['mdDayNumber']) && $tpl['mdDayNumber'] == $d) ? 'selected="selected"' : ''?>><?=getWeekday($d, false)?></OPTION>
                            <? endforeach; ?>
                            </SELECT>
                        <INPUT type="text" id="start_stunde" name="start_stunde" maxlength="2" size="2" value="<?=$tpl['start_stunde']?>">:
                        <INPUT type="text" id="start_minute" name="start_minute" maxlength="2" size="2" value="<?=$tpl['start_minute']?>">&nbsp;<?=_("bis")?>&nbsp;
                        <INPUT type="text" id="end_stunde" name="end_stunde" maxlength="2" size="2" value="<?=$tpl['end_stunde']?>">:
                        <INPUT type="text" id="end_minute" name="end_minute" maxlength="2" size="2" value="<?=$tpl['end_minute']?>">&nbsp;<?=_("Uhr")?>
                        <?=Termin_Eingabe_javascript(3);?>
                        &nbsp;&nbsp;<?=_("Beschreibung:")?>&nbsp;<INPUT type="text" name="description" value="<?=$tpl['mdDescription']?>">
                    </FONT>
                    <br>
                        <?=_("Turnus")?>:
                        <select name="turnus">
                        <option value="0"<?=$tpl['cycle'] == 0 ? 'selected' : ''?>><?=_("w�chentlich");?></option>
                        <option value="1"<?=$tpl['cycle'] == 1 ? 'selected' : ''?>><?=_("zweiw�chentlich")?></option>
                        <option value="2"<?=$tpl['cycle'] == 2 ? 'selected' : ''?>><?=_("dreiw�chentlich")?></option>
                        </select>
                        &nbsp;&nbsp;
                        <?=_("beginnt in der")?>:
                        <select name="startWeek">
                        <?
                            foreach ($start_weeks as $value => $data) :
                                echo '<option value="'.$value.'"';
                                if ($tpl['week_offset'] == $value) echo ' selected="selected"';
                                echo '>'.$data['text'].'</option>', "\n";
                            endforeach;
                        ?>
                        </select>
                        &nbsp;&nbsp;
                        <?=_("SWS Dozent:")?>
                        &nbsp;
                        <INPUT type="text" name="sws" maxlength="3" size="1" value="<?=$tpl['sws']?>">
                </TD>
            </TR>
            <TR>
                <TD colspan="9" class="table_row_even" align="center">
                    <BR/>
                    <INPUT type="hidden" name="cmd" value="doAddCycle">
                    <?= Button::create(_('�bernehmen')) ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL()) ?>
                </TD>
            </TR>
        </TABLE>
        </FORM>
    </TD>
</TR>
<?
unset($tpl)
?>
