<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<form class="studip_form" action="<?= $controller->link_for() ?>" method="post">
<?= CSRFProtection::tokenTag()?>
<input type="hidden" name="type" value="<?=htmlReady($type)?>">
<input type="hidden" name="rule_id" value="<?=htmlReady($rule_id)?>">
<?= $rule_template ?>
    <br>
<label class="caption"><?= _("Name für diese Anmelderegel")?></label>
<input type="text" name="instant_course_set_name" size="70" value="<?= htmlReady($course_set_name) ?>">
<div data-dialog-button>
    <?= Studip\Button::create(_("Speichern"), 'save', array('data-dialog' => ''))?>
</div>
</form>