<form name="cal_restrict_categories" method="post" action="<?= URLHelper::getLink('', array('cmd' => $cmd, 'atime' => $atime)) ?>">
    <span style="font-size: small; color: #555555;"><?= _("Kategorie:") ?></span>
    <? $cal_restrict = Request::getArray('cal_restrict'); ?>
    <select style="font-size: small;" name="cal_restrict[studip_category]" onChange="document.cal_restrict_categories.submit()">
        <option value="" style="font-weight: bold;"><?= _("Alle Kategorien") ?></option>
        <? foreach (Config::get()->getValue('PERS_TERMIN_KAT') as $key => $category) : ?>
        <option style="font-weight:bold; color:<?= $category['color'] ?>;" value="<?= $key ?>"<?= ($cal_restrict['studip_category'] == $key ? ' selected="selected"' : '') ?>>
            <?= htmlReady($category['name']) ?>
        </option>
        <? endforeach; ?>
    </select>
    <input type="image" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" border="0" style="vertical-align: bottom;">
</form>

