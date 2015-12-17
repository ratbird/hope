<?
# Lifter010: TODO
    use Studip\Button;
?>
<? $search_exp = trim(Request::get('search_exp')); ?>
<form action="<?= URLHelper::getLink("#$anker") ?>" method="post" style="display: inline">
    <?= CSRFProtection::tokenTag() ?>
    <?
    if ($search_exp) :
        $users = getSearchResults($search_exp, Request::option('role_id'));
        if ($users) :
    ?>
    <select name="persons_to_add[]" size="10" multiple style="width: 90%">
        <? if (is_array($users)) foreach ($users as $user) : ?>
        <option value="<?= $user['username']?>">
            <?= htmlReady(my_substr($user['fullname'],0,35)) ?> (<?= $user['username'] ?>), <?= $user['perms'] ?>
        </option>
        <? endforeach; ?>
    </select>
    <a href="<?= URLHelper::getLink("?role_id=".Request::option('role_id')."&refresh=true#$anker") ?>">
        <?= Icon::create('refresh', 'clickable', ['title' => _('neue Suche')])->asImg(16) ?>
    </a>
    <br><br>
    <input type="hidden" name="cmd" value="addPersonsToRoleSearch">
    <?= Button::create(_('Eintragen'), 'eintragen') ?>
    <br>
        <? else : // no users there ?>
    <?= _("kein Treffer") ?>
    <?= Icon::create('refresh', 'clickable', ['title' => _('neue Suche')])->asInput(array('valign'=>'bottom','name'=>'search','value'=>_('Personen suchen'),)) ?>
        <? endif; // users there? ?>
    <? else : ?>
        <input type="text" name="search_exp" value="" style="width: 90%">
        <?= Icon::create('search', 'clickable', ['title' => _('Person suchen')])->asInput(array('name'=>'search','value'=>_('Personen suchen'),)) ?>
        <br><br>
    <? endif;   ?>
    <input type="hidden" name="role_id" value="<?= Request::option('role_id') ?>">
    <input type="hidden" name="range_id" value="<?= Request::option('range_id') ?>">
</form>
