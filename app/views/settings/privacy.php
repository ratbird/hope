<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<form method="post" action="<?= $controller->url_for('settings/privacy/global') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    
    <table id="main_content" class="settings zebra-hover">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="2">
                    <?= _('Privatsph�re') ?>:
                    <?= _('Globale Einstellungen') ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <label for="global_vis">
                        <?= _('Globale Sichtbarkeit') ?><br>
                        <dfn id="global_vis_description">
                        <?= _('Sie k�nnen w�hlen, ob Sie f�r andere NutzerInnen sichtbar sein '
                              .'und alle Kommunikationsfunktionen von Stud.IP nutzen k�nnen '
                              .'wollen, oder ob Sie unsichtbar sein m�chten und dann nur '
                              .'eingeschr�nkte Kommunikationsfunktionen nutzen k�nnen.') ?>
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
                            printf ("<option %s value=\"global\">"._("sichtbar f�r alle Nutzer")."</option>", $global_visibility=='global' ? 'selected="selected"' : '');
                            $visible_text = _('sichtbar f�r eigene Nutzerdom�ne');
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
                        <?= _('auffindbar �ber die Personensuche') ?>
                    </label>
                    <br>
                <? endif;�?>
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
        <? if ($FOAF_ENABLE): ?>
            <tr>
                <td>
                    <label for="foaf_show_identity">
                    <?= _('Eigene Identit�t in Verbindungsketten zwischen Nutzern ("Friend of a friend"-Liste) offenlegen') ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" id="foaf_show_identity" name="foaf_show_identity" value="1"
                           <? if ($config->getValue('FOAF_SHOW_IDENTITY')) echo 'checked'; ?>>
                </td>
            </tr>
        <? endif; ?>
    <? endif; ?>
        </tbody>
        <tfoot>
            <tr>
                 <td colspan="<?= $user_domains ? 6 : 5; ?>">
                    <?= Button::create(_('�bernehmen'), 'store', array('title' =>  _('�nderungen speichern')))?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<form method="post" action="<?= $controller->url_for('settings/privacy/homepage') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    
    <table class="settings zebra-hover">
        <colgroup>
            <col width="34%">
        <? for($i = 1; $i <= $colCount; $i++): ?>
            <col width="<?= $colWidth ?>%">
        <? endfor; ?>
        </colgroup>
        <thead>
            <tr>
                <th colspan="<?= $colCount + 1 ?>">
                    <?= _('Privatsph�re') ?>:
                    <?= _('Eigenes Profil') ?>
                </th>
            </tr>
            <tr class="divider">
                <th style="text-align: left; font-size: 10pt"><?= _('Profil-Element'); ?></th>
                <th style="font-size: 10pt;" colspan="<?= $colCount ?>"><?= _('sichtbar f�r'); ?></th>
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
                        <td colspan="<?= 1 + $colCount ?>">
                            <?= htmlReady($element['name']) ?>
                        </td>
                    </tr>
                <? else: ?>
                    <tr>
                        <td style="padding-left: <?= $element['padding'] ?>"><?= htmlReady($element['name']) ?></td>
                        <? foreach ($homepage_elements['states'] as $state): ?>
                            <td>
                                <input type="radio" name="visibility_update[<?= $element['id'] ?>]" value="<?= $state ?>"
                                       <? if ($element['state'] == $state) echo 'checked'; ?>>
                            </td>
                        <? endforeach; ?>
                    </tr>
                <? endif; ?>
            <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                 <td colspan="<?= 1 + $colCount ?>">
                    <?= Button::create(_('�bernehmen'), 'store', array('title' =>  _('�nderungen speichern')))?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<br><br>
<form method="post" action="<?= $controller->url_for('settings/privacy/bulk') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    <table class="zebra settings">
        <thead>
            <tr>
                <th width="50%" colspan="2"><?= _('Bulk Aktionen auf Profil-Elemente') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table_row_even">
                    <label>
                        <?= _('neu hinzugef�gte Profil-Elemente sind standardm��ig sichtbar f�r'); ?>
                        <select name="default">
                            <option value="">-- <?= _('bitte w�hlen'); ?> --</option>
                        <? foreach ($visibilities as $visibility => $label): ?>
                            <option value="<?= $visibility ?>" <? if ($default_homepage_visibility == $visibility) echo 'selected'; ?>>
                                <?= htmlReady($label) ?>
                            </option>
                        <? endforeach; ?>
                        </select>
                    </label>
                </td>
                <td class="table_row_even">
                    <?= Button::create(_('�bernehmen'), 'store_default', array('title' =>  _('�nderungen speichern')))?>
                </td>
            </tr>
            <tr>
                <td class="table_row_even">
                    <label>
                        <?= _('alle Sichtbarkeiten setzen auf'); ?>
                        <select name="all">
                            <option value="">-- <?= _("bitte w�hlen"); ?> --</option>
                        <? foreach ($visibilities as $visibility => $label): ?>
                            <option value="<?= $visibility ?>">
                                <?= htmlReady($label) ?>
                            </option>
                        <? endforeach; ?>
                        </select>
                    </label>
                </td>
                <td class="table_row_even">
                    <?= Button::create(_('�bernehmen'), 'store_all', array('title' => _('�nderungen speichern'))) ?>
                </td>
            </tr>
        </tbody>
    </table>
</form>