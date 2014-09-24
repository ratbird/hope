<? if (isset($title)): ?>
    <h1><?=$title?></h1>
<? endif; ?>
<div class="mpscontainer" data-dialogname="<?= $name; ?>"><p><?= htmlReady(studip_utf8encode($description)); ?></p>
<form method="POST" action="<?= URLHelper::getLink('dispatch.php/multipersonsearch/js_form_exec/?name=' . $name); ?>" id="<?= $name; ?>"<?= $jsFunction ? ' onSubmit="return '.htmlReady($jsFunction).'(this);"' : "" ?>>
    <input id="<?= $name . '_searchinput'; ?>" type="text" placeholder="<?= _("Suchen"); ?>" value="" name="<?= $name . '_searchinput'; ?>" style="width: 210px;" aria-label="<?= _("Suchen"); ?>"></input>
    <?= Assets::img('icons/16/blue/search.png',
                    tooltip2(_('Suche starten')) +
                    array('onclick' => 'STUDIP.MultiPersonSearch.search()')) ?>
    <?= Assets::img('icons/16/blue/decline.png',
                    tooltip2(_('Suche zurücksetzen')),
                    array('onclick' => 'STUDIP.MultiPersonSearch.resetSearch()')) ?>
    <p><? foreach($quickfilter as $title => $users) : ?>
        <? $title = studip_utf8encode($title); ?>
        <a href="#" class="quickfilter" data-quickfilter="<?= md5($title); ?>"><?= htmlReady($title); ?> (<?= count($users); ?>)</a>
        <select multiple="multiple" id="<?= $name . '_quickfilter_' . md5($title); ?>" style="display: none;">
        <? foreach($users as $user) : ?>
            <option value="<?= $user->id ?>"><?= Avatar::getAvatar($user->id)->getURL(Avatar::MEDIUM); ?> -- <?= htmlReady(studip_utf8encode($user->getFullName('full_rev'))) ?> -- <?= htmlReady($user->perms) ?> (<?= htmlReady($user->username)?>)</option>
        <? endforeach; ?>
         </select>
    <? endforeach; ?></p>
    <select multiple="multiple" id="<?= $name . '_selectbox'; ?>" name="<?= $name . '_selectbox'; ?>[]" data-init-js="true">
    </select>
    <select multiple="multiple" id="<?= $name . '_selectbox_default'; ?>" style="display: none;">
        <? foreach ($defaultSelectableUsers as $person): ?>
            <option value="<?= $person->id ?>"><?= Avatar::getAvatar($person->id)->getURL(Avatar::MEDIUM); ?> -- <?= htmlReady(studip_utf8encode($person->getFullName('full_rev'))) ?> -- <?= htmlReady($person->perms) ?> (<?= htmlReady($person->username)?>)</option>
        <? endforeach; ?>
        <? foreach ($defaultSelectedUsers as $person): ?>
            <option value="<?= $person->id ?>" selected><?= Avatar::getAvatar($person->id)->getURL(Avatar::MEDIUM); ?> -- <?= htmlReady(studip_utf8encode($person->getFullName('full_rev'))) ?> -- <?= htmlReady($person->perms) ?> (<?= htmlReady($person->username)?>)</option>
        <? endforeach; ?>
    </select>

    <?= $additionHTML; ?>

    <? if ($ajax): ?>
        <?= \Studip\Button::create(_('Speichern'), 'confirm', array('data-dialog-button' => true)) ?>
    <? else: ?>
        <?= \Studip\Button::create(_('Speichern'), 'confirm') ?>
        <?= \Studip\Button::create(_('Abbrechen'), $name . '_button_abort') ?>
    <? endif; ?>
    <?= CSRFProtection::tokenTag() ?>

</form>
</div>
