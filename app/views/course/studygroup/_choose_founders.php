<?
# Lifter010: TODO
?>
<tr>
	<? if(empty($flash['founders']) && empty($founders)) :?>
		<td style='text-align:right; vertical-align:top;'><?= _("GruppengründerIn hinzufügen:") ?></td>
		<td nowrap>
            <div style="width: 49%; float: left;">
            <? if (is_array($results_choose_founders)) : ?>
                <select name="choose_founder">
                    <? foreach ($results_choose_founders as $user_id => $data) : ?>
                    <option value="<?= $user_id ?>"><?= htmlReady(my_substr($data['fullname']." (".$data['username'],0,35)) ?>) - <?= $data['perms'] ?></option>
                    <? endforeach; ?>
                </select>
				<input type="image" name="add_founder" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" title="<?= _("NutzerIn hinzufügen") ?>">
                <input type="image" name="new_search" src="<?= Assets::image_path('icons/16/blue/refresh.png') ?>" title="<?= _("neue Suche starten") ?>">
                <? if (sizeof($results_choose_founders) == 500) : ?>
                <br><span style="color:red"><?= sprintf(_("Es werden nur die ersten %s Treffer angezeigt!"), 500) ?></span>
                <? endif; ?>
            <? else : ?>
                <input type="text" name="search_for_founder">
                <input type="image" name="search_founder" src="<?= Assets::image_path('icons/16/blue/search.png') ?>" title="<?= _("Suchen") ?>"><br>
                <?= _("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.") ?>
            <? endif; ?>
            </div>
		</td>
	<? else: ?>
		<td style='text-align:right; vertical-align:top;'><?= _("Gruppengründer:") ?></td>
		<td nowrap>
			<div style="width: 49%; float: left;">
	        <? if (is_array($founders) && sizeof($founders) > 0) :
	            foreach ($founders as $user_id) :?>
	                <input type="image" name="remove_founder" src="<?= Assets::image_path('icons/16/blue/trash.png') ?>" <?= tooltip("NutzerIn entfernen") ?>>
	                <?= htmlReady(get_fullname($user_id, 'full_rev')) ?> (<?= get_username($user_id) ?>)
	                <input type="hidden" name="founders[]" value="<?= $user_id ?>">
	                <br>
	            <? endforeach; ?>
	            <br>
	        <? endif; ?>
	        &nbsp;
	        </div>
		</td>
    <? endif; ?>
</tr>
