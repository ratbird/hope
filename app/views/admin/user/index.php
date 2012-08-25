<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if ($flash['delete']) : ?>
<?= $this->render_partial("admin/user/_delete", array('data' => $flash['delete'])) ?>
<? endif ?>

<h3><?= _('Benutzerverwaltung') ?></h3>

<form action="<?= $controller->url_for('admin/user/') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default collapsable">
    <tr class="table_row_even">
        <td align="right" width="15%">
            <?= _("Benutzername:") ?>
        </td>
        <td width="35%">
            <input name="username" type="text" value="<?= htmlReady($user['username']) ?>">
        </td>
        <td align="right" width="15%">
            <?=_('Vorname:')?>
        </td>
        <td width="35%">
            <input name="vorname" type="text" value="<?= htmlReady($user['vorname']) ?>">
        </td>
    </tr>
    <tr class="table_row_odd">
        <td align="right" width="15%">
            <?= _("E-Mail:")?>
        </td>
        <td width="35%">
            <input name="email" type="text" value="<?= htmlReady($user['email']) ?>">
        </td>
        <td align="right" width="15%">
            <?= _("Nachname:")?>
        </td>
        <td width="35%">
            <input name="nachname" type="text" value="<?= htmlReady($user['nachname']) ?>">
        </td>
    </tr>
    <tr class="table_row_even">
        <td align="right" width="15%">
            <?= _("Status:")?>
        </td>
        <td width="35%">
            <select name="perm">
            <? foreach(array("alle", "user", "autor", "tutor", "dozent", "admin", "root") as $one) : ?>
                <option <?= ($user['perm'] == $one) ? 'selected' : ''?> value="<?= $one ?>"><?= ($one=="alle")? _('alle') : $one ?></option>
            <? endforeach ?>
            </select>
            <input type="checkbox" name="locked" value="1" <?= ($user['locked'] == 1) ? 'checked':'' ?>> <?=_("nur gesperrt")?>
        </td>
        <td align="right" width="15%">
            <?= _("inaktiv:")?>
        </td>
        <td width="35%">
            <select name="inaktiv">
               <? foreach(array("<=" => ">=", "=" => "=", ">" => "<", "nie" =>_("nie")) as $i => $one) : ?>
                <option value="<?= htmlready($i) ?>" <?= ($user['inaktiv'] == $i) ? 'selected' : ''?>><?= htmlready($one) ?></option>
                <? endforeach ?>
            </select>
            <input name="inaktiv_tage" type="text" value="<?= htmlReady($user['inaktiv_tage']) ?>" size="10"> Tage
        </td>
    </tr>
    <tbody <?= ($advanced) ? '': 'class="collapsed"' ?>>
    <tr class="table_header header-row">
        <td colspan="4" class="toggle-indicator">
            <a class="toggler" href="<?= $controller->url_for('admin/user/')?><?= ($advanced) ? '' : 'index/advanced' ?>" title="<?= _('Zusätzliche Suchfelder ein-/ausblenden') ?>">
                <b><?= _('Erweiterte Suche')?></b>
            </a>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td align="right" width="15%">
            <?= _("Nutzerdomäne:")?>
        </td>
        <td width="35%">
            <select name="userdomains">
                <option value=""><?= _("Alle")?></option>
                <option value="null-domain" <?= ($user['userdomains'] == 'null-domain') ? 'selected' : ''?>><?= _("Ohne Domäne")?></option>
                <? foreach($userdomains as $one) : ?>
                    <option <?= ($user['userdomains'] == $one->getId()) ? 'selected' : ''?> value="<?= htmlReady($one->getId()) ?>"><?= htmlReady($one->getName() ? $one->getName() : $one->getId()) ?></option>
                <? endforeach ?>
            </select>
        </td>
        <td align="right" width="15%">
            <?= _("Authentifizierung:")?>
        </td>
        <td width="35%">
            <select name="auth_plugins">
               <option value=""><?= _("Alle")?></option>
               <? foreach($available_auth_plugins as $one) : ?>
                <option <?= ($user['auth_plugins'] == $one) ? 'selected' : ''?>><?= htmlready($one) ?></option>
                <? endforeach ?>
            </select>
        </td>
    </tr>
    <? if (count($datafields) > 0) : ?>
        <? $i = 0; foreach($datafields as $datafield) : ?>
            <? if ($i % 2 == 0) : ?>
            <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
            <? endif ?>
                <td align="right" nowrap><?= htmlReady($datafield->getName()) ?>:</td>
                <td>
                <? if ($datafield->getType() == 'bool') : ?>
                    <input type="radio" name="<?= $datafield->getID()?>" value="1" <?= ($user[$datafield->getID()] === "1") ? 'checked' : '' ?>> <?= _('ja') ?>
                    <input type="radio" name="<?= $datafield->getID()?>" value="0" <?= ($user[$datafield->getID()] === "0") ? 'checked' : '' ?>> <?= _('nein') ?>
                <? elseif ($datafield->getType() == 'selectbox') : ?>
                    <select name="<?= $datafield->getID()?>">
                        <option value="alle"><?= _('alle') ?></option>
                        <? foreach (array_map('trim', explode("\n", $datafield->getTypeParam())) as $entry) :?>
                        <option value="<?= $entry ?>" <?= ($user[$datafield->getID()] == $entry) ? 'selected' : '' ?>><?= htmlReady($entry) ?></option>
                        <? endforeach ?>
                    </select>
                <? else : ?>
                    <input type="text" name="<?= $datafield->getID()?>" value="<?= htmlReady($user[$datafield->getID()]) ?>">
                <? endif ?>
                </td>
            <? if ($i % 2 != 0 && $i != 0) : ?>
            </tr>
            <? endif ?>
        <? $i++; endforeach ?>
        <? if ($i % 2 != 0) : ?>
            <td></td>
            <td></td>
        </tr>
        <? endif ?>
     <? endif ?>
    </tbody>
    <tr>
        <td colspan="4" align="center">
            <?= Button::create(_('Suchen'), 'search')?>
            <?= Button::create(_('Zurücksetzen'), 'reset')?>
        </td>
    </tr>
</table>
</form>

<? if (count($users) > 0 && $users != 0) : ?>
<?= $this->render_partial("admin/user/_results", array('users' => $users)) ?>
<? endif ?>

<? //infobox
include '_infobox.php';

$infobox = array(
    'picture' => 'infobox/board1.jpg',
    'content' => array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag'   => $aktionen
        ),
        array(
            'kategorie' => _("Information"),
            'eintrag'   => array(
                array(
                    "text" => _("Auf dieser Seite können Sie nach Benutzern suchen und die Benutzerdaten einsehen bzw. verändern."),
                    "icon" => "icons/16/black/info.png"
                )
            )
        )
    )
);
