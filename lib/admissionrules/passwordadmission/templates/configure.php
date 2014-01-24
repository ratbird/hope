<h3><?= htmlReady($rule->getName()) ?></h3>
<?= $tpl ?>
<br/>
<label for="password1" class="caption">
    <?= _('Zugangspasswort') ?>:
</label>
<input type="password" name="password1" size="25" max="40" value="<?= htmlReady($rule->getPassword()) ?>" required/>
<br/>
<label for="password2" class="caption">
    <?= _('Passwort wiederholen') ?>:
</label>
<input type="password" name="password2" size="25" max="40" value="<?= htmlReady($rule->getPassword()) ?>" required/>