<? $pos_inst = 1; ?>
<form action="<?= URLHelper::getLink('?cmd=edit_leben&username=' . $username . '&view=' . $view . '&studipticket=' . get_ticket()) ?>" method="POST" name="pers">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<?
if (is_array($institutes) && sizeof($institutes) > 0) :
	foreach ($institutes as $inst_id => $institute) : ?>
<tr>
	<td class="printhead" valign="bottom" colspan="2" height="23" nowrap style="padding-left: 3px" width="99%">
		<a name="<?= $inst_id ?>"></a>
		<a class="tree" href="<?= URLHelper::getLink('?view='. $view .'&username='. $username .'&switch='. $inst_id .'&trash='. rand() .'#'. $inst_id) ?>">
			<? if ($open == $inst_id) :
				echo Assets::img('forumgraurunt');
			else :
				echo '&nbsp;' . Assets::img('forumgrau');
			endif; ?>
			<?= htmlReady($institute['Name']) ?>
		</a>
	</td>
	<td class="printhead" nowrap="nowrap" width="1%" valign="bottom">
		<? if ($pos_inst > 1) : ?>
		<a href="<?= URLHelper::getLink('?view=Karriere&username='. $username .'&cmd=move&direction=up&move_inst='. $inst_id .'&studipticket='. get_ticket()) ?>"><?= Assets::img('move_up'); ?></a>
		<? endif; if ($pos_inst < sizeof($institutes)) : ?>
		<a href="<?= URLHelper::getLink('?view=Karriere&username='. $username .'&cmd=move&direction=down&move_inst='. $inst_id .'&studipticket='. get_ticket()) ?>"><?= Assets::img('move_down'); ?></a>
		<? endif; ?>
		&nbsp;
	</td>
</tr>
	<? if ($open == $inst_id) :
		echo $this->render_partial('statusgruppen/institute_modify_edit_about',
			array('followers' => sizeof($institute['roles']), 'inst_id' => $inst_id, 'data' => $institute, 'user_id' => $user_id, 'user_perm' => $user_perm)
		);
	endif;

	$pos_role = 1;
	$max_roles = 0;
	$flattened_roles = Statusgruppe::getFlattenedRoles($institute['roles']);

	if (is_array($flattened_roles))
	foreach ($flattened_roles as $role) {
		if ($role['user_there']) $max_roles++;
	}

	if (is_array($institute['roles'])) foreach ($flattened_roles as $role_id => $role) :
		if ($role['user_there']) :
?>
<tr>
	<td class="blank">
		<?
		if ($max_roles > $pos_role) :
			echo Assets::img('forumstrich3');
		else :
			echo Assets::img('forumstrich2');
		endif;
		?>
	</td>
	<td class="printhead" valign="bottom" height="23" nowrap style="padding-left: 3px" width="99%">
		<a name="<?= $role_id ?>">
		<a class="tree" href="<?= URLHelper::getLink('?view='. $view .'&username='. $username .'&switch='. $role_id .'&trash='. rand() .'#'. $role_id) ?>">
			<? if ($open == $role_id) :
				echo Assets::img('forumgraurunt');
			else :
				echo '&nbsp;' . Assets::img('forumgrau');
			endif; ?>
			<?= htmlReady($role['name_long']) ?>
		</a>
	</td>
	<td class="printhead"></td>
</tr>
<?
 	if ($open == $role_id) :
		echo $this->render_partial('statusgruppen/role_modify_edit_about',
			array('followers' => $max_roles > $pos_role, 'role_id' => $role_id, 'role' => $role['role'], 'inst_id' => $inst_id)
		);
	endif;
?>
<?
			$pos_role++;
		endif; // user is in this role
	endforeach; // roles

	$pos_inst++;
endforeach; // institutes ?>
</table>
</form>
<? else : ?>
<tr>
	<td class="blank" align="center">
		<b><?= _("Sie sind keinem Institut / keiner Einrichtung zugeordnet!") ?></b><br>
	</td>
</tr>
<?
endif;
?>
</table>
</form>
<br>
<table class="blank" width="100%">
<?
if ($GLOBALS['perm']->have_perm('admin'))
	echo $this->render_partial('statusgruppen/edit_about_add_person_to_role',
		array('subview_id' => $subview_id, 'admin_insts' => $admin_insts, 'sub_admin_insts' => $sub_admin_insts));
?>
</table>
