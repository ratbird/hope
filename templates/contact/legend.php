<div class="contact-legend">
    <?= _('Bedienung:') ?>
    <ul>
        <li>
            <?= Assets::img('icons/16/blue/mail') ?>
            <?= _('Nachricht an Kontakt') ?>
        </li>
    <? if ($open): ?>
        <li>
            <?= Assets::img('icons/16/blue/arr_1up') ?>
            <?= _('Kontakt zuklappen') ?>
        </li>
        <li>
            <?= Assets::img('icons/16/blue/person') ?>
            <?= _('Buddystatus') ?>
        </li>
        <li>
            <?= Assets::img('icons/16/blue/edit') ?>
            <?= _('Eigene Rubriken') ?>
        </li>
        <li>
            <?= Assets::img('icons/16/blue/trash') ?>
            <?= _('Kontakt löschen') ?>
        </li>
    <? else: ?>
        <li>
            <?= Assets::img('icons/16/blue/arr_1down') ?>
            <?= _('Kontakt aufklappen') ?>
        </li>
    <? endif; ?>

    <? if ($open || $contact['view'] == 'gruppen'): ?>
        <li>
            <?= Assets::img('icons/16/blue/vcard') ?>
            <?= _('als vCard exportieren') ?>
        </li>
    <? endif; ?>
    </ul>
</div>
