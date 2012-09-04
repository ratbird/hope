<? use Studip\Button; ?>

<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td class="blank">&nbsp;</td>
    </tr>
    <tr>
        <td class="blank" align="center">

        <form action="<?= URLHelper::getLink('?view=' . $view) ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="forumsend" value="bla">

            <table class="zebra settings" width="70%" align="center" cellpadding="8" cellspacing="0" border="0" id="main_content">
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
                <tbody>
                    <tr>
                        <td>
                            <label for="neuauf"><?= _('Neue Beiträge immer aufgeklappt') ?></label>
                        </td>
                        <td>
                            <input type="checkbox" name="neuauf" id="neuauf" value="1"
                                   <? if ($forum['neuauf'] == 1) echo 'checked'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="rateallopen"><?= _('Bewertungsbereich bei geöffneten Postings immer anzeigen') ?></label>
                            <div class="setting_info">
                                <?= _('Die Aktivierung dieser Einstellung blendet ein Kästchen neben den Forenbeiträgen ein, mit dem Sie Beiträge bewerten können.') ?>
                            </div>
                        </td>
                        <td>
                            <input type="checkbox" name="rateallopen" id="rateallopen" value="TRUE"
                                   <? if($forum['rateallopen'] == TRUE) echo 'checked'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="showimages"><?= _('Bilder im Bewertungsbereich anzeigen') ?></label>
                        </td>
                        <td>
                            <input type="checkbox" name="showimages" id="showimages" value="TRUE"
                                   <? if ($forum['showimages'] == TRUE) echo 'checked'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?= _('Sortierung der Themenanzeige') ?>
                        </td>
                        <td>
                            <label>
                                <input type="radio" value="asc" name="sortthemes"
                                       <? if ($forum['sortthemes'] == 'asc') echo 'checked'; ?>>
                                <?= _('Erstelldatum des Ordners - neue unten') ?>
                            </label><br>

                            <label>
                                <input type="radio" value="desc" name="sortthemes"
                                       <? if ($forum['sortthemes'] == 'desc') echo 'checked'; ?>>
                                <?= _('Erstelldatum des Ordners - neue oben') ?>
                            </label><br>

                            <label>
                                <input type="radio" value="last" name="sortthemes"
                                       <?if ($forum['sortthemes'] == 'last') echo 'checked'; ?>>
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
                                       <? if ($forum['themeview'] == 'tree') echo 'checked'; ?>>
                                <?= _('Treeview') ?>
                            </label><br>

                            <label>
                                <input type="radio" value="mixed" name="themeview"
                                       <? if ($forum['themeview'] == 'mixed') echo 'checked'; ?>>
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
                                       <? if (in_array($forum['presetview'], words('tree mixed'))) echo 'checked'; ?>>
                                <?= _('Themenansicht') ?>
                            </label><br>

                            <label>
                                <input type="radio" value="neue" name="presetview"
                                    <? if ($forum['presetview'] == 'neue') echo 'checked'; ?>>
                                <?= _('Neue Beiträge') ?>
                            </label><br>

                            <label>
                                <input type="radio" value="flat" name="presetview"
                                       <? if ($forum['presetview'] == 'flat') echo 'checked'; ?>>
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
                                <option value="<?= $i ?>" <? if ($i * 604800 == $forum['shrink']) echo 'selected'; ?>>
                                    <?= $i ?> <?= _('Wochen') ?>
                                </option>
                            <? endfor; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table_row_odd">
                        <td colspan="2" align="middle">
                            <?= Button::create(_('Übernehmen'), array('title' => _('Änderungen übernehmen'))) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>        
        <br>

        </td>
    </tr>
</table>
