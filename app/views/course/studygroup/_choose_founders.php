<tr>
	<td style='text-align:right; vertical-align:top;'><?= _("Gruppengründer:") ?></td>
	<td nowrap>
		<div style="width: 49%; float: left;">
		<? if (is_array($founders) && sizeof($founders) > 0) : 
			foreach ($founders as $founder) :?>
				<input type="image" name="remove_founder" value="<?= $founder['username'] ?>" src="<?= Assets::image_path('trash') ?>" <?= tooltip("NutzerIn entfernen") ?>>
				<?= $founder['fullname'] ?> (<?= $founder['username'] ?>)
				<input type="hidden" name="founders[<?= $founder['username'] ?>]" value="<?= $founder['fullname'] ?>">
				<br>
			<? endforeach; ?>
			<br>
		<? endif; ?>
		&nbsp;
		</div>
        <? if(empty($flash['founders']) && empty($founders)) :?>
		    <div style="width: 49%; float: left;">
    		<? if (is_array($results_choose_founders)) : ?>
    			<b><?= sizeof($results_choose_founders) ?></b>
    			<?= sizeof($results_choose_founders) == 1 ? _("NutzerIn gefunden:") : _("NutzerInnen gefunden:") ?><br>
    			<input type="image" name="add_founder" src="<?= Assets::image_path('move_left.gif') ?>" title="<?= _("NutzerIn hinzufügen") ?>">
    			<select name="choose_founder">
    				<? foreach ($results_choose_founders as $user_id => $data) : ?>
    				<option value="<?= $data['username'] ?>"><?= htmlReady(my_substr($data['fullname']." (".$data['username'],0,35)) ?>) - <?= $data['perms'] ?></option>
    				<? endforeach; ?>
    			</select>
    			<input type="image" name="new_search" src="<?= Assets::image_path('rewind.gif') ?>" title="<?= _("neue Suche starten") ?>">
    			<? if (sizeof($results_choose_founders) == 500) : ?>
    			<br><span style="color:red"><?= sprintf(_("Es werden nur die ersten %s Treffer angezeigt!"), 500) ?></span>
    			<? endif; ?>
    		<? else : ?>
    			<?= _("GruppengründerIn hinzufügen:") ?><br>
    			<input type="text" name="search_for_founder">
    			<input type="image" name="search_founder" src="<?= Assets::image_path('suchen.gif') ?>" title="<?= _("Suchen") ?>"><br>
    			<?= _("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein.") ?>
    		<? endif; ?>
    		</div>
        <? endif; ?>
	</td>
</tr>
