<?
# Lifter010: TODO
?>
<tr>
    <td style='text-align:right; vertical-align:top;'><?=_("Gruppengr�nder:")?></td>
    <td nowrap>
        <div style="width: 50%; float: left; vertical-align:middle;">
            <? if(is_array($founders) && sizeof($founders) > 0) : ?>
                <? foreach($founders as $founder) : ?>
                    <?= htmlReady(get_fullname_from_uname($founder['username'])) ?>
                <? endforeach; ?>
            <? endif; ?>
        </div>
        <? if(!empty($tutors)) :?>
            <div style="width: 50%; float: left; vertiacl-align:middle;">
                <?= Assets::input("icons/16/yellow/arr_2left.png", array('type' => "image", 'class' => "middle", 'name' => "replace_founder", 'title' => _('Als Gruppengr�nderIn eintragen'))) ?>
                <select name="choose_founder">
                    <? foreach($tutors as $uid => $tutor) : ?>
                        <option value="<?=$uid?>"> <?= htmlReady($tutor['fullname']) ?> </option>
                    <? endforeach ; ?>
                </select>
            </div>
        <? endif; ?>
    </td>
</tr>
