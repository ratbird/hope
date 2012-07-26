<?
# Lifter010: TEST
use Studip\Button, Studip\LinkButton;
?>
<form method="post" action="<?= UrlHelper::getLink('?change_object_perms='. $resObject->getId()) ?>">
<?= CSRFProtection::tokenTag() ?>
<table class="zebra" border="0" celpadding="2" cellspacing="0" width="99%" align="center">
    <colgroup>
        <col width="4%">
        <col width="20%">
        <col>
        <col width="50%">
        <col>
    </colgroup>
    <tbody>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2">
                <?= _('verantwortlich:') ?><br>
                <a href="<?= $resObject->getOwnerLink()?>"><?= htmlReady($resObject->getOwnerName(TRUE)) ?></a>
            </td>
            <td>
            <? if ($owner_perms) : ?>
                <?= _('verantworlicheN NutzerIn &auml;ndern:') ?><br>
                <? showSearchForm('search_owner', $search_string_search_owner, FALSE,TRUE); ?>
            <? else : ?>
                <?= MessageBox::info(_('Sie können den/die verantwortlicheN NutzerIn nicht ändern.')) ?>
            <? endif; ?>
            </td>

            <!-- Infobox -->
            <td rowspan="8" valign="top" style="padding-left: 20px" align="right">
            <?
                $content[] = array('kategorie' => _("Informationen:"),
                    'eintrag' => array(
                        array(
                            'icon' => 'icons/16/black/info.png',
                            'text' => _("Hier können Sie Berechtigungen für den Zugriff auf die Ressource vergeben.") ."<br>".
                                _("<b>Achtung:</b> Alle hier erteilten Berechtigungen gelten ebenfalls für die Ressourcen, die der gewählten Ressource untergeordnet sind!")
                        ),

                        array(
                            'icon' => 'icons/16/black/search.png',
                            'text' => '<a href="'. URLHelper::getLink('resources.php?view=search&quick_view_mode=' . $view_mode) .'">'
                                   . _('zur Ressourcensuche') . '</a>'
                        )
                    )
                );

                $infobox = $GLOBALS['template_factory']->open('infobox/infobox_generic_content.php');

                $infobox->picture = 'infobox/schedules.jpg';
                $infobox->content = $content;

                echo $infobox->render();
            ?>
            </td>

        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2" valign="top">
                <?= _('Berechtigungen:') ?>
            </td>
            <td valign="top">
                <?= _('Berechtigung hinzuf&uuml;gen')?><br>
                <? showSearchForm('search_perm_user', $search_string_search_perm_user, FALSE, FALSE, FALSE, TRUE) ?>
            </td>
        </tr>
    <? if (count($selectPerms) > 0): ?>
        <? $i=0; foreach ($selectPerms as $user_id => $perm): ?>
        <tr>
            <td>&nbsp;</td>
            <td>
                <input type="hidden" name="change_user_id[]" value="<?= $user_id ?>">
                <a href="<?= $resObject->getOwnerLink($user_id) ?>">
                    <?= htmlReady($resObject->getOwnerName(TRUE, $user_id)) ?>
                </a>
            </td>
            <td nowrap style="padding-right: 20px">
                &nbsp;
            <!-- admin-perms -->
            <? if (($resObject->getOwnerType($user_id) == 'user') && $owner_perms): ?>
                <label>
                    <input type="radio" name="change_user_perms[<?= $i ?>]" value="admin"
                           <? if ($perm == 'admin') echo 'checked'; ?>>
                    admin
                </label>
            <? else: ?>
                <label style="color: #888;">
                    <input type="radio" disabled <? if ($perm == 'admin') echo 'checked'; ?>>
                    admin
                </label>
            <? endif; ?>

            <!-- tutor-perms -->
            <? if (($resObject->getOwnerType($user_id) == 'user') && $admin_perms && (($perm == 'tutor') || $owner_perms)): ?> 
                <label>
                    <input type="radio" name="change_user_perms[<?= $i ?>]" value="tutor"
                           <? if ($perm == 'tutor') echo 'checked'; ?>>
                    tutor
                </label>
            <? else: ?>
                <label style="color: #888;">
                    <input type="radio" disabled <? if ($perm == 'tutor') echo 'checked'; ?>>
                    tutor
                </label>
            <? endif; ?>

            <!-- autor-perms -->
            <? if ($admin_perms && (($perm == 'autor') || $owner_perms)): ?>
                <label>
                    <input type="radio" name="change_user_perms[<?= $i ?>]" value="autor"
                           <? if ($perm == 'autor') echo 'checked'; ?>>
                    autor
                </label>
            <? else: ?>
                <label style="color: #888;">
                    <input type="radio" disabled <? if ($perm == 'autor') echo 'checked'; ?>>
                    autor
                </label>
            <? endif; ?>

            <!-- Trash  -->
            <? if ($owner_perms || ($admin_perms && $perm == 'autor')) : ?>
                <a href="<?= UrlHelper::getLink('?change_object_perms='. $resObject->getId() .'&delete_user_perms='. $user_id) ?>">
                    <?= Assets::img('icons/16/blue/trash.png', tooltip2(_('Berechtigung löschen'))) ?>
                </a>
            <? else : ?>
                <?= Assets::img('icons/16/grey/decline/trash.png', tooltip2(_('Sie dürfen diese Berechtigung leider nicht löschen'))) ?>
            <? endif; ?>
            </td>
            <td>
            <? if ($perm == 'admin'): ?>
                <?= _('Nutzer ist <b>Admin</b> und kann s&auml;mtliche Belegungen und Eigenschaften &auml;ndern und Rechte vergeben.') ?>
            <? elseif ($perm == 'tutor'): ?>
                <?= _('Nutzer ist <b>Tutor</b> und kann s&auml;mtliche Belegungen &auml;ndern.') ?>
            <? elseif ($perm == 'autor'): ?>
                <?= _('Nutzer ist <b>Autor</b> und kann nur eigene Belegungen &auml;ndern.') ?>
            <? endif; ?>
            </td>
        </tr>
        <? $i += 1; endforeach; ?>
    <? else : ?>
        <tr>
            <td>&nbsp;</td>
            <td colspan="3">
                <?= MessageBox::info(_('Es sind keine weiteren Berechtigungen eingetragen')) ?>
            </td>
        </tr>
    <? endif; // selectPerms ?>

    <? if ((getGlobalPerms($user->id) == 'admin') && $resObject->isRoom()): ?>
        <tr>
            <td>&nbsp;</td>
            <td colspan="3">
                <?= _('Blockierung:') ?><br>
                <label>
                    <?= _('Diesen Raum bei globaler Blockierung gegen eine Bearbeitung durch lokale Administratoren und andere Personen sperren:')?>
                    <input type="checkbox" name="change_lockable" <? if ($resObject->isLockable()) echo 'checked'; ?>>
                </label><br>
                <?= _('<b>aktueller Zustand</b>:') ?>
                <? if ($resObject->isLockable()): ?>
                    <?= _('Raum <u>kann</u> blockiert werden') ?>
                <? else: ?>
                    <?= _('Raum kann <u>nicht</u> blockiert werden') ?>
                <? endif; ?>
            </td>
        </tr>
    <? endif; ?>

        <tr>
            <td>&nbsp;</td>
            <td colspan="3" align="center">
                <br><?= Button::create(_('Übernehmen'), array('title' => _('Zuweisen'))) ?>
            </td>
        </tr>
    </tbody>
</table>
</form>
