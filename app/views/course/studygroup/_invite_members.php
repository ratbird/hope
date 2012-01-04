<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<h2><?= _("Neue Gruppenmitglieder einladen") ?></h2>
<form action="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/add_invites') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <div>
        <?= _("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.") ?><br>
        <?= QuickSearch::get("choose_member", $inviting_search)
                            ->withButton()
                            ->render() ?>
        <? if(isset($this->flash['choose_member_parameter'])) : ?>
            <?=Button::create(_("einladen"), 'add_member', array('style' =>'vertical-align:middle;'))?>
        <? endif; ?>
    </div>
</form>
