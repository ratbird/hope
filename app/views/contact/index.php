<table class="default">
    <caption>
        <?= htmlReady($title) ?>
        <span class='actions'>
            <?= $multiPerson ?>
            <? if ($filter): ?>
                <a href="<?= $controller->url_for('contact/editGroup/'.$filter) ?>" data-dialog="size=auto" title="<?= _('Gruppe bearbeiten') ?>">
                    <?= Assets::img('icons/16/blue/edit.svg') ?>
                </a>
                <a href="<?= $controller->url_for('contact/deleteGroup/'.$filter) ?>" data-dialog="size=auto"  title="<?= _('Gruppe löschen') ?>">
                    <?= Assets::img('icons/16/blue/trash.svg') ?>
                </a>
            <? endif; ?>
        </span>
    </caption>
    <thead>
        <tr>
            <th>
                <?= _('Name') ?>
            </th>
            <th>
                <?= _('Stud.IP') ?>
            </th>
            <th>
                <?= _('E-Mail') ?>
            </th>
            <th class="actions">
                <?= _('Aktionen') ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($contacts as $header => $contactgroup): ?>
            <tr id="letter_<?= $header ?>">
                <th colspan="4">
                    <?= $header ?>
                </th>
            </tr>
            <? foreach ($contactgroup as $contact): ?>
                <tr id="contact_<?= $contact->id ?>">
                    <td>
                        <?= ObjectdisplayHelper::avatarlink($contact) ?>
                    </td>
                    <td>
                        <a data-dialog="button" href="<?= URLHelper::getLink('dispatch.php/messages/write', array('rec_uname' => $contact->username)) ?>">
                            <?= htmlReady($contact->username) ?>
                        </a>
                    </td>
                    <td>
                        <a href="mailto:<?= htmlReady($contact->email) ?>">
                            <?= htmlReady($contact->email) ?>
                        </a>
                    </td>
                    <td class="actions">
                        <a title="<?= $filter ? _("Kontakt aus Gruppe entfernen") : _("Kontakt entfernen") ?>" href="<?= $controller->url_for('contact/remove/'.$filter, array('user' => $contact->username)) ?>">
                            <?= Assets::img('icons/16/blue/remove/person.png') ?>
                        </a>
                        <a title="<?= _("vCard herunterladen") ?>" href="<?= $controller->url_for('contact/vcard', array('user[]' => $contact->username)) ?>">
                            <?= Assets::img('icons/16/blue/vcard.png') ?>
                        </a>
                    </td>
                </tr>
            <? endforeach; ?>
        <? endforeach; ?>
    </tbody>
</table>