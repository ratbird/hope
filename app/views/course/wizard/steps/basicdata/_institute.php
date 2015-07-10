<div class="institute">
    <input type="hidden" name="participating[<?= $inst->id ?>]" value="1" id="<?= $inst->id ?>"/>
    <?= htmlReady($inst->name) ?>
    <?= Assets::input('icons/blue/trash.svg', array(
        'name' => 'remove_participating['.$inst->id.']',
        'onclick' => "return STUDIP.CourseWizard.removeParticipatingInst('".$inst->id."')")) ?>
</div>
