<? if (isset($title)): ?>
    <h1><?=$title?></h1>
<? endif; ?>
<form method="POST" action="<?= $controller->url_for("admin/statusgroups/memberAdd/{$group->id}") ?>">
    <input type="hidden" name="search_persons_selectable_hidden" value="<?=htmlReady(serialize($selectablePersonsHidden));?>">
    <input type="hidden" name="search_persons_selected_hidden" value="<?=htmlspecialchars(serialize($selectedPersonsHidden))?>">
    <input type="hidden" name="last_search_hidden" value="<?=$search?>">
    <input type="hidden" name="not_first_call" value="true">
    <?= CSRFProtection::tokenTag() ?>

    <!-- neue Suche -->
    <div id="search_persons" style="width: 800px;">
        <label>
            <input name="freesearch" type="text" placeholder="<?=_('Suchen')?>"
                   aria-label="<?= _('Suchbegriff') ?>" style="width: 45%" value="<?= $search ?>">
            <input type="image" name="submit_search" class="stay_on_dialog" src="<?= Assets::image_path('icons/16/blue/search.png')?>"
                   aria-label="<?= _('Suche starten') ?>">
        </label>
        <br><br>
         <select name="search_preset" aria-label="<?= _('Vorauswahl bestimmter Bereiche, alternativ zur Suche') ?>"
                onchange="jQuery('input[name=submit_search_preset]').click()" style="width: 45%">
            <option><?=_('--- Suchvorlagen ---')?></option>
            <option value="inst">
                <?= _("aktuelle Einrichtung"); ?>
            </option>
        </select>
        <input type="image" name="submit_search_preset" class="stay_on_dialog" src="<?= Assets::image_path('icons/16/blue/accept.png')?>" aria-label="<?= _('Vorauswahl anwenden') ?>">

        <div id="search_persons_content">
            <div style="display: inline-block; float: left; width: 45%; height: 100%">
                <label><?=_('Suchergebnis')?><br>
                <select id="search_persons_selectable" name="search_persons_selectable[]" style="minWidth: 200px; width: 100%; height: 116px" style="height: 16px" multiple
                        aria-label="<?= _('Gefundene Bereiche, die der Ankündigung hinzugefügt werden können') ?>"
                        ondblclick="jQuery('#search_persons_add').click()">
                        <? foreach ($selectablePersons as $person): ?>
                            <option value="<?= $person->id ?>" <?= in_array($person->id, $selectedMembers) ? "selected" : "" ; ?>><?= htmlReady($person->getFullName('full_rev')) ?> - <?= htmlReady($person->perms) ?> (<?= htmlReady($person->username)?>)</option>
                        <? endforeach; ?>
                </select>
                <a href="javascript:selectAll();"><?= _('Alle auswählen'); ?></a>
                </label>
            </div>
            <div style="display: inline-block; width: 10%; text-align: center">
                <br>
                <br>
                <br>
                <input type="image" id="search_persons_add" class="stay_on_dialog" name="search_persons_add" src="<?= Assets::image_path('icons/16/blue/arr_2right.png')?>" aria-label="<?= _('In den Suchergebnissen markierte Bereiche der Ankündigung hinzufügen') ?>">
                <br><br>
                <input type="image" id="search_persons_remove" class="stay_on_dialog" name="search_persons_remove" src="<?= Assets::image_path('icons/16/blue/arr_2left.png')?>" aria-label="<?= _('Bei den bereits ausgewählten Bereichen die markierten Bereiche entfernen') ?>">
            </div>
            <div style="display: inline-block; float: right; width: 45%">
                <label>
                <div>
                    <? $selectedCount = count($selectedPersons);
                    if ($selectedCount == 0) : ?>
                        <?=_('Noch haben Sie niemanden ausgewählt.')?>
                    <? elseif ($selectedCount == 1) : ?>
                        <?=_('Sie haben 1 Person ausgewählt')?>
                    <? else : ?>
                        <?=sprintf(_('Sie haben %s Personen ausgewählt'), $selectedCount)?>
                    <? endif ?>
                </div>
                <select id="search_persons_selected" name="search_persons_selected[]" style="minWidth: 200px; width: 100%; height: 116px" size="7" multiple
                        aria-label="<?= _('Bereiche, in denen die Ankündigung angezeigt wird') ?>"
                        ondblclick="jQuery('#search_persons_remove').click()">
                    <? foreach ($selectedPersons as $user): ?>
                        <option value="<?= $user->id ?>" <?= in_array($user->id, $selectedMembers) ? "selected" : "" ; ?>><?= htmlReady($user->getFullName('full_rev')) ?> - <?= htmlReady($user->perms) ?> (<?= htmlReady($user->username)?>)</option>
                    <? endforeach; ?>
                </select>
                <a href="javascript:deselectAll();"><?= _('Alle entfernen'); ?></a>
                </label>
            </div>
        </div>
    </div>
    <br>
    <?= \Studip\Button::create(_('Speichern'), 'save') ?>
    <?= \Studip\Button::create(_('Abbrechen'), 'abort') ?>
</form>
