<form name="links_admin_search" action="<?= URLHelper::getLink(Request::path()) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

    <legend>
        <?= _('Bitte wählen Sie die Einrichtung aus, die Sie bearbeiten wollen:') ?>
    </legend>
    <select name="cid" required>
        <option value="">
            -- <?= _('bitte Einrichtung auswählen') ?> --
        </option>
    <? foreach ($institutes as $institute): ?>
        <option value="<?= htmlReady($institute['Institut_id']) ?>"
                style="<?= $institute['is_fak'] ? 'font-weight:bold' : 'text-indent: 2ex' ?>">
            <?= htmlReady(my_substr($institute['Name'],0,80)) ?>
        </option>
    <? endforeach; ?>
    </select>
    
    <?= Studip\Button::create(_('Einrichtung auswählen')) ?>
</form>
