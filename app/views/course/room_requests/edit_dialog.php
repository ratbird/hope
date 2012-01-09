<?
use Studip\Button, Studip\LinkButton;

$buttons = '<span>' . Button::createAccept(_('OK'), 'save_close', array('title' => _('Speichern und schließen'))) . '</span>';
$buttons .= '<span style="padding-left:1em">'
             . LinkButton::createCancel(_('abbrechen'), array('onClick' => 'STUDIP.RoomRequestDialog.dialog.dialog(\'close\');return false;', 'title' => _('Abbrechen und schließen')))
             . '</span>';
$buttons .= '<span style="padding-left:1em">' . Button::create(_('übernehmen'), 'save', array('title' => _('Änderungen speichern'))) . '</span>';
?>
<form method="POST" name="room_request" onSubmit="return false;"
      action="<?=$this->controller->link_for('edit_dialog/' . $course_id,
                 array('request_id' => $request->getId()))?>">
    <?= CSRFProtection::tokenTag() ?>
    <? foreach(PageLayout::getMessages() as $pm) : ?>
        <?= $pm ?>
    <? endforeach; ?>
    <?= $this->render_partial('course/room_requests/_form.php',
            array('submit' => $buttons)); ?>
</form>
