<?
# Lifter010: TODO
?>
<table class="table_row_even" cellspacing="0" cellpadding="2" border="0" width="95%">
    <tr>
        <td>&nbsp;<?= _("VeranstaltungsteilnehmerInnen") ?></td>
    </tr>
    <tr>
        <td valign="top" align="center">

            <select size="10" name="seminarPersons[]" multiple="multiple" style="width:100%;">
            <? if (is_array($seminar_persons)) foreach ($seminar_persons as $key => $val) : ?>
                <option <?=($val['hasgroup'])?'style="color: #777777;"':''?> value="<?=$val['username']?>">
                <?=htmlReady(my_substr($val['fullname'], 0, 20))?> (<?=$val['username']?>) - <?=$val['perms']?>
                </option>
            <? endforeach; ?>
            </select><br>
            <br>
        </td>
    </tr>
    <?if($show_search_and_members_form) : ?>
    <tr>
        <td>&nbsp;<?= _("Mitarbeiterliste") ?></td>
    </tr>
    <tr>
        <td valign="top" align="center">

            <select size="5" name="institutePersons[]" multiple="multiple" style="width:100%;">
            <? if (is_array($inst_persons)) foreach ($inst_persons as $key => $val) : ?>
                <option <?=($val['hasgroup'])?'style="color: #777777;"':''?> value="<?=$val['username']?>">
                <?=htmlReady(my_substr($val['fullname'], 0, 20))?> (<?=$val['username']?>) - <?=$val['perms']?>
                </option>
            <? endforeach; ?>
            </select><br>
            <br>
        </td>
    </tr>

    <tr>
        <td nowrap>&nbsp;<?= _("freie Personensuche") ?></td>
    </tr>
    <tr>
        <td valign="top">

    <?
        $search_exp = Request::get('search_exp');
        if ($search_exp):
            $users = getSearchResults(trim($search_exp), $range_id, 'sem');
            if ($users): ?>
                <select name="searchPersons[]" size="5" multiple style="width: 90%;">
                <? if (is_array($users)) foreach ($users as $user) : ?>
                    <option value="<?= htmlReady($user['username']) ?>">
                        <?= htmlReady(my_substr($user['fullname'],0,35)) ?> (<?= $user['username'] ?>), <?= $user['perms'] ?>
                    </option>
                <? endforeach; ?>
                </select>
                <?= Assets::input('icons/16/blue/refresh.png', tooltip2(_('Personen suchen')), array(
                        'valign' => 'bottom',
                        'name' => 'search',
                        'value' => _('Personen suchen'),
                )) ?>
                <br>
            <? else : // no users there ?>
                <?= _('kein Treffer') ?>
                <?= Assets::input('icons/16/blue/refresh.png', tooltip2(_('neue Suche')) + array(
                        'valign' => 'bottom',
                        'name' => 'search',
                        'value' => _('Personen suchen'),
                )) ?>
            <? endif; // users there? ?>
        <? else: ?>
            <input type="text" name="search_exp" value="" style="width: 90%;">
            <?= Assets::input('icons/16/blue/search.png', tooltip2(_('Person suchen')) + array(
                    'name' => 'search',
                    'value' => _('Personen suchen'),
            )) ?>
            <br><br>
        <? endif; ?>
        </td>
    </tr>
    <? endif;?>
</table>

