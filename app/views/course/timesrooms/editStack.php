<form method="post" action="<?= $controller->url_for('course/timesrooms/saveStack/' . $cycle_id, $editParams) ?>" class="studip-form"
      data-dialog="size=big">
    <input type="hidden" name="method" value="edit" />

    <section>
        <label for="related_persons"><?= _('Durchführende Dozenten') ?></label>

        <select name="related_persons_action"
                aria-label="<?= _('Wählen Sie aus, ob Dozenten den ausgewählten regelmäßigen Terminen hinzugefügt, von diesen entfernt oder für diese Termine definiert werden sollen.') ?>">
            <option value="">-- <?= _('Aktion auswählen') ?> --</option>
            <option value="add"
                    title="<?= _('Die ausgewählten Dozenten werden den ausgewählten Terminen hinzugefügt. Die zuvor schon durchführenden Dozenten bleiben aber weiterhin zusätzlich eingetragen.') ?>">
                ... <?= _('hinzufügen') ?></option>
            <option value="delete"
                    title="<?= _('Die ausgewählten Dozenten leiten nicht die ausgewählten Termine. Andere Dozenten bleiben bestehen.') ?>">
                ... <?= _('entfernen') ?></option>
        </select>

        <select name="related_persons[]" id="related_persons" multiple class="multiple">
            <? foreach ($teachers as $teacher) : ?>
                <option value="<?= htmlReady($teacher['user_id']) ?>"><?= htmlReady($teacher['fullname']) ?></option>
            <? endforeach ?>
        </select>
    </section>

    <? if (count($gruppen)) : ?>
        <section>
            <label for="related_groups">
                <?= _('Betrifft die Gruppen') ?>
            </label>
            <select name="related_groups_action"
                    aria-label="<?= _('Wählen Sie aus, ob Dozenten den ausgewählten regelmäßigen Terminen hinzugefügt, von diesen entfernt oder für diese Termine definiert werden sollen.') ?>">
                <option value="">-- <?= _('Aktion auswählen') ?> --</option>
                <option value="add"
                        title="<?= _('Die ausgewählten Dozenten werden den ausgewählten Terminen hinzugefügt. Die zuvor schon durchführenden Dozenten bleiben aber weiterhin zusätzlich eingetragen.') ?>">
                    ... <?= _('hinzufügen') ?></option>
                <option value="delete"
                        title="<?= _("Die ausgewählten Dozenten leiten nicht die ausgewählten Termine. Andere Dozenten bleiben bestehen.") ?>">
                    ... <?= _('entfernen') ?></option>
            </select>
            <br>

            <select id="related_groups" name="related_groups[]" multiple class="multiple"
                    aria-label="<?= _('Wählen Sie die Gruppen aus, für die die Termine gelten. Ist keine Gruppe ausgewählt, gilt der Termin für alle Nutzer und Gruppen der Veranstaltung.') ?>">
                <? foreach ($gruppen as $gruppe) : ?>
                    <option value="<?= htmlReady($gruppe->statusgruppe_id) ?>"><?= htmlReady($gruppe->name) ?></option>
                <? endforeach ?>
            </select>
            <br>
        </section>
    <? endif ?>


    <p><strong><?= _('Raumangaben') ?></strong></p>
    <? if (Config::get()->RESOURCES_ENABLE && $resList->numberOfRooms()) : ?>
        <? $resList->reset() ?>
        <section>
            <label class="horizontal">
                <input style="display: inline" type="radio" name="action" value="room" checked="checked" />
            </label>

            <select name="room" onFocus="jQuery('input[type=radio][name=action][value=room]').attr('checked', 'checked')">
                <option value="">-- <?= _('Raum auswählen') ?> --</option>
                <? while ($res = $resList->next()) : ?>
                    <option value="<?= $res['resource_id'] ?>">
                        <?= my_substr(htmlReady($res["name"]), 0, 30) ?> <?= $seats[$res['resource_id']] ? '(' . $seats[$res['resource_id']] . ' ' . _('Sitzplätze') . ')' : '' ?>
                    </option>
                <? endwhile; ?>
            </select>

            <?= Icon::create('room-clear', 'inactive', ['title' => _("Nur buchbare Räume anzeigen")])->asImg(16, ["class" => 'bookable_rooms_action', "data-name" => 'bulk_action']) ?>

        </section>

        <? $placerholder = _('Freie Ortsangabe (keine Raumbuchung):') ?>
    <? else : ?>

        <? $placerholder = _('Freie Ortsangabe:') ?>
    <? endif ?>

    <section>
        <label class="horizontal">
            <input type="radio" name="action" value="freetext" style="display: inline" />
        </label>
        <input type="text" name="freeRoomText" maxlength="255" value="<?= $tpl['freeRoomText'] ?>"
               placeholder="<?= $placerholder ?>"
               onFocus="jQuery('input[type=radio][name=action][value=freetext]').attr('checked', 'checked')" />
    </section>
    <? if (Config::get()->RESOURCES_ENABLE) : ?>
        <section>
            <label class="horizontal">
                <input type="radio" name="action" value="noroom" style="display:inline" />
                <?= _('Kein Raum') ?>
            </label>
        </section>
    <? endif ?>

    <section>
        <label class="horizontal">
            <input type="radio" style="display: inline" name="action" value="nochange" checked="checked" />
            <?= _('Keine Änderungen an den Raumangaben vornehmen') ?>
        </label>
    </section>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept(_('Änderungen speichern'), 'save') ?>
        <? if (Request::get('fromDialog') == 'true') : ?>
            <?= Studip\LinkButton::create(_('Zurück zur Übersicht'), $controller->url_for('course/timesrooms/index'), array('data-dialog' => 'size=big')) ?>
        <? endif ?>
    </footer>
</form>
