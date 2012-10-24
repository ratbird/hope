<? use Studip\Button, Studip\LinkButton; ?>

<? if ($verify_action === 'reset'): ?>
<?= $controller->verifyDialog(
        _('Wollen Sie Ihre Foren-Einstellungen wirklich zurücksetzen?'),
        array('settings/forum/reset', true),
        array('settings/forum')
    ) ?>
<? endif; ?>

<form action="<?= $controller->url_for('settings/forum/store') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="zebra-hover settings" id="main_content">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th><?= _('Option') ?></th>
                <th><?= _('Auswahl') ?></th>
            </tr>
        </thead>
        <tbody class="labeled">
            <tr>
                <td>
                    <label for="neuauf"><?= _('Neue Beiträge immer aufgeklappt') ?></label>
                </td>
                <td>
                    <input type="checkbox" name="neuauf" id="neuauf" value="1"
                           <? if ($settings['neuauf']) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="rateallopen"><?= _('Bewertungsbereich bei geöffneten Postings immer anzeigen') ?></label>
                    <dfn>
                        <?= _('Die Aktivierung dieser Einstellung blendet ein Kästchen neben den Forenbeiträgen ein, mit dem Sie Beiträge bewerten können.') ?>
                    </dfn>
                </td>
                <td>
                    <input type="checkbox" name="rateallopen" id="rateallopen" value="TRUE"
                           <? if($settings['rateallopen']) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="showimages"><?= _('Bilder im Bewertungsbereich anzeigen') ?></label>
                </td>
                <td>
                    <input type="checkbox" name="showimages" id="showimages" value="TRUE"
                           <? if ($settings['showimages']) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <?= _('Sortierung der Themenanzeige') ?>
                </td>
                <td>
                    <label>
                        <input type="radio" value="asc" name="sortthemes"
                               <? if ($settings['sortthemes'] == 'asc') echo 'checked'; ?>>
                        <?= _('Erstelldatum des Ordners - neue unten') ?>
                    </label><br>

                    <label>
                        <input type="radio" value="desc" name="sortthemes"
                               <? if ($settings['sortthemes'] == 'desc') echo 'checked'; ?>>
                        <?= _('Erstelldatum des Ordners - neue oben') ?>
                    </label><br>

                    <label>
                        <input type="radio" value="last" name="sortthemes"
                               <?if ($settings['sortthemes'] == 'last') echo 'checked'; ?>>
                        <?= _('Datum des neuesten Beitrags - neue oben') ?>
                    </label><br>
                </td>
            </tr>
            <tr>
                <td>
                    <?= _('Anzeigemodus der Themenanzeige') ?>
                </td>
                <td>
                    <label>
                        <input type="radio" value="tree" name="themeview"
                               <? if ($settings['themeview'] == 'tree') echo 'checked'; ?>>
                        <?= _('Treeview') ?>
                    </label><br>

                    <label>
                        <input type="radio" value="mixed" name="themeview"
                               <? if ($settings['themeview'] == 'mixed') echo 'checked'; ?>>
                        <?= _('Flatview') ?>
                    </label><br>
                </td>
            </tr>
            <tr>
                <td>
                    <?= _('Standardansicht') ?>
                </td>
                <td>
                    <label>
                        <input type="radio" value="theme" name="presetview"
                               <? if (in_array($settings['presetview'], words('tree mixed'))) echo 'checked'; ?>>
                        <?= _('Themenansicht') ?>
                    </label><br>

                    <label>
                        <input type="radio" value="neue" name="presetview"
                            <? if ($settings['presetview'] == 'neue') echo 'checked'; ?>>
                        <?= _('Neue Beiträge') ?>
                    </label><br>

                    <label>
                        <input type="radio" value="flat" name="presetview"
                               <? if ($settings['presetview'] == 'flat') echo 'checked'; ?>>
                        <?= _('Letzte Beiträge') ?>
                    </label><br>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="shrink"><?= _('Alte Beiträge standardmäßig zuklappen nach') ?></label>
                </td>
                <td>
                    <select name="shrink" id="shrink">
                        <option value="0"><?= _('ausgeschaltet') ?></option>
                    <? for ($i = 1; $i < 20; $i += 1): ?>
                        <option value="<?= $i ?>" <? if ($i * 604800 == $settings['shrink']) echo 'selected'; ?>>
                            <?= $i ?> <?= _('Wochen') ?>
                        </option>
                    <? endfor; ?>
                    </select>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">
                    <?= Button::createAccept(_('Übernehmen'), 'store', array('title' => _('Änderungen übernehmen'))) ?>
                    <?= LinkButton::create(_('Zurücksetzen'), $controller->url_for('settings/forum/verify/reset'), array('title' => _('Einstellungen zurücksetzen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>        
