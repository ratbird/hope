<? use Studip\Button, Studip\LinkButton; ?>

<? if (!Request::isXhr()) : ?>
    <h1><?= _('Raumanfragen bearbeiten / erstellen') ?></h1>
<? endif ?>
<? if ($request) : ?>
    <h2><?= htmlready($request->getTypeExplained()) ?></h2>
<? endif ?>
<form method="post" name="room_request"
      action="<?= $this->controller->link_for('edit/' . $course_id, $params) ?>"
    <?= Request::isXhr() ? 'data-dialog="size=big"' : '' ?> class="studip-form">
    <?= CSRFProtection::tokenTag() ?>
    <?= $this->render_partial('course/room_requests/_form.php'); ?>

    <div data-dialog-button>
        <?= Button::createAccept(_('Speichern und zur�ck zur �bersicht'), 'save_close', array('title' => _('Speichern und zur�ck zur �bersicht'))) ?>
        <?= Button::create(_('�bernehmen'), 'save', array('title' => _('�nderungen speichern'))) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->link_for('index/' . $course_id), array('title' => _('Abbrechen'))) ?>
    </div>
</form>
