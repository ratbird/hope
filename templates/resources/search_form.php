<? if ($search_string): ?>

    <input type="hidden" name="search_string_<?= $name ?>" value="<?= htmlReady($search_string) ?>">
    <?= Assets::input('icons/16/yellow/arr_2' . $img_dir, tooltip2(_('diesen Eintrag übernehmen')) + array(
            'name' => 'send_' . $name,
            'value' => _('übernehmen'),
    )) ?>

    <select align="absmiddle" name="submit_<?= $name ?>">
    <? if ($allow_all): ?>
        <option value="all"><?= _('jedeR') ?></option>
    <? endif; ?>

    <? foreach ($results as $art => $items): ?>
        <optgroup label="<?= htmlReady($art) ?>">
        <? foreach ($items as $key => $val): ?>
            <option value="<?= htmlReady($key) ?>" <?= tooltip($val['name']) ?>>
                <?= htmlReady(my_substr($val['name'], 0, 30)) ?>
            </option>
        <? endforeach; ?>
        </optgroup>
    <? endforeach; ?>
    </select>

    <?= Assets::input('icons/16/blue/refresh.png', tooltip2(_('Suche zurücksetzen')) + array(
            'align' => 'absmiddle',
            'name' => 'reset_' . $name,
            'value' => _('neue Suche'),
    )) ?>

<? else: ?>

    <input type="text" align="absmiddle" size="30" maxlength="255"
           name="search_string_<?= $name ?>">
    <?= Assets::input('icons/16/blue/search.png', tooltip2(_('Starten Sie hier Ihre Suche')) + array(
            'align' => 'absmiddle',
            'name' => 'do_' . $name,
            'value' => _('suchen'),
    )) ?>

<? endif; ?>