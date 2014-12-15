<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<form method="post" action="<?= $controller->url_for('settings/privacy/global') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table id="main_content" class="default">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <caption>
            <?= _('Privatsphäre') ?>:
            <?= _('Globale Einstellungen') ?>
        </caption>
        <thead>
            <tr>
                <th>
                    <?= _('Element') ?>
                </th>
                                <th>
                    <?= _('Einstellung') ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <label for="global_vis">
                        <?= _('Globale Sichtbarkeit') ?>
                        <dfn>
                        <?= _('Sie können wählen, ob Sie für andere NutzerInnen sichtbar sein '
                              .'und alle Kommunikationsfunktionen von Stud.IP nutzen können '
                              .'wollen, oder ob Sie unsichtbar sein möchten und dann nur '
                              .'eingeschränkte Kommunikationsfunktionen nutzen können.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                <? if ($global_visibility != 'always' && $global_visibility != 'never' &&
                        ($user_perm != 'dozent' || !get_config('DOZENT_ALWAYS_VISIBLE'))):
                        // only show selection if visibility can be changed
                ?>
                    <select name="global_visibility" aria-describedby="global_vis_description" id="global_vis">
                    <?php
                        if (count($user_domains)) {
                            printf ("<option %s value=\"global\">"._("sichtbar für alle Nutzer")."</option>", $global_visibility=='global' ? 'selected="selected"' : '');
                            $visible_text = _('sichtbar für eigene Nutzerdomäne');
                        } else {
                            $visible_text = _('sichtbar');
                        }
                        printf ("<option %s value=\"yes\">".$visible_text."</option>", ($global_visibility=='yes' || ($global_visibility=='unknown' && get_config('USER_VISIBILITY_UNKNOWN'))) ? 'selected' : '');
                        printf ("<option %s value=\"no\">"._("unsichtbar")."</option>", ($global_visibility=='no' || ($global_visibility=='unknown' && !get_config('USER_VISIBILITY_UNKNOWN'))) ? 'selected' : '');
                    ?>
                    </select>
                <? else: ?>
                    <? if ($global_visibility == 'never'): ?>
                        <i><?= _('Ihre Kennung wurde von einem Administrator unsichtbar geschaltet.') ?></i>
                    <? elseif ($user_perm == 'dozent' && get_config('DOZENT_ALWAYS_VISIBLE')): ?>
                        <i><?= _('Sie haben Dozentenrechte und sind daher immer global sichtbar.') ?></i>
                    <? else: ?>
                        <i><?= _('Sie sind immer global sichtbar.') ?></i>
                    <? endif; ?>
                    <input type="hidden" name="global_visibility" value="<?= $global_visibility ?>">
                <? endif; ?>
                </td>
            </tr>
    <? if (($global_visibility == 'yes' || $global_visibility == 'global' ||
            ($global_visibility == 'unknown' && get_config('USER_VISIBILITY_UNKNOWN')) ||
           ($user_perm == 'dozent' && get_config('DOZENT_ALWAYS_VISIBLE'))) &&
           (!$NOT_HIDEABLE_FIELDS[$user_perm]['online'] ||
            !$NOT_HIDEABLE_FIELDS[$user_perm]['search'] ||
            !$NOT_HIDEABLE_FIELDS[$user_perm]['email'])):
    ?>
            <tr>
                <td>
                    <label>
                        <?= _('Erweiterte Einstellungen') ?>
                        <dfn>
                            <?= _('Stellen Sie hier ein, in welchen Bereichen des Systems Sie erscheinen wollen.') ?>
                        <? if (!$NOT_HIDEABLE_FIELDS[$user_perm]['email']): ?>
                            <br>
                            <?=  _('Wenn Sie hier Ihre E-Mail-Adresse verstecken, wird stattdessen die E-Mail-Adresse Ihrer (Standard-)Einrichtung angezeigt.') ?>
                        <? endif; ?>
                            </dfn>
                    </label>
                </td>
                <td>
                <? if (!$NOT_HIDEABLE_FIELDS[$user_perm]['online']): ?>
                    <label>
                        <input type="checkbox" name="online" value="1"
                               <? if ($online_visibility) echo 'checked'; ?>>
                        <?= _('sichtbar in "Wer ist online"') ?>
                    </label>
                    <br>
                <? endif; ?>
                <? if (!$NOT_HIDEABLE_FIELDS[$user_perm]['search']): ?>
                    <label>
                        <input type="checkbox" name="search" value="1"
                               <? if ($search_visibility) echo 'checked'; ?>>
                        <?= _('auffindbar über die Personensuche') ?>
                    </label>
                    <br>
                <? endif; ?>
                <? if (!$NOT_HIDEABLE_FIELDS[$user_perm]['email']): ?>
                    <label>
                        <input type="checkbox" name="email" value="1"
                               <? if ($email_visibility) echo 'checked'; ?>>
                        <?= _('eigene E-Mail Adresse sichtbar') ?>
                    </label>
                    <br>
                <? endif; ?>
                </td>
            </tr>
    <? endif; ?>
        </tbody>
        <tfoot>
            <tr>
                 <td colspan="<?= $user_domains ? 6 : 5; ?>">
                    <?= Button::create(_('Übernehmen'), 'store', array('title' =>  _('Änderungen speichern')))?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<form method="post" action="<?= $controller->url_for('settings/privacy/homepage') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="default">
        <colgroup>
            <col width="34%">
        <? for($i = 1; $i <= $colCount; $i++): ?>
            <col width="<?= $colWidth ?>%">
        <? endfor; ?>
        </colgroup>
        <caption><?= _('Privatsphäre') ?>:
        <?= _('Eigenes Profil') ?></caption>
        <thead>
            <tr>
                <th ><?= _('Profil-Element'); ?></th>
                <th style='text-align: center;' colspan="<?= $colCount++ ?>"><?= _('sichtbar für'); ?></th>
            </tr>
        </thead>
        <tbody class="privacy">
            <tr>
                <td>&nbsp;</td>
                <? foreach ($visibilities as $visibility): ?>
                    <td><?= htmlReady($visibility) ?></td>
                <? endforeach; ?>
            </tr>
            <? foreach ($homepage_elements['entry'] as $element): ?>
                <? if ($element['is_header']): ?>
                    <tr>
                        <th colspan="<?= 1 + $colCount ?>">
                            <?= htmlReady($element['name']) ?>
                        </th>
                    </tr>
                <? else: ?>
                    <tr>
                        <td style="padding-left: <?= $element['padding'] ?>"><?= htmlReady($element['name']) ?></td>
                        <? if ($element['is_category']): ?>
                            <td colspan="<?= $colCount ?>"></td>
                        <? else: ?>
                            <? foreach ($homepage_elements['states'] as $state): ?>
                                <td>
                                    <input type="radio" name="visibility_update[<?= $element['id'] ?>]" value="<?= $state ?>"
                                           <? if ($element['state'] == $state) echo 'checked'; ?>>
                                </td>
                            <? endforeach; ?>
                        <? endif; ?>
                    </tr>
                <? endif; ?>
            <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?= 1 + $colCount ?>">
                    <?= _('Neue Elemente auf') ?>
                    <select name="default">
                        <option value="">-- <?= _('bitte wählen'); ?> --</option>
                        <? foreach ($visibilities as $visibility => $label): ?>
                            <option value="<?= $visibility ?>" <? if ($default_homepage_visibility == $visibility) echo 'selected'; ?>>
                                <?= htmlReady($label) ?>
                            </option>
                        <? endforeach; ?>
                    </select>
                    <?= _('setzen') ?>
                </td>
            </tr>
            <tr>
                <td colspan="<?= 1 + $colCount ?>">
                    <?= _('Jetzt alle Sichtbarkeiten auf') ?>
                    <select name="all">
                        <option value="">-- <?= _("bitte wählen"); ?> --</option>
                        <? foreach ($visibilities as $visibility => $label): ?>
                            <option value="<?= $visibility ?>">
                                <?= htmlReady($label) ?>
                            </option>
                        <? endforeach; ?>
                    </select>
                    <?= _('setzen') ?>
                </td>
            </tr>
            <tr>
                <td colspan="<?= 1 + $colCount ?>">
                    <?= Button::create(_('Übernehmen'), 'store', array('title' =>  _('Änderungen speichern')))?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>