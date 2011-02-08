<?
# Lifter010: TODO
?>
<form action="<?= $controller->url_for('admin/configuration/user_configuration') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <?= QuickSearch::get("user_id",new StandardSearch("user_id"))
                   ->noSelectbox()
                   ->setInputStyle("width: 150px")
                   ->render(); ?>
</form>
