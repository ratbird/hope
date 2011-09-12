<?
# Lifter010: TODO
?>
<h2><?= _("Neue Gruppenmitglieder einladen") ?></h2>
<form action="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/add_invites') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <div>
        <?= _("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.") ?><br>
        <?= $test = QuickSearch::get("choose_member", $inviting_search)
                            ->withButton()
                            ->render() ?>
        <? if(isset($this->flash['choose_member_parameter'])) : ?>
            <input type="image" name="add_member" <?= makebutton('einladen','src')?> style="vertical-align:middle;">
            <a href="<?= $controller->url_for('course/studygroup/edit_members/') ?>"><?=  makeButton('abbrechen', 'img') ?></a><br>
        <? endif; ?>
    </div>
</form>
