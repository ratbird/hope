<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<form class="studip_form" action="<?= $controller->link_for() ?>" method="post">
<?= CSRFProtection::tokenTag()?>
<input type="hidden" name="type" value="<?=htmlReady($type)?>">
<input type="hidden" name="rule_id" value="<?=htmlReady($rule_id)?>">
<label class="caption"><?= _("Name für diese Anmelderegel")?></label>
<input type="text" name="instant_course_set_name" size="70" value="<?= htmlReady($course_set_name) ?>">
<?= $rule_template ?>
<?= Studip\Button::create(_("Speichern"), 'save', array('rel' => 'lightbox'))?>
<?= Studip\LinkButton::create(_("Abbrechen"), '#', array('rel' => 'close'))?>
</form>