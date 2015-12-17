<? if (!Request::isXhr()) : ?>
    <h1><?= _("Raumanfrage erstellen") ?></h1>
<? endif ?>
<form method="POST" class="studip-form" name="new_room_request"
      action="<?= $this->controller->link_for('edit/' . $course_id, $url_params) ?>" <?= Request::isXhr() ? 'data-dialog="size=big"' : '' ?>>
    <?= CSRFProtection::tokenTag() ?>
    <? if (count($options)) : ?>
        <section>
            <label for="new_room_request_type"><?= _("Art der Raumanfrage:") ?></label>
            <select id="new_room_request_type" name="new_room_request_type">
                <? foreach ($options as $one) : ?>
                    <option value="<?= $one['value'] ?>">
                        <?= htmlReady($one['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </section>
        <div class="text-center" data-dialog-button>

        </div>
    <? else : ?>
        <?= MessageBox::info(_("In dieser Veranstaltung können keine weiteren Raumanfragen gestellt werden.")) ?>
    <? endif ?>
    <div data-dialog-button>
        <? if (count($options)) : ?>
            <?= Studip\Button::create(_('Erstellen')) ?>
            <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->link_for('index/' . $course_id)) ?>
        <? else : ?>
            <?= Studip\LinkButton::create(_('Zurück zur Übersicht'), $controller->link_for('index/' . $course_id), array('data-dialog' => 'size=big')) ?>
        <? endif ?>
    </div>
</form>
