<?
# Lifter010: TODO
?>
<tr>
    <td width="100%" colspan="2">
        <b><?= $selectionlist_title ?>:</b>
    </td>
</tr>
<tr>
    <td width="100%" align="center" colspan="2">
        <form action="<?= URLHelper::getLink() ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
            <table border="0" cellspacing="0" cellpadding="0">
                <tbody><tr>
                    <td valign="center">
                        <select name="newFilter" size="1" title="<?= _("Semester auswählen") ?>">
                        <? for ($i = 0; $i < count($selectionlist); $i++) : ?>
                            <? if ( $selectionlist[$i]['is_selected'] ) : ?>
                            <option value="<?=$selectionlist[$i]['value']?>" selected><?=$selectionlist[$i]['linktext']?></option>
                            <? else: ?>
                            <option value="<?=$selectionlist[$i]['value']?>"><?=$selectionlist[$i]['linktext']?></option>
                            <? endif; ?>
                        <? endfor; ?>
                        </select>
                    </td>
                    <td valign="center">
                        &nbsp;
                        <input type="image" src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/accept.png" name="semester" border="0">
                        <input type="hidden" name="cmd" value="applyFilter">
                    </td>
                </tr></tbody>
            </table>
        </form>
    </td>
</tr>

