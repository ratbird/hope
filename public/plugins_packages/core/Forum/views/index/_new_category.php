<? if ($has_perms) : ?>
<a name="create"></a>
<form action="<?= PluginEngine::getLink('coreforum/index/add_category') ?>" method="post" id="tutorAddCategory">
    <?= CSRFProtection::tokenTag() ?>
    <h2><?= _('Neue Kategorie erstellen') ?></h2>

    <div style="width: 100%;">
        <span class="area_input">
            <input type="text" size="50" placeholder="<?= _('Titel für neue Kategorie') ?>" name="category" required>
            <?= Studip\Button::create('Kategorie erstellen') ?>
        </span>
    </div>
</form>
<br>
<? endif ?>