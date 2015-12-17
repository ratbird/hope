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
                            <option value="<?=$selectionlist[$i]['value']?>" selected>
                                <?= htmlReady($selectionlist[$i]['linktext']) ?>
                            </option>
                            <? else: ?>
                            <option value="<?=$selectionlist[$i]['value']?>">
                                <?= htmlReady($selectionlist[$i]['linktext']) ?>
                            </option>
                            <? endif; ?>
                        <? endfor; ?>
                        </select>
                    </td>
                    <td valign="center">
                        &nbsp;
                        <?= Icon::create('accept', 'clickable')->asInput(["name" => 'semester', "type" => 'image']) ?>
                        <input type="hidden" name="cmd" value="applyFilter">
                    </td>
                </tr></tbody>
            </table>
        </form>
    </td>
</tr>

