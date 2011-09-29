<h3><?=_("Raumanfrage erstellen")?></h3>
<form method="POST" name="new_room_request" action="<?=$this->controller->link_for('edit/' . $course_id)?>">
<?= CSRFProtection::tokenTag() ?>
<? if (count($options)) : ?>
<div>
    <label for="new_room_request_type"><?= _("Art der Raumanfrage:")?></label>
    <select id="new_room_request_type" name="new_room_request_type">
        <? foreach ($options as $one) : ?>
        <option value="<?= $one['value']?>">
        <?= htmlReady($one['name'])?>
        </option>
    <? endforeach ?>
    </select>
</div>
<div style="text-align:center;padding:5px;">
    <?= makeButton('erstellen', 'input')?>
</div>
<? else :?>
    <?= MessageBox::info(_("In dieser Veranstaltung können keine weiteren Raumanfragen gestellt werden.")) ?>
<? endif ?>
</form>
<?
$infobox_content = array(
    array(
        'kategorie' => _('Raumanfragen und gewünschte Raumeigenschaften'),
        'eintrag'   => array(
    array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier können Sie festlegen, welche Art von Raumanfrage Sie erstellen möchten.")
    ),
    array(
            'icon' => 'icons/16/black/minus.png',
            'text' => '<a href="'.$controller->link_for('index/'.$course_id).'">'._('Anlegen abbrechen').'</a>'
        ))
    ),
);
$infobox = array('picture' => 'infobox/board2.jpg', 'content' => $infobox_content);
