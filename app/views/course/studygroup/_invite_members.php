<h2><?= _("Neue Gruppenmitglieder einladen") ?></h2>
<form action="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$GLOBALS['user']->id.'/add_invites/') ?>" method=post>
	<? if(empty($flash['members']) && empty($members)) :?>
		<div>
			<? if (is_array($results_choose_members)) : ?>
				<div style="clear:left">
					<select name="choose_member">
						<? foreach ($results_choose_members as $user_id => $data) : ?>
							<option value="<?= $data['username'] ?>" style="background: url(<?= Avatar::getAvatar($user_id)->getURL(Avatar::SMALL)?>) no-repeat left center;padding-left: 25px;">
									<?= htmlReady(my_substr($data['fullname'],0,40))." (".$data['username'] ?>)
							</option>
						<? endforeach; ?>
					</select>
					<input type="image" name="add_member" <?= makebutton('einladen','src')?> style="vertical-align:middle;">
					<input type="image" name="new_search" src="<?= Assets::image_path('rewind.gif') ?>" title="<?= _("neue Suche starten") ?>" style="vertical-align:middle;">
				<div>
			<? else : ?>
				<?= _("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein.") ?><br>
				<div style="clear:left">
					<input type="text" name="search_for_member" style="width:300px;vertical-align:middle;">
					<input type="image" name="search_member" src="<?= Assets::image_path('suchen.gif') ?>" title="<?= _("Suchen") ?>" style="vertical-align:middle;"><br>	
				<div>
			<? endif; ?>
		</div>
	<? endif; ?>
</form>
