<? use Studip\Button; ?>

<form action="<?= $controller->url_for('online/buddy/config') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <label>
        <input type="checkbox" name="show_only_buddys"
               value="1" <? if ($show_only_buddys) echo 'checked'; ?>>
        <?= _('Nur Buddies in der Übersicht der aktiven Benutzer anzeigen') ?>
    </label><br>

    <label>
        <input type="checkbox" name="show_groups"
               value="1" <? if ($show_groups) echo 'checked'; ?>>
        <?= _('Kontaktgruppen bei der Buddy-Darstellung berücksichtigen') ?>
    </label><br>
    
    <?= Button::createAccept(_('Übernehmen'), 'store',
                             array('title' => _('Änderungen übernehmen'))) ?>
</form>
