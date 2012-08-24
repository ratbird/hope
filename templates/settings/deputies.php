<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td class="blank" width="100%" colspan="2" align="center">
            <blockquote>
                <font size="-1"><b><?php echo _("Legen Sie hier fest, wer standardmäßig als Vertretung in Ihren Veranstaltungen eingetragen sein soll."); ?></b></font>
            </blockquote>
        </td>
    </tr>
    <tr>
        <td class="blank" align="right" width="50%">
            <?php echo _('Suche nach NutzerInnen:'); ?>&nbsp;
        </td>
        <td class="blank" align="left" width="50%">
            <form method="post" action="<?php echo URLHelper::getLink('edit_about.php', array('view' => 'deputies', 'cmd' => 'set_deputy', 'studipticket' => get_ticket())); ?>">
                <?= CSRFProtection::tokenTag() ?>
                <input type="IMAGE" src="<?= Assets::image_path('icons/16/yellow/arr_2left.png'); ?>" <?= tooltip(_("NutzerIn hinzufügen")); ?> border="0" name="add_deputy">
                <?php
                $exclude_users = array($GLOBALS['user']->id);
                if (is_array($deputies)) {
                    $exclude_users = array_merge($exclude_users, array_map(create_function('$d', 'return $d["user_id"];'), $deputies));
                }
                print QuickSearch::get("deputy_id",
                                       new PermissionSearch(
                                           "user",
                                           _("Vor-, Nach- oder Benutzername"),
                                           "user_id",
                                           array('permission' => $permission, 'exclude_user' => $exclude_users)
                                           )
                                      )
                                      ->withButton()
                                      ->render(); ?>
             </form>
        </td>
    </tr>
    <tr>
       <td class="blank" colspan="2" align="center">
            <br/>
            <form method="post" action="<?php echo URLHelper::getLink('edit_about.php', array('view' => 'deputies', 'cmd' => 'change_deputies', 'studipticket' => get_ticket())); ?>">
            <?= CSRFProtection::tokenTag() ?>
            <table width="50%" align="center" cellpadding="8" cellspacing="0" border="0">
                <tr>
                <?php if ($deputies) { ?>
                <tr>
                    <th width="<?php echo ($edit_about_enabled ? '60%' : '75%') ?>"><?php echo _('Nutzer'); ?></th>
                    <?php if ($edit_about_enabled) { ?>
                    <th width="20%"><?php echo _('darf mein Profil bearbeiten'); ?></th>
                    <?php } ?>
                    <th width="<?php echo ($edit_about_enabled ? '20%' : '25%') ?>"><?php echo _('löschen'); ?></th>
                </tr>
                <?php foreach ($deputies as $deputy) { ?>
                <tr class="<?=TextHelper::cycle('table_row_odd', 'table_row_even')?>">
                    <td>
                        <?php echo Avatar::getAvatar($boss['user_id'])->getImageTag(Avatar::SMALL); ?>
                        <?php echo htmlReady($deputy['fullname'].' ('.$deputy['username'].', '._('Status').': '.$deputy['perms'].')'); ?>
                        <input type="hidden" name="deputy_ids[]" value="<?php echo $deputy['user_id'] ?>"/>
                        <input type="hidden" name="deputy_saved_edit_about[]" value="<?php echo $deputy['edit_about'] ?>"/>
                        <?php if ($edit_about_enabled) { ?>
                        <input type="hidden" name="edit_about_<?php echo $deputy['user_id']; ?>" value="0"/>
                        <?php } ?>
                    </td>
                    <?php if ($edit_about_enabled) { ?>
                    <td align="left">
                        <input type="radio" name="edit_about_<?php echo $deputy['user_id']; ?>" value="1"<?php echo ($deputy['edit_about'] ? ' checked="checked"' : ''); ?>/><?php echo _("ja")?>
                        <br/>
                        <input type="radio" name="edit_about_<?php echo $deputy['user_id']; ?>" value="0"<?php echo ($deputy['edit_about'] ? '' : ' checked="checked"'); ?>/><?php echo _("nein")?>
                    </td>
                    <?php } ?>
                    <td align="center">
                        <input type="checkbox" name="delete_deputy[]" value="<?php echo $deputy['user_id'] ?>"/>
                    </td>
                </tr>
                <?php } ?>
                <tr class="<?=TextHelper::cycle('table_row_odd', 'table_row_even')?>">
                    <td colspan="3" align="center">
                        <?= Button::create(_('Übernehmen'), 'change_deputies', array('title' =>  _('Änderungen speichern')))?>
                    </td>
                </tr>
                <?php } else { ?>
                <tr>
                    <td class="blank" colspan="2" align="center">
                        <i><?php echo _('Sie haben noch niemanden als Ihre Standard-Dozierendenvertretung eingetragen. Benutzen Sie obige Personensuche, um dies zu tun.') ?></i>
                    </td>
                </tr>
                <?php } ?>
                </tr>
            </table>
            </form>
            <br/>
        </td>
    </tr>
</table>