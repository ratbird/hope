<h2><?= _("Neue Gruppenmitglieder einladen") ?></h2>
<form action="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$GLOBALS['user']->id.'/add_invites/') ?>" method=post>
	<? if(empty($flash['members']) && empty($members)) :?>
		<div>
			<? if (is_array($results_choose_members)) : ?>
				<b><?= sizeof($results_choose_members) ?></b>
				<?= sizeof($results_choose_members) == 1 ? _("NutzerIn gefunden:") : _("NutzerInnen gefunden:") ?><br>
				<select name="choose_member">
					<? foreach ($results_choose_members as $user_id => $data) : ?>
						<option value="<?= $data['username'] ?>" style="width:300px;
								background: url(<?= Avatar::getAvatar($data['user_id'])->getURL(Avatar::SMALL)?>) 
								no-repeat left center; padding-left: 25px;height:25px">
								<?= htmlReady(my_substr($data['fullname'],0,40))." (".$data['username'] ?>)
						</option>
					<? endforeach; ?>
				</select>
				<input type="image" name="new_search" src="<?= Assets::image_path('rewind.gif') ?>" title="<?= _("neue Suche starten") ?>">
				<? if (sizeof($results_choose_members) == 500) : ?>
					<br><span style="color:red"><?= sprintf(_("Es werden nur die ersten %s Treffer angezeigt!"), 500) ?></span>
				<? endif; ?>
				<br>
				&nbsp;<input type="image" name="add_member" <?= makebutton('einladen','src')?>>
			<? else : ?>
				<?= _("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein.") ?><br>
				<input type="text" name="search_for_member" style="width:300px">
				<input type="image" name="search_member" src="<?= Assets::image_path('suchen.gif') ?>" title="<?= _("Suchen") ?>"><br>	
			<? endif; ?>
		</div>
	<? endif; ?>
</form>
