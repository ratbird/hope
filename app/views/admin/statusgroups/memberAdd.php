<? if (isset($title)): ?>
    <h1><?=$title?></h1>
<? endif; ?>
<form method="POST" action="<?= $controller->url_for("admin/statusgroups/memberAdd/{$group->id}") ?>">
    <input type="hidden" name="search_persons_selectable_hidden" value="<?=htmlReady(serialize($selectablePersonsHidden));?>">
    <input type="hidden" name="search_persons_selected_hidden" value="<?=htmlReady(serialize($selectedPersonsHidden))?>">
    <input type="hidden" name="last_search_hidden" value="<?= htmlReady($search)?>">
    <input type="hidden" name="last_search_preset" value="<?= htmlReady($searchPreset)?>">
    <input type="hidden" name="not_first_call" value="true">
    <?= CSRFProtection::tokenTag() ?>

    <!-- neue Suche -->
    <div id="search_persons" style="width: 800px;">
        <label>
            <input name="freesearch" type="text" placeholder="<?=_('Suchen')?>"
                   aria-label="<?= _('Suchbegriff') ?>" style="width: 45%" value="<?= htmlReady($search) ?>">
            <input type="image" name="submit_search" class="stay_on_dialog" src="<?= Assets::image_path('icons/16/blue/search.png')?>"
                   aria-label="<?= _('Suche starten') ?>">
        </label>
        <br><br>
         <select name="search_preset" aria-label="<?= _('Vorauswahl bestimmter Bereiche, alternativ zur Suche') ?>"
                onchange="jQuery('input[name=submit_search_preset]').click()" style="width: 45%">
            <option><?=_('--- Suchvorlagen ---')?></option>
            <option value="inst" <?= $searchPreset == "inst" ? "selected" : ""; ?>>
                <?= _("aktuelle Einrichtung"); ?>
            </option>
        </select>
        <input type="image" name="submit_search_preset" class="stay_on_dialog" src="<?= Assets::image_path('icons/16/blue/accept.png')?>" aria-label="<?= _('Vorauswahl anwenden') ?>">

        <div id="search_persons_content">
            <div style="display: inline-block; float: left; width: 44%; height: 100%">
                <label><?=_('Suchergebnis')?><br>
                <select id="search_persons_selectable" name="search_persons_selectable[]" style="minWidth: 200px; width: 100%; height: 116px" style="height: 16px" multiple
                        aria-label="<?= _('Gefundene Personen, die der Gruppe hinzugef�gt werden k�nnen') ?>"
                        ondblclick="jQuery('#search_persons_add').click()">
                        <? foreach ($selectablePersons as $person): ?>
                            <option value="<?= $person->id ?>"><?= htmlReady($person->nachname . ', ' . $person->vorname) ?> - <?= htmlReady($person->perms) ?> (<?= htmlReady($person->username)?>)</option>
                        <? endforeach; ?>
                </select>
                <a href="javascript:STUDIP.statusgroups.addMembers.selectAll();" id="search_persons_select_all" style="display: none;"><?= _('Alle hinzuf�gen'); ?></a>
                </label>
            </div>
            <div style="display: inline-block; width: 10%; text-align: center">
                <br>
                <br>
                <br>
                <input type="image" id="search_persons_add" class="stay_on_dialog" name="search_persons_add" src="<?= Assets::image_path('icons/16/blue/arr_2right.png')?>" aria-label="<?= _('In den Suchergebnissen markierte Bereiche der Gruppe hinzuf�gen') ?>">
                <br><br>
                <input type="image" id="search_persons_remove" class="stay_on_dialog" name="search_persons_remove" src="<?= Assets::image_path('icons/16/blue/arr_2left.png')?>" aria-label="<?= _('Bei den bereits ausgew�hlten Personen die markierten Personen entfernen') ?>">
            </div>
            <div style="display: inline-block; float: right; width: 44%">
                <label>
                <div>
                    <? $selectedCount = count($selectedPersons);
                    if ($selectedCount == 0) : ?>
                        <?=_('Niemand ist in der Gruppe eingetragen.')?>
                    <? elseif ($selectedCount == 1) : ?>
                        <?=_('In der Gruppe ist eine Person eingetragen.')?>
                    <? else : ?>
                        <?=sprintf(_('In der Gruppe sind %s Personen eingetragen.'), $selectedCount)?>
                    <? endif ?>
                </div>
                <select id="search_persons_selected" name="search_persons_selected[]" style="minWidth: 200px; width: 100%; height: 116px" size="7" multiple
                        aria-label="<?= _('Personen, die in die Gruppe eingetragen werden') ?>"
                        ondblclick="jQuery('#search_persons_remove').click()">
                    <? foreach ($selectedPersons as $user): ?>
                        <option value="<?= $user->id ?>"><?= htmlReady($user->nachname . ', ' . $user->vorname) ?> - <?= htmlReady($user->perms) ?> (<?= htmlReady($user->username)?>)</option>
                    <? endforeach; ?>
                </select>
                <a href="javascript:STUDIP.statusgroups.addMembers.deselectAll();" id="search_persons_deselect_all" style="display: none;"><?= _('Alle austragen'); ?></a>
                </label>
            </div><br>
        </div>
    </div>
    <br>
    <?= \Studip\Button::create(_('Speichern'), 'save') ?>
    <?= \Studip\Button::create(_('Abbrechen'), 'abort') ?>
</form>
<script>STUDIP.statusgroups.addMembers.init();</script>
