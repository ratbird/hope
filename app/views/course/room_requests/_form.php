<input type="hidden" name="room_request_form" value="1">
<? if (isset($new_room_request_type)) : ?>
    <input type="hidden" name="new_room_request_type" value="<?= $new_room_request_type ?>">
<? endif ?>
<?= MessageBox::info(_('Sie haben die M�glichkeit, gew�nschte Raumeigenschaften sowie einen konkreten Raum anzugeben.
        Diese Raumw�nsche werden von der zentralen Raumverwaltung bearbeitet.'),
    array(_('<b>Achtung:</b> Um sp�ter einen passenden Raum f�r Ihre Veranstaltung zu bekommen,
        geben Sie bitte <span style="text-decoration: underline">immer</span> die gew�nschten Eigenschaften mit an!')
    )) ?>

<section class="times-rooms-grid ">
    <section>
        <h2><?= _('Art des Wunsches') ?></h2>
        <article>
            <?= htmlready($request->getTypeExplained(), 1, 1); ?>
        </article>
    </section>
    <section>
        <h2><?= _('Bearbeitungsstatus') ?></h2>
        <article>
            <? if ($request->isNew()) : ?>
                <?= _("Diese Anfrage ist noch nicht gespeichert") ?>
            <? else : ?>
                <?= htmlReady($request->getStatusExplained()); ?>
            <? endif ?>
        </article>
    </section>
</section>


<div style="clear: both"></div>

<?
if ($request_resource_id = $request->getResourceId()) :
    $resObject = ResourceObject::Factory($request_resource_id);
?>
    <section style="margin: 20px 0;">
        <h2><?= _('Gew�nschter Raum') ?></h2>

        <p>
            <strong><?= htmlReady($resObject->getName()) ?></strong>
        </p>

        <p><?= _("verantwortlich:") ?>
            <a href="<?= $resObject->getOwnerLink() ?>"><?= htmlReady($resObject->getOwnerName()) ?></a>
            <?= Icon::create('trash', 'clickable', ['title' => _('den ausgew�hlten Raum l�schen')])->asInput(null, ["type" => "image", "style" => "vertical-align:middle", "name" => "reset_resource_id"]) ?>
            <?= tooltipIcon(_('Der ausgew�hlte Raum bietet folgende der w�nschbaren Eigenschaften:') . " \n" . $resObject->getPlainProperties(true)) ?>
        </p>
        <input type="hidden" name="selected_room" value="<?= htmlready($request_resource_id) ?>">
    </section>
<? endif ?>


<section class="times-rooms-grid ">
    <section>
        <h2>
            <?= _("Raumeigenschaften angeben:") ?>
        </h2>
        <? if ($request->getCategoryId()) : ?>
            <? if (count($room_categories)) : ?>
                <label for="select_room_type">
                    <?= _('Gew�hlter Raumtyp') ?>
                </label>
                <select name="select_room_type" id="select_room_type">
                    <? foreach ($room_categories as $rc) : ?>
                        <?= sprintf('<option value="%s" %s>%s </option>',
                            $rc["category_id"],
                            ($request->category_id == $rc["category_id"]) ? "selected" : "",
                            htmlReady($rc["name"])
                        ) ?>
                    <? endforeach ?>
                </select>
                <?= Assets::input("icons/blue/accept", array('type'  => "image",
                                                             'style' => "vertical-align:middle",
                                                             'name'  => "send_room_type",
                                                             'value' => _("Raumtyp ausw�hlen"),
                                                             'title' => _('Raumtyp ausw�hlen')
                )) ?>
                <?= Icon::create('refresh', 'clickable', ['title' => _('alle Angaben zur�cksetzen')])->asInput(null, ["type" => "image", "style" => "vertical-align:middle", "name" => "reset_room_type"]) ?>
            <? endif ?>
            <? $props = $request->getAvailableProperties() ?>
            <? if (!empty($props)) : ?>
                <h4><?= _('Folgende Eigenschaften sind w�nschbar:') ?></h4>
                <? foreach ($props as $index => $prop) : ?>
                    <section>
                        <label for="<?= $prop['type'] ?>_<?= $index ?>">
                            <?= htmlReady($prop["name"]) ?>
                        </label>

                        <? if ($prop['type'] == 'bool') : ?>
                            <input type="checkbox" id="bool_<?= $index ?>"
                                   name="request_property_val[<?= $prop["property_id"] ?>]"
                                <?= $request->getPropertyState($prop["property_id"]) ? "checked" : "" ?>>
                            <label for="bool_<?= $index ?>" class="horizontal">
                                <?= htmlReady($prop["options"]) ?>
                            </label>
                        <? elseif ($prop['type'] == 'num'): ?>
                            <? if ($prop['system'] == 2) : ?>
                                <input type="text" id="num_<?= $index ?>"
                                       name="request_property_val[<?= $prop["property_id"] ?>]"
                                       value="<?= htmlReady($request->getPropertyState($prop["property_id"])) ?>">
                                <? if ($admission_turnout) : ?>
                                    <br><input id="seats_are_admission_turnout" type="checkbox"
                                               name="seats_are_admission_turnout"
                                        <?= ($request->getPropertyState($prop["property_id"]) == $admission_turnout && $admission_turnout > 0) ? "checked" : "" ?>>
                                    <label for="seats_are_admission_turnout"
                                           class="horizontal"><?= _('max. Teilnehmeranzahl �bernehmen') ?></label>
                                <? endif ?>
                            <? else : ?>
                                <input id="num_<?= $index ?>" type="text"
                                       name="request_property_val[<?= $prop["property_id"] ?>]"
                                       value="<?= htmlReady($request->getPropertyState($prop["property_id"])) ?>">
                            <? endif ?>
                        <? elseif ($prop['type'] == 'text') : ?>
                            <textarea id="text_<?= $index ?>" name="request_property_val[<?= $prop["property_id"] ?>]"
                                      cols="30"
                                      rows="2"><?= htmlReady($request->getPropertyState($prop["property_id"])) ?></textarea>
                        <? else : ?>
                            <? $options = explode(";", $prop["options"]); ?>
                            <select id="select_<?= $index ?>" name="request_property_val[<?= $prop["property_id"] ?>]">
                                <option value="">--</option>
                                <? foreach ($options as $a) : ?>
                                    <option <?= ($request->getPropertyState($prop["property_id"]) == $a) ? "selected" : "" ?>
                                        value="<?= $a ?>"><?= htmlReady($a) ?></option>
                                <? endforeach ?>
                            </select>
                        <? endif ?>
                    </section>
                <? endforeach ?>
            <? endif ?>
        <? else : ?>
            <label for="select_room_type">
                <?= _('Bitte geben Sie zun�chst einen Raumtyp an, der f�r Sie am besten geeignet ist') ?>
            </label>
            <select name="select_room_type" id="select_room_type">
                <option value=""><?= _('bitte ausw�hlen') ?></option>
                <? foreach ($room_categories as $rc) : ?>
                    <option value="<?= $rc["category_id"] ?>"><?= htmlReady($rc["name"]) ?></option>
                <? endforeach ?>
            </select>
            <?= Assets::input("icons/blue/accept", array('type'  => "image",
                                                         'style' => "vertical-align:middle",
                                                         'name'  => "send_room_type",
                                                         'value' => _("Raumtyp ausw�hlen"),
                                                         'title' => _('Raumtyp ausw�hlen')
            )) ?>
        <? endif ?>

        <? if ($request->category_id) : ?>
            <section>
                <label class="horizontal" for="search_rooms">
                    <?= _('passende R�ume suchen') ?>
                </label>
                <?= Assets::input("icons/yellow/arr_2right", array('type'  => "image",
                                                                   'class' => "middle",
                                                                   'search_rooms',
                                                                   'name'  => "search_properties",
                                                                   'title' => _('passende R�ume suchen')
                )) ?>

            </section>
        <? endif ?>
    </section>
    <section>
        <h2>
            <?= _('Raum suchen') ?>
        </h2>
        <? if (!empty($search_result)) : ?>
            <? if (count($search_result)) : ?>
                <p>
                    <strong><?= sizeof($search_result) ?></strong> <?= (!$search_by_properties ? _("R�ume gefunden:") : _("passende R�ume gefunden.")) ?>
                </p>
            <? endif ?>
            <div class="selectbox">
                <fieldset>
                    <? foreach ($search_result as $key => $val)  : ?>
                        <div>
                            <input type="radio" name="select_room" value="<?= $key ?>">
                            <label class="horizontal">
                                <?= Assets::img('icons/16/' . $val['overlap_status'] . '/radiobutton-checked'); ?>
                                <?= htmlReady(my_substr($val['name'], 0, 50)); ?>
                            </label>
                        </div>
                    <? endforeach ?>
                </fieldset>
            </div>
            <?= Studip\Button::create(_("Raum als Wunschraum ausw�hlen"), 'send_room') ?>
            <?= Studip\Button::create(_("neue Suche starten"), 'reset_room_search') ?>
            <? if ($search_by_properties) : ?>
                <p><strong><?= _('Diese R�ume erf�llen die Wunschkriterien, die Sie links angegeben haben.') ?></strong>
                </p>
            <? endif ?>
        <? else : ?>
            <p><strong><?= _('Keinen') ?></strong> <?= _('Raum gefunden') ?></p>
        <? endif ?>
        <? if (!count($search_result)) : ?>
            <section>
                <label for="search_exp_room">
                    <?= _('Geben Sie zur Suche den Raumnamen ganz oder teilweise ein:'); ?>
                </label>

                <input id="search_exp_room" type="text" size="30" maxlength="255" name="search_exp_room">
                <?= Icon::create('search', 'clickable', ['title' => _('Suche starten')])->asInput(null, ["type" => "image", "class" => "middle", "name" => "search_room"]) ?>
            </section>
        <? endif ?>
    </section>
</section>

<? if ($is_resources_admin) : ?>
    <section>
        <h2><?= _('Benachrichtigungen') ?></h2>

        <p><?= _('Sie k�nnen hier angeben, welche Nutzer bei Ablehnung der Raumanfrage benachrichtigt werden sollen.') ?></p>

        <input type="radio" name="reply_recipients" id="reply_recipients_requester" value="requester" checked>
        <label for="reply_recipients_requester" class="horizontal">
            <?= _('Der Ersteller der Anfrage') ?>
        </label>

        <input type="radio" name="reply_recipients" id="reply_recipients_lecturer"
               value="lecturer" <?= ($request->reply_recipients == 'lecturer' ? 'checked' : '') ?>>
        <label for="reply_recipients_lecturer" class="horizontal">
            <?= _('Der Ersteller der Anfrage und alle Lehrenden der zugeh�rigen Lehrveranstaltung') ?>
        </label>
    </section>
<? endif ?>

<section>
    <h2><?= _('Nachricht an den Raumadministrator') ?></h2>

    <p><?= _('Sie k�nnen hier eine Nachricht an den Raumadministrator verfassen, um weitere W�nsche oder Bemerkungen zur gew�nschten Raumbelegung anzugeben.') ?></p>
        <textarea name="comment" cols="58" rows="4"
                  style="width:90%"><?= htmlReady($request->getComment()); ?></textarea>
</section>

