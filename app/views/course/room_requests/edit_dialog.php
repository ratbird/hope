<?
$buttons = '<span>' . makeButton('uebernehmen','input',_("Änderungen speichern"),'save') . '</span>';
$buttons .= '<span style="padding-left:1em">
             <a href="#" onClick="STUDIP.RoomRequestDialog.dialog.dialog(\'close\');return false;">'
             . makeButton('abbrechen','img',_("Abbrechen")) . '</a></span>';
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
