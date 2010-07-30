<table width="95%" border="0" cellpadding="2" cellspacing="0">
	<tr height="30px">
		<td width="5%"><input type="image" name="assign_<?= $statusgruppe['statusgruppe_id'] ?>" src="<?= Assets::image_path('move.gif') ?>" border="0" <?= tooltip(_("Markierte Personen dieser Gruppe zuordnen")) ?>>&nbsp; </td>
		<td width="86%" class="printhead">&nbsp; <?= htmlReady($statusgruppe['name']) ?> </td>
		<? if (get_config('CALENDAR_GROUP_ENABLE') && $edit) : ?>
			<td width="1%" style="white-space:nowrap;" class="printhead">
				Kalendergruppe: <input type="checkbox" name="is_cal_group" value="1" <?= $statusgruppe['calpermission'] == 1 ? 'checked' : '' ?>>
			</td>
			<td width="1%" class="printhead"><img src="<?= Assets::image_path('group_cal.gif') ?>" border="0" <?= tooltip(_("Mein Kalender ist für diesen Benutzer gesperrt.")) ?>></td>
			<td width="1%" class="printhead"><img src="<?= Assets::image_path('group_cal_visible.gif') ?>" border="0" <?= tooltip(_("Mein Kalender ist für diesen Benutzer lesbar.")) ?>></td>
			<td width="1%" class="printhead"><img src="<?= Assets::image_path('group_cal_writable.gif') ?>" border="0" <?= tooltip(_("Mein Kalender ist für diesen Benutzer schreibbar.")) ?>></td>
		<? endif ?>
		<? if (!$edit) : ?>
			<td width="3%" class="printhead">
				<a href="<?= URLHelper::getLink('', array('edit_id' => $statusgruppe['statusgruppe_id'], 'range_id' => $GLOBALS['user']->id, 'view' => '#' . $statusgruppe['statusgruppe_id'], 'cmd'=> 'edit_statusgruppe')) ?>"><img src="<?= Assets::image_path('einst.gif') ?>" border="0" <?= tooltip(_("Bearbeiten")) ?>></a>
			</td>
		<? endif ?>
		<td width="5%"><a href="<?= URLHelper::getLink('', array('cmd' => 'verify_remove_statusgruppe', 'statusgruppe_id' => $statusgruppe['statusgruppe_id'], 'range_id' => $GLOBALS['user']->id, 'view' => '#' . $statusgruppe['statusgruppe_id'], 'name' => $statusgruppe['name'])) ?>"><img border="0" src="<?= Assets::image_path('trash_att.gif') ?>" <?= tooltip(_("Gruppe mit Personenzuordnung entfernen")) ?>></a></td>
	</tr>
	
	<? $k = 1 ?>
	<? foreach ($contacts as $contact) : ?>
	<tr>
		<td><?= $k ?></td>
		<? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
			<? if ($edit) : ?>
				<td colspan="2" class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>"><?= htmlReady($contact['fullname']) ?></td>
				<td style="text-align:center;" class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>">
					<input type="radio" name="calperm_<?= $contact['username'] ?>" value="<?= CALENDAR_PERMISSION_FORBIDDEN ?>" <?= $contact['calpermission'] == CALENDAR_PERMISSION_FORBIDDEN ? 'checked' : '' ?>>
				</td>
				<td style="text-align:center;" class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>">
					<input type="radio" name="calperm_<?= $contact['username'] ?>" value="<?= CALENDAR_PERMISSION_READABLE ?>" <?= $contact['calpermission'] == CALENDAR_PERMISSION_READABLE ? 'checked' : '' ?>>
				</td>
				<td style="text-align:center;" class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>">
					<input type="radio" name="calperm_<?= $contact['username'] ?>" value="<?= CALENDAR_PERMISSION_WRITABLE ?>" <?= $contact['calpermission'] == CALENDAR_PERMISSION_WRITABLE ? 'checked' : '' ?>>
				</td>
			<? else : ?>
				<td class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>"><?= htmlReady($contact['fullname']) ?></td>
				<td class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>">
					<? if ($contact['calpermission'] == CALENDAR_PERMISSION_READABLE) : ?>
						<img src="<?= Assets::image_path('group_cal_visible') ?>" <?= tooltip(_("Mein Kalender ist für diesen Benutzer lesbar")) ?>>
					<? elseif ($contact['calpermission'] == CALENDAR_PERMISSION_WRITABLE) : ?>
						<img src="<?= Assets::image_path('group_cal_writable') ?>" <?= tooltip(_("Mein Kalender ist für diesen Benutzer schreibbar")) ?>>
					<? endif ?>
				</td>
			<? endif ?>
		<? else : ?>
			<? if ($edit) : ?>
			<? else : ?>
				<td class="<?= $k % 2 ? 'steel1' : 'steelgraulight' ?>" colspan="2"><?= htmlReady($contact['fullname']) ?></td>
			<? endif ?>
		<? endif ?>
		<td><a href="<?= URLHelper::getLink('', array('cmd' => 'remove_person', 'statusgruppe_id' => $statusgruppe['statusgruppe_id'], 'username' => $contact['username'], 'range_id' => $GLOBALS['user']->id, 'view' => '#' . $statusgruppe['statusgruppe_id'])) ?>"><img border="0" src="<?= Assets::image_path('trash.gif') ?>" <?= tooltip(_("Person aus der Gruppe entfernen")) ?>></a></td>
	</tr>
	<? $k++ ?>
	<? endforeach ?>
	
</table>

<? if ($edit) : ?>
	<p style="text-align:center;">
		<input type="image" <?= makeButton('uebernehmen', 'src') ?> name="" value="">
	</p>
<? elseif ($move) : ?>
	<p style="text-align:center;"><a href="<?= URLHelper::getLink('', array('cmd' => 'swap', 'statusgruppe_id' => $statusgruppe['statusgruppe_id'], 'range_id' => $GLOBALS['user']->id, 'view' => '#' . $statusgruppe['statusgruppe_id'])) ?>"><img border="0" src="<?= Assets::image_path('move_up.gif') ?>" <?= tooltip(_("Gruppenreihenfolge tauschen")) ?>><img border="0" src="<?= Assets::image_path('move_down.gif') ?>" <?= tooltip(_("Gruppenreihenfolge tauschen")) ?>></a><br>&nbsp;</p>
<? endif ?>
