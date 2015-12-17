<?
# Lifter010: TODO
?>
<tr>
    <td style='text-align:right; vertical-align:top;'><?=_("Gruppengründer:")?></td>
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
                <?= Icon::create('arr_2left', 'sort', ['title' => _('Als GruppengründerIn eintragen')])->asInput(["type" => "image", "class" => "middle", "name" => "replace_founder"]) ?>
                <select name="choose_founder">
                    <? foreach($tutors as $uid => $tutor) : ?>
                        <option value="<?=$uid?>"> <?= htmlReady($tutor['fullname']) ?> </option>
                    <? endforeach ; ?>
                </select>
            </div>
        <? endif; ?>
    </td>
</tr>
