<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td class="blank" width="100%" colspan="2" align="center">
            <p class="info">
                <b><?php echo _("Hier können Sie Ihre Sichtbarkeit im System einstellen."); ?></b>
            </p>
            <form method="post" action="<?php echo URLHelper::getLink('edit_about.php', array('cmd' => 'change_global_visibility', 'studipticket' => get_ticket(), 'username' => Request::get('username'))); ?>">
                <table width="70%" align="center"cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <th width="50%"><?php echo _("Option"); ?></th>
                        <th width="50%"><?php echo _("Auswahl"); ?></th>
                    </tr>
                    <tr>
                        <td colspan="2" class="steelgraulight" style="border-bottom: 1px dotted black; border-top: 1px dotted black;" align="center">
                            <b><?php echo _('globale Einstellungen'); ?></b>
                        </td>
                    </tr>
                    <tr>
                        <td width="50%" align="right" class="blank" style="border-bottom:1px dotted black;" width="66%">
                            <font size="-1"><?print _("globale Sichtbarkeit");?></font><br>
                            <br><div align="left"><font size="-1">
                            <?php echo _("Sie können wählen, ob Sie für andere NutzerInnen sichtbar sein und alle Kommunikationsfunktionen von Stud.IP nutzen können wollen, oder ob Sie unsichtbar sein möchten und dann nur eingeschränkte Kommunikationsfunktionen nutzen können.");?>
                            </font></div>
                        </td>
                        <td width="50%" class="<?=TextHelper::cycle('steel1', 'steelgraulight')?>" width="34%">
                            <?php
                            if ($global_visibility != 'always' && $global_visibility != 'never' &&
                                ($user_perm != 'dozent' || !get_config('DOZENT_ALWAYS_VISIBLE'))) {
                                // only show selection if visibility can be changed
                                ?>
                            <select name="global_visibility">
                            <?php
                                if (count($user_domains)) {
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
                                } else if ($my_perm == 'dozent' && get_config('DOZENT_ALWAYS_VISIBLE')) {
                                    echo "<i>"._('Sie haben Dozentenrechte und sind daher immer global sichtbar.')."</i>";
                                } else {
                                    echo "<i>"._('Sie sind immer global sichtbar.')."</i>";
                                }
                                echo '<input type="hidden" name="global_visibility" value="'.$global_visibility.'">';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    if (($global_visibility == 'yes' || $global_visibility == 'global' ||
                        ($global_visibility == 'unknown' && get_config('USER_VISIBILITY_UNKNOWN')) ||
                        ($my_perm == 'dozent' && get_config('DOZENT_ALWAYS_VISIBLE'))) &&
                        (!$NOT_HIDEABLE_FIELDS[$my_perm]['online'] ||
                        !$NOT_HIDEABLE_FIELDS[$my_perm]['chat'] ||
                        !$NOT_HIDEABLE_FIELDS[$my_perm]['search'] ||
                        !$NOT_HIDEABLE_FIELDS[$my_perm]['email'])) {
                    ?>
                    <tr>
                        <td align="right" class="blank" style="border-bottom:1px dotted black;">
                            <font size="-1"><?print _("erweiterte Einstellungen");?></font><br>
                            <br>
                            <div align="left">
                            <font size="-1">
                            <?php echo _("Stellen Sie hier ein, in welchen Bereichen des Systems Sie erscheinen wollen."); ?>
                            <?php
                            if (!$NOT_HIDEABLE_FIELDS[$my_perm]['email']) {
                                echo '<br>';
                                echo _("Wenn Sie hier Ihre E-Mail-Adresse verstecken, wird stattdessen die E-Mailadresse Ihrer (Standard-)Einrichtung angezeigt.");
                            }
                            ?>
                            </font>
                            </div>
                        </td>
                        <td class="<?=TextHelper::cycle('steel1', 'steelgraulight')?>">
                            <?php if (!$NOT_HIDEABLE_FIELDS[$my_perm]['online']) {?>
                            <input type="checkbox" name="online"<?php echo $online_visibility ? ' checked="checked"' : '' ?>>
                            <?php echo _('sichtbar in "Wer ist online"'); ?>
                            <br>
                            <?php } ?>
                            <?php if (!$NOT_HIDEABLE_FIELDS[$my_perm]['chat'] && get_config('CHAT_ENABLE')) {?>
                            <input type="checkbox" name="chat"<?php echo $chat_visibility ? ' checked="checked"' : '' ?>>
                            <?php echo _('eigener Chatraum sichtbar'); ?>
                            <br>
                            <?php } ?>
                            <?php if (!$NOT_HIDEABLE_FIELDS[$my_perm]['search']) {?>
                            <input type="checkbox" name="search"<?php echo $search_visibility ? ' checked="checked"' : '' ?>>
                            <?php echo _('auffindbar über die Personensuche'); ?>
                            <br>
                            <?php } ?>
                            <?php if (!$NOT_HIDEABLE_FIELDS[$my_perm]['email']) {?>
                            <input type="checkbox" name="email"<?php echo $email_visibility ? ' checked="checked"' : '' ?>>
                            <?php echo _('eigene E-Mail-Adresse sichtbar'); ?>
                            <br>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td class="<?=TextHelper::cycle('steel1', 'steelgraulight')?>" colspan="2">
                            <input type="hidden" name="view" value="privacy">
                            <?php echo makeButton('uebernehmen', 'input', _('Änderungen speichern'), 'change_global_visibility'); ?>
                        </td>
                    </tr>
                </table>
            </form>
            <br/>
            <form method="post" action="<?php echo URLHelper::getLink('edit_about.php', array('cmd' => 'change_homepage_visibility', 'studipticket' => get_ticket(), 'username' => Request::get('username'))); ?>">
                <table width="70%" align="center"cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td colspan="<?php echo $user_domains ? 6 : 5; ?>" class="steelgraulight" style="border-bottom: 1px dotted black; border-top: 1px dotted black;" align="center">
                            <b><?php echo _('eigenes Profil'); ?></b>
                        </td>
                    </tr>
                        <td class="steel1" colspan="<?php echo $user_domains ? 6 : 5; ?>">
                            <form method="post" action="<?php echo URLHelper::getLink('edit_about.php', array('cmd' => 'change_all_homepage_visibility', 'studipticket' => get_ticket(), 'username' => Request::get('username'))); ?>">
                                    <?php echo _('alle Sichtbarkeiten setzen auf'); ?>
                                    <select name="all_homepage_visibility">
                                        <option value="">-- <?php echo _("bitte wählen"); ?> --</option>
                                        <option value="<?php echo VISIBILITY_ME; ?>"><?= _("nur mich selbst") ?></option>
                                        <option value="<?php echo VISIBILITY_BUDDIES; ?>"><?= _("Buddies") ?></option>
                                        <?php if ($user_domains) { ?>
                                        <option value="<?php echo VISIBILITY_DOMAIN; ?>"><?= _("meine Nutzerdomäne") ?></option>
                                        <?php } ?>
                                        <option value="<?php echo VISIBILITY_STUDIP; ?>"><?= _("Stud.IP-intern") ?></option>
                                        <option value="<?php echo VISIBILITY_EXTERN; ?>"><?= _("externe Seiten") ?></option>
                                    </select>
                                    <input type="hidden" name="view" value="privacy">
                                    <?php echo makeButton('uebernehmen', 'input', _('Änderungen speichern'), 'set_all_homepage_visibility'); ?>
                            </form>
                        </td>
                    <tr>
                        <th width="'40%'"><?php echo _('Profil-Element'); ?></th>
                        <th colspan="<?php echo $user_domains ? 5 : 4; ?>" align="center"><?php echo _('sichtbar für'); ?></th>
                    </tr>
                    <tr class="steelgraulight">
                        <td width="40%">&nbsp;</td>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><i><?php echo _('nur mich selbst'); ?></i></td>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><i><?php echo _('Buddies'); ?></i></td>
                        <?php if ($user_domains) { ?>
                        <td align="center" width="12%"><i><?php echo _('Nutzerdomäne'); ?></i></td>
                        <?php } ?>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><i><?php echo _('Stud.IP-intern'); ?></i></td>
                        <td align="center" width="<?php echo $user_domains ? '12%' : '15%'; ?>"><i><?php echo _('externe Seiten'); ?></i></td>
                    </tr>
                    <?php foreach ($homepage_elements as $category => $elements) { ?>
                    <tr class="blue_gradient">
                        <td colspan="<?php echo $user_domains ? 6 : 5; ?>">
                            <?php echo $category; ?>
                        </td>
                    </tr>
                    <?php foreach ($elements as $key => $element) { ?>
                    <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
                        <td><?php echo $element['name']; ?>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_ME; ?>"<?php echo ($element['visibility'] == VISIBILITY_ME) ? ' checked="checked"' : ''; ?>>
                        </td>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_BUDDIES; ?>"<?php echo ($element['visibility'] == VISIBILITY_BUDDIES) ? ' checked="checked"' : ''; ?>>
                        </td>
                        <?php if ($user_domains) { ?>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_DOMAIN; ?>"<?php echo ($element['visibility'] == VISIBILITY_DOMAIN) ? ' checked="checked"' : ''; ?>>
                        </td>
                        <?php } ?>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_STUDIP; ?>"<?php echo ($element['visibility'] == VISIBILITY_STUDIP) ? ' checked="checked"' : ''; ?>>
                        </td>
                        <td align="center">
                            <input type="radio" name="<?php echo $key; ?>" value="<?php echo VISIBILITY_EXTERN; ?>"<?php echo ($element['visibility'] == VISIBILITY_EXTERN) ? ' checked="checked"' : ''; ?>>
                        </td>
                    </tr>
                    <?php
                            }
                        }
                    ?>
                    <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
                        <td colspan="<?php echo $user_domains ? 6 : 5; ?>">
                            <input type="hidden" name="view" value="privacy">
                            <?php echo makeButton('uebernehmen', 'input', _('Änderungen speichern'), 'change_homepage_visibility'); ?>
                        </td>
                    </tr>
                </table>
            </form>
            <br><br>
        </td>
    </tr>
</table>
