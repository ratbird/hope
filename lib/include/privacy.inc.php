<?php
/**
* Privacy settings
*
* Helper functions for handling privacy settings
*
*
* @author       Thomas Hackl <thomas.hackl@uni-passau.de>
* @access       public
* @modulegroup  library
* @module       privacy.inc
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// mystudip.inc.php
// helper functions for handling personal settings
// Copyright (c) 2003 Stefan Suchi <suchi@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

if ($perm->have_perm("root"))
    $user_id = get_userid($username);
else
    $user_id = $auth->auth["uid"];

// Get visibility settings from database.
$global_visibility = get_global_visibility_by_id($user_id);
$online_visibility = get_local_visibility_by_id($user_id, 'online');
$chat_visibility = get_local_visibility_by_id($user_id, 'chat');
$search_visibility = get_local_visibility_by_id($user_id, 'search');
$email_visibility = get_local_visibility_by_id($user_id, 'email');

// Now get elements of user's homepage.
$homepage_elements = $my_about->get_homepage_elements();


?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td class="blank" width="100%" colspan="2" align="center">
            <blockquote>
                <font size="-1"><b><?php echo _("Hier können Sie Ihre Sichtbarkeit im System einstellen."); ?></b></font>
            </blockquote>
            <h2><?php echo _('globale Einstellungen'); ?></h2>
            <form method="post" action="<? echo $PHP_SELF ?>?cmd=change_global_visibility&studipticket=<?=get_ticket()?>">
                <table width="50%" align="center"cellpadding="8" cellspacing="0" border="0">
                    <tr  <?php $cssSw->switchClass() ?>>
                        <td align="right" class="blank" style="border-bottom:1px dotted black;" width="66%">
                            <font size="-1"><?print _("globale Sichtbarkeit");?></font><br>
                            <br><div align="left"><font size="-1">
                            <?php echo _("Sie können wählen, ob Sie für andere NutzerInnen sichtbar sein und alle Kommunikationsfunktionen von Stud.IP nutzen können wollen, oder ob Sie unsichtbar sein möchten und dann nur eingeschränkte Kommunikationsfunktionen nutzen können.");?>
                            </font></div>
                        </td>
                        <td <?php echo $cssSw->getFullClass() ?> width="34%">
                            <?php 
                            if ($global_visibility != 'always' && $global_visibility != 'never' && 
                                ($perm->get_perm() != 'dozent' || !get_config('DOZENT_ALWAYS_VISIBLE'))) {
                                // only show selection if visibility can be changed
                                ?>
                            <select name="global_visibility">
                            <?php
                                if (count(UserDomain::getUserDomains())) {
                                    printf ("<option %s value=\"global\">"._("sichtbar für alle Nutzer")."</option>", $global_visibility=='global' ? 'selected="selected"' : '');
                                    $visible_text = _('sichtbar für eigene Nutzerdomäne');
                                } else {
                                    $visible_text = _('sichtbar');
                                }
                                printf ("<option %s value=\"yes\">".$visible_text."</option>", ($global_visibility=='yes' || ($global_visibility=='unknown' && get_config('USER_VISIBILITY_UNKNOWN'))) ? 'selected="selected"' : '');
                                printf ("<option %s value=\"no\">"._("unsichtbar")."</option>", ($global_visibility=='no' || ($global_visibility=='unknown' && !get_config('USER_VISIBILITY_UNKNOWN'))) ? 'selected="selected"' : '');
                            ?>
                            </select>
                            <?php
                            } else {
                                if ($global_visibility == 'never') {
                                    echo "<i>"._('Ihre Kennung wurde von einem Administrator unsichtbar geschaltet.')."</i>";
                                } else if ($perm->get_perm() == 'dozent' && get_config('DOZENT_ALWAYS_VISIBLE')) {
                                    echo "<i>"._('Sie haben Dozentenrechte und sind daher immer global sichtbar.')."</i>";
                                } else {
                                    echo "<i>"._('Sie sind immer global sichtbar.')."</i>";
                                }
                                echo '<input type="hidden" name="global_visibility" value="'.$global_visibility.'"/>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php 
                    if (($global_visibility == 'yes' || $global_visibility == 'global' || 
                        ($global_visibility == 'unknown' && get_config('USER_VISIBILITY_UNKNOWN')) ||
                        ($perm->get_perm() == 'dozent' && get_config('DOZENT_ALWAYS_VISIBLE'))) && 
                        (!$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['online'] || 
                        !$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['chat'] || 
                        !$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['search'] || 
                        !$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['email'])) { 
                    ?>
                    <tr <?php $cssSw->switchClass() ?>>
                        <td  align="right" class="blank" style="border-bottom:1px dotted black;" width="66%">
                            <font size="-1"><?print _("erweiterte Einstellungen");?></font><br>
                            <br>
                            <div align="left">
                            <font size="-1">
                            <?php echo _("Stellen Sie hier ein, in welchen Bereichen des Systems Sie erscheinen wollen."); ?>
                            <?php
                            if (!$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['email']) {
                                echo '<br/>';
                                echo _("Wenn Sie hier Ihre E-Mailadresse verstecken, wird stattdessen die E-Mailadresse Ihrer (Standard-)Einrichtung angezeigt.");
                            }
                            ?>
                            </font>
                            </div>
                        </td>
                        <td <?php echo $cssSw->getFullClass() ?> width="34%">
                            <?php if (!$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['online']) {?>
                            <input type="checkbox" name="online"<?php echo $online_visibility ? ' checked="checked"' : '' ?>/>
                            <?php echo _('sichtbar in "Wer ist online"'); ?>
                            <br/>
                            <?php } ?>
                            <?php if (!$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['chat']) {?>
                            <input type="checkbox" name="chat"<?php echo $chat_visibility ? ' checked="checked"' : '' ?>/>
                            <?php echo _('eigener Chatraum sichtbar'); ?>
                            <br/>
                            <?php } ?>
                            <?php if (!$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['search']) {?>
                            <input type="checkbox" name="search"<?php echo $search_visibility ? ' checked="checked"' : '' ?>/>
                            <?php echo _('auffindbar über die Personensuche'); ?>
                            <br/>
                            <?php } ?>
                            <?php if (!$NOT_HIDEABLE_FIELDS[$perm->get_perm()]['email']) {?>
                            <input type="checkbox" name="email"<?php echo $email_visibility ? ' checked="checked"' : '' ?>/>
                            <?php echo _('eigene Emailadresse sichtbar'); ?>
                            <br/>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr <?php $cssSw->switchClass() ?>>
                        <td <?php echo $cssSw->getFullClass() ?> align="center" colspan="2">
                            <input type="hidden" name="view" value="privacy"/>
                            <?php echo makeButton('uebernehmen', 'input', _('Änderungen speichern'), 'change_global_visibility'); ?>
                        </td>
                    </tr>
                </table>
            </form>
            <br/>
            <h2><?php echo _('eigene Homepage'); ?></h2>
            <form method="post" action="<? echo $PHP_SELF ?>?cmd=change_all_homepage_visibility&studipticket=<?=get_ticket()?>">
                    <?php echo _('alle Sichtbarkeiten setzen auf'); ?>
                    <select name="all_homepage_visibility">
                        <option value="<?php echo VISIBILITY_ME; ?>"><?= _("nur mich selbst") ?></option>
                        <option value="<?php echo VISIBILITY_BUDDIES; ?>"><?= _("Buddies") ?></option>
                        <?php if ($user_domains) { ?>
                        <option value="<?php echo VISIBILITY_DOMAIN; ?>"><?= _("meine Nutzerdomäne") ?></option>
                        <?php } ?>
                        <option value="<?php echo VISIBILITY_STUDIP; ?>"><?= _("Stud.IP-intern") ?></option>
                        <option value="<?php echo VISIBILITY_EXTERN; ?>"><?= _("externe Seiten") ?></option>
                    </select>
                    <input type="hidden" name="view" value="privacy"/>
                    <?php echo makeButton('uebernehmen', 'input', _('Änderungen speichern'), 'set_all_homepage_visibility'); ?>
            </form>
            <form method="post" action="<? echo $PHP_SELF ?>?cmd=change_homepage_visibility&studipticket=<?=get_ticket()?>">
                <table width="50%" align="center"cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td colspan="<?php echo $user_domains ? 6 : 5; ?>" align="center">
                        </td>
                    </tr>
                    <tr class="steel2">
                        <td>&nbsp;</td>
                        <td colspan="<?php echo $user_domains ? 5 : 4; ?>" align="center"><?php echo _('sichtbar für'); ?></td>
                    </tr>
                    <tr class="steel2">
                        <td width="'40%'"><?php echo _('Homepage-Element'); ?></td>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><?php echo _('nur mich selbst'); ?></td>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><?php echo _('Buddies'); ?></td>
                        <?php if ($user_domains) { ?>
                        <td align="center" width="12%"><?php echo _('Nutzerdomäne'); ?></td>
                        <?php } ?>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><?php echo _('Stud.IP-intern'); ?></td>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><?php echo _('externe Seiten'); ?></td>
                    </tr>
                    <?php foreach ($homepage_elements as $key => $field) { ?>
                    <tr>
                        <td><?php echo $field['name']; ?>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_ME; ?>"<?php echo ($homepage_elements[$key]['visibility'] == VISIBILITY_ME) ? ' checked="checked"' : ''; ?>/>
                        </td>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_BUDDIES; ?>"<?php echo ($homepage_elements[$key]['visibility'] == VISIBILITY_BUDDIES) ? ' checked="checked"' : ''; ?>/>
                        </td>
                        <?php if ($user_domains) { ?>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_DOMAIN; ?>"<?php echo ($homepage_elements[$key]['visibility'] == VISIBILITY_DOMAIN) ? ' checked="checked"' : ''; ?>/>
                        </td>
                        <?php } ?>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_STUDIP; ?>"<?php echo ($homepage_elements[$key]['visibility'] == VISIBILITY_STUDIP) ? ' checked="checked"' : ''; ?>/>
                        </td>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_EXTERN; ?>"<?php echo ($homepage_elements[$key]['visibility'] == VISIBILITY_EXTERN) ? ' checked="checked"' : ''; ?>/>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr <?php $cssSw->switchClass() ?>>
                        <td <?php echo $cssSw->getFullClass() ?> align="center" colspan="<?php echo $user_domains ? 6 : 5; ?>">
                            <input type="hidden" name="view" value="privacy"/>
                            <?php echo makeButton('uebernehmen', 'input', _('Änderungen speichern'), 'change_homepage_visibility'); ?>
                        </td>
                    </tr>
                </table>
            </form>
            <br/><br/>
        </td>
    </tr>
</table>

