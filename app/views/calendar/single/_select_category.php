<form name="filter_categories" method="post" action="<?= $action_url ?>">
    <span style="font-size: small; color: #555555;"><?= _("Kategorie:") ?></span>
    <select style="font-size: small;" name="category" onChange="document.filter_categories.submit()">
        <option value="" style="font-weight: bold;"><?= _("Alle Kategorien") ?></option>
        <? foreach (Config::get()->getValue('PERS_TERMIN_KAT') as $key => $cat) : ?>
        <option style="font-weight:bold; color:<?= $cat['color'] ?>;" value="<?= $key ?>"<?= ($category == $key ? ' selected="selected"' : '') ?>>
            <?= htmlReady($cat['name']) ?>
        </option>
        <? endforeach; ?>
    </select>
    
    <input type="image" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" border="0" class="text-top">
</form>

