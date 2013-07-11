<? use Studip\Button; ?>

<ul class="online-config">
    <li>
        <a href="<?= $controller->url_for('online/config/show_buddies/' . get_ticket()) ?>">
            <?= Assets::img('icons/16/blue/checkbox-' . ($show_only_buddys ? 'checked' : 'unchecked'),
                            array('class' => 'text-top')) ?>
        </a>
        <span><?= _('Nur Buddies in der Übersicht der aktiven Benutzer anzeigen') ?></span>
    </li>
    <li>
        <a href="<?= $controller->url_for('online/config/show_groups/' . get_ticket()) ?>">
            <?= Assets::img('icons/16/blue/checkbox-' . ($show_groups ? 'checked' : 'unchecked'),
                            array('class' => 'text-top')) ?>
        </a>
        <span><?= _('Kontaktgruppen bei der Buddy-Darstellung berücksichtigen') ?></span>
    </li>
</ul>
