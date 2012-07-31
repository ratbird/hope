<? if ($search_string): ?>

    <input type="hidden" name="search_string_<?= $name ?>" value="<?= $search_string ?>">
    <input type="image" name="send_<?= $name ?>"
           src="<?= Assets::image_path('icons/16/yellow/arr_2' . $img_dir) ?>"
           value="<?= _('übernehmen') ?>" 
           <?= tooltip(_('diesen Eintrag übernehmen')) ?>>

    <select align="absmiddle" name="submit_<?= $name ?>">
    <? if ($allow_all): ?>
        <option value="all"><?= _('jedeR') ?></option>
    <? endif; ?>

    <? foreach ($results as $art => $items): ?>
        <optgroup label="<?= $art ?>">
        <? foreach ($items as $key => $val): ?>
            <option value="<?= $key ?>" <?= tooltip($val['name']) ?>>
                <?= my_substr($val['name'], 0, 30); ?>
            </option>
        <? endforeach; ?>
        </optgroup>
    <? endforeach; ?>
    </select>

    <input type="image" align="absmiddle" name="reset_<?= $name ?>"
           src="<?= Assets::image_path('icons/16/blue/refresh.png') ?>"
           value="<?= _('neue Suche') ?>"
           <?=tooltip (_("Suche zurücksetzen")) ?>
           border="0">

<? else: ?>

    <input type="text" align="absmiddle" size="30" maxlength="255"
           name="search_string_<?= $name ?>">
    <input type="image" align="absmiddle" name="do_<?= $name ?>" border="0"
           src="<?= Assets::image_path('icons/16/blue/search.png') ?>"
           value="<?=_('suchen')?>"
           <?= tooltip(_('Starten Sie hier Ihre Suche')) ?>>

<? endif; ?>