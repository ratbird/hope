<?
define('SELECTED', ' checked');
if ($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ENABLE_BOOKINGSTATUS_COLORING']) {
    $class = 'steelred';
} else {
    $class = 'printhead';
}
?>
<TR>
    <TD class="steel1" colspan="9">
        <A name="newCycle" />
        <FORM action="<?= URLHelper::getLink() ?>" method="post" name="Formular">
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD width="2%" align="right" valign="top" class="<?=$class?>">
                    <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumgraurunt.gif" border="0" align="abstop">
                </TD>
                <TD width="98%" nowrap class="<?=$class?>" colspan="8">
                    <FONT size="-1">
                        <SELECT name="day">
                            <OPTION value="1" <?=$tpl['day']==1?SELECTED:''?>><?=_("Montag")?></OPTION>
                            <OPTION value="2" <?=$tpl['day']==2?SELECTED:''?>><?=_("Dienstag")?></OPTION>
                            <OPTION value="3" <?=$tpl['day']==3?SELECTED:''?>><?=_("Mittwoch")?></OPTION>
                            <OPTION value="4" <?=$tpl['day']==4?SELECTED:''?>><?=_("Donnerstag")?></OPTION>
                            <OPTION value="5" <?=$tpl['day']==5?SELECTED:''?>><?=_("Freitag")?></OPTION>
                            <OPTION value="6" <?=$tpl['day']==6?SELECTED:''?>><?=_("Samstag")?></OPTION>
                            <OPTION value="0" <?=$tpl['day']==0?SELECTED:''?>><?=_("Sonntag")?></OPTION>
                        </SELECT>
                        <!--<SELECT name="turnus">
                            <OPTION value="0"><?=_("w&ouml;chentlich")?></OPTION>
                            <OPTION value="1"><?=_("14-t&auml;glich")?></OPTION>
                        </SELECT>-->
                        <INPUT type="text" id="start_stunde" name="start_stunde" maxlength="2" size="2" value="<?=$tpl['start_stunde']?>">:
                        <INPUT type="text" id="start_minute" name="start_minute" maxlength="2" size="2" value="<?=$tpl['start_minute']?>">&nbsp;<?=_("bis")?>&nbsp;
                        <INPUT type="text" id="end_stunde" name="end_stunde" maxlength="2" size="2" value="<?=$tpl['end_stunde']?>">:
                        <INPUT type="text" id="end_minute" name="end_minute" maxlength="2" size="2" value="<?=$tpl['end_minute']?>">&nbsp;<?=_("Uhr")?>
                        <?=Termin_Eingabe_javascript(3);?>
                        &nbsp;&nbsp;Beschreibung:&nbsp;<INPUT type="text" name="description" value="<?=$tpl['mdDescription']?>">
                    </FONT>
                </TD>
            </TR>
            <TR>
                <TD colspan="9" class="steel1" align="center">
                    <BR/>
                    <INPUT type="hidden" name="cmd" value="doAddCycle">
                    <INPUT type="image" <?=makebutton('uebernehmen', 'src')?>>
                    <a href="<?= URLHelper::getLink() ?>">
                        <IMG <?=makebutton('abbrechen', 'src')?> border="0">
                    </A>
                </TD>
            </TR>
        </TABLE>
        </FORM>
    </TD>
</TR>
<?
unset($tpl)
?>
