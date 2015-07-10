<div class="<?= $class ?>">
    <input type="hidden" name="<?= $inputname ?>[<?= $user->id ?>]" value="1" id="<?= $user->id ?>"/>
    <?= Avatar::getAvatar($user->id)->getImageTag(Avatar::SMALL) ?>
    <?= htmlReady($user->getFullname('full_rev')) ?> (<?= htmlReady($user->username) ?>)
    <?= Assets::input('icons/blue/trash.svg', array(
        'name' => 'remove_'.$class.'['.$user->id.']',
        'onclick' => "return STUDIP.CourseWizard.removePerson('".$user->id."')")) ?>
</div>
