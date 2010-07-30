<?= _("Personen im Adressbuch") ?><br>
<select size="10" name="selected_contacts[]" multiple>
	<? foreach ($contacts as $contact) : ?>
	<option style="color:<?= in_array($contact['user_id'], $assigned) ? '#777' : '#000' ?>;" value="<?= $contact['username'] ?>"><?= htmlReady(my_substr($contact['fullname'], 0, 35)) ?> (<?= $contact['username'] ?>) - <?= $contact['perms'] ?></option>
	<? endforeach ?>
</select>
