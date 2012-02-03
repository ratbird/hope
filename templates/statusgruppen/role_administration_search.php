<?
# Lifter010: TODO
    use Studip\Button;
?>
<? $search_exp = $GLOBALS['search_exp']; ?>
<form action="<?= URLHelper::getLink("#$anker") ?>" method="post" style="display: inline">
    <?= CSRFProtection::tokenTag() ?>
    <?
    if ($search_exp) :
        $users = getSearchResults(trim($GLOBALS['search_exp']), $role_id);
        if ($users) :
    ?>
    <select name="persons_to_add[]" size="10" multiple style="width: 90%">
        <? if (is_array($users)) foreach ($users as $user) : ?>
        <option value="<?= $user['username']?>">
            <?= htmlReady(my_substr($user['fullname'],0,35)) ?> (<?= $user['username'] ?>), <?= $user['perms'] ?>
        </option>
        <? endforeach; ?>
    </select>
    <a href="<?= URLHelper::getLink("?role_id=$role_id&refresh=true#$anker") ?>">
        <?= Assets::img('icons/16/blue/refresh.png', array(
            'title' => _('neue Suche')
        )) ?>
    </a>
    <br><br>
    <input type="hidden" name="cmd" value="addPersonsToRoleSearch">
    <?= Button::create(_('Eintragen'), 'eintragen') ?>
    <br>
        <? else : // no users there ?>
    <?= _("kein Treffer") ?>
    <input type="image" valign="bottom" name="search" src="<?= Assets::image_path('icons/16/blue/refresh.png') ?>"  value="<?=_("Personen suchen")?>" <?= tooltip(_("neue Suche")) ?>>&nbsp;
        <? endif; // users there? ?>
    <? else : ?>
        <input type="text" name="search_exp" value="" style="width: 90%">
        <input type="image" name="search" src="<?= Assets::image_path('icons/16/blue/search.png') ?>" value="Personen suchen" <?= tooltip(_("Person suchen")) ?>>&nbsp;
        <br><br>
    <? endif;   ?>
    <input type="hidden" name="role_id" value="<?= $role_id ?>">
    <input type="hidden" name="range_id" value="<?= $range_id ?>">
</form>
