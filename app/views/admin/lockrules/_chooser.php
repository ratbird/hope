<?
# Lifter010: TODO
?>
<form action="<?=$controller->url_for('admin/lockrules')?>" method="post">
<?=CSRFProtection::tokenTag()?>
    <select name="lock_rule_type" onchange="this.form.submit();">
    <? foreach (array('sem' => _("Veranstaltung"), 'inst' => _("Einrichtung"), 'user' => _("Nutzer")) as $type => $desc) : ?>
        <option value="<?= $type ?>" <?= $type == $lock_rule_type ? 'selected' : '' ?>>
                <?= $desc ?>
        </option>
        <? endforeach ?>
    </select>
    <noscript>
        <?= Assets::input("icons/16/blue/accept.png", array('type' => "image", 'class' => "middle", 'name' => "show")) ?>
    </noscript>
</form>