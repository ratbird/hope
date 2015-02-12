<h3><?= $rule->getName() ?></h3>
<?= $tpl ?>
<br/>
<label for="maxnumber" class="caption">
    <?= _('Maximale Anzahl erlaubter Anmeldungen') ?>:
</label>
<input type="number" name="maxnumber" size="4" min="1" value="<?= $rule->getMaxNumber() ?>" required/>