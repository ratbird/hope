<? use Studip\Button, Studip\LinkButton; ?>

<? 
    $printVisibility = function ($visibility) {
        $mapping = array(
            VISIBILITY_ME      => _('sichtbar für mich selbst'),
            VISIBILITY_BUDDIES => _('sichtbar für meine Buddies'),
            VISIBILITY_DOMAIN  => _('sichtbar für meine Nutzerdomäne'),
            VISIBILITY_STUDIP  => _('sichtbar für alle Stud.IP-Nutzer'),
            VISIBILITY_EXTERN  => _('sichtbar auf externen Seiten'),
        );
        return $mapping[$visibility] ?: _('Unbekannt');
    }
?>

<? if ($verify && $verify['action'] === 'delete'): ?>
<?= $controller->verifyDialog(
        sprintf(_('Möchten Sie wirklich die Kategorie "%s" löschen?'), Kategorie::find($verify['id'])->name),
        array('settings/categories/delete', $verify['id'], true),
        array('settings/categories')
    ) ?>
<? endif; ?>

<? if (count($categories) === 0): ?>
<p class="info"><?= _('Es existieren zur Zeit keine eigenen Kategorien.') ?></p>
<? else: ?>
<form action="<?= $controller->url_for('settings/categories/store') ?>" method="post" name="main_content">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="zebra-big settings" style="width: 100%">
        <colgroup>
            <col width="100px">
            <col>
            <col width="200px">
            <col width="100px">
        </colgroup>
    <? foreach ($categories as $index => $category): ?>
        <tbody style="vertical-align: top;">
            <tr>
                <td>
                    <label for="name<?= $index ?>"><?= _('Name') ?>:</label>
                </td>
                <td>
                    <input required type="text" name="categories[<?= $category->id ?>][name]" id="name<?= $index ?>"
                           aria-label="<?= _('Name der Kategorie') ?>" style="width: 100%"
                           value="<?= htmlReady($category->name) ?>">
                </td>
                <td style="text-align: right;">
                    <?= $printVisibility($visibilities[$category->id]) ?>
                </td>
                <td rowspan="2" style="text-align: right; vertical-align: middle;">
                <? if ($index > 0): ?>
                    <a href="<?= $controller->url_for('settings/categories/swap', $category->id, $last->id) ?>">
                        <?= Assets::img('icons/16/yellow/arr_2up', array('class' => 'text-top', 'title' =>_('Kategorie nach oben verschieben'))) ?>
                    </a>
                <? else: ?>
                    <?= Assets::img('icons/16/grey/arr_2up', array('class' => 'text-top')) ?>
                <? endif; ?>

                <? if ($index < $count - 1): ?>
                    <a href="<?= $controller->url_for('settings/categories/swap', $category->id, $categories[$index + 1]->id) ?>">
                        <?= Assets::img('icons/16/yellow/arr_2down', array('class' => 'text-top', 'title' =>_('Kategorie nach unten verschieben'))) ?>
                    </a>
                <? else: ?>
                    <?= Assets::img('icons/16/grey/arr_2down', array('class' => 'text-top')) ?>
                <? endif; ?>

                    <a href="<?= $controller->url_for('settings/categories/delete', $category->id) ?>">
                        <?= Assets::img('icons/16/blue/trash', array('class' => 'text-top', 'title' => _('Kategorie löschen'))) ?>
                    </a>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="content<?= $index ?>"><?= _('Inhalt') ?>:</label>
                </td>
                <td colspan="2">
                    <textarea id="content<?= $index ?>" name="categories[<?= $category->id ?>][content]"
                              class="resizable add_toolbar" style="width: 100%; height: 200px;"
                              aria-label="<?= _('Inhalt der Kategorie:') ?>"
                    ><?= htmlReady($category->content) ?></textarea>
                </td>
            </tr>
        </tbody>
    <? $last = $category; 
       endforeach; ?>
    <? if ($hidden_count > 0): ?>
        <tbody>
            <tr>
                <td colspan="4">
                    <?= sprintf(ngettext(_('Es existiert zusätzlich eine Kategorie, die Sie nicht einsehen und bearbeiten können.'),
                                         _('Es existiereren zusätzlich %s Kategorien, die Sie nicht einsehen und bearbeiten können.'),
                                         $hidden_count), $hidden_count) ?>
                </td>
            </tr>
        </tbody>
    <? endif; ?>
        <tfoot>
            <tr>
                <td colspan="4">
                    <?= Button::create(_('Übernehmen'), 'store') ?>
                </td>
        </tfoot>
    </table>
</form>
<? endif; ?>
