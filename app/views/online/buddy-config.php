<? use Studip\Button; ?>

<form action="<?= $controller->url_for('online/buddy/config') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <label>
        <input type="checkbox" name="show_only_buddys"
               value="1" <? if ($show_only_buddys) echo 'checked'; ?>>
        <?= _('Nur Buddies in der �bersicht der aktiven Benutzer anzeigen') ?>
    </label><br>

    <label>
        <input type="checkbox" name="show_groups"
               value="1" <? if ($show_groups) echo 'checked'; ?>>
        <?= _('Kontaktgruppen bei der Buddy-Darstellung ber�cksichtigen') ?>
    </label><br>
    
    <?= Button::createAccept(_('�bernehmen'), 'store',
                             array('title' => _('�nderungen �bernehmen'))) ?>
</form>
