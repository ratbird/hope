<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<h3><?= sprintf(_("Es wurden %s Personen gefunden"), count($users)) ?></h3>

<form action="<?= $controller->url_for('admin/user/delete') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr class="sortable">
        <th align="left" colspan="2" <?= ($sortby == 'username') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=username&order='.$order.'&toggle='.($sortby == 'username'))?>"><?=_("Benutzername")?></a>
        </th>
        <th>
        &nbsp;
        </th>
        <th align="left" <?= ($sortby == 'perms') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=perms&order='.$order.'&toggle='.($sortby == 'perms'))?>"><?=_("Status")?></a>
        </th>
        <th align="left" <?= ($sortby == 'Vorname') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=Vorname&order='.$order.'&toggle='.($sortby == 'Vorname'))?>"><?=_("Vorname")?></a>
        </th>
        <th align="left" <?= ($sortby == 'Nachname') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=Nachname&order='.$order.'&toggle='.($sortby == 'Nachname'))?>"><?=_("Nachname")?></a>
        </th>
        <th align="left" <?= ($sortby == 'Email') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=Email&order='.$order.'&toggle='.($sortby == 'Email'))?>"><?=_("E-Mail")?></a>
        </th>
        <th align="left" <?= ($sortby == 'changed') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=changed&order='.$order.'&toggle='.($sortby == 'changed'))?>"><?=_("inaktiv")?></a>
        </th>
        <th align="left" <?= ($sortby == 'mkdate') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=mkdate&order='.$order.'&toggle='.($sortby == 'mkdate'))?>"><?=_("registriert seit")?></a>
        </th>
        <th colspan="2" <?= ($sortby == 'auth_plugin') ? 'class="sort' . $order . '"' : ''?>>
            <a href="<?=URLHelper::getLink('?sortby=auth_plugin&order='.$order.'&toggle='.($sortby == 'auth_plugin'))?>"><?=_("Authentifizierung")?></a>
        </th>
    </tr>

    <? foreach ($users as $user) : ?>
    <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even')?>">
        <td>
            <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $user['username'])) ?>" title="<?= _('Profil des Benutzers anzeigen')?>">
                 <?= Avatar::getAvatar($user['user_id'], $user['username'])->getImageTag(Avatar::SMALL, array('title' => htmlReady($user['Vorname'] . ' ' . $user['Nachname']))) ?>
            </a>
        </td>
        <td>
            <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $user['username'])) ?>" title="<?= _('Profil des Benutzers anzeigen')?>">
                <?= $user['username'] ?>
            </a>
        </td>
        <td>
            <?
            $tooltxt = _("Sichtbarkeit:") . ' ' . $user['visible'];
            $tooltxt .= "\n" . _("Dom�nen:") . ' ' . $user['userdomains'];
            if ($user['locked'] == '1') {
                $tooltxt .= "\n" .  _("Nutzer ist gesperrt!");
            }
            ?>
           <?= tooltipicon($tooltxt) ?>
        </td>
        <td>
            <?= $user['perms'] ?>
        </td>
        <td>
            <?= htmlReady($user['Vorname']) ?>
        </td>
        <td>
            <?= htmlReady($user['Nachname']) ?>
        </td>
        <td>
            <?= htmlReady($user['Email']) ?>
        </td>
        <td>
        <? if ($user["changed_timestamp"] != "") :
            $inactive = time() - $user['changed_timestamp'];
            if ($inactive < 3600 * 24) {
                $inactive = gmdate('H:i:s', $inactive);
            } else {
                $inactive = floor($inactive / (3600 * 24)).' '._('Tage');
            }
        else :
            $inactive = _("nie benutzt");
        endif ?>
        <?= $inactive ?>
        </td>
        <td>
            <?= ($user["mkdate"]) ? date("d.m.Y", $user["mkdate"]) : _('unbekannt') ?>
        </td>
        <td><?= htmlReady($user['auth_plugin']) ?></td>
        <td align="right" nowrap>
            <a href="<?= $controller->url_for('admin/user/edit/'.$user['user_id']) ?>" title="<?= _('Detailansicht des Benutzers anzeigen')?>">
                <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Diesen Benutzer bearbeiten'))) ?>
            </a>
            <a href="<?= $controller->url_for('admin/user/delete/'.$user['user_id']) ?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Diesen Benutzer l�schen'))) ?>
            </a>
            <input class="check_all" type="checkbox" name="user_ids[]" value="<?= $user['user_id'] ?>">
        </td>
    </tr>
    <? endforeach ?>

    <tr class="table_footer">
        <td colspan="11" align="right">
            <?= Button::create(_('L�schen'), array('title' => _('Alle ausgew�hlten Benutzer l�schen')))?>
            <input class="middle" type="checkbox" name="check_all" title="<?= _('Alle Benutzer ausw�hlen') ?>">
        </td>
    </tr>
</table>
</form>

<script>
jQuery("input[name='check_all']").click(function() {
    if(jQuery(this).attr("checked")) {
        jQuery(".check_all").attr("checked","checked");
    } else {
        jQuery(".check_all").removeAttr("checked");
    }
});
</script>