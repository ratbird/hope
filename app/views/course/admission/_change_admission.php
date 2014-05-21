<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<form class="studip_form" action="<?= $controller->link_for() ?>" method="post">
<?= CSRFProtection::tokenTag()?>
<? foreach($request as $k => $v) : ?>
    <?= addHiddenFields($k, $v) ?>
<? endforeach ?>
<?= Studip\Button::create(_("Ja"), $button_yes, array('data-dialog' => ''))?>
<?= Studip\Button::create(_("Nein"), $button_no, array('data-dialog' => ''))?>
<?= Studip\LinkButton::create(_("Abbrechen"), $controller->url_for('/index'), array('data-dialog' => 'close')) ?>
</form>