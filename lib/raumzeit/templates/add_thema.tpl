<TR>
    <TD class="steel" colspan="3">
        &nbsp;
    </TD>
</TR>
<TR>
    <TD width="10%">
        &nbsp;
    </TD>
    <TD class="blank" colspan="2">
        <FONT size="-1">
            <B><?=("Titel:")?></B><BR/>
            <INPUT type="text" name="theme_title" maxlength="255" size="50" value="<?=$tpl['theme_title']?>"><BR/>
            <B><?=_("Beschreibung:")?></B><BR/>
            <TEXTAREA name="theme_description" rows="5" cols="50"><?=$tpl['theme_description']?></TEXTAREA><BR/>
            <B><?=_("Art:")?></B><BR/>
            <SELECT name="theme_type">
            <?
            foreach ($TERMIN_TYP as $key => $val) {
                echo '<OPTION value="'.$key.'"';
                if ($tpl['theme_type'] == $key) {
                    echo ' selected';
                }
                echo '>'.$val['name']."</OPTION>\n";
            }
            ?>
            </SELECT>
        </FONT>
        <BR/>
        <CENTER>
            <INPUT type="image" <?=makebutton('uebernehmen', 'src')?> align="absmiddle" name="doAddIssue">
            <IMG <?=makebutton('abbrechen', 'src')?> border="0" align="absmiddle">
        </CENTER>
    </TD>
</TR>
