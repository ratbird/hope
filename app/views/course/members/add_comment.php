<? use Studip\Button, Studip\LinkButton;?>

<?= $xhr ? '' : '<h1>' . _("Bemerkung hinzufügen") . '</h1>'; ?>

<form action="<?=$controller->url_for(sprintf('course/members/set_comment/%s', $user->user_id))?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <textarea style="width: 100%" name="comment" cols="60" aria-label="<?=_('Bemerkung')?>" rows="8" id="comment"><?=htmlReady($comment)?></textarea>
    <?= Button::createAccept(_('Speichern'), 'save'); ?>
    <?= Button::createCancel(_('Abbrechen')); ?>
</form>
